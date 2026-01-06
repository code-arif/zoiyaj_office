<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookImage extends Model
{
    protected $guarded = ['id'];

    public function getImageUrlAttribute($value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return $value ? url($value) : null;
    }

    protected $hidden = ['created_at', 'updated_at'];



    // book relation
    public function book()
    {
        return $this->belongsTo(Book::class);
    }




}
