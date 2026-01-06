<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookReview extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'rating',
        'review',
    ];



    public function book()
    {
        return $this->belongsTo(Book::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }




}
