<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'receiver' => [
                'id' => $this->resource['receiver']->id,
                'first_name' => $this->resource['receiver']->first_name,
                'email' => $this->resource['receiver']->email,
                'avatar' => $this->resource['receiver']->avatar ? asset('' . $this->resource['receiver']->avatar) : asset('default/profile.jpg'),
                'last_activity_at' => $this->resource['receiver']->last_activity_at,
            ],
            'sender' => [
                'id' => $this->resource['sender']->id,
                'first_name' => $this->resource['sender']->first_name,
                'email' => $this->resource['sender']->email,
                'avatar' => $this->resource['sender']->avatar ? asset('' . $this->resource['sender']->avatar) : asset('default/profile.jpg'),
                'last_activity_at' => $this->resource['sender']->last_activity_at,
            ],
            'room' => [
                'id' => $this->resource['room']->id,
                'first_user_id' => $this->resource['room']->first_user_id,
                'second_user_id' => $this->resource['room']->second_user_id,
            ],
            'chat' => $this->resource['chat']->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'room_id' => $message->room_id,
                    'text' => $message->text,
                    'file' => $message->file,
                    'media_type' => $this->getMediaType($message),
                    'status' => $message->status,
                    'created_at' => $message->created_at,
                    'humanize_date'   => $message->created_at ? $message->created_at->diffForHumans(short:true) : 'just now',
                    // 'sender' => [
                    //     'id' => $message->sender->id,
                    //     'first_name' => $message->sender->first_name,
                    //     'email' => $message->sender->email,
                    //     'avatar' => $message->sender->avatar,
                    // ],
                    // 'receiver' => [
                    //     'id' => $message->receiver->id,
                    //     'first_name' => $message->receiver->first_name,
                    //     'email' => $message->receiver->email,
                    //     'avatar' => $message->receiver->avatar,
                    // ],
                ];
            }),
        ];
    }


    /**
     * Media type handling
     */
    private function getMediaType($message): ?string
    {
        if (!$message->file) return null;

        $extension = strtolower(pathinfo($message->file, PATHINFO_EXTENSION));

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
