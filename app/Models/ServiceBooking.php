<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBooking extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'notes',
        'points',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(ProfessinalService::class, 'service_id', 'id');
    }
}
