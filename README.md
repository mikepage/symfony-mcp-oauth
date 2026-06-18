# Symfony MCP server with OAuth 2.1

A minimal, self-contained example of a [Model Context Protocol](https://modelcontextprotocol.io)
server built on Symfony and secured with **OAuth 2.1** — PKCE, Dynamic Client
Registration, and the RFC 8414 / RFC 9728 discovery documents that MCP clients
(Claude Code, Claude Desktop, ChatGPT, the OpenAI API, …) crawl automatically.

It is served **in-process by Symfony** over Streamable HTTP at `/mcp`; there is
no separate process to run.

## What's inside

- **MCP server** (`symfony/mcp-bundle`) at `/mcp`, with tools auto-discovered
  from `src/Mcp/Tool` via the `#[McpTool]` attribute. Two example tools ship:
  `whoami` (proves the OAuth handshake) and `echo`.
- **OAuth 2.1 authorization server** (`league/oauth2-server`):
  - `/.well-known/oauth-authorization-server` & `/.well-known/oauth-protected-resource` — discovery
  - `/oauth/register` — Dynamic Client Registration (RFC 7591)
  - `/oauth/authorize` — authorization endpoint + login + consent screens
  - `/oauth/token` — authorization-code (PKCE) and refresh-token grants
- **Resource server**: the `/mcp` firewall validates the bearer token with stock
  Symfony `access_token` security; a 401 carries the RFC 9728 `resource_metadata`
  pointer so clients can discover how to authenticate.
- **Stateless** CSRF and a session-independent authorize→login→authorize resume
  (see “Design notes”), so the browser flow works behind a load balancer with
  no shared session store.

## Requirements

PHP 8.3+, Composer, and the `openssl` CLI. The example uses SQLite, so no
database server is needed.

## Setup

```sh
make keys          # generate the RSA key pair the server signs tokens with
# set OAUTH_ENCRYPTION_KEY in .env.local (the keys command prints a value), or keep the committed default
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console app:create-user you@example.com "Your Name"   # prints a generated password
make serve         # http://localhost:8000
```

`make setup` runs the dependency, key, and database steps in one go.

## Try it

Discovery and an unauthenticated probe:

```sh
curl -s http://localhost:8000/.well-known/oauth-authorization-server | jq
curl -i http://localhost:8000/mcp        # 401 + WWW-Authenticate: Bearer resource_metadata="…"
```

### From Claude Code

```sh
claude mcp add --transport http mcp-example http://localhost:8000/mcp
```

The first tool call runs the full OAuth flow: Dynamic Client Registration, then a
browser login with the user you created and a consent screen. Then ask the agent
to call `whoami`.

Any MCP client that speaks remote/HTTP MCP works the same way — point it at
`http://localhost:8000/mcp` (a public HTTPS URL in production; cloud clients such
as Claude Desktop / ChatGPT connect from the vendor's infrastructure, so the
endpoint must be reachable over the internet).

## Design notes

- **Tokens are signed JWTs** (`league/oauth2-server`) using the RSA key pair in
  `config/oauth/`. Issued access/refresh tokens and authorization codes are
  persisted (SQLite) so they can be revoked; an unknown token id is treated as
  revoked.
- **The token `sub` is the user's email**, matching the Symfony user provider, so
  the resource server loads the same user the JWT login would.
- **DNS-rebinding allowlist**: the MCP SDK's default protection only allows
  `localhost`; `App\Mcp\Controller\McpController` allowlists the hosts in the
  `mcp.allowed_hosts` parameter (`config/services.yaml`) instead — add your
  production host there.
- **Session-independent login resume**: CSRF is stateless (`config/packages/csrf.yaml`)
  and `App\OAuth\Security\OAuthLoginEntryPoint` preserves the authorization query
  when redirecting to the login form, which is replayed as `_target_path`. This
  keeps the authorize→login→authorize resume working across multiple app
  instances without a shared session store.

## Layout

```
src/OAuth/           authorization + resource server, repositories, controllers, security
src/Mcp/             MCP controller, 401 challenge listener, example tools
src/Entity/          User + persisted OAuth client/token/code entities
templates/oauth/     login + consent screens
config/packages/     mcp.yaml, security.yaml, csrf.yaml
```

## License

MIT
