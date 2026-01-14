<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessinalService extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'starting_price',
        'duration'

    ];

    protected $table = 'professinal_services';

}
