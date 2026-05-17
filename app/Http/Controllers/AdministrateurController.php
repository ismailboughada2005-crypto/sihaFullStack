<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdministrateurRequest;
use App\Http\Requests\UpdateAdministrateurRequest;
use App\Models\Administrateur;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdministrateurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $administrateur = Administrateur::all();
        return response()->json($administrateur, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdministrateurRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create user account
            $user = User::create([
                'name' => $request->nom,
                'email' => $request->email,
                'password' => $request->motDePasse,
                'role' => 'admin',
            ]);

            // Create admin record
            $data = $request->validated();
            $data['user_id'] = $user->id;
            $administrateur = Administrateur::create($data);

            return response()->json($administrateur, 200);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $administrateur = Administrateur::findOrFail($id);
        return response()->json($administrateur,200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdministrateurRequest $request, string $id)
    {
        $administrateur = Administrateur::findOrFail($id);
        $administrateur->update($request->validated());
        return response()->json($administrateur,200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $currentUser = auth()->user();
        $seniorAdminEmail = 'admin@siha.com';

        // 1. Check if the authenticated user is the senior admin
        if (!$currentUser || $currentUser->email !== $seniorAdminEmail) {
            return response()->json([
                'message' => 'Unauthorized: Only the senior administrator (admin@siha.com) can revoke administrative access.'
            ], 403);
        }

        $administrateur = Administrateur::findOrFail($id);

        // 2. Prevent deleting the senior admin record itself
        if ($administrateur->user_id) {
            $userToDelete = User::find($administrateur->user_id);
            if ($userToDelete && $userToDelete->email === $seniorAdminEmail) {
                return response()->json([
                    'message' => 'Critical: The root administrator account cannot be deleted.'
                ], 403);
            }
            User::destroy($administrateur->user_id);
        } else {
            $administrateur->delete();
        }

        return response()->json(null, 204);
    }
}
