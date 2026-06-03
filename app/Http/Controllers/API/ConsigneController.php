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
        /**07/05/2026 */
       /* if (!empty($consigneData['parametres'])) {
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
        }*/

            /**07/05/2026 */

        /*if (!empty($consigneData['parametres'])) {
            foreach ($consigneData['parametres'] as $cle => $valeur) {
                // Si la valeur est un tableau/objet, on la JSON-encode pour la stocker proprement
                $valToStore = $valeur;
                if (is_array($valeur) || is_object($valeur)) {
                    $valToStore = json_encode($valeur, JSON_UNESCAPED_UNICODE);
                }

                Parametre_consigne::updateOrCreate(
                    [
                        'codification_id' => $codificationId,
                        'consigne_id' => $consigneData['consigne_id'],
                        'cle' => $cle
                    ],
                    [
                        'valeur' => $valToStore ?? ''
                    ]
                );
            }
        }*/
        // Enregistrement des paramètres globaux ou par groupe
        if (!empty($consigneData['parametres'])) {
            // Format attendu : liste d'objets {champ, ...}
            if (array_is_list($consigneData['parametres'])) {
                foreach ($consigneData['parametres'] as $parametre) {
                    if (empty($parametre['champ'])) {
                        continue;
                    }

                    $nomChamp = strtolower($parametre['champ']);
                    if (!isset($champs[$nomChamp])) {
                        continue;
                    }

                    $champId = $champs[$nomChamp]->id;
                    foreach ($parametre as $cle => $valeur) {
                        if ($cle === 'champ') {
                            continue;
                        }

                        Parametre_consigne::updateOrCreate(
                            [
                                'codification_id' => $codificationId,
                                'consigne_id' => $consigneData['consigne_id'],
                                'champ_id' => $champId,
                                'cle' => $cle
                            ],
                            [
                                'valeur' => $valeur ?? ''
                            ]
                        );
                    }
                }
            }
            // Ancien format associatif : on conserve si besoin, sans champ spécifique
            elseif (is_array($consigneData['parametres'])) {
                foreach ($consigneData['parametres'] as $cle => $valeur) {
                    Parametre_consigne::updateOrCreate(
                        [
                            'codification_id' => $codificationId,
                            'consigne_id' => $consigneData['consigne_id'],
                            'cle' => $cle
                        ],
                        [
                            'valeur' => is_array($valeur) || is_object($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE) : ($valeur ?? '')
                        ]
                    );
                }
            }
        }

        // Enregistrement des paramètres attachés aux groupes
        if (!isset($consigneData['groupes'])) {
            return;
        }

        foreach ($consigneData['groupes'] as $groupeData) {
            $groupParams = $groupeData['parametres'] ?? [];
            if (empty($groupParams) || !is_array($groupParams)) {
                continue;
            }

            foreach ($groupeData['champs'] as $nomChamp) {
                $nomChamp = strtolower($nomChamp);
                if (!isset($champs[$nomChamp])) {
                    continue;
                }

                $champId = $champs[$nomChamp]->id;
                foreach ($groupParams as $cle => $valeur) {
                    Parametre_consigne::updateOrCreate(
                        [
                            'codification_id' => $codificationId,
                            'consigne_id' => $consigneData['consigne_id'],
                            'champ_id' => $champId,
                            'cle' => $cle
                        ],
                        [
                            'valeur' => is_array($valeur) || is_object($valeur) ? json_encode($valeur, JSON_UNESCAPED_UNICODE) : ($valeur ?? '')
                        ]
                    );
                }
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
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('consigne_id'); // On groupe par ID de consigne

        // On récupère TOUS les paramètres de ce dossier
        $parametresDossier = Parametre_consigne::where('codification_id', $codificationId)
        ->orderBy('id', 'asc')
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
                /**07 */
                /*foreach ($parametresDossier[$consigneId] as $p) {
                    $item['parametres'][$p->cle] = $p->valeur;
                }*/

                /**07/05/2026 - Décodage JSON des paramètres complexes (ex: listes) */    
                /*foreach ($parametresDossier[$consigneId] as $p) {
                    $raw = $p->valeur;
                    $decoded = null;
                    if (is_string($raw)) {
                        $maybe = json_decode($raw, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decoded = $maybe;
                        }
                    }
                    $item['parametres'][$p->cle] = $decoded !== null ? $decoded : $raw;
                } */ 
                foreach ($parametresDossier[$consigneId] as $p) {
                    $champ = Champ::find($p->champ_id);

                    if (!$champ) {
                        continue;
                    }

                    $nomChamp = $champ->nom_champ;

                    if (!isset($item['parametres'][$nomChamp])) {
                        $item['parametres'][$nomChamp] = [];
                    }

                    $item['parametres'][$nomChamp][$p->cle] = $p->valeur;
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
