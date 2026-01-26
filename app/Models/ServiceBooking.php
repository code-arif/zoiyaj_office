<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'service_id',
        'scheduled_date',
        'scheduled_time',
    ];

    protected $table = 'service_bookings';

    // protected $casts = [
    //     'scheduled_date' => 'date',
    //     'scheduled_time' => 'time',
    // ];


    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }
    public function service()
    {
        return $this->belongsTo(ProfessinalService::class, 'service_id', 'id');
    }
}
