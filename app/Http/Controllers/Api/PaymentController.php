<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentGateways\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentGatewayManager $gatewayManager;

    public function __construct(PaymentGatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;
    }

    public function index(Request $request)
    {
        $query = Payment::with('order')
            ->whereHas('order', function ($q) {
                $q->where('user_id', auth()->id());
            });

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $payments = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string|in:credit_card,paypal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::where('user_id', auth()->id())->findOrFail($request->order_id);

        if ($order->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Payments can only be processed for orders in confirmed status',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $gateway = $this->gatewayManager->get($request->payment_method);
            $result = $gateway->processPayment($order->total, $request->all());

            $payment = Payment::create([
                'payment_id' => $result['transaction_id'],
                'order_id' => $order->id,
                'status' => $result['success'] ? 'successful' : 'failed',
                'payment_method' => $request->payment_method,
                'amount' => $order->total,
                'gateway_response' => json_encode($result),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $result['success'] ? 'Payment processed successfully' : 'Payment processing failed',
                'data' => $payment->load('order'),
            ], $result['success'] ? 201 : 400);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $payment = Payment::with('order')
            ->whereHas('order', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }
}
