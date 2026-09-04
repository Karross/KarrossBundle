.PHONY: build install update test test-integration e2e-browsers test-e2e bash phpstan cs-fix cs-fix-check all-fix all-check

# (Re)build the Docker image (when Dockerfile or composer.json change)
build:
	docker compose build

# Install / update vendor inside the container
install:
	docker compose run --rm php composer install

# Update dependencies from composer.json (no committed lock) inside the container
update:
	docker compose run --rm php composer update --no-interaction --prefer-dist

# Run the whole PHPUnit test suite
test:
	docker compose run --rm php vendor/bin/phpunit

# Run only the integration test suite
test-integration:
	docker compose run --rm php vendor/bin/phpunit --testsuite integration

# Install Playwright browsers (Chromium) into var/ms-playwright
e2e-browsers:
	docker compose run --rm php vendor/bin/playwright-install --browsers

# Run only the E2E test suite
test-e2e:
	docker compose run --rm php vendor/bin/phpunit --testsuite e2e

# PHPStan static analysis (level max, with baseline)
phpstan:
	docker compose run --rm php vendor/bin/phpstan analyse --no-progress

# Auto-fix code style (php-cs-fixer)
cs-fix:
	docker compose run --rm php vendor/bin/php-cs-fixer fix

# Check code style without modifying files (php-cs-fixer dry-run)
cs-fix-check:
	docker compose run --rm php vendor/bin/php-cs-fixer fix --dry-run

# Run every auto-fixable tool (code style, ...)
all-fix: cs-fix

# Run every checker in order (style, static analysis, tests)
all-check: cs-fix-check phpstan test

# Open a shell inside the container
bash:
	docker compose run --rm php bash
