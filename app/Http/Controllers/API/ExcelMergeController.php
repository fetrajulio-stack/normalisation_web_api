<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\Log;

class ExcelMergeController extends Controller
{
    public function merge(Request $request)
    {
        // 1. CONFIGURATION DES LIMITES
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        // Validation des nouvelles données : dossier et codeDossier
        $request->validate([
            'files' => 'required|array|min:2',
            'joinKey' => 'required|string',
            'dossier' => 'nullable|string',
            'codeDossier' => 'nullable|string'
        ]);

        try {
            $joinKey = $request->input('joinKey');
            $files = $request->file('files');

            // Récupération des infos pour le nom du fichier
            // On nettoie les noms pour éviter les caractères interdits dans les noms de fichiers
            $dossier = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $request->input('dossier', 'Export'));
            $codeDossier = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $request->input('codeDossier', 'SansCode'));

            // 2. LECTURE DES FICHIERS
            $data1 = Excel::toArray([], $files[0])[0];
            $data2 = Excel::toArray([], $files[1])[0];

            // 3. EXTRACTION ET NETTOYAGE DES ENTÊTES
            $h1 = array_map('trim', array_shift($data1));
            $h2 = array_map('trim', array_shift($data2));

            $idx1 = array_search($joinKey, $h1);
            $idx2 = array_search($joinKey, $h2);

            if ($idx1 === false || $idx2 === false) {
                return response()->json(['error' => "La colonne '$joinKey' est introuvable."], 422);
            }

            // 4. CRÉATION DU DICTIONNAIRE (Lookup Table)
            $lookup = [];
            foreach ($data2 as $row) {
                $val = $row[$idx2] ?? null;
                if ($val !== null) {
                    $rowWithoutKey = $row;
                    unset($rowWithoutKey[$idx2]);
                    $lookup[$val] = array_values($rowWithoutKey);
                }
            }

            // 5. ASSEMBLAGE
            $finalOutput = [];
            $h2WithoutKey = $h2;
            unset($h2WithoutKey[$idx2]);
            $finalOutput[] = array_merge($h1, array_values($h2WithoutKey));

            foreach ($data1 as $r1) {
                $currentLot = $r1[$idx1] ?? null;
                if ($currentLot !== null && isset($lookup[$currentLot])) {
                    $finalOutput[] = array_merge($r1, $lookup[$currentLot]);
                } else {
                    $emptyPadding = array_fill(0, count($h2) - 1, null);
                    $finalOutput[] = array_merge($r1, $emptyPadding);
                }
            }

            // 6. GÉNÉRATION DU NOM DE FICHIER DYNAMIQUE
            // Résultat attendu : Fusion_ALZHEIMER_TYPE1.xlsx
            $fileName = "Fusion_" . $dossier . "_" . $codeDossier . ".xlsx";

            return Excel::download(new class(collect($finalOutput)) implements FromCollection {
                protected $data;
                public function __construct($data) { $this->data = $data; }
                public function collection() { return $this->data; }
            }, $fileName);

        } catch (\Exception $e) {
            Log::error("Erreur Fusion Excel: " . $e->getMessage());
            return response()->json(['error' => "Erreur serveur : " . $e->getMessage()], 500);
        }
    }
}
