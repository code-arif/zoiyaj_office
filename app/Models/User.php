<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'accessibilties',
        'bio',
        'latitude',
        'longitude',
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
            'is_wheelchair_accessibility'     => 'boolean',
            'is_hijab_friendly'               => 'boolean',
            'is_prone'                        => 'boolean',

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

    public function getThumbAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && ! empty($value)) {

            return url($value);
        }
        return $value;
    }

    public function getLogoPathAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && ! empty($value)) {

            return url($value);
        }
        return $value;
    }

    public function getCertificatePathAttribute($value): string | null
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

    // public function user_categories()
    // {
    //     return $this->belongsToMany(Category::class, 'user_categories', 'user_id', 'category_id');
    // }

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
        return $this->hasMany(ProfessionalSpecialty::class, 'user_id', 'id');
    }

    public function working_hours()
    {
        return $this->hasMany(ProfessinalWorkingHour::class, 'user_id', 'id');
    }

    public function user_brands()
    {
        return $this->hasMany(ProfessionalBrand::class, 'user_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(ProfessinalService::class, 'user_id', 'id');
    }

    public function portfolios()
    {
        return $this->hasMany(ProfessionalPortfolio::class, 'user_id', 'id');
    }

    public function specialties()
    {
        return $this->belongsToMany(
            Specialty::class,
            'professional_specialties'
        );
    }
    public function service_bookings()
    {
        return $this->hasMany(ServiceBooking::class, 'user_id', 'id');
    }

    // user preferences
    public function preferences()
    {
        return $this->hasMany(UserPreference::class, 'user_id', 'id');
    }

    /**
     * Messages sent by this user
     */
    public function senders(): HasMany
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }

    /**
     * Messages received by this user
     */
    public function receivers(): HasMany
    {
        return $this->hasMany(Chat::class, 'receiver_id');
    }

    /**
     * Rooms where user is first user
     */
    public function firstUserRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'first_user_id');
    }

    /**
     * Rooms where user is second user
     */
    public function secondUserRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'second_user_id');
    }

    public function user_categories()
    {
        return $this->hasMany(ProfessionalCategory::class, 'user_id', 'id');
    }

    // professional services
    public function professionalServices()
    {
        return $this->hasMany(ProfessinalService::class, 'user_id', 'id');
    }

    // professional reviews
    public function professionalReviews()
    {
        return $this->hasMany(ServiceReview::class, 'professional_id', 'id');
    }

    // client reviews
    public function clientReviews()
    {
        return $this->hasMany(ServiceReview::class, 'client_id', 'id');
    }

    // bookmarks
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'client_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    /* manage follower system */

    /**
     * Users who follow this user (only professionals should have followers)
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Users this user is following
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Check if authenticated user follows this professional
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Increment/decrement followers_count safely
     */
    public function incrementFollowersCount()
    {
        $this->increment('followers_count');
    }

    public function decrementFollowersCount()
    {
        $this->decrement('followers_count');
    }

    // hidden professionals
    public function hiddenProfessionals()
    {
        return $this->belongsToMany(
            User::class,
            'hidden_professionals',
            'user_id',
            'professional_id'
        )->withTimestamps('hidden_at');
    }

    public function hiddenByUsers()
    {
        return $this->belongsToMany(
            User::class,
            'hidden_professionals',
            'professional_id', // swapped
            'user_id'          // swapped
        );
    }

    // favourite professionals
    public function favouriteProfessionals()
    {
        return $this->belongsToMany(
            User::class,
            'favourite_professionals',
            'user_id',
            'professional_id'
        )->withPivot('favourited_at')->withTimestamps();
    }
}
