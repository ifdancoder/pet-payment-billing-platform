<?php

namespace App\Application\Payment\DataTransferObjects;

enum ChargeStatus
{
    case Succeeded;
    case Failed;
    case Pending;
}
