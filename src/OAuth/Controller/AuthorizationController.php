<?php

declare(strict_types=1);

namespace App\OAuth\Controller;

use App\Entity\User;
use App\OAuth\Entity\Scope;
use App\OAuth\Entity\UserEntity;
use App\OAuth\Psr7Bridge;
use App\OAuth\Repository\ScopeRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The browser-facing authorization endpoint and its login + consent screens.
 * Sits behind a stateful firewall so the user authenticates with a session
 * before granting an MCP client access on their behalf.
 */
final class AuthorizationController extends AbstractController
{
    /**
     * Form fields that are not part of the replayed OAuth authorization request.
     */
    private const array NON_OAUTH_FIELDS = ['action' => true, '_csrf_token' => true];

    public function __construct(
        private readonly AuthorizationServer $authorizationServer,
        private readonly Psr7Bridge $psr7Bridge,
    ) {
    }

    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET', 'POST'])]
    public function authorize(Request $request): Response
    {
        /** @var UserInterface&User $user */
        $user = $this->getUser();

        try {
            // On POST the original authorization parameters are replayed from the
            // consent form's hidden fields; on GET they arrive as query params.
            $parameters = $request->isMethod('POST')
                ? array_diff_key($request->request->all(), self::NON_OAUTH_FIELDS)
                : $request->query->all();

            $psrRequest = $this->psr7Bridge->toPsrRequest($request)->withQueryParams($parameters);
            $authRequest = $this->authorizationServer->validateAuthorizationRequest($psrRequest);

            if ($request->isMethod('GET')) {
                return $this->render('oauth/consent.html.twig', [
                    'client_name' => $authRequest->getClient()->getName(),
                    'scopes' => array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $authRequest->getScopes()),
                    'user' => $user,
                    'parameters' => $parameters,
                ]);
            }

            if (!$this->isCsrfTokenValid('submit', (string) $request->request->get('_csrf_token'))) {
                throw new \RuntimeException('Invalid CSRF token.');
            }

            $approved = 'approve' === $request->request->get('action');

            $authRequest->setUser(new UserEntity($user->getUserIdentifier()));
            $authRequest->setAuthorizationApproved($approved);

            if ($approved) {
                $authRequest->setScopes([new Scope(ScopeRepository::SCOPE_MCP)]);
            }

            $psrResponse = $this->authorizationServer->completeAuthorizationRequest($authRequest, $this->psr7Bridge->createResponse());
        } catch (OAuthServerException $exception) {
            $psrResponse = $exception->generateHttpResponse($this->psr7Bridge->createResponse());
        }

        return $this->psr7Bridge->toSymfonyResponse($psrResponse);
    }

    #[Route('/oauth/login', name: 'oauth_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // The authorization query is carried here by OAuthLoginEntryPoint. Replay
        // it as the success/failure target so the authorize → login → authorize
        // resume does not depend on the session target path (which isn't shared
        // across multiple app instances). `_target_path` / `_failure_path` are
        // the stock form-login parameters read by the success/failure handlers.
        $authorizationQuery = $request->query->all();
        $hasAuthorizationQuery = $authorizationQuery !== [];

        return $this->render('oauth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'target_path' => $hasAuthorizationQuery ? $this->generateUrl('oauth_authorize', $authorizationQuery) : null,
            'failure_path' => $hasAuthorizationQuery ? $this->generateUrl('oauth_login', $authorizationQuery) : null,
        ]);
    }
}
