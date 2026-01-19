<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $guarded = ['id'];

    public function specialty()
    {
        return $this->belongsTo(
            ProfessionalSpecialty::class,
        );
    }
}
