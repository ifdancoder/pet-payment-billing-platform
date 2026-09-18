<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Notification\Ports\Inbound\INotificationServicePort;
use App\Application\Notification\Queries\GetNotification\GetNotificationQuery;
use App\Application\Notification\Queries\ListNotifications\ListNotificationsQuery;
use App\Presentation\Http\V1\Resources\NotificationResource;
use App\Presentation\Http\V1\Resources\NotificationResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class NotificationController extends Controller
{
    public function __construct(private readonly INotificationServicePort $notificationService) {}

    public function index(string $merchant): JsonResponse
    {
        $notifications = $this->notificationService->listNotifications(new ListNotificationsQuery($merchant));

        return (new NotificationResourceCollection($notifications))->response();
    }

    public function show(string $merchant, string $notification): JsonResponse
    {
        $found = $this->notificationService->getNotification(new GetNotificationQuery($merchant, $notification));

        return NotificationResource::make($found)->response();
    }
}
