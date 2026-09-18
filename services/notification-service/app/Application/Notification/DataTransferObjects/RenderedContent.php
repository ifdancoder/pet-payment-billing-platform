<?php

namespace App\Application\Notification\DataTransferObjects;

final class RenderedContent
{
    public function __construct(
        public readonly string $subject,
        public readonly string $bodyText,
        public readonly string $bodyHtml,
    ) {}
}
