<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method',
        'payment_date',
        'transaction_id',
        'status',
        'notes',
        'processed_by',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

}
