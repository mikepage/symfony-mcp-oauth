<?php

declare(strict_types=1);

namespace App\OAuth\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point for the browser-facing OAuth firewall: when an unauthenticated
 * user hits /oauth/authorize, redirect them to the login form **carrying the
 * original authorization query**.
 *
 * The stock form-login entry point drops the query, leaving the authorize →
 * login → authorize resume to rely on Symfony's session "target path". That
 * session isn't shared across multiple app instances (file-based sessions, no
 * sticky affinity), so the resume can silently fall back to "/" in production
 * while working locally on a single instance. Preserving the query lets the
 * login form replay it as `_target_path`, so the resume no longer depends on
 * the session — matching the deliberately stateless CSRF setup in
 * config/packages/csrf.yaml.
 */
final readonly class OAuthLoginEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('oauth_login', $request->query->all()),
        );
    }
}
