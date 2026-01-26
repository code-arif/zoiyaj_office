<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'owner_id',
        'user_id',
        'date',
        'status',
        'points',
        'notes',
    ];

    public function serviceBookings()
    {
        return $this->hasMany(ServiceBooking::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }


    public function serviceBookingTimes()
    {
        return $this->hasMany(ServiceBookingTime::class, 'booking_id');
    }

}
