<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'client_id',
        'professional_id',
    ];

    protected $table = 'bookmarks';

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id', 'id');
    }


}
