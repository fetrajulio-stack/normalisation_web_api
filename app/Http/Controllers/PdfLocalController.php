<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfLocalController extends Controller
{
    public function upload(Request $request)
    {
        // 1. Validation
        $request->validate([
            'pdfs' => 'required',
            'pdfs.*' => 'file|mimes:pdf|max:20480',
            'nom_dossier' => 'required|string',
            'nom_code_dossier' => 'required|string',
        ]);

        $nomDossier = $request->nom_dossier;
        $nomCodeDossier = $request->nom_code_dossier;

        // 2. Chemin local sécurisé
        $basePath = "fichier_indexe/{$nomDossier}/{$nomCodeDossier}";

        // 3. Création dossier si inexistant
        if (!Storage::disk('local')->exists($basePath)) {
            Storage::disk('local')->makeDirectory($basePath);
        }

        $uploadedFiles = [];

        // 4. Upload des fichiers
        foreach ($request->file('pdfs') as $file) {

           /* $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $storedName  = $nomCodeDossier . '_' . time() . '_' . rand(1000,9999) . '.pdf';
            $storedName = $file->getClientOriginalName();

            $path = $file->storeAs($basePath, $storedName, 'local');

            $uploadedFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'stored_path' => $path,
            ];*/

             $storedName = $file->getClientOriginalName();
            
            $path = $file->storeAs($basePath, $storedName, 'local');

            $uploadedFiles[] = [
                'original_name' => $storedName,
                'stored_name' => $storedName,
                'stored_path' => $path,
            ];


        }

        return response()->json([
            'success' => true,
            'message' => 'PDF uploadé avec succès',
            'files' => $uploadedFiles,
            'storage_path' => storage_path("app/{$basePath}")
        ]);
    }
}