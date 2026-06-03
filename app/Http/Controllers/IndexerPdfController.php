<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexerPdfController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'pdfs.*' => 'required|file|mimes:pdf|max:20480',
            'nom_dossier' => 'required|string',
            'nom_code_dossier' => 'required|string',
        ]);

        $basePath = "\\\\172.17.1.5\\preparation";

        $targetPath =
            $basePath . "\\" .
            $request->nom_dossier . "\\" .
            $request->nom_code_dossier . "\\Fichier_indexe\\";

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        foreach ($request->file('pdfs') as $file) {
            $name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $file->move($targetPath, $name);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'PDF uploadé avec succès'
        ]);
    }
}
