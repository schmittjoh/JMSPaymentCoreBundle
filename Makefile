-include .env .env.local

PHP_CONTAINER:=php
DOCKER_COMPOSE:=docker-compose
DOCKER_COMPOSE_RUN=$(DOCKER_COMPOSE) run --rm --no-deps
override DOCKER_COMPOSE_RUN_PHP:=$(DOCKER_COMPOSE) run --rm --no-deps $(PHP_CONTAINER) php -d memory_limit=4096M
override DOCKER_COMPOSE_RUN_PHP:=$(DOCKER_COMPOSE) run --rm --no-deps $(PHP_CONTAINER) php -d memory_limit=4096M
override DOCKER_COMPOSE_RUN_PHP_COVERAGE:=$(DOCKER_COMPOSE) run -e XDEBUG_MODE=coverage --rm --no-deps $(PHP_CONTAINER) php -dxdebug.mode=coverage -dmemory_limit=4096M -dzend_extension=xdebug
DOCKER_COMPOSE_EXEC:=$(DOCKER_COMPOSE) exec
CHANGED_FILES:=$(shell git diff --name-only)

help: ## Show this help.
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | grep -v '###'

build: ## Build containers
	eval "$(ssh-agent)"
	DOCKER_BUILDKIT=1 $(DOCKER_COMPOSE) build  --pull --build-arg --build-arg

clean: ## Clean everything
	$(DOCKER_COMPOSE) down -v --remove-orphans
	rm -rf ./vendor
	rm -rf ./.ecs_cache

install: build install-vendor start ## install project

install-vendor: ## Install composer dependencies
	$(DOCKER_COMPOSE_RUN) $(PHP_CONTAINER) composer install --no-interaction --prefer-dist --no-scripts  -vv

ssh: ## Log into php container
	$(DOCKER_COMPOSE_EXEC) $(PHP_CONTAINER) bash

ssh-root: ## Log into php container
	$(DOCKER_COMPOSE) exec -u root $(PHP_CONTAINER) bash

start: ## Start containers
	$(DOCKER_COMPOSE) up -d

stop: ## Stop containers
	$(DOCKER_COMPOSE) stop

restart: ## Restart containers
	$(DOCKER_COMPOSE) restart

%:
    @:

-include ./vendor/gsoi/test-pack/Makefile