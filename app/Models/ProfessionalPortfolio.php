<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalPortfolio extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'image',
        'video',

    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function getImageAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && ! empty($value)) {

            return url($value);
        }
        return $value;
    }

    public function getVideoAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && ! empty($value)) {

            return url($value);
        }
        return $value;
    }

    protected $table = 'professional_portfolios';
}
