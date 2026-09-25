#!/bin/sh

set -eu

repository_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
env_file=${ENV_FILE:-"$repository_root/.env"}
context=${KIND_CONTEXT:-kind-pet-payment-billing-platform}
namespace=pet-payment-billing-platform

if [ ! -f "$env_file" ]; then
    echo "Missing $env_file; run 'make init' first." >&2
    exit 1
fi

set -a
. "$env_file"
set +a

# Existing local clusters keep infrastructure data on PVCs. Reuse their
# current database/broker credentials while rotating application keys so the
# stored PostgreSQL role and RabbitMQ user remain reachable.
if [ "${PRESERVE_INFRA_CREDENTIALS:-0}" = "1" ]; then
    POSTGRES_USER=$(kubectl --context "$context" -n "$namespace" get secret postgres-secret -o jsonpath='{.data.POSTGRES_USER}' | base64 -d)
    POSTGRES_PASSWORD=$(kubectl --context "$context" -n "$namespace" get secret postgres-secret -o jsonpath='{.data.POSTGRES_PASSWORD}' | base64 -d)
    RABBITMQ_DEFAULT_USER=$(kubectl --context "$context" -n "$namespace" get secret rabbitmq-secret -o jsonpath='{.data.RABBITMQ_DEFAULT_USER}' | base64 -d)
    RABBITMQ_DEFAULT_PASS=$(kubectl --context "$context" -n "$namespace" get secret rabbitmq-secret -o jsonpath='{.data.RABBITMQ_DEFAULT_PASS}' | base64 -d)
fi

require() {
    eval "value=\${$1:-}"
    if [ -z "$value" ]; then
        echo "Missing $1 in $env_file." >&2
        exit 1
    fi
}

for name in IDENTITY_APP_KEY CUSTOMER_APP_KEY CATALOG_APP_KEY SUBSCRIPTION_APP_KEY BILLING_APP_KEY PAYMENT_APP_KEY NOTIFICATION_APP_KEY AUTH_ED25519_PUBLIC_KEY_BASE64 AUTH_ED25519_SECRET_KEY_BASE64 INTERNAL_SERVICE_ACCESS_TOKEN POSTGRES_USER POSTGRES_PASSWORD RABBITMQ_DEFAULT_USER RABBITMQ_DEFAULT_PASS; do
    require "$name"
done

apply_secret() {
    kubectl --context "$context" -n "$namespace" create secret generic "$@" --dry-run=client -o yaml \
        | kubectl --context "$context" apply -f -
}

kubectl --context "$context" apply -f "$repository_root/infrastructure/kubernetes/base/namespace.yaml"
apply_secret postgres-secret --from-literal=POSTGRES_USER="$POSTGRES_USER" --from-literal=POSTGRES_PASSWORD="$POSTGRES_PASSWORD"
apply_secret rabbitmq-secret --from-literal=RABBITMQ_DEFAULT_USER="$RABBITMQ_DEFAULT_USER" --from-literal=RABBITMQ_DEFAULT_PASS="$RABBITMQ_DEFAULT_PASS" --from-literal=RABBITMQ_URI="amqp://$RABBITMQ_DEFAULT_USER:$RABBITMQ_DEFAULT_PASS@rabbitmq:5672/"
apply_secret identity-secret --from-literal=APP_KEY="$IDENTITY_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64" --from-literal=AUTH_ED25519_SECRET_KEY_BASE64="$AUTH_ED25519_SECRET_KEY_BASE64"
apply_secret customer-secret --from-literal=APP_KEY="$CUSTOMER_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64"
apply_secret catalog-secret --from-literal=APP_KEY="$CATALOG_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64"
apply_secret subscription-secret --from-literal=APP_KEY="$SUBSCRIPTION_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64" --from-literal=INTERNAL_SERVICE_ACCESS_TOKEN="$INTERNAL_SERVICE_ACCESS_TOKEN"
apply_secret billing-secret --from-literal=APP_KEY="$BILLING_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64"
apply_secret payment-secret --from-literal=APP_KEY="$PAYMENT_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64"
apply_secret notification-secret --from-literal=APP_KEY="$NOTIFICATION_APP_KEY" --from-literal=AUTH_ED25519_PUBLIC_KEY_BASE64="$AUTH_ED25519_PUBLIC_KEY_BASE64" --from-literal=INTERNAL_SERVICE_ACCESS_TOKEN="$INTERNAL_SERVICE_ACCESS_TOKEN"
