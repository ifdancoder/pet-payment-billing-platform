<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Payment\Ports\Inbound\IPaymentServicePort;
use App\Application\Payment\Queries\GetPayment\GetPaymentQuery;
use App\Application\Payment\Queries\ListPayments\ListPaymentsQuery;
use App\Presentation\Http\V1\Resources\PaymentResource;
use App\Presentation\Http\V1\Resources\PaymentResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PaymentController extends Controller
{
    public function __construct(private readonly IPaymentServicePort $paymentService) {}

    public function index(string $merchant): JsonResponse
    {
        $payments = $this->paymentService->listPayments(new ListPaymentsQuery($merchant));

        return (new PaymentResourceCollection($payments))->response();
    }

    public function show(string $merchant, string $payment): JsonResponse
    {
        $found = $this->paymentService->getPayment(new GetPaymentQuery($merchant, $payment));

        return PaymentResource::make($found)->response();
    }
}
