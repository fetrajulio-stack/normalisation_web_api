<?php

namespace App\Http\Controllers\API;
use App\Exports\ArrayExport;
use App\Exports\NormalisationExport;
use App\Http\Controllers\Controller;
use App\Models\Codification;
use App\Models\Consigne;
use App\Services\ConsigneExecutor;
use App\Services\TabFilterService;
use App\Services\TextNormalizerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AccessService;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PDO;


class NormalisationController extends Controller
{
    protected TextNormalizerService  $normalizer;
    protected TabFilterService  $tabFilter;
    // Injection du service via le constructeur
    public function __construct(TextNormalizerService $normalizer, TabFilterService $tabFilter)
    {
        $this->normalizer = $normalizer;
        $this->tabFilter = $tabFilter;
    }

    public function test()
    {

        $path = "D:\\DEVELOPPEMENT\\PRODUCTION\\MASQUE\\STEFI FRANCE ALZEIMER\\FRA-09558-BINOME-FORMATEUR-FAI\\Normalisation\\parametre.mdb";

        $pdo = new \PDO(
            "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$path;Uid=;Pwd=;"
        );
        // Récupérer la liste des tables
        $tables = $pdo->query("
            SELECT idq,libq
            FROM SOURCE
        ");
        $rows = $tables->fetchAll(\PDO::FETCH_ASSOC);
        dump($rows);
    }


    public function importParametre(Request $request)
    {
        /************************************ */
        $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        /************************************ */
        //$pdo = AccessService::connect("D:\DEVELOPPEMENT\PRODUCTION\NORMALISATION\STEFI MEDIAMETRIE\MED-08251-AVATAR-DFEDC-ADULTE\parametre.mdb",null,null);
        $pdo = AccessService::connect($zCheminParametreMdb,null,null);
        $sourceRows = $pdo->query(" SELECT idq FROM SOURCE")->fetchAll(PDO::FETCH_ASSOC);
        $tableName = 'source';
        Schema::dropIfExists($tableName);

        Schema::create($tableName, function (Blueprint $table) use ($sourceRows) {
            $table->bigIncrements('id');
            $table->string('n_lot')->nullable()->default(null);
            $table->string('n_ima')->nullable()->default(null);
            $table->string('n_enr')->nullable()->default(null);
            foreach ($sourceRows as $row) {
                $text_utf8 = mb_convert_encoding( $row['idq'] , 'UTF-8', 'Windows-1252');

                $colName = $this->normalizer->normalizeFieldName($text_utf8);
                 $default = isset($row['defaut']) ? $row['defaut'] : "";
                if (is_numeric($default)) {
                    $table->integer($colName)->default($default);
                } else {
                    $table->text($colName)->nullable()->default(null);
                }
            }

            $table->timestamps();
        });

        // Insérer ligne par défaut
       /** $insertData = [];
        foreach ($sourceRows as $row) {
            $colName = str_replace(' ', '_', $row['idq']);
            $insertData[$colName] = $row['Defaut'] ?? null;
        }
        DB::table($tableName)->insert($insertData);
        */
        return response()->json(['message' => 'Table SOURCE importée avec succès !']);

    }

    /**
     * Parcourir un répertoire distant et lire les fichiers .OK.MDB
     */
    public function AssemblageMdb()
    {
        // Répertoire réseau (UNC)
        $networkPath = 'D:\\DEVELOPPEMENT\\PRODUCTION\\MASQUE\\STEFI FRANCE ALZEIMER\\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\\LOT';
        //$networkPath = 'Z:/STEFI FRANCE ALZHEIMER/STEFI FRANCE ALZHEIMER/FRA-09558-INTERVENANT-ENTRETIEN-INDIVIDUEL-TYPE2';

        // Vérifier si le chemin existe
        if (!is_dir($networkPath)) {
            return response()->json(['error' => 'Le répertoire distant est inaccessible.']);
        }


        // Tableau pour stocker les données
        $allData = [];

        // Fonction récursive pour parcourir le répertoire
        $allData = $this->getMdbFiles($networkPath);
            dd($allData);
        return response()->json($allData);
    }

    /**
     * Fonction récursive pour récupérer tous les fichiers .OK.MDB
     */
    private function getMdbFiles($dir)
    {
        $files = [];

        // Créer un objet RecursiveDirectoryIterator pour parcourir récursivement
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // Vérifier si le fichier se termine par ".OK.MDB" (insensible à la casse)
                if (preg_match('/\.OK\.MDB$/i', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function importMdb(Request $request)
    {

        /************************************ */
        $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');
        $cheminLot = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'SOURCE';
          //  dd($cheminLot);
        /************************************ */

        $livraisonPath = 'D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\Normalisation\livraison.mdb'; // livraison.mdb
      //  $cheminLot = 'D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\LOTS';              // chemin parent des LOTS


        /*************************LECTURE DU FICHIER PARAMETRE.CAT ET RESUPERATION DE L'EXTENSION***************************** */
        $ini = parse_ini_file('D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\Normalisation\Parametre.cat', true);
        // récupère la valeur de normalisation dans parametre.cat
        $extention = $ini['parametre']['normalisation'] ?? null; // affichera "VO"
        // récupère la valeur de passe dans parametre.cat
        $passsword = $ini['parametre']['passe'] ?? null; // affichera "VO"

        /*************************RECUPERATION DES LOTS***************************** */
        $listLots = $this->listLots($cheminLot);

        /*************************************************************************** */
        /************ Connexion PDO vers livraison.mdb puis vider la table source****************************** */
          $cnn =  AccessService::connect($livraisonPath,null,null);

        /**$resdelete = $cnn->exec("DELETE FROM SOURCE"); // vide la table*/

        DB::table('source')->truncate();
        /***********************************TRANFORMATION DE CERTAINS CLES ET FORMATAGE**************************************** */
        $tMap = [
            "Fichier" => "N_LOT",
            "Tiff"    => "N_IMA",
            "xOrdre"  => "N_ENR",
        ];
        $regleFormat = [
            "N_ENR" => fn($v) => sprintf('%04d', (int)$v),
        ];
        foreach ($listLots as $lotPath) {


            // Parcours récursif des fichiers MDB .OK.MDB
            $mdbFiles = $this->getOkMdbFile($lotPath, $extention);

            foreach ($mdbFiles as $filePath) {
               // $cnnS = $this->connectAccess($filePath); // fichier de saisie

                $cnnS = AccessService::mdbConnect(
                    $filePath,
                    $passsword
                );

                // Lecture de la table TRAVAIL
                $sqlTravail = "SELECT * FROM Travail ORDER BY TIFF, XORDRE";
                $rs = odbc_exec($cnnS, $sqlTravail);
                ////$rows = odbc_fetch_array($rs);
                $tMysqlSourceFields = SELF::getMysqlSourceFields();

                $batch = [];
                   //// dump($rows);
         while ($rows = odbc_fetch_array($rs)) {

                    $filtered = $this->tabFilter->filterAndNormalize(
                        self::getNewDataFormat($rows,$regleFormat,$tMap),
                        $tMysqlSourceFields
                    );

                    $batch[] = $filtered;

                    if (count($batch) >= 500) {

                        DB::table('source')->insert($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                   // DB::table('source')->insert($batch);

                    foreach ($batch as $row) {

                        // Conversion UTF-8 pour toutes les valeurs string
                        foreach ($row as $key => $value) {
                            if (is_string($value)) {
                                // Supprime un éventuel préfixe b" et convertit en UTF-8
                                $value = preg_replace('/^\s*b"/', '', $value); // retire b"
                                $value = trim($value, '"'); // retire les guillemets éventuels
                                $row[$key] = mb_convert_encoding($value, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
                            }
                        }

                        try {
                            DB::table('source')->insert($row);
                        } catch (\Illuminate\Database\QueryException $e) {

                            logger()->error('ERREUR INSERT LIGNE MDB', [
                                'sql'       => $e->getSql(),
                                'bindings'  => $e->getBindings(),
                                'code'      => $e->getCode(),
                                'message' => $e->getMessage(),
                                'row'     => $row,
                            ]);
                            //inserer les erreurs dans la table source_import_errors
                           /** DB::table('source_import_errors')->insert([
                                'n_ima' => $row['n_ima'] ?? null,
                                'error_message' => $e->getMessage(),
                                'row_data' => json_encode($row, JSON_UNESCAPED_UNICODE)
                            ]);*/
                            // on continue sans bloquer tout le fichier
                            continue;
                        }
                    }
                }
                /************************************************* */


                $cnnS = null; // fermer PDO
            }
        }

        return response()->json(['message' => 'Import terminé.']);
    }

    public function listLots($dir)
    {
        $Lots = [];

        if (!is_dir($dir)) {
            return [];
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue; // ignorer . et ..
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $Lots[] = $fullPath;
            }
        }

        return $Lots;
    }

    private function getOkMdbFile($dir, $extension)
    {

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.'.$extension.'\.OK\.MDB$/i', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }


    public function getMysqlSourceFields()
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('source');
        //dd($columns);
        return $columns;
    }

    public function getNewDataFormat( $tData,$regleFormat,$map )
    {
        $result = [];
        foreach ($tData as $key => $value) {

            $newKey = $map[$key] ?? $key;

            if (isset($regleFormat[$newKey])) {
                $value = $regleFormat[$newKey]($value);
            }

            $result[$newKey] = $value;
        }
        return $result;
    }

    public function normaliser($codification_id)
    {
        // Récupère toutes les consignes avec leurs groupes et champs
        $consignes = Consigne::with([
            'groupes.champs.champ',
            'parametres'
        ])
            ->whereHas('groupes.champs', function ($q) use ($codification_id) {
                $q->whereHas('champ', function ($qc) use ($codification_id) {
                    $qc->where('codification_id', $codification_id);
                });
            })
            ->get();

        //   dd($consignes);
        $executor = new ConsigneExecutor();

        // Récupère toutes les lignes de la table source
        $lignes = DB::table('source')->get();

        $rowsForExport = [];

        foreach ($lignes as $ligne) {
            $data = (array) $ligne;

            // Boucle sur chaque consigne
            foreach ($consignes as $consigne) {

                // Récupère le handler correspondant
                $handler = $executor->getHandler($consigne->code);

                // Boucle sur chaque groupe de la consigne
                foreach ($consigne->groupes as $groupe) {

                    // Récupère les noms de champs du groupe dans le bon ordre
                    $champs = $groupe->champs
                        ->map(fn($gc) => strtolower($gc->champ->nom_champ))
                        ->toArray();
                   //     dd($champs);
                    // Récupère les paramètres de la consigne
                    $parametres = $consigne->parametres->pluck('valeur', 'cle')->toArray();

                    // Applique la consigne sur ce groupe de champs


                    $data = $handler->appliquer($data, $champs, $parametres);

                }
            }

            $rowsForExport[] = $data;

        }
        /**************************RECUPERATION DE CODE DOSSIER*********************************** */
        $codification = Codification::findOrFail($codification_id);
        $codeDossier = $codification->code_dossier;

        $filePath = 'Exports/'.$codeDossier . '.xlsx';
        /************************************************************* */


        Excel::store(new NormalisationExport($rowsForExport), $filePath, 'local');
        return response()->json([
            'status' => 'OK',
            'message' => 'Fichier Excel généré',
            'path' => storage_path('app/' . $filePath)
        ]);
       /** return Excel::download(
            new NormalisationExport($rowsForExport),
            'resultat_normalisation.xlsx'
        );*/
    }

}
