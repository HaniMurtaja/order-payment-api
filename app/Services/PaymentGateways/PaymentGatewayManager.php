<?php

namespace App\Services\PaymentGateways;

class PaymentGatewayManager
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerDefaultGateways();
    }

    protected function registerDefaultGateways(): void
    {
        $this->register('credit_card', new CreditCardGateway());
        $this->register('paypal', new PayPalGateway());
    }

    public function register(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function get(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' not found");
        }

        return $this->gateways[$name];
    }

    public function all(): array
    {
        return $this->gateways;
    }
}

