<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'room_id',
        'order_number',
        'buyer_id',
        'seller_id',
        'buyer_id',
        'book_id',
        'total_amount',
        'book_price',
        'shipping_cost',
        'platform_fee',
        'shipping_address',

        'status',
        'payment_status',
        'tracking_number',
        'courier_name',
        'stripe_payment_intent',
        'paid_at',

    ];


    // book relation
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'id');
    }



    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }








}
