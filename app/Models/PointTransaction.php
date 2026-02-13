<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
     protected $table = 'point_transactions';

    protected $fillable = [
       'user_id',
       'user_type',
       'booking_id',
       'points',
       'action',
       'confirm_date',
       'checkin_date'
    ];
}
