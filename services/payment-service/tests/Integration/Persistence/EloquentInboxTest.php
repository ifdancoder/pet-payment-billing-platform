<?php

use App\Shared\Infrastructure\Persistence\Eloquent\Inbox\EloquentInbox;
use App\Shared\Infrastructure\Persistence\Eloquent\Inbox\InboxMessageModel;

test('recordIfNew records a new event and returns true', function () {
    $inbox = new EloquentInbox;

    $recorded = $inbox->recordIfNew('9f8e7d6c-5b4a-4321-9876-abcdef012345', 'invoice.created.v1');

    expect($recorded)->toBeTrue();
    $model = InboxMessageModel::query()->find('9f8e7d6c-5b4a-4321-9876-abcdef012345');
    expect($model)->not->toBeNull()
        ->and($model->event_type)->toBe('invoice.created.v1');
});

test('recordIfNew returns false and does not throw for an already-recorded event', function () {
    $inbox = new EloquentInbox;
    $inbox->recordIfNew('9f8e7d6c-5b4a-4321-9876-abcdef012345', 'invoice.created.v1');

    $recorded = $inbox->recordIfNew('9f8e7d6c-5b4a-4321-9876-abcdef012345', 'invoice.created.v1');

    expect($recorded)->toBeFalse();
    expect(InboxMessageModel::query()->where('id', '9f8e7d6c-5b4a-4321-9876-abcdef012345')->count())->toBe(1);
});
