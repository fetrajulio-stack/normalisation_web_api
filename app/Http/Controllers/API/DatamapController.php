<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Datamap;
use App\Services\FixedLengthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatamapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index(Request $request)
{
    $query = Datamap::with(['champ']);

    if ($request->filled('codification_id')) {
        $query->where('codification_id', $request->codification_id);
    }

    $datamaps = $query->get()->map(function ($item) {
        return [
            'idq' => $item->champ->nom_champ, // 🔥 IMPORTANT
            'position' => $item->position,
            'longueur' => $item->longueur,
        ];
    });

    return response()->json([
        'datamap' => $datamaps
    ]);
}

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $request->validate([
        'codification_id' => 'required|exists:codifications,id',
        'datamap' => 'required|array',
        'datamap.*.position' => 'required|integer|min:0',
        'datamap.*.longueur' => 'required|integer|min:1',
        'datamap.*.idq' => 'required|string'
    ]);

    DB::beginTransaction();

    try {

        // 🔥 1. Supprimer les anciens datamaps
        Datamap::where('codification_id', $request->codification_id)->delete();

        $bulkData = [];

        foreach ($request->datamap as $item) {

            $champ = \App\Models\Champ::where('nom_champ', $item['idq'])->first();

            if (!$champ) continue;

            $bulkData[] = [
                'position' => $item['position'],
                'longueur' => $item['longueur'],
                'codification_id' => $request->codification_id,
                'champ_id' => $champ->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // ⚡ Insert en masse (rapide)
        Datamap::insert($bulkData);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap remplacé avec succès'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(Datamap $datamap)
    {
        $datamap->load(['codification', 'champ']);

        return response()->json([
            'status' => 'success',
            'data' => $datamap
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Datamap $datamap)
    {
        $validated = $request->validate([
            'position' => 'required|integer|min:1',
            'longueur' => 'required|integer|min:1',
            'codification_id' => 'required|exists:codifications,id',
            'champ_id' => 'required|exists:champs,id'
        ]);

        $datamap->update($validated);

        $datamap->load(['codification', 'champ']);

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap updated successfully',
            'data' => $datamap
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Datamap $datamap)
    {
        $datamap->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Datamap deleted successfully'
        ]);
    }


    /**
     * Exporte un fichier CSV vers un format texte à longueur fixe
     */
    public function exportToTxt(Request $request, FixedLengthService $service)
    {
        try {
            // 1. Validation
            $request->validate([
                'codification_id' => 'required',
                'nom_code_dossier' => 'required|string'
            ]);

            $fileNameExcel = $request->nom_code_dossier . '.xlsx';
            $filePath = storage_path('app/public/Exports/' . $fileNameExcel);

            if (!file_exists($filePath)) {
                return response()->json(['error' => "Fichier Excel introuvable sur le serveur : $fileNameExcel"], 404);
            }

            // 2. Config Datamap
            $config = Datamap::with('champ')
                ->where('codification_id', $request->codification_id)
                ->orderBy('position', 'asc')
                ->get();

            if ($config->isEmpty()) {
                return response()->json(['error' => "Aucun paramétrage (Datamap) trouvé pour cet ID"], 404);
            }

            // 3. Lecture Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $headers = array_shift($rows);
            $headers = array_map(fn($h) => strtoupper(trim(strval($h))), $headers);

            $records = [];
            foreach ($rows as $row) {
                if (count(array_filter($row)) > 0) {
                    $fullRow = [];
                    foreach ($headers as $index => $headerName) {
                        $fullRow[$headerName] = isset($row[$index]) ? trim(strval($row[$index])) : '';
                    }

                    $filteredData = [];
                    foreach ($config as $mapItem) {
                        $nomCible = strtoupper(trim($mapItem->champ->nom_champ));
                        $valeur = '';

                        // On cherche la colonne qui correspond (ex: Q1 trouve Q1_SEXE)
                        foreach ($fullRow as $keyExcel => $valExcel) {
                            if ($keyExcel === $nomCible || str_starts_with($keyExcel, $nomCible . '_')) {
                                $valeur = $valExcel;
                                break;
                            }
                        }
                        $filteredData[$mapItem->champ->nom_champ] = $valeur;
                    }
                    $records[] = (object) $filteredData;
                }
            }

            // 4. Génération
            $content = $service->generate($config, collect($records));

            // 5. Enregistrement sécurisé
            $fileNameTxt = $request->nom_code_dossier . '.txt';

            // Vérifier si le dossier existe, sinon le créer
            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists('public/Exports')) {
                \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory('public/Exports');
            }

            \Illuminate\Support\Facades\Storage::disk('local')->put('public/Exports/' . $fileNameTxt, $content);

            return response()->json([
                'status' => 'success',
                'filename' => $fileNameTxt
            ]);





        } catch (\Exception $e) {
            // C'est ça qui va empêcher l'erreur 500 et te dire ce qui ne va pas
            return response()->json(['error' => "Erreur PHP : " . $e->getMessage()], 500);
        }
    }


    public function downloadTxt($filename) {
        // Utilise storage_path pour être sûr de l'endroit
        $path = storage_path('app/public/Exports/' . $filename);

        if (!file_exists($path)) {
            // Debug : si ça 404, on veut savoir où il a cherché
            return response()->json(['error' => "Fichier introuvable à : " . $path], 404);
        }

        // Le headers 'Content-Disposition' force le téléchargement sans changer de page
        return response()->download($path, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }



//    public function exportToTxt(Request $request, FixedLengthService $service)
//    {
//        // On récupère tout ce qui arrive dans la requête pour le debug
//        $allInput = $request->all();
//
//        $request->validate([
//            'codification_id' => 'required|exists:datamaps,codification_id',
//        ]);
//
//        // 1. On récupère la configuration (le plan)
//        $config = \App\Models\Datamap::with('champ')
//            ->where('codification_id', $request->codification_id)
//            ->orderBy('position', 'asc')
//            ->orderBy('id', 'asc')
//            ->get();
//
//        $recordsData = $request->input('records');
//        $records = collect($recordsData)->map(function($item) {
//            return (object) $item;
//        });
//
//        // LE DEBUG (On affiche enfin tout proprement)
////        dd([
////            'Vérification Request ALL' => $allInput,
////            'Contenu de RECORDS extrait' => $records,
////            'Type de RECORDS' => gettype($records),
////            'Première clé de la CONFIG' => $config->first()->champ->nom_champ ?? 'Pas de config',
////        ]);
//
//        // Le reste du code ne s'exécutera pas tant que le dd() est là
//        $content = $service->generate($config, $records);
//
//        // --- ICI ON ENREGISTRE LE FICHIER ---
//
//        // On définit le nom (ex: export_codif_5_20260323_1720.txt)
//        $fileName = 'export_codif_' . $request->codification_id . '_' . now()->format('Ymd_His') . '.txt';
//
//        // Chemin relatif pour le disque 'local' (va dans storage/app/public/Exports/)
//        $path = 'public/Exports/' . $fileName;
//
//        // Enregistrement physique
//        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $content);
//
//        // --- ENFIN, ON RÉPOND ---
//
//        // Option A : Si tu veux voir le JSON dans Insomnia avec le chemin
//        return response()->json([
//            'status' => 'success',
//            'message' => 'Fichier généré avec succès',
//            'file_path' => storage_path('app/' . $path),
//            'filename' => $fileName,
//            'preview' => mb_substr($content, 0, 100) . '...' // Petit aperçu
//        ]);
//
//        return response($content)->header('Content-Type', 'text/plain');
//
//    }




}

