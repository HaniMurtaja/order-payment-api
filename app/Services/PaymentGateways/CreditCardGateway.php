<?php

namespace App\Services\PaymentGateways;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, array $data): array
    {
        $success = rand(0, 100) > 10;
        
        return [
            'success' => $success,
            'transaction_id' => 'CC_' . uniqid(),
            'message' => $success ? 'Payment processed successfully' : 'Payment failed',
            'gateway' => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return 'credit_card';
    }
}

