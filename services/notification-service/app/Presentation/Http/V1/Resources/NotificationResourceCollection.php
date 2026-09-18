<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Notification\Notification;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class NotificationResourceCollection extends ResourceCollection
{
    public $collects = NotificationResource::class;

    /**
     * @param  array<int, Notification>  $notifications
     */
    public function __construct(array $notifications)
    {
        parent::__construct($notifications);
    }
}
