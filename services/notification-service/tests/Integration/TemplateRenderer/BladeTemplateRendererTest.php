<?php

use App\Domain\Notification\ValueObjects\NotificationType;
use App\Infrastructure\Notification\Adapters\TemplateRenderer\BladeTemplateRenderer;

test('render renders the payment-succeeded subject, text and html templates', function () {
    $renderer = new BladeTemplateRenderer;

    $content = $renderer->render(NotificationType::PaymentReceipt, [
        'customerName' => 'Jane Doe',
        'amount' => '19.99',
        'currency' => 'USD',
        'paymentId' => 'pay_123',
        'paidAt' => '2026-09-01T00:05:00+00:00',
    ]);

    expect($content->subject)->toBe('Your payment receipt (19.99 USD)')
        ->and($content->bodyText)->toContain('Jane Doe')
        ->and($content->bodyText)->toContain('19.99 USD')
        ->and($content->bodyText)->toContain('pay_123')
        ->and($content->bodyHtml)->toContain('<strong>19.99 USD</strong>')
        ->and($content->bodyHtml)->toContain('Jane Doe');
});
