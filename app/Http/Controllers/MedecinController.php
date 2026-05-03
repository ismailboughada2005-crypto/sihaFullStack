<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedecinRequest;
use App\Http\Requests\UpdateMedecinRequest;
use App\Models\Medecin;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MedecinController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medecin = Medecin::all();
        return response()->json($medecin, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedecinRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create user account
            $user = User::create([
                'name' => $request->nom,
                'email' => $request->email,
                'password' => $request->motDePasse, // Will be hashed by model cast
                'role' => 'doctor',
            ]);

            // Create medecin record
            $data = $request->validated();
            $data['user_id'] = $user->id;
            $medecin = Medecin::create($data);

            return response()->json($medecin, 200);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $medecin = Medecin::findOrFail($id);
        return response()->json($medecin, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedecinRequest $request, string $id)
    {
        $medecin = Medecin::findOrFail($id);
        $medecin->update($request->validated());

        return response()->json($medecin, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medecin = Medecin::findOrFail($id);
        if ($medecin->user_id) {
            User::destroy($medecin->user_id);
        } else {
            $medecin->delete();
        }

        return response()->json(null, 204);
    }
}
