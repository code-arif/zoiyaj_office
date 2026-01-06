<?php
namespace App\Http\Controllers\Api\Seller;

use App\Events\MessageSendEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ApiResponse;

    public function order_list(Request $request)
    {

        $user = auth('api')->user();

        $orders = Order::where('seller_id', $user->id)->get();

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

        return $this->success($data, 'Seller orders retrieved successfully.');

    }

    public function order_details($id)
    {
        $user = auth('api')->user();

        $order = Order::where('seller_id', $user->id)->where('id', $id)->first();

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
            'tracking_number'  => $order->tracking_number ?? null,
            'courier_name'     => $order->courier_name,
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

    // update shipping info
    public function update_shipping_info(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'order_id'        => 'required|exists:orders,id',
            'tracking_number' => 'required|string',
            'courier_name'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $order = Order::where('seller_id', $user->id)
            ->where('id', $request->order_id)
            ->first();

        if (! $order) {
            return $this->error([], 'Order not found.', 404);
        }

        // also manage chat

        $room = $order->room;
        if ($room) {
            // send message to buyer about shipping update
            $messageContent = "The order (Order No: {$order->order_number}) has been shipped via {$request->courier_name}. Tracking Number: {$request->tracking_number}.";

            $message = $room->chats()->create([
                'sender_id'   => $user->id,
                'receiver_id' => $order->buyer_id,
                'text'        => $messageContent,
            ]);

        }

        // event
        broadcast(new MessageSendEvent($message))->toOthers();

        $order->tracking_number = $request->tracking_number;
        $order->courier_name    = $request->courier_name;
        $order->status          = 'shipped';
        $order->shipped_at      = now();
        $order->save();

        return $this->success($order, 'Shipping information updated successfully.');

    }



}
