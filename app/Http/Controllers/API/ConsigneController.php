<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Champ;
use App\Models\Codification;
use App\Models\Consigne;
use App\Models\Consigne_groupe;
use App\Models\Consigne_groupe_champ;
use App\Models\Parametre_consigne;
use App\Services\AccessService;
use App\Services\TextNormalizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EncodingService;
use PDO;
use Exception;

class ConsigneController extends Controller
{
    protected TextNormalizerService $normalizer;
    protected $encodingService;

    public function __construct(TextNormalizerService $normalizer, EncodingService $encodingService)
    {
        $this->normalizer = $normalizer;
        $this->encodingService = $encodingService;
    }

    public function listAll()
    {
        $consignes = Consigne::orderBy('id')->get();
        return response()->json([
            'status' => 'success',
            'data' => $consignes
        ]);
    }

    /**
     * API PRINCIPALE D'ENREGISTREMENT
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom_dossier' => 'required|string',
            'nom_code_dossier' => 'required|string',
            'consignes' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Création ou récupération de la codification (le dossier)
            $codification = Codification::firstOrCreate([
                'dossier' => $request->nom_dossier,
                'code_dossier' => $request->nom_code_dossier
            ]);

            // 2️⃣ Import champs depuis MDB
            $this->importFromMdb($codification->id, $request->nom_dossier, $request->nom_code_dossier);

            // 3️⃣ Préchargement des champs pour ce dossier
            $champs = Champ::where('codification_id', $codification->id)
                ->get()
                ->keyBy('nom_champ');

            // 4️⃣ Nettoyage des anciens paramètres pour CE dossier uniquement avant ré-enregistrement
            // Cela évite les doublons si on modifie le paramétrage du même dossier
            Parametre_consigne::where('codification_id', $codification->id)->delete();

            // 5️⃣ Enregistrement des consignes avec le codification_id
            foreach ($request->consignes as $consigneData) {
                $this->storeSingleConsigne($consigneData, $champs, $codification->id);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Paramétrage enregistré avec succès'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistre UNE consigne liée à un dossier (codificationId)
     */
    private function storeSingleConsigne(array $consigneData, $champs, $codificationId)
    {
        // Enregistrement des groupes et champs
        if (isset($consigneData['groupes'])) {
            foreach ($consigneData['groupes'] as $groupeData) {
                $groupe = Consigne_groupe::create([
                    'consigne_id' => $consigneData['consigne_id'],
                    'ordre' => $groupeData['ordre']
                ]);

                foreach ($groupeData['champs'] as $ordre => $nomChamp) {
                    $nomChamp = strtolower($nomChamp);
                    if (isset($champs[$nomChamp])) {
                        Consigne_groupe_champ::create([
                            'consigne_groupe_id' => $groupe->id,
                            'champ_id' => $champs[$nomChamp]->id,
                            'ordre' => $ordre + 1
                        ]);
                    }
                }
            }
        }

        // 🎯 PARTIE CORRIGÉE : Enregistrement des paramètres (valeur_defaut)
        // On utilise updateOrCreate avec codification_id pour ne pas écraser les autres dossiers
        if (!empty($consigneData['parametres'])) {
            foreach ($consigneData['parametres'] as $cle => $valeur) {
                Parametre_consigne::updateOrCreate(
                    [
                        'codification_id' => $codificationId, // Filtre par dossier
                        'consigne_id' => $consigneData['consigne_id'],
                        'cle' => $cle
                    ],
                    [
                        'valeur' => $valeur ?? ''
                    ]
                );
            }
        }
    }

    public function edit($codificationId)
    {
        // On récupère tous les groupes liés à cette codification
        $groupes = Consigne_groupe::whereHas('champs.champ', function ($q) use ($codificationId) {
            $q->where('codification_id', $codificationId);
        })
            ->with(['consigne', 'champs.champ'])
            ->get()
            ->groupBy('consigne_id'); // On groupe par ID de consigne

        // On récupère TOUS les paramètres de ce dossier
        $parametresDossier = Parametre_consigne::where('codification_id', $codificationId)
            ->get()
            ->groupBy('consigne_id');

        $result = [];

        // On boucle sur chaque type de consigne trouvé
        foreach ($groupes as $consigneId => $groupesDeLaConsigne) {

            $item = [
                'consigne_id' => $consigneId,
                'groupes' => [],
                'parametres' => []
            ];

            // 1. Récupérer les paramètres (ex: valeur_defaut NR ou 5)
            if (isset($parametresDossier[$consigneId])) {
                foreach ($parametresDossier[$consigneId] as $p) {
                    $item['parametres'][$p->cle] = $p->valeur;
                }
            }

            // 2. Récupérer tous les groupes (sans écraser)
            foreach ($groupesDeLaConsigne as $groupe) {
                $item['groupes'][] = [
                    'ordre' => $groupe->ordre,
                    'champs' => $groupe->champs->sortBy('ordre')->pluck('champ.nom_champ')->toArray()
                ];
            }

            $result[] = $item;
        }

        return response()->json($result);
    }

