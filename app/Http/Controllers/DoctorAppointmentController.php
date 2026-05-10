<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medecin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorAppointmentController extends Controller
{
    private function getDoctor()
    {
        $user = Auth::user();
        if ($user->role !== 'doctor') {
            abort(403, 'Unauthorized');
        }
        return Medecin::where('user_id', $user->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $doctor = $this->getDoctor();
        
        $query = Appointment::with('patient')
            ->where('doctor_id', $doctor->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('appointment_date', now()->toDateString());
                    break;
                case 'upcoming':
                    $query->whereDate('appointment_date', '>', now()->toDateString());
                    break;
            }
        }

        if ($request->filled('search')) {
            $query->whereHas('patient', function($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('prenom', 'like', '%' . $request->search . '%');
            });
        }

        $appointments = $query->latest()->paginate(10);

        // Stats
        $stats = [
            'total' => Appointment::where('doctor_id', $doctor->id)->count(),
            'today' => Appointment::where('doctor_id', $doctor->id)->whereDate('appointment_date', now()->toDateString())->count(),
            'confirmed' => Appointment::where('doctor_id', $doctor->id)->where('status', 'confirmed')->count(),
            'pending' => Appointment::where('doctor_id', $doctor->id)->where('status', 'pending')->count(),
        ];

        return response()->json([
            'appointments' => $appointments,
            'stats' => $stats
        ]);
    }

    public function confirm(Appointment $appointment)
    {
        $doctor = $this->getDoctor();
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json(['message' => 'Appointment confirmed successfully', 'appointment' => $appointment]);
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $doctor = $this->getDoctor();
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return response()->json(['message' => 'Appointment cancelled successfully', 'appointment' => $appointment]);
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $doctor = $this->getDoctor();
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $request->notes ?? $appointment->notes,
        ]);

        return response()->json(['message' => 'Appointment marked as completed', 'appointment' => $appointment]);
    }
}
