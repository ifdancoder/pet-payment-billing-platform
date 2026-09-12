<?php

use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Notification\GenericNotificationMail;
use App\Infrastructure\Customer\Adapters\Notification\MailNotificationAdapter;
use Illuminate\Support\Facades\Mail;

test('send delivers a mail to the given recipient with the given subject', function () {
    Mail::fake();

    (new MailNotificationAdapter)->send(
        Email::fromString('jane@example.com'),
        'Welcome',
        'Hello, Jane!',
    );

    Mail::assertSent(GenericNotificationMail::class, function (GenericNotificationMail $mail) {
        return $mail->hasTo('jane@example.com') && $mail->subject === 'Welcome';
    });
});

test('send renders the given body as the mail content', function () {
    Mail::fake();

    (new MailNotificationAdapter)->send(
        Email::fromString('jane@example.com'),
        'Welcome',
        'Hello, Jane!',
    );

    Mail::assertSent(GenericNotificationMail::class, fn (GenericNotificationMail $mail) => str_contains($mail->render(), 'Hello, Jane!'));
});
