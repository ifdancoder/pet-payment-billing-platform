<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;
use Tests\Support\Kubectl;

/**
 * Infrastructure question, not a business one: does billing-api's own
 * PodDisruptionBudget/RollingUpdate configuration
 * (infrastructure/kubernetes/base/availability/, maxUnavailable: 0,
 * maxSurge: 1, 2 replicas, a real readinessProbe) actually deliver a
 * zero-downtime rollout through the real Ingress — not just that the
 * manifest declares one. See docs/architecture/testing-strategy.md.
 *
 * `kubectl rollout restart` always creates a new ReplicaSet, even with
 * no spec change, so this is safe and meaningful to run on every test
 * run without needing an actual image/config diff first.
 *
 * Run: point kubectl at the target cluster first (see README.md), then
 * composer install; composer test
 */
test('a rolling restart of billing-api never drops a request through the gateway', function () {
    $kubectl = new Kubectl;
    $api = Api::client();

    $registration = $api->post('/v1/auth/register', ['json' => [
        'email' => 'kind-rollout-'.Uuid::uuid4()->toString().'@example.com',
        'password' => 'correct horse battery staple',
        'merchant_name' => 'kind smoke: rolling update',
    ]]);
    expect($registration->getStatusCode())->toBe(201);
    $account = json_decode($registration->getBody()->getContents(), true);
    $merchantId = $account['merchant_id'];
    Api::authenticate($account['access_token']);
    $api = Api::client();

    $before = $kubectl->podNames('app.kubernetes.io/name=billing,app.kubernetes.io/component=api');

    $kubectl->rolloutRestart('billing-api');

    $statuses = [];
    $rolloutComplete = false;
    $deadline = microtime(true) + 90;

    do {
        // Any always-200 endpoint works here — this loop is about
        // whether the gateway ever fails to reach *a* healthy
        // billing-api pod during the rollout, not about billing's own
        // business logic.
        $response = $api->get("/v1/merchants/{$merchantId}/invoices");
        $statuses[] = $response->getStatusCode();

        $status = $kubectl->replicaStatus('billing-api');
        $currentPods = $kubectl->podNames('app.kubernetes.io/name=billing,app.kubernetes.io/component=api');
        $rolloutComplete = $status['replicas'] === 2
            && $status['updatedReplicas'] === 2
            && $status['readyReplicas'] === 2
            // Do not accept the deployment's briefly stale pre-restart
            // 2/2 status before its controller has observed the new
            // generation. At least one new pod makes the transition
            // observable without depending on controller timing.
            && $currentPods !== $before;

        if (! $rolloutComplete) {
            usleep(100_000);
        }
    } while (! $rolloutComplete && microtime(true) < $deadline);

    // The rollout genuinely finished within budget — not that the loop
    // just gave up.
    expect($rolloutComplete)->toBeTrue();

    // The loop actually polled *during* the rollout window, not just
    // once before or after it — otherwise "every response was 200"
    // would prove nothing about availability during the transition.
    expect(count($statuses))->toBeGreaterThan(3);

    foreach ($statuses as $i => $status) {
        expect($status)->toBe(200, "request #{$i} during the rollout");
    }

    // A real rollout happened — new pods, not the same two restarted
    // in place, which `rollout restart` never does anyway, but this is
    // the actual observable proof rather than trusting the command
    // name.
    $after = $kubectl->podNames('app.kubernetes.io/name=billing,app.kubernetes.io/component=api');
    expect($after)->not->toEqual($before);
});
