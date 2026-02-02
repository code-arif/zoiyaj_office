<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckInBooking extends Model
{
    protected $table = 'check_in_bookings';

    protected $fillable = [
        'booking_id',
        'client_id',
        'professional_id',
        'redeem_tier_id',
        'client_checked_in_at',
        'professional_confirmed_at',
        'status',
        'points_given_on_checkin_confirmed',
    ];
}
