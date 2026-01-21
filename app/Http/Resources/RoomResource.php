<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_user_id' => $this->first_user_id,
            'second_user_id' => $this->second_user_id,
            'first_user' => [
                'id' => $this->first_user->id,
                'first_name' => $this->first_user->first_name,
                'email' => $this->first_user->email,
                'avatar' => $this->first_user->avatar,
                'last_activity_at' => $this->first_user->last_activity_at,
            ],
            'second_user' => [
                'id' => $this->second_user->id,
                'first_name' => $this->second_user->first_name,
                'email' => $this->second_user->email,
                'avatar' => $this->second_user->avatar,
                'last_activity_at' => $this->second_user->last_activity_at,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
