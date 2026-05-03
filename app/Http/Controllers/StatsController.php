<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Medecin;
use App\Models\Secretaire;
use App\Models\Appointment;
use App\Models\Administrateur;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function counts()
    {
        return response()->json([
            'patients' => Patient::count(),
            'doctors' => Medecin::count(),
            'staff' => Secretaire::count(),
            'appointments' => Appointment::count(),
            'admins' => Administrateur::count(),
        ]);
    }
}
