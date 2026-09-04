.PHONY: build install test test-integration bash

# (Re)build the Docker image (when Dockerfile or composer.json change)
build:
	docker compose build

# Install / update vendor inside the container
install:
	docker compose run --rm php composer install

# Run the whole PHPUnit test suite
test:
	docker compose run --rm php vendor/bin/phpunit

# Run only the integration test suite
test-integration:
	docker compose run --rm php vendor/bin/phpunit --testsuite integration

# Open a shell inside the container
bash:
	docker compose run --rm php bash
