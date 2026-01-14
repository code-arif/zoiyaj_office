<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalBrand extends Model
{
    protected $fillable = [
        'user_id',
        'brand_id',

    ];

    protected $table = 'professional_brands';

}
