<?php
namespace App\Http\Controllers\Api\User;

use Stripe\Stripe;
use App\Models\Book;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Events\MessageSendEvent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PhysicalOrderController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        $platform_fee = 1.00; // Example platform fee

        $book = Book::find($request->book_id);

        $shipping_cost = $book->shipping_cost ?? 0;

        if ($book->type !== 'physical') {
            return $this->error([], 'This book is not a physical book,You need choise physical book ', 400);
        }

        $user = auth('api')->user();

        if ($user->id === $book->uploaded_by) {
            return $this->error([], 'You cannot buy your own book, Please choise another one', 400);
        }

        if ($book->stock <= 0) {
            return $this->error([], 'This book is out of stock', 400);
        }

        if (! $book) {
            return $this->error([], 'Book not found', 404);
        }

        $total_amount = ($platform_fee + $shipping_cost + $book->price) * 100; // in cents

        try {
            $checkout_session = Session::create([
                'payment_method_types'       => ['card'],
                'line_items'                 => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => [
                            'name' => $book->title,
                        ],
                        'unit_amount'  => (int) $total_amount,
                    ],
                    'quantity'   => 1,
                ]],
                'mode'                       => 'payment',
                'success_url'                => 'https://kniholap.netlify.app',
                'cancel_url'                 => 'https://kniholap.netlify.app',

                'billing_address_collection' => 'required',

                'metadata'                   => [
                    'book_id'          => $book->id,
                    'buyer_id'         => auth('api')->id(),
                    'shipping_address' => $request->shipping_address,
                    'shipping_cost'    => $shipping_cost,
                    'platform_fee'     => $platform_fee,
                    'seller_id'        => $book->uploaded_by,
                ],

            ]);

            return $this->success(['checkout_url' => $checkout_session->url], 'Checkout session created successfully');

        } catch (\Exception $e) {
            return $this->error([], 'Failed to create checkout session: ' . $e->getMessage(), 500);
        }

    }

    public function buyer_order_list(Request $request)
    {

        $user = auth('api')->user();

        $orders = Order::where('buyer_id', $user->id)->get();

        $data = $orders->map(function ($order) {
            return [
                'id'               => $order->id,
                'order_number'     => $order->order_number,
                'room_id'          => $order->room_id,
                'seller_id'        => $order->seller_id,
                'buyer_id'         => $order->buyer_id,
                'book_id'          => $order->book_id,
                'total_amount'     => $order->total_amount,
                'book_price'       => $order->book_price,
                'shipping_cost'    => $order->shipping_cost,
                'platform_fee'     => $order->platform_fee,
                'shipping_address' => $order->shipping_address,
                'status'           => $order->status,
                'paid_at'          => $order->paid_at,

                'book'             => $order->book ? [
                    'title'       => $order->book ? $order->book->title : null,
                    'author'      => $order->book ? $order->book->author : null,
                    'cover_image' => $order->book ? $order->book->cover_image : null,
                    'type'        => $order->book ? $order->book->type : null,
                    'price'       => $order->book ? $order->book->price : null,

                ] : null,

            ];
        });

        return $this->success($data, 'Buyer orders retrieved successfully.');

    }

    public function buyer_order_details($id)
    {
        $user = auth('api')->user();

        $order = Order::where('buyer_id', $user->id)->where('id', $id)->first();

        if (! $order) {
            return $this->error([], 'Order not found.', 404);
        }

        $data = [
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'room_id'          => $order->room_id,
            'seller_id'        => $order->seller_id,
            'buyer_id'         => $order->buyer_id,
            'book_id'          => $order->book_id,
            'total_amount'     => $order->total_amount,
            'book_price'       => $order->book_price,
            'shipping_cost'    => $order->shipping_cost,
            'platform_fee'     => $order->platform_fee,
            'shipping_address' => $order->shipping_address,
            'status'           => $order->status,
            'paid_at'          => $order->paid_at,

            'book'             => $order->book ? [
                'title'       => $order->book ? $order->book->title : null,
                'author'      => $order->book ? $order->book->author : null,
                'cover_image' => $order->book ? $order->book->cover_image : null,
                'type'        => $order->book ? $order->book->type : null,
                'price'       => $order->book ? $order->book->price : null,

            ] : null,

        ];

        return $this->success($data, 'Order details retrieved successfully.');
    }

    // confirm delivery
    public function confirm_delivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        $order = Order::find($request->order_id);

        if (! $order) {
            return $this->error([], 'Order not found.', 404);
        }

        if ($order->buyer_id !== auth('api')->id()) {
            return $this->error([], 'You are not authorized to confirm delivery for this order.', 403);
        }

        $order->status       = 'delivered';
        $order->delivered_at = now();
        $order->save();

        // send message to seller about delivery confirmation
        $room = $order->room;
        $user = auth('api')->user();

        if ($room) {
            // send message to buyer about shipping update
            $messageContent = "Order {$order->order_number} has been marked as delivered by the buyer.";

            $message = $room->chats()->create([
                'sender_id'   => $user->id,
                'receiver_id' => $order->buyer_id,
                'text'        => $messageContent,
            ]);

        }

        // event
        broadcast(new MessageSendEvent($message))->toOthers();

        // Transfer money to seller
        $this->transferToSeller($order);

        return $this->success($order, 'Delivery confirmed successfully.');

    }

    // transfer money to seller

    private function transferToSeller($order)
    {

        Stripe::setApiKey(config('services.stripe.secret'));

        $sellerStripeId = $order->seller->stripe_account_id;

        // Transfer::create([
        //     'amount'         => ($order->book_price + $order->shipping_cost) * 100,
        //     'currency'       => 'eur',
        //     'destination'    => $sellerStripeId,
        //     'transfer_group' => $order->order_number,
        //     'description'    => "Payout for {$order->order_number}",
        // ]);

        $order->update(['status' => 'completed', 'completed_at' => now()]);

    }

}
