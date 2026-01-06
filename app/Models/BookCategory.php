<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCategory extends Model
{
    protected $guarded = ['id'];


    protected $hidden = ['created_at', 'updated_at'];

     public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');

    }

}
