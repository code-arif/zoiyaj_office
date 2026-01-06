<?php
namespace App\Http\Controllers\Api\User;

use App\Events\MessageSendEvent;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatSystemController extends Controller
{

    use ApiResponse;

    public function conversation($order_id, $receiver_id): JsonResponse
    {

        $sender_id = Auth::guard('api')->id();

        $order = Order::where('id', $order_id)
            ->where(function ($query) use ($receiver_id, $sender_id) {
                $query->where('buyer_id', $receiver_id)->where('seller_id', $sender_id);
            })->orWhere(function ($query) use ($receiver_id, $sender_id) {
            $query->where('buyer_id', $sender_id)->where('seller_id', $receiver_id);
        })->first();

        if (! $order) {
            return $this->error([], 'Order not found between these users', 404);
        }

        // room exist check
        $room = Room::where('id', $order->room_id)->first();
        if (! $room) {
            return $this->error([], 'Chat room not found for this order', 404);
        }

        Chat::where('receiver_id', $sender_id)->where('sender_id', $receiver_id)->update(['status' => 'read']);

        $chat = Chat::query()
            ->where(function ($query) use ($receiver_id, $sender_id) {
                $query->where('sender_id', $sender_id)->where('receiver_id', $receiver_id);
            })
            ->orWhere(function ($query) use ($receiver_id, $sender_id) {
                $query->where('sender_id', $receiver_id)->where('receiver_id', $sender_id);
            })
            ->with([
                'sender:id,first_name,last_name,avatar,last_activity_at',
                'receiver:id,first_name,last_name,avatar,last_activity_at',
                'room:id,first_user_id,second_user_id',
            ])
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $room = Room::where(function ($query) use ($receiver_id, $sender_id) {
            $query->where('first_user_id', $receiver_id)->where('second_user_id', $sender_id);
        })->orWhere(function ($query) use ($receiver_id, $sender_id) {
            $query->where('first_user_id', $sender_id)->where('second_user_id', $receiver_id);
        })->first();

        if (! $room) {
            $room = Room::create([
                'first_user_id'  => $sender_id,
                'second_user_id' => $receiver_id,
            ]);
        }

        $data = [
            'receiver' => User::select('id', 'first_name', 'last_name', 'avatar', 'last_activity_at')->where('id', $receiver_id)->first(),
            'sender'   => User::select('id', 'first_name', 'last_name', 'avatar', 'last_activity_at')->where('id', $sender_id)->first(),
            'room'     => $room,
            'chat'     => $chat,
        ];

        return $this->success($data, 'Conversation retrieved successfully.');
    }

    public function send($receiver_id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text'    => 'nullable|string|max:255',
            'file'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        $sender_id = Auth::guard('api')->id();

        $receiver_exist = User::where('id', $receiver_id)->first();
        if (! $receiver_exist || $receiver_id == $sender_id) {
            return $this->error([], 'User not found or cannot chat with yourself', 404);
        }

        $room = Room::where('id', $request->room_id)
            ->first();


        $file = null;
        if ($request->hasFile('file')) {
            $file = Helper::fileUpload($request->file('file'), 'chat', time() . '_' . getFileName($request->file('file')));
        }

        $chat = Chat::create([
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'text'        => $request->text,
            'file'        => $file,
            'room_id'     => $room->id,
            'status'      => 'sent',
        ]);

        //* Load the senders information
        $chat->load([
            'sender:id,first_name,last_name,avatar,last_activity_at',
            'receiver:id,first_name,last_name,avatar,last_activity_at',
            'room:id,first_user_id,second_user_id',
        ]);

        broadcast(new MessageSendEvent($chat))->toOthers();

        $data = [
            'chat' => $chat,
        ];

        return $this->success($data, 'Message sent successfully.');
    }

    public function seenAll($receiver_id): JsonResponse
    {
        $sender_id = Auth::guard('api')->id();

        $receiver_exist = User::where('id', $receiver_id)->first();
        if (! $receiver_exist || $receiver_id == $sender_id) {
            return response()->json(['success' => false, 'message' => 'User not found or cannot chat with yourself', 'data' => [], 'code' => 200]);
        }

        $chat = Chat::where('receiver_id', $sender_id)->where('sender_id', $receiver_id)->update(['status' => 'read']);

        $data = [
            'chat' => $chat,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Message seen successfully',
            'data'    => $data,
            'code'    => 200,
        ]);
    }

    public function seenSingle($chat_id): JsonResponse
    {
        $sender_id = Auth::guard('api')->id();

        $chat = Chat::where('id', $chat_id)->where('receiver_id', $sender_id)->update(['status' => 'read']);

        $data = [
            'chat' => $chat,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Message seen successfully',
            'data'    => $data,
            'code'    => 200,
        ]);
    }

    public function room($room_id)
    {
        $sender_id = Auth::guard('api')->id();

        $room = Room::where('id', $room_id)->first();
        if (! $room || ($room->first_user_id != $sender_id && $room->second_user_id != $sender_id)) {
            return $this->error([], 'Chat room not found', 404);
        }

        $data = [
            'room'     => $room,

            'receiver' => User::select('id', 'first_name', 'last_name', 'avatar', 'last_activity_at')->where('id', $room->first_user_id == $sender_id ? $room->second_user_id : $room->first_user_id)->first(),

            'messages' => Chat::where('room_id', $room->id)
                ->with([
                    'sender:id,first_name,last_name,avatar,last_activity_at',
                    'receiver:id,first_name,last_name,avatar,last_activity_at',
                    // 'room:id,first_user_id,second_user_id',
                ])
                ->orderBy('created_at', 'asc')
                ->get(),
        ];

        return $this->success($data, 'Chat room retrieved successfully.');

    }
}
