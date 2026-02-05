<?php

namespace App\Services\PaymentGateways;

class PayPalGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, array $data): array
    {
        $success = rand(0, 100) > 15;
        
        return [
            'success' => $success,
            'transaction_id' => 'PP_' . uniqid(),
            'message' => $success ? 'Payment processed successfully' : 'Payment failed',
            'gateway' => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return 'paypal';
    }
}

