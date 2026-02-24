<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Champ;
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
        $dossiers = Dossier::with('cathegories')
            ->orderBy('nom_dossier', 'asc')
            //->orderBy('code_dossier', 'desc')
            ->get();

        // Transformation propre → tableau
        $array = $dossiers->toArray();
        /**foreach ($array as $row) {
            json_encode($row);
            if (json_last_error() !== JSON_ERROR_NONE) {
                dd($row);
            }
        }*/
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
      //  dd($zCheminParametreMdb);

        //$pdo = AccessService::connect("D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\Normalisation\parametre.mdb",null,null);
          $pdo = AccessService::connect($zCheminParametreMdb,null,null);

        $sourceRows = $pdo->query(" SELECT idq,defaut FROM SOURCE")->fetchAll(PDO::FETCH_ASSOC);

        $sourceRows = $this->encodingService->utf8EncodeRecursive($sourceRows);

        foreach ($sourceRows as &$item) {
            $item['idq'] = $this->normalizer->normalizeFieldName($item['idq']);
        }
        return response()->json($sourceRows);
    }






}
