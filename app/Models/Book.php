<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Book extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function getCoverImageAttribute($value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return $value ? url($value) : null;
    }

    public function getPdfFileAttribute($value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return $value ? url($value) : null;
    }

    public function book_categories()
    {
        return $this->hasMany(BookCategory::class);
    }

    public function book_images()
    {
        return $this->hasMany(BookImage::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function isWishlistedBy(User $user)
    {
        return $this->wishlistedBy()->where('user_id', $user->id)->exists();
    }

    public function book_reviews()
    {
        return $this->hasMany(BookReview::class);
    }

    // avg rating
    public function book_reviews_avg_rating()
    {
        return $this->book_reviews()->select('book_id', DB::raw('AVG(rating) as average_rating'))->groupBy('book_id');
    }

    // orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // // book ratings
    // public function ratings()
    // {
    //     return $this->hasMany(BookReview::class)->select('book_id', DB::raw('AVG(rating) as average_rating'))->groupBy('book_id');
    // }

    public function completions()
    {
        return $this->hasMany(UserBookCompletion::class);
    }

    public function completedBy(User $user)
    {
        return $this->completions()->where('user_id', $user->id)->exists();
    }

    public function completionCount()
    {
        return $this->completions()->count();
    }

}
