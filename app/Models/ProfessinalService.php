<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessinalService extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'starting_price',
        'duration',
        'image',
        'category_id',

    ];


    protected $table = 'professinal_services';


    // imageurl attribute
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






    public function professional()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class, 'service_id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(ServiceReview::class, 'service_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
