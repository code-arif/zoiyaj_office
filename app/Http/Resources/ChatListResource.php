<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'last_activity_at' => $this->last_activity_at,
            'last_chat' => $this->when($this->last_chat, function () {
                return [
                    'id' => $this->last_chat->id,
                    'text' => $this->last_chat->short_text,
                    'file' => $this->last_chat->file,
                    'status' => $this->last_chat->status,
                    'created_at' => $this->last_chat->created_at,
                    'sender_id' => $this->last_chat->sender_id,
                    'receiver_id' => $this->last_chat->receiver_id,
                ];
            }),
            'unread_count' => $this->unread_count ?? 0,
        ];
    }
}
