<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Champ;
use App\Models\Codification;
use App\Services\AccessService;
use App\Services\TabFilterService;
use App\Services\TextNormalizerService;
use Illuminate\Http\Request;
use App\Models\Dossier;
use App\Services\EncodingService;
use PDO;

class CodificationController extends Controller
{
    protected TextNormalizerService  $normalizer;
    protected $encodingService;

    public function __construct(TextNormalizerService $normalizer, EncodingService $encodingService)
    {
        $this->normalizer = $normalizer;
        $this->encodingService = $encodingService;
    }

    public function listCodification()
    {
        $dossiers = Dossier::with(['cathegories' => function($query) {
            $query->orderBy('id_code_dossier', 'asc');
        }])
            ->orderBy('nom_dossier', 'asc')
            ->get();
        // Transformation propre → tableau
        $array = $dossiers->toArray();

        foreach ($array as &$dossier) {
            if (!empty($dossier['cathegories'])) {

                usort($dossier['cathegories'], function ($a, $b) {
                    return strcmp($a['code_dossier'], $b['code_dossier']);
                });

            }
        }

        //dd($array);
        // Conversion UTF-8 récursive
        $array = $this->encodingService->utf8EncodeRecursive($array);

        return response()->json($array);
    }

    public function test()
    {
        $dossier = Dossier::with(['cathegories' => function($q) {
            $q->limit(10);
        }])->find(33);

        return response()->json($dossier->toArray());
    }


    public function getChampsByCodeDossier( Request $request )
    {
        $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');

        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        // dd($basePath, $zCheminParametreMdb);

        $systemExploitation = env('SYSTEM_EXPLOITATION');

        if ($systemExploitation === 'Windows') {
            $pdo = AccessService::connect($zCheminParametreMdb,null,null);

           /**DEBUT: Quelques dossiers dans n'utilise pas "ordreq" mais "ordref" dans la table livraison */
            
            $stmt = $pdo->query("SELECT * FROM [LIVRAISON]");
            $columns = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = strtolower($meta['name']);
            }
        }
        else {
            $columns = AccessService::getColumns($zCheminParametreMdb, 'LIVRAISON');
        }
        
        // Détection dynamique
        $orderBy = null;

        if (in_array('ordreq', $columns)) {
            $orderBy = 'ordreq';
        } elseif (in_array('ordref', $columns)) {
            $orderBy = 'ordref';
        }
        $sourceRows = [];
        // Construction SQL
        $sql = "SELECT [idq] FROM [LIVRAISON]";

        if ($orderBy) {
            $sql .= " ORDER BY [$orderBy] ASC";
        }
        // Exécution
        if ($systemExploitation === 'Windows') {
           $sourceRows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
           // dd($sourceRows);
        }else{
            $sql = "SELECT idq FROM LIVRAISON";
            
           /*$rawRows = AccessService::query($zCheminParametreMdb, $sql);
           //dd($rawRows,$sql);
           foreach ($rawRows as $row) {
                // Comme on a sélectionné uniquement 'idq', chaque ligne est la valeur de idq
                if($row === null || $row === "" || stripos(trim($row), 'Rows retrieved') !== false) continue; // Ignorer les valeurs nulles
                $sourceRows[] = ['idq' => trim($row)];
            }*/

           $sql = "SELECT idq, $orderBy FROM LIVRAISON";
            $rawRows = AccessService::query($zCheminParametreMdb, $sql);

            dd($rawRows);
            // 1. Nettoyer et récupérer uniquement les vraies lignes de données
            $validRows = [];
            foreach ($rawRows as $row) {
                if ($row['idq'] === null || $row['idq'] === "") continue; // Ignorer les null/vides
                
                // Ignorer les messages de log (ex: retour de mdb-tools)
                if (stripos(trim($row['idq']), 'Rows retrieved') !== false) {
                    continue;
                }
                $validRows[] = $row['idq'];
            }

            // 2. Trier le tableau en PHP par la colonne $orderBy (Ordre Ascendant)
            usort($validRows, function($a, $b) use ($orderBy) {
                // S'adapte selon si ton service retourne des tableaux associatifs ou des objets
                $valA = is_array($a) ? ($a[$orderBy] ?? '') : ($a->$orderBy ?? '');
                $valB = is_array($b) ? ($b[$orderBy] ?? '') : ($b->$orderBy ?? '');
                
                return $valA <=> $valB; // L'opérateur <=> gère le tri ascendant
            });

            // 3. Construire le tableau final avec uniquement l'idq
            $sourceRows = [];
            foreach ($validRows as $row) {
                // On extrait uniquement l'idq de la ligne (qu'elle soit un tableau ou un objet)
                $idq = is_array($row) ? $row['idq'] : (is_object($row) ? $row->idq : trim($row));
                
                $sourceRows[] = ['idq' => $idq];
            }
        }
        

        $sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);
        //dd($sourceRows);

        foreach ($sourceRows as &$item) {
            $item['idq'] = $this->normalizer->normalizeFieldName($item['idq']);
        }

        return response()->json($sourceRows);
    }


    public function getId(Request $request)
    {
        $request->validate([
            'nom_dossier'  => 'required|string',
            'code_dossier' => 'required|string',
        ]);

        $codification = Codification::where('dossier', $request->nom_dossier)
            ->where('code_dossier', $request->code_dossier)
            ->first();

        if (!$codification) {
            return response()->json([
                'message' => 'Codification non trouvée'
            ], 404);
        }

        return response()->json([
            'id' => $codification->id
        ]);
    }

    public function getNomLot(Request $request)
    {
        $zDossier = $request->query('nom_dossier');
        $zCode_dossier = $request->query('nom_code_dossier');

        $basePath = config('normalisation.mdb_base_path');
        $targetPath = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier;


        if (empty($zDossier) || empty($zCode_dossier)) {
            return response()->json([
                'message' => 'Paramètres manquants : nom_dossier et nom_code_dossier requis'
            ], 422);
        }


        try {
            if (!is_dir($targetPath)) {
                return response()->json([
                    'message' => 'Chemin introuvable',
                    'path' => $targetPath
                ], 404);
            }

            $items = array_filter(scandir($targetPath), function ($name) use ($targetPath) {
                if ($name === '.' || $name === '..') {
                    return false;
                }
                $sub = $targetPath . '\\' . $name;
                return is_dir($sub);
            });

            $items = array_values($items);
            $firstLot = $items[0] ?? null;

            return response()->json([
                'lot_name' => $firstLot,
                'path' => $targetPath,
            ]);
        } catch (\Exception $e) {
            Log::error('getNomLot error', [
                'path' => $targetPath,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur serveur lors du listing du dossier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
