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

class ConsigneController extends Controller
{
    protected TextNormalizerService  $normalizer;
    protected $encodingService;
    public function __construct(TextNormalizerService $normalizer,EncodingService $encodingService)
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

    public function store_old(Request $request)
    {

        foreach ($request->all() as $consigneData)
        {
            $this->storeSingleConsigne_old($consigneData);
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Paramétrage enregistré avec succès'
        ]);
    }

    private function storeSingleConsigne_old( $consigneData )
    {

        DB::transaction(function () use ($consigneData) {

            $consigne = Consigne::findOrFail($consigneData['consigne_id']);

            foreach ($consigneData['groupes'] as $groupeData) {

                $groupe = Consigne_groupe::updateOrCreate([
                    'consigne_id' => $consigne->id,
                    'ordre_execution' => $groupeData['ordre'] ?? 1,
                ]);

                foreach ($groupeData['champs'] as $ordre => $champId) {

                    $champ = Champ::findOrFail($champId);

                    Consigne_groupe_champ::updateOrCreate([
                        'consigne_groupe_id' => $groupe->id,
                        'champ_id'           => $champ->id,
                        'ordre'              => $ordre + 1,
                    ]);
                }

            }

            if ( ISSET( $consigneData['parametres'] ) ) {
                foreach ($consigneData['parametres'] as $cle => $valeur) {
                    Parametre_consigne::updateOrCreate(
                        [
                            'consigne_id' => $consigne->id,
                            'cle' => $cle,
                        ],
                        [
                            'valeur' => $valeur ?? '',
                        ]
                    );
                }
            }
        });
    }

    /**
     * API PRINCIPALE
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

            // 1️⃣ Création ou récupération codification
            $codification = Codification::firstOrCreate([
                'dossier' => $request->nom_dossier,
                'code_dossier' => $request->nom_code_dossier
            ]);

            // 2️⃣ Import champs depuis MDB
            $listeChamps = $this->importFromMdb($codification->id, $request->nom_dossier, $request->nom_code_dossier);


            // 3️⃣ Préchargement des champs
            $champs = Champ::where('codification_id', $codification->id)
                ->get()
                ->keyBy('nom_champ');

            // Avant de réenregistrer les nouveaux paramètres :supprimmer les anciens enregistrement
            Parametre_consigne::where('codification_id', $codification->id)->delete();

            // 4️⃣ Enregistrement consignes
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
     * Enregistre UNE consigne
     */
    private function storeSingleConsigne(array $consigneData, $champs, $codificationId)
    {

        foreach ($consigneData['groupes'] as $groupeData) {

            // création groupe
            $groupe = Consigne_groupe::create([
                'consigne_id' => $consigneData['consigne_id'],
                'ordre' => $groupeData['ordre']
            ]);

            // association champs
            foreach ($groupeData['champs'] as $ordre => $nomChamp) {

                $nomChamp = strtolower($nomChamp);

                if (!isset($champs[$nomChamp])) {
                    throw new \Exception("Champ introuvable : $nomChamp");
                }

                Consigne_groupe_champ::create([
                    'consigne_groupe_id' => $groupe->id,
                    'champ_id' => $champs[$nomChamp]->id,
                    'ordre' => $ordre + 1
                ]);
            }
        }

        // paramètres
        if (!empty($consigneData['parametres'])) {

            foreach ($consigneData['parametres'] as $cle => $valeur) {

                Parametre_consigne::updateOrCreate(
                    [
                        'codification_id' => $codificationId,
                        'consigne_id' => $consigneData['consigne_id'],
                        'cle' => $cle
                    ],
                    [
                        'valeur' => $valeur
                    ]
                );
            }
        }
    }

    /**
     * SIMULATION import MDB
     * A remplacer par votre logique existante
     */
    private function importFromMdb($codificationId, $zDossier, $zCode_dossier)
    {
        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        if (!file_exists($zCheminParametreMdb)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Fichier Parametre.mdb introuvable',
                'chemin' => $zCheminParametreMdb
            ], 404);
        }

        $pdo = AccessService::connect($zCheminParametreMdb,null,null);

        $sourceRows = $pdo->query(" SELECT idq,defaut FROM SOURCE")->fetchAll(PDO::FETCH_ASSOC);

        $sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);

        // Ajouter les champs supplémentaires n_lot, n_ima, n_enr
        $extraChamps = ['n_lot', 'n_ima', 'n_enr'];

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


    }

    public function edit($codificationId)
    {
        // récupérer tous les groupes liés aux champs de cette codification
        $groupes = Consigne_groupe::whereHas('champs.champ', function ($q) use ($codificationId) {
            $q->where('codification_id', $codificationId);
        })
            ->with([
                'consigne',
                'champs.champ',
                'parametres'
            ])
            ->get();

        $result = [];

        foreach ($groupes as $groupe) {

            if (!isset($result[$groupe->consigne_id])) {

                $result[$groupe->consigne_id] = [
                    'consigne_id' => $groupe->consigne_id,
                    'groupes' => [],
                    'parametres' => []
                ];

                // paramètres de consigne
                foreach ($groupe->parametres as $p) {
                    $result[$groupe->consigne_id]['parametres'][$p->cle] = $p->valeur;
                }
            }

            $result[$groupe->consigne_id]['groupes'][] = [
                'ordre' => $groupe->ordre_execution,
                'champs' => $groupe->champs
                    ->sortBy('ordre')
                    ->pluck('champ.nom_champ')
                    ->toArray()
            ];
        }

        return response()->json(array_values($result));
    }

    public function update(Request $request, $codificationId)
    {
        // 🧹 Nettoyage ancien paramétrage
        $groupesIds = Consigne_groupe::whereHas('champs.champ', function ($q) use ($codificationId) {
            $q->where('codification_id', $codificationId);
        })->pluck('id');
        Consigne_groupe_champ::whereIn('consigne_groupe_id', $groupesIds)->delete();
        Consigne_groupe::whereIn('id', $groupesIds)->delete();


        $champs = Champ::where('codification_id', $codificationId)
            ->get()
            ->keyBy('nom_champ');

        // Avant de réenregistrer les nouveaux paramètres :supprimmer les anciens enregistrement
        Parametre_consigne::where('codification_id', $codificationId)->delete();

        // 2️⃣ Réinsérer comme une création
        foreach ($request->parametrage as $consigneData) {
            $this->storeSingleConsigne($consigneData, $champs, $codificationId);
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Paramétrage mis à jour avec succès'
        ]);
    }




}
