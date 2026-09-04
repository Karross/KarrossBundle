.PHONY: build install update test test-integration e2e-browsers test-e2e bash phpstan cs-fix cs-fix-check all-fix all-check seed serve run-test-app

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

# (Re)create the TestedApp sqlite file (var/tested-app.sqlite) with sample data
seed:
	docker compose run --rm php php tests/Integration/TestedApp/bin/seed.php

# Serve the TestedApp admin over real HTTP (browse locally in a real browser)
serve:
	docker compose run --rm -p 8000:8000 php php -S 0.0.0.0:8000 -t tests/Integration/TestedApp/public tests/Integration/TestedApp/public/index.php

# Hydrate the test data, then serve the test app on http://localhost:8000 (Ctrl-C to stop)
run-test-app:
	docker rm -f karross-test-app 2>/dev/null || true
	docker compose run --rm --name karross-test-app -p 8000:8000 php \
		bash -c 'php tests/Integration/TestedApp/bin/seed.php && exec php -S 0.0.0.0:8000 -t tests/Integration/TestedApp/public tests/Integration/TestedApp/public/index.php'
