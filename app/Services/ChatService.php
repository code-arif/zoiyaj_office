<?php

namespace App\Services;

use App\Events\MessageSendEvent;
use Illuminate\Support\Facades\DB;
use App\Repositories\ChatRepository;
use App\Repositories\RoomRepository;
use App\Repositories\UserRepository;

class ChatService
{
    protected $chatRepository;
    protected $roomRepository;
    protected $userRepository;

    public function __construct(
        ChatRepository $chatRepository,
        RoomRepository $roomRepository,
        UserRepository $userRepository
    ) {
        $this->chatRepository = $chatRepository;
        $this->roomRepository = $roomRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get user chat list with search and pagination
     */
    public function getUserChatList(int $userId, ?string $keyword = null, int $perPage = 50): array
    {
        return $this->userRepository->getUsersWithLastMessage($userId, $keyword, $perPage);
    }


    /**
     * Get conversation between two users
     */
    public function getConversation(int $senderId, int $receiverId)
    {
        // Validate receiver exists and not same as sender
        if (!$this->userRepository->exists($receiverId) || $receiverId == $senderId) {
            return null;
        }

        // Mark messages as read
        $this->chatRepository->markAsRead($senderId, $receiverId);

        // Get or create room
        $room = $this->roomRepository->findOrCreate($senderId, $receiverId);

        // Get messages
        $messages = $this->chatRepository->getConversationMessages($senderId, $receiverId);

        // Get user details
        $receiver = $this->userRepository->findBasicInfo($receiverId);
        $sender = $this->userRepository->findBasicInfo($senderId);

        return [
            'receiver' => $receiver,
            'sender' => $sender,
            'room' => $room,
            'chat' => $messages,
        ];
    }

    /**
     * Send message
     */
    public function sendMessage(int $senderId, int $receiverId, array $data)
    {
        // Validate receiver exists and not same as sender
        if (!$this->userRepository->exists($receiverId) || $receiverId == $senderId) {
            return null;
        }

        return DB::transaction(function () use ($senderId, $receiverId, $data) {
            // Get or create room
            $room = $this->roomRepository->findOrCreate($senderId, $receiverId);

            // Handle file upload
            $filePath = null;
            if (isset($data['file'])) {
                $filePath = $this->uploadFile($data['file']);
            }

            // Create chat message
            $chat = $this->chatRepository->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'text' => $data['text'] ?? null,
                'file' => $filePath,
                'room_id' => $room->id,
                'status' => 'sent',
            ]);

            // Load relationships
            $chat->load(['sender', 'receiver', 'room']);

            // Broadcast event
            broadcast(new MessageSendEvent($chat))->toOthers();

            return $chat;
        });
    }

    /**
     * Mark all messages as read
     */
    public function markAllAsRead(int $senderId, int $receiverId)
    {
        // Validate receiver exists and not same as sender
        if (!$this->userRepository->exists($receiverId) || $receiverId == $senderId) {
            return null;
        }

        return $this->chatRepository->markAllAsRead($senderId, $receiverId);
    }

    /**
     * Mark single message as read
     */
    public function markSingleAsRead(int $userId, int $chatId)
    {
        return $this->chatRepository->markSingleAsRead($userId, $chatId);
    }

    /**
     * Get or create room
     */
    public function getOrCreateRoom(int $senderId, int $receiverId)
    {
        // Validate receiver exists and not same as sender
        if (!$this->userRepository->exists($receiverId) || $receiverId == $senderId) {
            return null;
        }

        return $this->roomRepository->findOrCreateWithUsers($senderId, $receiverId);
    }

    /**
     * Upload file helper
     */
    protected function uploadFile($file): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs('chat', $filename, 'public');
    }
}
