<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemTier extends Model
{
    protected $fillable = [
        'tier_name',
        'points_required',
        'discount_amount',
        'description',
        'is_active',
    ];


    protected $casts = [
        'is_active' => 'boolean',
    ];




}
