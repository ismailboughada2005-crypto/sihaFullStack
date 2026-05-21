<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user and authenticate
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@siha.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $this->actingAs($admin);
    }

    public function test_patient_cannot_book_two_conflicting_appointments(): void
    {
        // 1. Create a patient
        $patient = Patient::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'dept' => 'Cardiology',
            'status' => 'Active',
            'phone' => '123456789',
            'email' => 'john.doe@example.com',
        ]);

        // 2. Create two doctors
        $doctorUser1 = User::create([
            'name' => 'Doctor One',
            'email' => 'doc1@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctor1 = Medecin::create([
            'user_id' => $doctorUser1->id,
            'nom' => 'Dr. One',
            'specialite' => 'Cardiology',
            'email' => 'doc1@example.com',
            'motDePasse' => bcrypt('password'),
            'status' => 'Active',
        ]);

        $doctorUser2 = User::create([
            'name' => 'Doctor Two',
            'email' => 'doc2@example.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
        ]);
        $doctor2 = Medecin::create([
            'user_id' => $doctorUser2->id,
            'nom' => 'Dr. Two',
            'specialite' => 'Dermatology',
            'email' => 'doc2@example.com',
            'motDePasse' => bcrypt('password'),
            'status' => 'Active',
        ]);

        // 3. Book first appointment: should succeed
        $response1 = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '10:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'First session',
        ]);

        $response1->assertStatus(201);
        $appointment1Id = $response1->json('id');

        // 4. Try to book second appointment at the same date & time with Dr. Two: should fail
        $response2 = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '10:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Second session (conflict)',
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('appointment_time');
        $this->assertEquals('You already have an appointment at this time.', $response2->json('message'));

        // 5. Try to book at a different time: should succeed
        $response3 = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '11:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Non-conflicting session',
        ]);

        $response3->assertStatus(201);

        // 6. Cancel the first appointment and retry booking the conflict: should succeed
        $appointment1 = Appointment::find($appointment1Id);
        $appointment1->update(['status' => 'cancelled']);

        $response4 = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor2->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '10:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Retry now that first is cancelled',
        ]);

        $response4->assertStatus(201);
    }

    public function test_room_cannot_be_double_booked_at_same_time(): void
    {
        // 1. Create two patients
        $patient1 = Patient::create([
            'nom' => 'Doe', 'prenom' => 'John', 'dob' => '1990-01-01', 'gender' => 'M', 'dept' => 'Cardiology', 'status' => 'Active', 'phone' => '123456789', 'email' => 'john.doe@example.com'
        ]);
        $patient2 = Patient::create([
            'nom' => 'Smith', 'prenom' => 'Jane', 'dob' => '1992-02-02', 'gender' => 'F', 'dept' => 'Dermatology', 'status' => 'Active', 'phone' => '987654321', 'email' => 'jane.smith@example.com'
        ]);

        // 2. Create two doctors
        $doctorUser1 = User::create([
            'name' => 'Doctor One', 'email' => 'doc1@example.com', 'password' => bcrypt('password'), 'role' => 'doctor'
        ]);
        $doctor1 = Medecin::create([
            'user_id' => $doctorUser1->id, 'nom' => 'Dr. One', 'specialite' => 'Cardiology', 'email' => 'doc1@example.com', 'motDePasse' => bcrypt('password'), 'status' => 'Active'
        ]);

        $doctorUser2 = User::create([
            'name' => 'Doctor Two', 'email' => 'doc2@example.com', 'password' => bcrypt('password'), 'role' => 'doctor'
        ]);
        $doctor2 = Medecin::create([
            'user_id' => $doctorUser2->id, 'nom' => 'Dr. Two', 'specialite' => 'Dermatology', 'email' => 'doc2@example.com', 'motDePasse' => bcrypt('password'), 'status' => 'Active'
        ]);

        // 3. Create a Room
        $room = \App\Models\Room::create([
            'room_number' => '305', 'type' => 'Consultation', 'status' => 'Available', 'capacity' => 1
        ]);

        // 4. Book first session in room 305 at 10:00: should succeed
        $response1 = $this->postJson('/api/appointments', [
            'patient_id' => $patient1->id,
            'doctor_id' => $doctor1->id,
            'room_id' => $room->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '10:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Patient 1 in room 305',
        ]);
        $response1->assertStatus(201);

        // 5. Book second session for Patient 2 in room 305 at 10:00: should fail
        $response2 = $this->postJson('/api/appointments', [
            'patient_id' => $patient2->id,
            'doctor_id' => $doctor2->id,
            'room_id' => $room->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '10:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Patient 2 in room 305 (conflict)',
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('room_id');
        $this->assertEquals('Room is already occupied at this time.', $response2->json('message'));

        // 6. Book second session for Patient 2 in room 305 at 11:00: should succeed
        $response3 = $this->postJson('/api/appointments', [
            'patient_id' => $patient2->id,
            'doctor_id' => $doctor2->id,
            'room_id' => $room->id,
            'appointment_date' => '2026-06-01',
            'appointment_time' => '11:00',
            'type' => 'Consultation',
            'status' => 'pending',
            'notes' => 'Patient 2 in room 305 at different time',
        ]);
        $response3->assertStatus(201);
    }
}
