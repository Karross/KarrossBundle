.PHONY: build install update test test-integration e2e-browsers test-e2e bash

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

# Open a shell inside the container
bash:
	docker compose run --rm php bash
