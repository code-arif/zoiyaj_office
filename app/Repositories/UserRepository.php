<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository
{
    protected $model;
    protected $chatRepository;

    public function __construct(User $model, ChatRepository $chatRepository)
    {
        $this->model = $model;
        $this->chatRepository = $chatRepository;
    }

    /**
     * Get users with last message (with search & pagination)
     */
    public function getUsersWithLastMessage(int $userId, ?string $keyword = null, int $perPage = 50): array
    {
        // Build base query for users who have chatted
        $query = $this->model
            ->select('id', 'first_name', 'email', 'avatar', 'last_activity_at')
            ->where('id', '!=', $userId)
            ->where(function ($q) use ($userId) {
                $q->whereHas('senders', function ($query) use ($userId) {
                    $query->where('receiver_id', $userId);
                })
                    ->orWhereHas('receivers', function ($query) use ($userId) {
                        $query->where('sender_id', $userId);
                    });
            });

        // Apply search filter if keyword exists
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        // Get paginated users
        $users = $query->paginate($perPage);

        // Attach last message and unread count to each user
        $usersWithMessages = $users->getCollection()->map(function ($user) use ($userId) {
            $user->last_chat = $this->chatRepository->getLastMessage($userId, $user->id);
            $user->unread_count = $this->chatRepository->getUnreadCount($userId, $user->id);
            return $user;
        });

        // Sort by last message timestamp (in-memory sorting after pagination)
        $sortedUsers = $usersWithMessages->sortByDesc(function ($user) {
            return optional($user->last_chat)->created_at;
        })->values();

        // Return formatted pagination data
        return [
            'data' => $sortedUsers,
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
        ];
    }

    /**
     * Find user basic info
     */
    public function findBasicInfo(int $userId): ?User
    {
        return $this->model
            ->select('id', 'first_name', 'email', 'avatar', 'last_activity_at')
            ->find($userId);
    }

    /**
     * Check if user exists
     */
    public function exists(int $userId): bool
    {
        return $this->model->where('id', $userId)->exists();
    }
}
