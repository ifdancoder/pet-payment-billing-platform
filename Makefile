.DEFAULT_GOAL := help

COMPOSE := docker compose

.PHONY: help
help: ## Show available commands
	@echo "Billing Platform"
	@echo ""
	@echo "Usage:"
	@echo "  make <target>"
	@echo ""
	@echo "Targets:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  %-15s %s\n", $$1, $$2}'

.PHONY: init
init: ## Initialize local development environment
	@test -f .env || cp .env.example .env
	@echo "Environment initialized."

.PHONY: up
up: ## Start the platform
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop the platform
	$(COMPOSE) down

.PHONY: restart
restart: down up ## Restart the platform

.PHONY: build
build: ## Build Docker images
	$(COMPOSE) build

.PHONY: ps
ps: ## Show running containers
	$(COMPOSE) ps

.PHONY: logs
logs: ## Follow logs
	$(COMPOSE) logs -f

.PHONY: config
config: ## Validate and render Docker Compose configuration
	$(COMPOSE) config

.PHONY: health
health: ## Check infrastructure health
	@curl --fail --silent http://localhost:$${GATEWAY_HTTP_PORT:-8080}/health
	@echo ""

.PHONY: clean
clean: ## Stop containers and remove local volumes
	$(COMPOSE) down --volumes --remove-orphans

.PHONY: reset
reset: clean up ## Recreate the local platform from scratch