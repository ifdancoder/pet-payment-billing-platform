<?php

namespace App\Infrastructure\Notification\Adapters\TemplateRenderer;

use App\Application\Notification\DataTransferObjects\RenderedContent;
use App\Application\Notification\Ports\Outbound\ITemplateRendererPort;
use App\Domain\Notification\ValueObjects\NotificationType;
use Illuminate\Support\Facades\View;

/**
 * Renders the on-disk Blade templates registered under the
 * "notification-templates" namespace (resources/notification-templates).
 * Kept separate from resources/views — these are message bodies, not
 * application UI.
 */
final class BladeTemplateRenderer implements ITemplateRendererPort
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function render(NotificationType $type, array $data): RenderedContent
    {
        $prefix = $this->prefixFor($type);

        return new RenderedContent(
            trim(View::make("notification-templates::{$prefix}.subject", $data)->render()),
            View::make("notification-templates::{$prefix}.text", $data)->render(),
            View::make("notification-templates::{$prefix}.html", $data)->render(),
        );
    }

    private function prefixFor(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::PaymentReceipt => 'payment-succeeded',
        };
    }
}
