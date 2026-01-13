<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProfessinalWorkingHour extends Model
{
    protected $fillable = ['day', 'is_closed', 'open_time', 'close_time'];

    // WorkingHour model
    public function getOpenTime12Attribute()
    {
        return $this->open_time
            ? Carbon::createFromFormat('H:i', $this->open_time)->format('h:i A')
            : null;
    }

    public function getCloseTime12Attribute()
    {
        return $this->close_time
            ? Carbon::createFromFormat('H:i', $this->close_time)->format('h:i A')
            : null;
    }

}
