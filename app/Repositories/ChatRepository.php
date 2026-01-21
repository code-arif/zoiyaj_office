<?php

namespace App\Repositories;

use App\Models\Chat;
use Illuminate\Support\Collection;

class ChatRepository
{
    protected $model;

    /**
     * Create a new class instance.
     */
    public function __construct(Chat $model)
    {
        $this->model = $model;
    }

    /**
     * Get conversation messages between two users
     */
    public function getConversationMessages(int $senderId, int $receiverId, int $limit = 50): Collection
    {
        return $this->model
            ->where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $senderId)
                    ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $senderId);
            })
            ->with([
                'sender:id,first_name,email,avatar,last_activity_at',
                'receiver:id,first_name,email,avatar,last_activity_at',
                'room:id,first_user_id,second_user_id',
            ])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get last message between two users
     */
    public function getLastMessage(int $userId, int $otherUserId)
    {
        return $this->model
            ->where(function ($query) use ($userId, $otherUserId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                    ->where('receiver_id', $userId);
            })
            ->latest('created_at')
            ->first();
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(int $receiverId, int $senderId): int
    {
        return $this->model
            ->where('receiver_id', $receiverId)
            ->where('sender_id', $senderId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }

    /**
     * Mark all messages as read
     */
    public function markAllAsRead(int $receiverId, int $senderId): int
    {
        return $this->model
            ->where('receiver_id', $receiverId)
            ->where('sender_id', $senderId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }

    /**
     * Mark single message as read
     */
    public function markSingleAsRead(int $userId, int $chatId): int
    {
        return $this->model
            ->where('id', $chatId)
            ->where('receiver_id', $userId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }

    /**
     * Create new chat message
     */
    public function create(array $data): Chat
    {
        return $this->model->create($data);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId, int $senderId): int
    {
        return $this->model
            ->where('receiver_id', $userId)
            ->where('sender_id', $senderId)
            ->where('status', '!=', 'read')
            ->count();
    }
}
