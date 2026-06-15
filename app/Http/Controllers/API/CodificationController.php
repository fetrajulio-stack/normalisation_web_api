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
           $rawRows = AccessService::query($zCheminParametreMdb, $sql);
           //dd($rawRows,$sql);
           foreach ($rawRows as $row) {
                // Comme on a sélectionné uniquement 'idq', chaque ligne est la valeur de idq
                if($row === null || $row === "" || str_contains(trim($row), '_rows_retrieved')) continue; // Ignorer les valeurs nulles
                $sourceRows[] = ['idq' => trim($row)];
            }
        }
       
        
            
        /**FIN: Quelques dossiers dans n'utilise pas "ordreq" mais "ordref" dans la table livraison */

        $sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);

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
