<?php

namespace App\Application\Notification\DataTransferObjects;

/**
 * The boundary DTO a TemplateRenderer adapter returns. Plain strings,
 * frozen at render time — CreateNotificationHandler persists these as
 * the Notification's content snapshot, never re-rendered at send time.
 */
final class RenderedContent
{
    public function __construct(
        public readonly string $subject,
        public readonly string $bodyText,
        public readonly string $bodyHtml,
    ) {}
}
