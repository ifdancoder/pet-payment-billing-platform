<?php

use App\Application\Notification\DataTransferObjects\SendEmailRequest;
use App\Application\Notification\DataTransferObjects\SendEmailStatus;
use App\Domain\Notification\ValueObjects\DeliveryAttemptId;
use App\Domain\Notification\ValueObjects\EmailAddress;
use App\Infrastructure\Notification\Adapters\EmailSender\Fake\FakeEmailSender;

test('send always succeeds with a deterministic provider reference', function () {
    $attemptId = DeliveryAttemptId::generate();
    $sender = new FakeEmailSender;

    $result = $sender->send(new SendEmailRequest($attemptId, EmailAddress::fromString('customer@example.com'), 'Your receipt', 'Thanks.', '<p>Thanks.</p>'));

    expect($result->status)->toBe(SendEmailStatus::Succeeded)
        ->and($result->providerReference)->toBe("fake:{$attemptId->toString()}")
        ->and($result->failureCode)->toBeNull();
});

test('send returns the same provider reference for a retried attempt id', function () {
    $attemptId = DeliveryAttemptId::generate();
    $sender = new FakeEmailSender;
    $request = new SendEmailRequest($attemptId, EmailAddress::fromString('customer@example.com'), 'Your receipt', 'Thanks.', '<p>Thanks.</p>');

    $first = $sender->send($request);
    $second = $sender->send($request);

    expect($first->providerReference)->toBe($second->providerReference);
});
