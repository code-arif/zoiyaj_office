<?php

namespace App\Http\Controllers\Api\User;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Http\Resources\ChatListResource;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ConversationResource;

class ChatSystemController extends Controller
{
    use ApiResponse;

    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get list of users with recent conversations (with search & pagination)
     */
    public function list(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 50);
        $keyword = $request->get('keyword');

        $result = $this->chatService->getUserChatList(
            auth()->id(),
            $keyword,
            $perPage
        );

        return $this->success([
            'users' => ChatListResource::collection($result['data']),
            'pagination' => [
                'page' => $result['current_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'current_page' => $result['current_page']
            ]
        ], 'Chat retrieved successfully');
    }

    /**
     * Get conversation messages between users
     */
    public function conversation(int $receiver_id): JsonResponse
    {
        $conversation = $this->chatService->getConversation(
            auth()->id(),
            $receiver_id
        );

        if (!$conversation) {
            return $this->error(
                [],
                'User not found or cannot chat with yourself',
                404
            );
        }

        return $this->success(
            ConversationResource::make($conversation)->resolve(),
            'Messages retrieved successfully'
        );
    }

    /**
     * Send message to user
     */
    public function send(int $receiver_id, SendMessageRequest $request): JsonResponse
    {
        $chat = $this->chatService->sendMessage(
            auth()->id(),
            $receiver_id,
            $request->validated()
        );

        if (!$chat) {
            return $this->error(
                [],
                'User not found or cannot chat with yourself',
                404
            );
        }

        return $this->success(
            ['chat' => ChatMessageResource::make($chat)],
            'Message sent successfully'
        );
    }

    /**
     * Mark all messages as read
     */
    public function seenAll(int $receiver_id): JsonResponse
    {
        $result = $this->chatService->markAllAsRead(
            auth()->id(),
            $receiver_id
        );

        if (!$result) {
            return $this->error(
                [],
                'User not found or cannot chat with yourself',
                404
            );
        }

        return $this->success(
            ['chat' => $result],
            'Messages marked as read successfully'
        );
    }

    /**
     * Mark single message as read
     */
    public function seenSingle(int $chat_id): JsonResponse
    {
        $result = $this->chatService->markSingleAsRead(
            auth()->id(),
            $chat_id
        );

        return $this->success(
            ['chat' => $result],
            'Message marked as read successfully'
        );
    }

    /**
     * Get or create room
     */
    public function room(int $receiver_id): JsonResponse
    {
        $room = $this->chatService->getOrCreateRoom(
            auth()->id(),
            $receiver_id
        );

        if (!$room) {
            return $this->error(
                [],
                'User not found or cannot chat with yourself',
                404
            );
        }

        return $this->success(
            ['room' => RoomResource::make($room)],
            'Room retrieved successfully'
        );
    }
}
