<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceReview extends Model
{
    protected $fillable = [
        'booking_id',
        'client_id',
        'professional_id',
        'rating',
        'comment',
    ];

    public function booking()
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id', 'id');
    }
}
