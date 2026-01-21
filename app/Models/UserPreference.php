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
        'preference_id',
        'type',
    ];

    protected $hidden = ['created_at', 'updated_at', 'allergies', 'ingredients_to_avoid', 'ethical_preferences' , 'skin_type', 'hair_type', 'hair_texture'];




    // Relation with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function preference()
    {
        return $this->belongsTo(Preference::class);
    }


}
