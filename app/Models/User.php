<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{

    use HasFactory, Notifiable, Billable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'branch_code',
        'password',
        'otp_expires_at',
        'is_otp_verified',
        'otp',
        'role',
        'avatar',
        'cover',
        'reset_password_token',
        'reset_password_token_expire_at',
        'stripe_account_id',
        'professional_name',
        'professional_email',
        'professional_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'years_in_business',
        'is_promo_participation',
        'is_sell_retail_products',
        'accessibilties'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'               => 'datetime',
            'otp_expires_at'                  => 'datetime',
            'is_otp_verified'                 => 'boolean',
            'reset_password_token_expires_at' => 'datetime',
            'password'                        => 'hashed',
        ];
    }

    public function getAvatarAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && ! empty($value)) {

            return url($value);
        }
        return $value;
    }






    public function get_project()
    {
        return $this->hasMany(CompanyProject::class, 'company_id', 'id');
    }

    public function user_categories()
    {
        return $this->belongsToMany(Category::class, 'user_categories', 'user_id', 'category_id');
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedBooks()
    {
        return $this->belongsToMany(Book::class, 'wishlists');
    }

    public function book_reviews()
    {
        return $this->hasMany(BookReview::class);
    }



    // as  seller orders
    public function sold_orders()
    {
        return $this->hasMany(Order::class, 'seller_id', 'id');
    }



    // as buyer orders
    public function bought_orders()
    {
        return $this->hasMany(Order::class, 'buyer_id', 'id');
    }


    public function book_completions()
    {
        return $this->hasMany(UserBookCompletion::class);
    }


    // user uploaded books by uploaded_by field
    public function books()
    {
        return $this->hasMany(Book::class, 'uploaded_by', 'id');
    }

    // total completed deliberies on order as seller
    public function total_completed_deliveries()
    {
        return $this->sold_orders()->where('status', 'completed')->count();

    }


    // as book owner total reviews
    public function total_book_reviews()
    {
        return $this->hasManyThrough(BookReview::class, Book::class, 'uploaded_by', 'book_id', 'id', 'id');
    }




    // total earned amount as seller
    public function total_earned_amount()
    {
        return $this->sold_orders()->sum('total_amount');
    }


    public function user_specialty()
    {
        return $this->hasMany(ProfessionalSpecialty::class);
    }


}
