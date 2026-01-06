<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chat;
use App\Models\Order;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class PhysicalWebhookController extends Controller
{
    use ApiResponse;

    public function handleWebhook(Request $request)
    {

        Log::info('Stripe webhook received');

        Stripe::setApiKey(config('services.stripe.secret'));
        $endpointSecret = config('services.stripe.webhook_secret');
        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                if (! $session->payment_status === 'paid') {
                    Log::info('Payment not completed for session: ' . $session->id);
                    return response()->json(['message' => 'Payment not completed'], 200);
                }

                // prevent duplicate processing
                $order = Order::where('stripe_payment_intent', $session->id)->first();
                if ($order && $order->status === 'paid') {
                    Log::info('Order already processed for session: ' . $session->id);
                    return response()->json(['message' => 'Order already processed'], 200);
                }

                $this->handleSuccessfulPayment($session);
                break;

            case 'payment_intent.payment_failed':

                $paymentIntent = $event->data->object;
                $this->handleFailedPayment($paymentIntent);
                break;

            default:
                Log::info('Unhandled event type: ' . $event->type);
        }
        // check
        return response()->json(['message' => 'Event received'], 200);
    }

    private function handleSuccessfulPayment($session)
    {
        $book = Book::findOrFail($session->metadata->book_id);

        Log::info('Creating order for buyer ID: ' . $session->metadata->buyer_id);

        $customerDetails = $session->customer_details;

        // Build shipping address
        $shippingAddress = null;
        if ($customerDetails && $customerDetails->address) {
            $addr = $customerDetails->address;

            $shippingAddress = $customerDetails->name . ', ' .
            $addr->line1 . ', ' .
            ($addr->line2 ?? '') . ', ' .
            $addr->city . ', ' .
            ($addr->state ?? '') . ', ' .
            $addr->postal_code . ', ' .
            $addr->country;
        }

        // ======================
        // Create Order
        // ======================
        $order = Order::create([
            'order_number'          => 'ORD-' . strtoupper(Str::random(16)),
            'buyer_id'              => $session->metadata->buyer_id,
            'seller_id'             => $book->uploaded_by,
            'book_id'               => $book->id,

            'book_price'            => $book->price,
            'shipping_cost'         => $session->metadata->shipping_cost,
            'platform_fee'          => 1.00,
            'total_amount'          => $session->amount_total / 100,

            'shipping_address'      => $shippingAddress,

            'stripe_payment_intent' => $session->payment_intent,
            'stripe_charge_id'      => $session->id,

            'status'                => 'pending',
            'payment_status'        => 'paid',
            'paid_at'               => now(),
        ]);

        Log::info('Order created successfully: ' . $order->order_number);

        // ======================
        // Decrease Book Stock
        // ======================
        if ($book->stock > 0) {
            $book->decrement('stock');
            Log::info('Book stock decremented for book ID: ' . $book->id);
        } else {
            Log::warning('Book stock already zero for book ID: ' . $book->id);
        }

        // ======================
        // Create Room (Order Based)
        // ======================
        $room = Room::create([
            'order_id'       => $order->id,
            'first_user_id'  => $order->buyer_id,
            'second_user_id' => $order->seller_id,
        ]);

        // Link room with order
        $order->update([
            'room_id' => $room->id,
        ]);

        Log::info("Room #{$room->id} created for order #{$order->order_number}");

        // ======================
        // Create Initial Chat Message
        // ======================
        Chat::create([
            'sender_id'   => $order->buyer_id,
            'receiver_id' => $order->seller_id,
            'room_id'     => $room->id,
            'text'        => "Hi! I just bought your book: '{$book->title}'. Please let me know when you can ship it.",
            'status' => 'sent',
        ]);

        Log::info("Initial chat message sent for room #{$room->id}");
    }

    private function handleFailedPayment($paymentIntent)
    {
        Log::warning('Payment failed for PaymentIntent: ' . $paymentIntent->id);
        // Additional handling logic can be added here

        $order = Order::where('stripe_payment_intent', $paymentIntent->id)->first();
        if ($order) {
            $order->status = 'cancelled';
            $order->save();
        }
    }

}
