<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalBrand extends Model
{
    protected $fillable = [
        'user_id',
        'brand_id',

    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $table = 'professional_brands';



    public function brand(){
        return $this->belongsTo(Brand::class);
    }

}
