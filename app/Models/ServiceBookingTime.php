<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBookingTime extends Model
{
    protected $fillable = [
        'booking_id',
        'scheduled_time',
        'scheduled_date',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }


}
