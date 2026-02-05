<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    public function test_user_can_process_payment_for_confirmed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total' => 100.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'payment_id', 'status', 'payment_method'],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
        ]);
    }

    public function test_user_cannot_process_payment_for_pending_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payments can only be processed for orders in confirmed status',
            ]);
    }

    public function test_user_can_view_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data'],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_user_can_filter_payments_by_order()
    {
        $order1 = Order::factory()->create(['user_id' => $this->user->id]);
        $order2 = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order1->id]);
        Payment::factory()->create(['order_id' => $order2->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/payments?order_id={$order1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_user_can_view_single_payment()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $payment->id],
            ]);
    }

    public function test_payment_validation_requires_order_id()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/payments', [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_payment_validation_requires_valid_payment_method()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_paypal_gateway_works()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'total' => 50.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'paypal',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'paypal',
        ]);
    }
}
