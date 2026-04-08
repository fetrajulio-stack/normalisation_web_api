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

        $sourceRows = $pdo->query(" SELECT idq FROM LIVRAISON ORDER BY ordreq ASC")->fetchAll(PDO::FETCH_ASSOC);
        //    dd($sourceRows);
        $tableName = 'source';
        Schema::dropIfExists($tableName);

        Schema::create($tableName, function (Blueprint $table) use ($sourceRows, $zDossier) {
            $table->bigIncrements('id');
            $table->string('n_lot')->nullable()->default(null);
            $table->string('n_ima')->nullable()->default(null);
            $table->string('n_enr')->nullable()->default(null);

            if (strtoupper(trim($zDossier)) === 'STEFI MEDIAMETRIE') {
                $table->string('ville')->nullable()->default(null);
                $table->string('seance')->nullable()->default(null);
            }

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

        // Filtrer les lots si une sélection a été envoyée par le frontend
        $selectedLots = $request->input('selected_lots'); // Array de noms de lots

        if (!empty($selectedLots)) {
            // Filtrer pour garder seulement les lots sélectionnés
            $listLots = array_filter($listLots, function ($lotPath) use ($selectedLots, $cheminLot) {
                $lotName = basename($lotPath); // Récupérer le nom du dossier
                return in_array($lotName, $selectedLots);
            });

            \Log::info('Lots filtrés selon la sélection', [
                'selected_lots_count' => count($selectedLots),
                'filtered_lots_count' => count($listLots),
                'selected_lots' => $selectedLots
            ]);
        }

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
                    //  TOUJOURS fermer la connexion
                    if ($cnnS) {
                        odbc_close($cnnS);
                    }
                    //  important
                    $cnnS = null;
                    unset($cnnS);

                    // laisser respirer ODBC
                    usleep(50000); // 50ms
                }
            }
        }

        return response()->json(['message' => 'Import terminé.']);
    }

    /**
     * Récupérer la liste des lots (sous-dossiers) pour un code dossier
     * Appelé par le frontend pour afficher le modal de sélection
     */
    public function getLots(Request $request)
    {
        try {
            $nom_dossier = $request->input('nom_dossier');
            $nom_code_dossier = $request->input('nom_code_dossier');

            if (!$nom_dossier || !$nom_code_dossier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ], 400);
            }

            $basepathProduction = config('normalisation.mdb_base_path');

            $cheminLot = $basepathProduction
                . DIRECTORY_SEPARATOR . $nom_dossier
                . DIRECTORY_SEPARATOR . $nom_code_dossier . DIRECTORY_SEPARATOR;

            // Vérifier que le répertoire existe
            if (!is_dir($cheminLot)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le répertoire des lots n\'existe pas',
                    'path' => $cheminLot
                ], 400);
            }

            // Récupérer tous les sous-dossiers (lots)
            $lots = [];
            foreach (scandir($cheminLot) as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullPath = $cheminLot . $item;
                if (is_dir($fullPath)) {
                    $lots[] = $item; // Garder juste le nom du dossier
                }
            }

            // Trier les lots par nom
            sort($lots);

            return response()->json([
                'success' => true,
                'lots' => $lots,
                'count' => count($lots)
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des lots', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
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
            $key = self::normalizeKey($key);

            $newKey = $map[$key] ?? $key;

            if (isset($regleFormat[$newKey])) {
                $value = $regleFormat[$newKey]($value);
            }

            $result[$newKey] = $value;
        }
       // dd($result);
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
        $dossier = $codification->dossier;

        $filePath = 'Exports/'.$codeDossier . '.xlsx';
        /************************************************************* */


        //Excel::store(new NormalisationExport($rowsForExport), $filePath, 'public');
        Excel::store(new NormalisationExport($rowsForExport, $dossier), $filePath, 'public');

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
                'tableName' => 'required|string',
                'codeDossierName' => 'nullable|string',
                'codification_id' => 'nullable|integer'
            ]);

            $file = $request->file('file');
            $tableName = $request->input('tableName', 'data_import');

            // Générer un nom de fichier unique
            // $filename = time() . '_' . $file->getClientOriginalName();
            $filename = $file->getClientOriginalName();
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME); // Nom sans extension
            $jsonFilename = $filenameWithoutExt . '.json';
            $txtFilename = $filenameWithoutExt . '.txt';
            $filePath = 'Exports/' . $filename;
            $jsonFilePath = 'Exports/' . $jsonFilename;
            $txtFilePath = 'Exports/' . $txtFilename;

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

                // Générer le fichier .txt avec formatage selon les datamaps
                $txtFilenameGenerated = null;
                $codificationId = $request->input('codification_id');

                \Log::info('=== DEBUG importExcel ===', [
                    'codification_id' => $codificationId,
                    'jsonDataRaw_count' => count($jsonDataRaw),
                    'headers_count' => count($headers ?? [])
                ]);

                if ($codificationId) {

                    try {

                        // Récupérer les datamaps pour cette codification
                        $datamaps = $this->getDatamaps($codificationId);

                        \Log::info('Datamaps retrieved', [
                            'codificationId' => $codificationId,
                            'datamaps_count' => count($datamaps),
                            'datamaps_data' => $datamaps
                        ]);

                        if (!empty($datamaps)) {

                            $txtContent = $this->generateTxtContent($jsonDataRaw, $datamaps, $headers);

                            \Log::info('TXT content generated', [
                                'filename' => $txtFilename,
                                'content_length' => strlen($txtContent),
                                'line_count' => count(explode(PHP_EOL, $txtContent))
                            ]);

                            // Sauvegarder le fichier .txt
                            $txtPath = storage_path('app/public/Exports/' . $txtFilename);

                            // Vérifier que le répertoire existe
                            $exportDir = storage_path('app/public/Exports');
                            if (!is_dir($exportDir)) {
                                mkdir($exportDir, 0777, true);
                                \Log::info('Créé le répertoire Exports', ['path' => $exportDir]);
                            }

                            $bytesWritten = file_put_contents($txtPath, $txtContent);

                            \Log::info('Fichier TXT écrit', [
                                'filename' => $txtFilename,
                                'path' => $txtPath,
                                'bytes_written' => $bytesWritten,
                                'file_exists' => file_exists($txtPath)
                            ]);

                            if ($bytesWritten !== false) {
                                $txtFilenameGenerated = $txtFilename;
                            } else {
                                \Log::error('Impossible d\'écrire le fichier TXT', [
                                    'path' => $txtPath,
                                    'filename' => $txtFilename
                                ]);
                            }
                        } else {
                            \Log::warning('Aucune datamap trouvée', ['codificationId' => $codificationId]);
                        }
                    } catch (\Exception $txtException) {
                        \Log::error('Erreur lors de la création du fichier TXT', [
                            'error' => $txtException->getMessage(),
                            'trace' => $txtException->getTraceAsString(),
                            'filename' => $txtFilename
                        ]);
                    }
                } else {
                    \Log::warning('codification_id non fourni ou nul');
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Fichier importé avec succès !',
                    'filename' => $filename,
                    'filepath' => $filePath,
                    'json_filename' => $jsonFilename,
                    'json_filepath' => $jsonFilePath,
                    'txt_filename' => $txtFilenameGenerated,
                    'txt_filepath' => $txtFilenameGenerated ? ('Exports/' . $txtFilenameGenerated) : null,
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

    /**
     * Récupérer les datamaps pour une codification
     */
    private function getDatamaps($codificationId)
    {

        \Log::info('getDatamaps called', ['codificationId' => $codificationId]);

        // Importer le modèle Datamap si nécessaire
        // Cela suppose qu'un modèle Datamap existe avec une relation vers Champ
        $datamaps = DB::table('datamaps')
            ->join('champs', 'datamaps.champ_id', '=', 'champs.id')
            ->where('datamaps.codification_id', $codificationId)
            ->select('champs.nom_champ', 'datamaps.position', 'datamaps.longueur')
            ->orderBy('datamaps.position')
            ->get()
            ->toArray();

        \Log::info('getDatamaps result', [
            'codificationId' => $codificationId,
            'datamaps_found' => count($datamaps),
            'sample' => isset($datamaps[0]) ? $datamaps[0] : null,
            'all_datamaps' => $datamaps
        ]);

        return $datamaps;
    }

    /**
     * Générer le contenu du fichier TXT avec formatage à largeur fixe
     */
    private function generateTxtContent($rows, $datamaps, $headers)
    {

        \Log::info('=== generateTxtContent START ===', [
            'rows_count' => count($rows),
            'datamaps_count' => count($datamaps),
            'first_row_sample' => isset($rows[0]) ? array_slice($rows[0], 0, 3) : null
        ]);

        $txtLines = [];

        // Créer un mapping entre les en-têtes Excel et les datamaps
        $datamap_rules = [];

        foreach ($datamaps as $datamap) {
            // Normaliser le nom du champ pour comparaison
            $normalizedChampName = strtolower(str_replace([' ', '-'], '_', trim($datamap->nom_champ)));
            $datamap_rules[$normalizedChampName] = [
                'position' => (int)$datamap->position,
                'longueur' => (int)$datamap->longueur
            ];
        }

        \Log::info('Datamap rules created', [
            'rules_count' => count($datamap_rules),
            'rules_keys' => array_keys($datamap_rules)
        ]);

        // Traiter chaque ligne de données
        $rowsProcessed = 0;
        $rowsSkipped = 0;

        foreach ($rows as $rowIndex => $rowData) {

            // Créer un tableau ordonné par position
            $position_data = [];
            unset($rowData['id']); // Supprimer l'id si présent, car ce n'est pas un champ à exporter

            foreach ($rowData as $fieldName => $value) {
                $normalizedField = strtolower(str_replace([' ', '-'], '_', trim($fieldName)));

                // Vérifier si ce champ a une règle de formatage
                if (isset($datamap_rules[$normalizedField])) {
                    $rule = $datamap_rules[$normalizedField];
                    $pos = $rule['position'];
                    $len = $rule['longueur'];

                    // Convertir la valeur en string et traiter le formatage
                    $strValue = (string)($value ?? '');

                    // Formater selon la longueur
                    if (strlen($strValue) > $len) {
                        // Tronquer si la valeur est trop longue
                        $strValue = substr($strValue, 0, $len);
                    } elseif (strlen($strValue) < $len) {
                        // Compléter avec des espaces si la valeur est trop courte
                        $strValue = str_pad($strValue, $len, ' ', STR_PAD_RIGHT);
                    }

                    $position_data[$pos] = $strValue;
                }
            }

            // Trier par position et créer la ligne
            if (!empty($position_data)) {
                ksort($position_data);
                $line = implode('', $position_data);
                $txtLines[] = $line;
                $rowsProcessed++;

                // LOG le premier exemple
                if ($rowIndex === 0) {
                    \Log::info('First row processed', [
                        'position_data_count' => count($position_data),
                        'line_preview' => substr($line, 0, 100)
                    ]);
                }
            } else {
                $rowsSkipped++;
                if ($rowIndex === 0) {
                    \Log::warning('First row was skipped - no position_data', [
                        'rowData_keys' => array_keys($rowData),
                        'expected_keys' => array_keys($datamap_rules)
                    ]);
                }
            }
        }

        \Log::info('=== generateTxtContent FINISH ===', [
            'rows_processed' => $rowsProcessed,
            'rows_skipped' => $rowsSkipped,
            'txtLines_count' => count($txtLines)
        ]);

        // Joindre toutes les lignes avec des sauts de ligne
        return implode(PHP_EOL, $txtLines);
    }

    public function downloadExcel($filename)
    {
        $path = storage_path('app/public/Exports/' . $filename);

        return response()->download($path);
    }

    public function normalizeKey($key)
    {
        // 🔥 1. supprimer le b" au début
        $key = preg_replace('/^b"/', '', $key);

        // 🔥 2. supprimer le " à la fin
        $key = trim($key, '"');

        // 🔥 3. corriger encodage
        $key = mb_convert_encoding($key, 'UTF-8', 'Windows-1252');

        // 🔥 4. enlever accents (SAFE)
        $key = @transliterator_transliterate(
            'Any-Latin; Latin-ASCII',
            $key
        );

        // 🔥 fallback si transliterator échoue
        if (!$key) {
            $key = iconv('UTF-8', 'ASCII//IGNORE', $key);
        }

        // 🔥 5. nettoyage final
      ///  $key = strtolower($key);
        ///$key = str_replace([' ', '-', '.'], '_', $key);
      ///  $key = preg_replace('/[^a-z0-9_]/', '', $key);

        return $key;
    }

}
