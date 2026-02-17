<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Champ;
use App\Services\AccessService;
use App\Services\TabFilterService;
use App\Services\TextNormalizerService;
use Illuminate\Http\Request;
use App\Models\Dossier;
use PDO;

class CodificationController extends Controller
{
    protected TextNormalizerService  $normalizer;

    public function __construct(TextNormalizerService $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function listCodification()
    {
        $dossiers = Dossier::with('cathegories')->get();

        // Transformation propre → tableau
        $array = $dossiers->toArray();
        /**foreach ($array as $row) {
            json_encode($row);
            if (json_last_error() !== JSON_ERROR_NONE) {
                dd($row);
            }
        }*/
        // Conversion UTF-8 récursive
        $array = $this->utf8EncodeRecursive($array);

        return response()->json($array);
    }

    public function test()
    {
        $dossier = Dossier::with(['cathegories' => function($q) {
            $q->limit(10);
        }])->find(33);

        return response()->json($dossier->toArray());
    }

    private function utf8EncodeRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->utf8EncodeRecursive($value);
            }
            return $data;
        }

        if (is_string($data)) {
            // Vérifie si déjà UTF-8
            if (!mb_check_encoding($data, 'UTF-8')) {
               // return mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
                return mb_convert_encoding($data, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
            }
            return $data;
        }

        return $data;
    }

    public function getChampsByCodeDossier( $codificationId )
    {

        $pdo = AccessService::connect("D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\Normalisation\parametre.mdb",null,null);

        $sourceRows = $pdo->query(" SELECT idq,defaut FROM SOURCE")->fetchAll(PDO::FETCH_ASSOC);

        $sourceRows = $this->utf8EncodeRecursive($sourceRows);

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

        return response()->json($sourceRows);
    }






}
