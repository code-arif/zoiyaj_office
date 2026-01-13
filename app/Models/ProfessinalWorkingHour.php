<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessinalWorkingHour extends Model
{
    protected $fillable = ['day', 'is_closed', 'open_time', 'close_time'];
}
