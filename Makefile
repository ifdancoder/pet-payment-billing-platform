.DEFAULT_GOAL := help

COMPOSE := docker compose
KIND_CONTEXT ?= kind-pet-payment-billing-platform

SERVICE_DIRS := \
	services/identity-service \
	services/customer-service \
	services/catalog-service \
	services/subscription-service \
	services/billing-service \
	services/payment-service \
	services/notification-service

COMPOSE_TEST_DIRS := \
	tests/component/subscription-service \
	tests/component/notification-service \
	tests/integration/subscription-to-billing \
	tests/integration/billing-to-payment \
	tests/integration/payment-to-billing \
	tests/integration/billing-to-subscription \
	tests/integration/payment-to-notification \
	tests/e2e/successful-subscription \
	tests/e2e/failed-payment \
	tests/e2e/overdue-subscription \
	tests/resilience/duplicate-delivery \
	tests/resilience/outbox-recovery \
	tests/resilience/rabbitmq-outage \
	tests/resilience/consumer-crash

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

.PHONY: test
test: ## Run all seven service test suites
	@set -eu; \
	for service in $(SERVICE_DIRS); do \
		echo "==> $$service"; \
		(cd "$$service" && composer test -- --compact); \
	done

.PHONY: test-docs
test-docs: ## Validate Markdown pairs/links and the OpenAPI contract
	@php scripts/check-markdown.php
	@php scripts/check-openapi.php

.PHONY: test-compose
test-compose: ## Run component, integration, E2E, and resilience suites
	@set -eu; \
	repository_root="$$(pwd)"; \
	for suite in $(COMPOSE_TEST_DIRS); do \
		echo "==> $$suite"; \
		cd "$$repository_root/$$suite"; \
		cleanup() { $(COMPOSE) down --volumes --remove-orphans; }; \
		trap cleanup EXIT INT TERM; \
		$(COMPOSE) up -d --build; \
		composer test -- --compact; \
		cleanup; \
		trap - EXIT INT TERM; \
	done

.PHONY: test-kind
test-kind: ## Run Kubernetes smoke tests against KIND_CONTEXT
	@set -eu; \
	temporary_kubeconfig="$$(mktemp)"; \
	trap 'rm -f "$$temporary_kubeconfig"' EXIT INT TERM; \
	kubectl config view --raw --minify --flatten \
		--context "$(KIND_CONTEXT)" > "$$temporary_kubeconfig"; \
	(cd tests/kind && KUBECONFIG="$$temporary_kubeconfig" composer test -- --compact)

.PHONY: test-all
test-all: ## Run every test and documentation contract check
	@$(MAKE) test
	@$(MAKE) test-docs
	@$(MAKE) test-compose
	@$(MAKE) test-kind

.PHONY: clean
clean: ## Stop containers and remove local volumes
	$(COMPOSE) down --volumes --remove-orphans

.PHONY: reset
reset: clean up ## Recreate the local platform from scratch
