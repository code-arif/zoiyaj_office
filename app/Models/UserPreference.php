<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class UserPreference extends Model
{
    use HasFactory;

    protected $table = 'user_preferences'; // Table name

    // Fillable fields
    protected $fillable = [
        'user_id',
        'allergies',
        'ingredients_to_avoid',
        'ethical_preferences',
        'skin_type',
        'hair_type',
        'hair_texture',
    ];


    // Relation with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