    public function update(Request $request, $codificationId)
    {
        DB::beginTransaction();
        try {
            // Nettoyage complet du paramétrage existant pour ce dossier
            $groupesIds = Consigne_groupe::whereHas('champs.champ', function ($q) use ($codificationId) {
                $q->where('codification_id', $codificationId);
            })->pluck('id');

            Consigne_groupe_champ::whereIn('consigne_groupe_id', $groupesIds)->delete();
            Consigne_groupe::whereIn('id', $groupesIds)->delete();
            Parametre_consigne::where('codification_id', $codificationId)->delete();

            $champs = Champ::where('codification_id', $codificationId)->get()->keyBy('nom_champ');

            foreach ($request->input('consignes', []) as $consigneData) {
                $this->storeSingleConsigne($consigneData, $champs, $codificationId);
            }

            DB::commit();
            return response()->json(['status' => 'OK', 'message' => 'Mise à jour réussie']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * SIMULATION import MDB
     * A remplacer par votre logique existante
     */
    /*private function importFromMdb($codificationId, $zDossier, $zCode_dossier)
    {
        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        // ✅ Vérification existence fichier        
        if (!file_exists($zCheminParametreMdb)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Fichier Parametre.mdb introuvable',
                'chemin' => $zCheminParametreMdb
            ], 404);
        }

        $pdo = AccessService::connect($zCheminParametreMdb,null,null);

        //$sourceRows = $pdo->query(" SELECT idq FROM LIVRAISON ORDER BY ordreq ASC")->fetchAll(PDO::FETCH_ASSOC);

        /**DEBUT: Quelques dossiers dans n'utilise pas "ordreq" mais "ordref" dans la table livraison */
        /*    $stmt = $pdo->query("SELECT * FROM [LIVRAISON]");
            $columns = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = strtolower($meta['name']);
            }

            // Détection dynamique
            $orderBy = null;

            if (in_array('ordreq', $columns)) {
                $orderBy = 'ordreq';
            } elseif (in_array('ordref', $columns)) {
                $orderBy = 'ordref';
            }

            // Construction SQL
            $sql = "SELECT [idq] FROM [LIVRAISON]";

            if ($orderBy) {
                $sql .= " ORDER BY [$orderBy] ASC";
            }
            // Exécution
            $sourceRows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
           /**FIN: Quelques dossiers dans n'utilise pas "ordreq" mais "ordref" dans la table livraison */
        
        /*$sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);

        // Ajouter les champs supplémentaires n_lot, n_ima, n_enr
        $extraChamps = ['n_lot', 'n_ima', 'n_enr','ville', 'seance'];


        foreach ($extraChamps as $champ) {
            Champ::updateOrCreate(
                [
                    'nom_champ' => $champ,
                    'codification_id' => $codificationId
                ],
                [
                    'valeur_defaut' => null
                ]
            );
        }

        foreach ($sourceRows as $row) {


            Champ::updateOrCreate(
                [
                    //'nom_champ' => strtolower($row['idq']),
                    'nom_champ' => $this->normalizer->normalizeFieldName($row['idq']),
                    'codification_id' => $codificationId
                ],
                [
                    'valeur_defaut' => $row['defaut'] ?? null
                ]
            );
        }


        return response()->json([
            'status' => 'OK',
            'message' => 'Champs importés avec succès'
        ]);


    }*/

        private function importFromMdb($codificationId, $zDossier, $zCode_dossier)
    {
        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        // ✅ Vérification existence fichier        
        if (!file_exists($zCheminParametreMdb)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Fichier Parametre.mdb introuvable',
                'chemin' => $zCheminParametreMdb
            ], 404);
        }

        $systemExploitation = env('SYSTEM_EXPLOITATION');
        $columns = [];

        // 1. Récupération des colonnes selon l'OS pour détecter le tri
        if ($systemExploitation === 'Windows') {
            $pdo = AccessService::connect($zCheminParametreMdb, null, null);
            $stmt = $pdo->query("SELECT * FROM [LIVRAISON]");
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = strtolower($meta['name']);
            }
        } else {
            $columns = AccessService::getColumns($zCheminParametreMdb, 'LIVRAISON');
        }

        // Détection dynamique du champ de tri
        $orderBy = null;
        if (in_array('ordreq', $columns)) {
            $orderBy = 'ordreq';
        } elseif (in_array('ordref', $columns)) {
            $orderBy = 'ordref';
        }

        // Construction SQL (Note : tu n'as sélectionné que 'idq', 'defaut' n'existera pas dans le résultat)
        $sql = "SELECT [idq] FROM [LIVRAISON]";
        if ($orderBy) {
            $sql .= " ORDER BY [$orderBy] ASC";
        }

        $sourceRows = [];

        // 2. Exécution selon l'OS
        if ($systemExploitation === 'Windows') {
            $sourceRows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $rawRows = AccessService::query($zCheminParametreMdb, $sql);
            foreach ($rawRows as $row) {
                $sourceRows[] = ['idq' => trim($row)];
            }
        }

        // Encodage
        $sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);

        // Ajouter les champs supplémentaires
        $extraChamps = ['n_lot', 'n_ima', 'n_enr', 'ville', 'seance'];

        foreach ($extraChamps as $champ) {
            Champ::updateOrCreate(
                [
                    'nom_champ' => $champ,
                    'codification_id' => $codificationId
                ],
                ['valeur_defaut' => null]
            );
        }

        // Insertion des champs issus de la MDB
        foreach ($sourceRows as $row) {
            Champ::updateOrCreate(
                [
                    'nom_champ' => $this->normalizer->normalizeFieldName($row['idq']),
                    'codification_id' => $codificationId
                ],
                [
                    // Attention : comme tu fais un "SELECT [idq]", $row['defaut'] n'existe pas ici.
                    'valeur_defaut' => $row['defaut'] ?? null 
                ]
            );
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Champs importés avec succès'
        ]);
    }


}
