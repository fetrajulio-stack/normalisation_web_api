<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
    {
       
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'matricule' => 'required|unique:users,matricule',
            'profil_id' => 'required|exists:profils,id'
        ]);
        

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'matricule' => $request->matricule,
            'actif' => true,
            'profil_id' => $request->profil_id,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user->load('profil')
        ], 201);
    }
}
