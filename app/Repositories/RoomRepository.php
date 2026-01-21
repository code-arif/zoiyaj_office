<?php

namespace App\Repositories;

use App\Models\Room;

class RoomRepository
{
    protected $model;

    /**
     * Create a new class instance.
     */
    public function __construct(Room $model)
    {
        $this->model = $model;
    }

    /**
     * Find room between two users
     */
    public function findBetweenUsers(int $userId1, int $userId2): ?Room
    {
        return $this->model
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where('first_user_id', $userId1)
                    ->where('second_user_id', $userId2);
            })
            ->orWhere(function ($query) use ($userId1, $userId2) {
                $query->where('first_user_id', $userId2)
                    ->where('second_user_id', $userId1);
            })
            ->first();
    }

    /**
     * Find or create room between two users
     */
    public function findOrCreate(int $userId1, int $userId2): Room
    {
        $room = $this->findBetweenUsers($userId1, $userId2);

        if (!$room) {
            $room = $this->model->create([
                'first_user_id' => $userId1,
                'second_user_id' => $userId2,
            ]);
        }

        return $room;
    }

    /**
     * Find or create room with user relationships loaded
     */
    public function findOrCreateWithUsers(int $userId1, int $userId2): Room
    {
        $room = $this->model
            ->with([
                'first_user:id,first_name,email,avatar,last_activity_at',
                'second_user:id,first_name,email,avatar,last_activity_at'
            ])
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where('first_user_id', $userId1)
                    ->where('second_user_id', $userId2);
            })
            ->orWhere(function ($query) use ($userId1, $userId2) {
                $query->where('first_user_id', $userId2)
                    ->where('second_user_id', $userId1);
            })
            ->first();

        if (!$room) {
            $room = $this->model->create([
                'first_user_id' => $userId1,
                'second_user_id' => $userId2,
            ]);
            $room->load([
                'first_user:id,first_name,email,avatar,last_activity_at',
                'second_user:id,first_name,email,avatar,last_activity_at'
            ]);
        }

        return $room;
    }
}
