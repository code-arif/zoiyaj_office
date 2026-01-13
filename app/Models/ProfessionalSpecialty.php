<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalSpecialty extends Model
{
     protected $fillable = [
        'user_id',
        'specialty_id'

    ];

    protected $table = 'professional_specialties';

}
