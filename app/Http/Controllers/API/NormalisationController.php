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
use PhpOffice\PhpSpreadsheet\IOFactory;
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
          //  dd($zCheminParametreMdb);
        /************************************ */
        //$pdo = AccessService::connect("D:\DEVELOPPEMENT\PRODUCTION\NORMALISATION\STEFI MEDIAMETRIE\MED-08251-AVATAR-DFEDC-ADULTE\parametre.mdb",null,null);
        $pdo = AccessService::connect($zCheminParametreMdb,null,null);

        $sourceRows = $pdo->query(" SELECT idq FROM SOURCE ORDER BY ordreq ASC")->fetchAll(PDO::FETCH_ASSOC);
    //    dd($sourceRows);
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
         //   dd($allData);
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
//dd($zDossier . DIRECTORY_SEPARATOR . $zCode_dossier);
        //$basepathProdcution = env('NORMALISATION_BASE_PATH');
        $basepathProdcution = config('normalisation.mdb_base_path');
      //dd("prod base path : " . $basepathProdcution);

        $basePath = config('normalisation.base_path');
       //dd($basePath);

        $cheminLot = $basepathProdcution
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier . DIRECTORY_SEPARATOR;

        $cheminMDBCat= $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.cat';

          //  dd($cheminLot);
        /************************************ */

        //$livraisonPath = 'D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\Normalisation\livraison.mdb'; // livraison.mdb
      //  $cheminLot = 'D:\DEVELOPPEMENT\PRODUCTION\MASQUE\STEFI FRANCE ALZEIMER\FRA-09558-INTERVENANT_ENTRETIEN_INDIVIDUEL-TYPE 2\LOTS';              // chemin parent des LOTS

        //D:\DEVELOPPEMENT\PRODUCTION\NORMALISATION\STEFI MEDIAMETRIE\MED-08251-AVATAR-DFEDC-ADULTE\SOURCE
    //  dd("123");
        /*************************LECTURE DU FICHIER PARAMETRE.CAT ET RESUPERATION DE L'EXTENSION***************************** */

        //$ini = parse_ini_file(
         //   'D:/DEVELOPPEMENT/PRODUCTION/NORMALISATION/STEFI MEDIAMETRIE/MED-08251-AVATAR-DFEDC-ADULTE/Parametre.cat',
          //  true
        //);*/
       // dd($ini);

        //$ini = parse_ini_file('D:\DEVELOPPEMENT\PRODUCTION\NORMALISATION\STEFI MEDIAMETRIE\MED-08251-AVATAR-DFEDC-ADULTE\Parametre.cat', true);
        $ini = parse_ini_file($cheminMDBCat);
        //dd($ini);

        // récupère la valeur de normalisation dans parametre.cat
        if(isset($ini['parametre'])){
            $extention = $ini['parametre']['normalisation']; // affichera "VO"
        }
        else if(isset($ini['normalisation'])) {
            $extention = $ini['normalisation']; // affichera "VO"
        }
        else{
            $extention = null;
        }

       // dd($extention);
        // récupère la valeur de passe dans parametre.cat
        if(isset($ini['parametre'])){
            $passsword = $ini['parametre']['passe']; // affichera "VO"
        }
        else if(isset($ini['passe'])) {
            $passsword = $ini['passe']; // affichera "VO"
        }
        else{
            $passsword = null;
        }


        /*************************RECUPERATION DES LOTS***************************** */
        $listLots = $this->listLots($cheminLot);
       // dd($cheminLot);
        //dd($listLots);

        /*************************************************************************** */
        /************ Connexion PDO vers livraison.mdb puis vider la table source****************************** */
          //$cnn =  AccessService::connect($livraisonPath,null,null);

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
//dd($mdbFiles);

            foreach ($mdbFiles as $filePath) {
                $cnnS = AccessService::mdbConnect($filePath, $passsword);

                try {

                    $sqlTravail = "SELECT * FROM Travail ORDER BY TIFF, XORDRE";
                    $rs = odbc_exec($cnnS, $sqlTravail);

                    $tMysqlSourceFields = self::getMysqlSourceFields();
                    $batch = [];

                    while ($rows = odbc_fetch_array($rs)) {

                        $filtered = $this->tabFilter->filterAndNormalize(
                            self::getNewDataFormat($rows, $regleFormat, $tMap),
                            $tMysqlSourceFields
                        );

                        $batch[] = $filtered;

                        if (count($batch) >= 500) {
                            DB::table('source')->insert($batch);
                            $batch = [];
                        }
                    }

                    if (!empty($batch)) {

                        foreach ($batch as $row) {

                            foreach ($row as $key => $value) {
                                if (is_string($value)) {
                                    $value = preg_replace('/^\s*b"/', '', $value);
                                    $value = trim($value, '"');
                                    $row[$key] = mb_convert_encoding(
                                        $value,
                                        'UTF-8',
                                        ['Windows-1252', 'ISO-8859-1', 'UTF-8']
                                    );
                                }
                            }

                            try {
                                DB::table('source')->insert($row);
                            } catch (\Illuminate\Database\QueryException $e) {
                                logger()->error('ERREUR INSERT LIGNE MDB', [
                                    'message' => $e->getMessage(),
                                    'row'     => $row,
                                ]);
                            }
                        }
                    }

                } finally {
                    // 🔥 TOUJOURS fermer la connexion
                    if ($cnnS) {
                        odbc_close($cnnS);
                    }
                }
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
            'groupes' => function ($qg) use ($codification_id) {
                $qg->with([
                    'champs' => function ($qc) use ($codification_id) {
                        $qc->whereHas('champ', function ($qcc) use ($codification_id) {
                            $qcc->where('codification_id', $codification_id);
                        })
                            ->with('champ');
                    }
                ]);
            },
            'parametres'
        ])
            ->whereHas('groupes.champs.champ', function ($q) use ($codification_id) {
                $q->where('codification_id', $codification_id);
            })
            ->get();

        //   dd($consignes);
        $executor = new ConsigneExecutor();

        // Récupère toutes les lignes de la table source
        $lignes = DB::table('source')->get();
          //  die(123);
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


       Excel::store(new NormalisationExport($rowsForExport), $filePath, 'public');

       /** Drop table source */
       // Schema::dropIfExists('source');

        return response()->json([
            'status' => 'OK',
            'message' => 'Fichier Excel généré',
            'url' => asset('storage/'.$filePath),
            'path' => storage_path('app/' . $filePath),
            'filename' => $codeDossier . '.xlsx'
        ]);

        /**return Excel::download(
            new NormalisationExport($rowsForExport),
            $codeDossier . '.xlsx'
        );*/

    }

    /**
     * Importer un fichier Excel uploadé
     */
    public function importExcel(Request $request)
    {
        try {
            // Valider le fichier
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
                'tableName' => 'required|string'
            ]);

            $file = $request->file('file');
            $tableName = $request->input('tableName', 'data_import');

            // Générer un nom de fichier unique
           // $filename = time() . '_' . $file->getClientOriginalName();
            $filename = $file->getClientOriginalName();
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME); // Nom sans extension
            $jsonFilename = $filenameWithoutExt . '.json';
            $filePath = 'Exports/' . $filename;
            $jsonFilePath = 'Exports/' . $jsonFilename;

            // Stocker le fichier original dans storage/app/public/Exports
            $storedPath = $file->storeAs('public/Exports', $filename);

            // Lire le contenu du fichier Excel
            try {
                // Charger le fichier Excel
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                // Vérifier qu'il y a des données
                if (empty($rows)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier Excel est vide'
                    ], 400);
                }

                // Récupérer les colonnes de la table cible
                $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

                // Traiter les données (première ligne = en-têtes)
                $headers = array_shift($rows); // Extraire la première ligne comme en-têtes
                $insertData = [];
                $jsonDataRaw = []; // Pour stocker TOUTES les données du fichier Excel
                $errorCount = 0;

                foreach ($rows as $rowIndex => $row) {
                    if (empty(array_filter($row))) {
                        // Ignorer les lignes vides
                        continue;
                    }

                    // Mapper les données avec les en-têtes (pour JSON - TOUTES les colonnes)
                    $rawRow = [];
                    foreach ($headers as $colIndex => $header) {
                        if ($header === null) continue;

                        // Normaliser le nom de la colonne
                        $normalizedHeader = strtolower(str_replace([' ', '-'], '_', trim($header)));
                        $value = $row[$colIndex] ?? null;

                        // Encoder en UTF-8 si nécessaire
                        if (is_string($value)) {
                            $value = mb_convert_encoding($value, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
                            $value = trim($value);
                        }

                        $rawRow[$normalizedHeader] = $value;
                    }

                    // Ajouter la ligne brute aux données JSON (TOUTES les colonnes)
                    if (!empty(array_filter($rawRow))) {
                        $jsonDataRaw[] = $rawRow;
                    }

                    // Mapper les données avec les en-têtes (pour la table - colonnes existantes uniquement)
                    $mappedRow = [];
                    foreach ($headers as $colIndex => $header) {
                        if ($header === null) continue;

                        // Normaliser le nom de la colonne
                        $normalizedHeader = strtolower(str_replace([' ', '-'], '_', trim($header)));

                        // Vérifier si la colonne existe dans la table
                        if (in_array($normalizedHeader, $columns)) {
                            $value = $row[$colIndex] ?? null;

                            // Encoder en UTF-8 si nécessaire
                            if (is_string($value)) {
                                $value = mb_convert_encoding($value, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
                                $value = trim($value);
                            }

                            $mappedRow[$normalizedHeader] = $value;
                        }
                    }

                    // Insérer seulement si la ligne contient des données
                    if (!empty(array_filter($mappedRow))) {
                        $insertData[] = $mappedRow;
                        $jsonData[] = $mappedRow; // Ajouter aux données JSON
                    }

                    // Insérer par lots de 500 lignes
                    if (count($insertData) >= 500) {
                        try {
                            DB::table($tableName)->insert($insertData);
                            $insertData = [];
                        } catch (\Illuminate\Database\QueryException $e) {
                            \Log::error('Erreur lors de l\'insertion des données Excel', [
                                'tableName' => $tableName,
                                'error' => $e->getMessage(),
                                'row' => $rowIndex
                            ]);
                            $errorCount++;
                        }
                    }
                }

                // Insérer les données restantes
                if (!empty($insertData)) {
                    try {
                        DB::table($tableName)->insert($insertData);
                    } catch (\Illuminate\Database\QueryException $e) {
                        \Log::error('Erreur lors de l\'insertion des données restantes', [
                            'tableName' => $tableName,
                            'error' => $e->getMessage()
                        ]);
                        $errorCount++;
                    }
                }

                // Sauvegarder les données en fichier JSON
                try {
                    $jsonPath = storage_path('app/public/Exports/' . $jsonFilename);
                    // Sauvegarder TOUTES les données du fichier Excel dans le JSON
                    $jsonContent = json_encode($jsonDataRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    file_put_contents($jsonPath, $jsonContent);

                    \Log::info('Fichier JSON créé avec succès', [
                        'filename' => $jsonFilename,
                        'path' => $jsonPath,
                        'rows' => count($jsonDataRaw),
                        'data_sample' => array_slice($jsonDataRaw, 0, 1) // Afficher le premier enregistrement
                    ]);
                } catch (\Exception $jsonException) {
                    \Log::error('Erreur lors de la création du fichier JSON', [
                        'error' => $jsonException->getMessage(),
                        'filename' => $jsonFilename
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Fichier importé avec succès !',
                    'filename' => $filename,
                    'filepath' => $filePath,
                    'json_filename' => $jsonFilename,
                    'json_filepath' => $jsonFilePath,
                    'rows_imported_json' => count($jsonDataRaw),  // Toutes les données du fichier
                    'rows_imported_db' => count($insertData),     // Données insérées en BD
                    'errors' => $errorCount > 0 ? "⚠️ $errorCount erreurs lors de l'import" : null
                ], 200);

            } catch (\Exception $e) {
                // Si la lecture du fichier échoue
                \Log::error('Erreur lors de la lecture du fichier Excel', [
                    'error' => $e->getMessage(),
                    'file' => $filename
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la lecture du fichier : ' . $e->getMessage()
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée : ' . implode(', ', $e->errors()['file'] ?? ['Fichier invalide'])
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'import Excel', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadExcel($filename)
    {
        $path = storage_path('app/public/Exports/' . $filename);

        return response()->download($path);
    }

}
