<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretaireRequest;
use App\Http\Requests\UpdateSecretaireRequest;
use App\Models\Secretaire;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SecretaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $secretaire = Secretaire::all();
        return response()->json($secretaire, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSecretaireRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create user account
            $user = User::create([
                'name' => $request->nom,
                'email' => $request->email,
                'password' => $request->motDePasse,
                'role' => 'staff',
            ]);

            // Create secretaire record
            $data = $request->validated();
            $data['user_id'] = $user->id;
            $secretaire = Secretaire::create($data);

            return response()->json($secretaire, 200);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $secretaire = Secretaire::findOrFail($id);
        return response()->json($secretaire, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSecretaireRequest $request, string $id)
    {
        $secretaire = Secretaire::findOrFail($id);
        $secretaire->update($request->validated());
        return response()->json($secretaire, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $secretaire = Secretaire::findOrFail($id);
        if ($secretaire->user_id) {
            User::destroy($secretaire->user_id);
        } else {
            $secretaire->delete();
        }

        return response()->json(null, 204);
    }
}
