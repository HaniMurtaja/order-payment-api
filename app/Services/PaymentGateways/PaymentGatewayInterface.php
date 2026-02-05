<?php

namespace App\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    public function processPayment(float $amount, array $data): array;
    
    public function getName(): string;
}

