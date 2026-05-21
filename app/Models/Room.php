<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['room_number', 'type', 'status', 'capacity'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
