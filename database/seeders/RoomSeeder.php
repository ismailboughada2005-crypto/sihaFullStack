<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Consultation', 'Diagnostics', 'Operating Room', 'ICU', 'Emergency', 'Waiting'];
        $rooms = [];

        // Generate 20 rooms
        for ($i = 1; $i <= 10; $i++) {
            $rooms[] = [
                'room_number' => '10' . $i,
                'type' => $types[($i - 1) % count($types)],
                'status' => 'Available',
                'capacity' => ($i % 3 === 0) ? 2 : 1
            ];
        }

        for ($i = 1; $i <= 10; $i++) {
            $rooms[] = [
                'room_number' => '20' . $i,
                'type' => $types[($i + 1) % count($types)],
                'status' => 'Available',
                'capacity' => ($i % 4 === 0) ? 4 : 1
            ];
        }

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['room_number' => $room['room_number']],
                $room
            );
        }
    }
}
