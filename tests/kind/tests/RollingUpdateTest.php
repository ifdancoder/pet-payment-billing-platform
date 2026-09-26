<?php

use Ramsey\Uuid\Uuid;
use Tests\Support\Api;
use Tests\Support\Kubectl;

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
        $response = $api->get("/v1/merchants/{$merchantId}/invoices");
        $statuses[] = $response->getStatusCode();

        $status = $kubectl->replicaStatus('billing-api');
        $currentPods = $kubectl->podNames('app.kubernetes.io/name=billing,app.kubernetes.io/component=api');
        $rolloutComplete = $status['replicas'] === 2
            && $status['updatedReplicas'] === 2
            && $status['readyReplicas'] === 2
            && $currentPods !== $before;

        if (! $rolloutComplete) {
            usleep(100_000);
        }
    } while (! $rolloutComplete && microtime(true) < $deadline);

    expect($rolloutComplete)->toBeTrue();

    expect(count($statuses))->toBeGreaterThan(3);

    foreach ($statuses as $i => $status) {
        expect($status)->toBe(200, "request #{$i} during the rollout");
    }

    $after = $kubectl->podNames('app.kubernetes.io/name=billing,app.kubernetes.io/component=api');
    expect($after)->not->toEqual($before);
});
