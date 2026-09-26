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
	@set -eu; \
	if [ ! -f .env ]; then umask 077; php scripts/generate-env.php > .env; fi; \
	missing=""; \
	for name in APP_KEY IDENTITY_APP_KEY CUSTOMER_APP_KEY CATALOG_APP_KEY SUBSCRIPTION_APP_KEY BILLING_APP_KEY PAYMENT_APP_KEY NOTIFICATION_APP_KEY AUTH_ED25519_PUBLIC_KEY_BASE64 AUTH_ED25519_SECRET_KEY_BASE64 INTERNAL_SERVICE_ACCESS_TOKEN POSTGRES_PASSWORD RABBITMQ_DEFAULT_PASS; do \
		grep -Eq "^$$name=.+$$" .env || missing="$$missing $$name"; \
	done; \
	if [ -n "$$missing" ]; then \
		echo "Existing .env is incomplete. Missing:$$missing" >&2; \
		echo "Preserve any values you need, then run 'make rotate-secrets'." >&2; \
		exit 1; \
	fi; \
	chmod 600 .env
	@echo "Environment initialized."

.PHONY: rotate-secrets
rotate-secrets: ## Replace the local .env with a newly generated secret set
	@set -eu; \
	temporary_env="$$(mktemp)"; \
	trap 'rm -f "$$temporary_env"' EXIT INT TERM; \
	umask 077; \
	php scripts/generate-env.php > "$$temporary_env"; \
	mv "$$temporary_env" .env; \
	trap - EXIT INT TERM
	@echo "Local secrets rotated."

.PHONY: kind-secrets
kind-secrets: init ## Provision local Kubernetes Secrets from the ignored .env
	@KIND_CONTEXT="$(KIND_CONTEXT)" scripts/create-kubernetes-secrets.sh

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
	secrets_file="$$(mktemp)"; \
	chmod 600 "$$secrets_file"; \
	php scripts/generate-env.php > "$$secrets_file"; \
	set -a; . "$$secrets_file"; set +a; \
	RABBITMQ_USER="$$RABBITMQ_DEFAULT_USER"; export RABBITMQ_USER; \
	RABBITMQ_PASSWORD="$$RABBITMQ_DEFAULT_PASS"; export RABBITMQ_PASSWORD; \
	unset COMPOSE_PROJECT_NAME GATEWAY_HTTP_PORT RABBITMQ_HOST RABBITMQ_PORT POSTGRES_HOST POSTGRES_PORT; \
	current_suite=""; \
	cleanup_all() { \
		if [ -n "$$current_suite" ]; then (cd "$$current_suite" && $(COMPOSE) down --volumes --remove-orphans); fi; \
		rm -f "$$secrets_file"; \
	}; \
	trap cleanup_all EXIT INT TERM; \
	for suite in $(COMPOSE_TEST_DIRS); do \
		echo "==> $$suite"; \
		current_suite="$$repository_root/$$suite"; \
		cd "$$current_suite"; \
		$(COMPOSE) up -d --build; \
		composer test -- --compact; \
		$(COMPOSE) down --volumes --remove-orphans; \
		current_suite=""; \
	done; \
	rm -f "$$secrets_file"; \
	trap - EXIT INT TERM

.PHONY: test-kind
test-kind: ## Run Kubernetes smoke tests against KIND_CONTEXT
	@set -eu; \
	temporary_kubeconfig="$$(mktemp)"; \
	trap 'rm -f "$$temporary_kubeconfig"' EXIT INT TERM; \
	kubectl config view --raw --minify --flatten \
		--context "$(KIND_CONTEXT)" > "$$temporary_kubeconfig"; \
	(cd tests/kind && KUBECONFIG="$$temporary_kubeconfig" composer test -- --compact)

.PHONY: test-load
test-load: ## Run a bounded k6 smoke against a disposable full platform
	@set -eu; \
	repository_root="$$(pwd)"; \
	secrets_file="$$(mktemp)"; \
	chmod 600 "$$secrets_file"; \
	php scripts/generate-env.php > "$$secrets_file"; \
	set -a; . "$$secrets_file"; set +a; \
	COMPOSE_PROJECT_NAME=billing-platform-load-test; export COMPOSE_PROJECT_NAME; \
	GATEWAY_HTTP_PORT=18080; export GATEWAY_HTTP_PORT; \
	POSTGRES_PORT=15432; export POSTGRES_PORT; \
	RABBITMQ_PORT=25672; export RABBITMQ_PORT; \
	RABBITMQ_MANAGEMENT_PORT=25673; export RABBITMQ_MANAGEMENT_PORT; \
	cleanup() { $(COMPOSE) down --volumes --remove-orphans; rm -f "$$secrets_file"; }; \
	trap cleanup EXIT INT TERM; \
	$(COMPOSE) up -d --build; \
	ready=false; \
	for attempt in $$(seq 1 60); do \
		if curl --fail --silent "http://127.0.0.1:$$GATEWAY_HTTP_PORT/health" >/dev/null; then ready=true; break; fi; \
		sleep 2; \
	done; \
	[ "$$ready" = true ] || { echo "Gateway did not become ready." >&2; exit 1; }; \
	docker run --rm --network host \
		-v "$$repository_root/tests/load:/scripts:ro" \
		-e BASE_URL="http://127.0.0.1:$$GATEWAY_HTTP_PORT" \
		grafana/k6:0.54.0 run /scripts/smoke.js; \
	cleanup; \
	trap - EXIT INT TERM

.PHONY: test-all
test-all: ## Run every test and documentation contract check
	@$(MAKE) test
	@$(MAKE) test-docs
	@$(MAKE) test-compose
	@$(MAKE) test-kind
	@$(MAKE) test-load

.PHONY: clean
clean: ## Stop containers and remove local volumes
	$(COMPOSE) down --volumes --remove-orphans

.PHONY: reset
reset: clean up ## Recreate the local platform from scratch
