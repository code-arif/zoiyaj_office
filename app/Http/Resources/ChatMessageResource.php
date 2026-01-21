<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'room_id' => $this->room_id,
            'text' => $this->text,
            'file' => $this->file,
            'media_type'      => $this->getMediaType(),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'humanize_date'   => $this->created_at ? $this->safe($this->created_at->diffForHumans()) : 'just now',
            'sender' => [
                'id' => $this->sender->id,
                'first_name' => $this->sender->first_name,
                'avatar' => $this->sender->avatar,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'first_name' => $this->receiver->first_name,
                'avatar' => $this->receiver->avatar,
            ],
        ];
    }

    /**
     * Media type handling
     */
    private function getMediaType(): ?string
    {
        if (!$this->file) return null;

        $extension = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));

        $image = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
        $video = ['mp4', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'webm', '3gp', 'mpeg', 'mpg'];
        $audio = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'wma'];
        $doc   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'];
        $zip   = ['zip', 'rar', '7z', 'tar', 'gz'];

        return match (true) {
            in_array($extension, $image) => 'image',
            in_array($extension, $video) => 'video',
            in_array($extension, $audio) => 'audio',
            in_array($extension, $doc)   => 'document',
            in_array($extension, $zip)   => 'archive',
            default => 'file'
        };
    }
}
