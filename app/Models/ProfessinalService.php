<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessinalService extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'starting_price',
        'duration'

    ];

    protected $table = 'professinal_services';

    public function professional()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class, 'service_id', 'id');
    }
}
