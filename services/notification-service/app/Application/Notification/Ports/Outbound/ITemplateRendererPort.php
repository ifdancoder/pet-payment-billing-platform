<?php

namespace App\Application\Notification\Ports\Outbound;

use App\Application\Notification\DataTransferObjects\RenderedContent;
use App\Domain\Notification\ValueObjects\NotificationType;

interface ITemplateRendererPort
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(NotificationType $type, array $data): RenderedContent;
}
