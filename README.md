# Order and Payment Management API

A Laravel-based RESTful API for managing orders and payments with an extensible payment gateway system.

## Requirements

- PHP >= 7.3
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 8.x

## Installation

1. Clone the repository:

2. Install dependencies:
composer install

3. Copy environment file:
cp .env.example .env

4. Generate application key:
php artisan key:generate

5. Configure database in `.env`:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_payment_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

6. Run migrations:
php artisan migrate

7. Start the development server:
php artisan serve


## API Endpoints

POST /api/auth/register
Content-Type: application/json

{
    "name": "Hany Murtaja",
    "email": "mortajahany@gmail.com",
    "password": "password123",
    "password_confirmation": "password123"
}

POST /api/auth/login
Content-Type: application/json

{
    "email": "mortajahany@gmail.com",
    "password": "password123"
}


GET /api/auth/me
Authorization: Bearer {token}

POST /api/auth/logout
Authorization: Bearer {token}


POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_name": "Jane Smith",
    "customer_email": "jane@example.com",
    "customer_phone": "1234567890",
    "shipping_address": "123 Main St",
    "items": [
        {
            "product_name": "Product 1",
            "quantity": 2,
            "price": 10.50
        }
    ]
}

GET /api/orders
Authorization: Bearer {token}

GET /api/orders?status=confirmed
Authorization: Bearer {token}

GET /api/orders/{id}
Authorization: Bearer {token}


PUT /api/orders/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_name": "Updated Name",
    "status": "confirmed"
}


DELETE /api/orders/{id}
Authorization: Bearer {token}


POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 1,
    "payment_method": "credit_card"
}


GET /api/payments
Authorization: Bearer {token}


GET /api/payments?order_id=1
Authorization: Bearer {token}


GET /api/payments/{id}
Authorization: Bearer {token}


## Payment Gateway Extensibility

The system uses the Strategy Pattern to allow easy addition of new payment gateways.

### How to Add a New Payment Gateway

1. Create a new gateway class implementing `PaymentGatewayInterface`:

```php
<?php

namespace App\Services\PaymentGateways;

class StripeGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, array $data): array
    {
       
        return [
            'success' => true,
            'transaction_id' => 'STRIPE_' . uniqid(),
            'message' => 'Payment processed successfully',
            'gateway' => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return 'stripe';
    }
}

2. Register the gateway in `PaymentGatewayManager`:

php
// In app/Services/PaymentGateways/PaymentGatewayManager.php

protected function registerDefaultGateways(): void
{
    $this->register('credit_card', new CreditCardGateway());
    $this->register('paypal', new PayPalGateway());
    $this->register('stripe', new StripeGateway()); // Add this line
}


3. Update validation rules in `PaymentController`:

php
// In app/Http/Controllers/Api/PaymentController.php

'payment_method' => 'required|string|in:credit_card,paypal,stripe',




### Gateway Configuration

env
CREDIT_CARD_API_KEY=your_key
PAYPAL_CLIENT_ID=your_client_id
STRIPE_SECRET_KEY=your_secret_key


## Testing

bash
php artisan test --filter AuthTest
php artisan test --filter OrderTest
php artisan test --filter PaymentTest


## Response Format

All API responses follow a consistent format:

Success Response
json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}

 Error Response
json
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}


