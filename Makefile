.PHONY: keys setup serve

## Generate the RSA key pair the OAuth server signs access tokens with.
keys:
	mkdir -p config/oauth
	openssl genrsa -out config/oauth/private.pem 2048
	openssl rsa -in config/oauth/private.pem -pubout -out config/oauth/public.pem
	chmod 600 config/oauth/private.pem
	@echo "Keys written to config/oauth/. Set OAUTH_ENCRYPTION_KEY in .env.local:"
	@echo "  OAUTH_ENCRYPTION_KEY=$$(php -r 'echo bin2hex(random_bytes(32));')"

## One-shot local setup: deps, keys, database schema.
setup: keys
	composer install
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction

## Run the built-in PHP server on http://localhost:8000
serve:
	php -S localhost:8000 -t public
