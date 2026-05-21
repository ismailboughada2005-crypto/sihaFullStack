<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'type',
        'status',
        'notes',
        'cancellation_reason',
        'confirmed_at',
        'completed_at',
        'room_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($appointment) {
            // Verify patient does not have conflicting appointments at the same date and time
            if (in_array($appointment->status, ['pending', 'confirmed'])) {
                $query = self::where('patient_id', $appointment->patient_id)
                    ->where('appointment_date', $appointment->appointment_date)
                    ->where('appointment_time', $appointment->appointment_time)
                    ->whereIn('status', ['pending', 'confirmed']);

                if ($appointment->exists) {
                    $query->where('id', '!=', $appointment->id);
                }

                if ($query->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'appointment_time' => 'You already have an appointment at this time.'
                    ]);
                }

                // Verify room is not occupied at the same date and time
                if ($appointment->room_id) {
                    $roomQuery = self::where('room_id', $appointment->room_id)
                        ->where('appointment_date', $appointment->appointment_date)
                        ->where('appointment_time', $appointment->appointment_time)
                        ->whereIn('status', ['pending', 'confirmed']);

                    if ($appointment->exists) {
                        $roomQuery->where('id', '!=', $appointment->id);
                    }

                    if ($roomQuery->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'room_id' => 'Room is already occupied at this time.'
                        ]);
                    }
                }
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Medecin::class, 'doctor_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
