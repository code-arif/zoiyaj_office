<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['title', 'image', 'status', 'slug'];

    protected $table = 'categories';

    protected $hidden = ['created_at',  'updated_at', 'status'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    public function getImageAttribute($value)
    {
        return $value ? url($value) : null;
    }

    // product models
    public function productModels()
    {
        return $this->hasMany(ProductModel::class, 'category_id', 'id');
    }

}
