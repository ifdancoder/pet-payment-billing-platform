<?php

namespace App\Application\Notification\DataTransferObjects;

enum SendEmailStatus
{
    case Succeeded;
    case Failed;
}
