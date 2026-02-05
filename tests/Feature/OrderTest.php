<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    public function test_user_can_create_order()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/orders', [
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'customer_phone' => '1234567890',
                'shipping_address' => '123 Main St',
                'items' => [
                    [
                        'product_name' => 'Product 1',
                        'quantity' => 2,
                        'price' => 10.50,
                    ],
                    [
                        'product_name' => 'Product 2',
                        'quantity' => 1,
                        'price' => 25.00,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'total', 'items'],
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'john@example.com',
            'total' => 46.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Product 1',
            'quantity' => 2,
        ]);
    }

    public function test_user_can_view_orders()
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data'],
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_user_can_filter_orders_by_status()
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('confirmed', $response->json('data.data.0.status'));
    }

    public function test_user_can_view_single_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $order->id],
            ]);
    }

    public function test_user_can_update_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/orders/{$order->id}", [
                'customer_name' => 'Updated Name',
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Updated Name',
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_delete_order_without_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_order_with_payments()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete order with associated payments',
            ]);
    }

    public function test_user_cannot_access_other_users_orders()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404);
    }
}
