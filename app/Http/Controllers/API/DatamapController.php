<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Datamap;
use App\Models\Champ;
use App\Services\FixedLengthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatamapController extends Controller
{
    /**
     * Récupère la liste des datamaps pour une codification donnée
     */
    public function index(Request $request)
    {
        $query = Datamap::with('champ');

        if ($request->filled('codification_id')) {
            $query->where('codification_id', $request->codification_id);
        }

        $datamaps = $query->get()->map(function ($item) {
            return [
                'idq' => $item->champ->nom_champ,
                'position' => $item->position,
                'longueur' => $item->longueur,
            ];
        });

        return response()->json([
            'datamap' => $datamaps
        ]);
    }

    /**
     * Crée ou met à jour des datamaps
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
            foreach ($request->datamap as $item) {
                $champ = Champ::where('nom_champ', $item['idq'])->first();

                if (!$champ) {
                    // Champ inexistant → ignore
                    continue;
                }

                // Update or create selon codification_id + champ_id
                Datamap::updateOrCreate(
                    [
                        'codification_id' => $request->codification_id,
                        'champ_id' => $champ->id
                    ],
                    [
                        'position' => $item['position'],
                        'longueur' => $item['longueur']
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Datamap enregistré avec succès'
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
     * Normaliser les noms de colonnes pour correspondre à ceux de la base de données
     */
    private function normalizeColumnName(string $name): string
    {
        $name = mb_strtolower($name);                  // tout en minuscules
        $name = str_replace([' ', '-', '/', '='], '_', $name); // remplacer espaces, - / = par _
        $name = preg_replace('/_+/', '_', $name);     // supprimer doublons de _
        $name = trim($name, '_');                     // enlever les _ au début et fin
        return $name;
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
     * Export TXT à longueur fixe via FixedLengthService
     */
    public function exportToTxt(Request $request, FixedLengthService $service)
    {
        try {
            $request->validate([
                'codification_id' => 'required',
                'nom_code_dossier' => 'required|string'
            ]);

            $datamapConfig = Datamap::with('champ')
                ->where('codification_id', $request->codification_id)
                ->orderBy('position')
                ->get();

            if ($datamapConfig->isEmpty()) {
                return response()->json(['error' => 'Aucun paramétrage (Datamap) trouvé pour cet ID'], 404);
            }

            $fileNameExcel = $request->nom_code_dossier . '.xlsx';
            $filePath = storage_path('app/public/Exports/' . $fileNameExcel);

            if (!file_exists($filePath)) {
                return response()->json(['error' => "Fichier Excel introuvable : $fileNameExcel"], 404);
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $headers = array_map(fn($h) => strtoupper(trim(strval($h))), array_shift($rows));

            $records = [];
            foreach ($rows as $row) {
                if (count(array_filter($row)) > 0) {
                    $fullRow = [];
                    foreach ($headers as $i => $headerName) {
                        $fullRow[$headerName] = isset($row[$i]) ? trim(strval($row[$i])) : '';
                    }

                    $filteredData = [];
                    foreach ($datamapConfig as $mapItem) {
                        // Normalisation du nom de la colonne
                        $nomCible = $this->normalizeColumnName(strtoupper(trim($mapItem->champ->nom_champ)));
                        $valeur = '';

                        foreach ($fullRow as $keyExcel => $valExcel) {
                            $keyExcelNorm = $this->normalizeColumnName($keyExcel);
                            if ($keyExcelNorm === $nomCible) {
                                $valeur = $valExcel;
                                break;
                            }
                        }

                        $filteredData[$mapItem->champ->nom_champ] = $valeur;
                    }

                    $records[] = (object) $filteredData;
                }
            }

            $content = $service->generate($datamapConfig, collect($records));

            $fileNameTxt = $request->nom_code_dossier . '.txt';

            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists('public/Exports')) {
                \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory('public/Exports');
            }

            \Illuminate\Support\Facades\Storage::disk('local')->put('public/Exports/' . $fileNameTxt, $content);

            return response()->json([
                'status' => 'success',
                'filename' => $fileNameTxt
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => "Erreur PHP : " . $e->getMessage()], 500);
        }
    }

    /**
     * Téléchargement du fichier TXT généré
     */
    public function downloadTxt($filename)
    {
        $path = storage_path('app/public/Exports/' . $filename);

        if (!file_exists($path)) {
            return response()->json(['error' => "Fichier introuvable à : $path"], 404);
        }

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

