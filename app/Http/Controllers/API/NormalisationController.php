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
use Illuminate\Support\Facades\Log;
use App\Imports\MappingImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;
use ZipArchive;


class NormalisationController extends Controller
{
    protected TextNormalizerService  $normalizer;
    protected TabFilterService  $tabFilter;
    // Injection du service via le constructeur
    public function __construct(TextNormalizerService $normalizer, TabFilterService $tabFilter)
    {
        $this->normalizer = $normalizer;
        $this->tabFilter = $tabFilter;

        $this->middleware('auth:sanctum')->except([
            'normaliser',
            'downloadExcel'
        ]);

    }

    private function normalizeKeyClient($value)
    {
         $value = trim($value);
        $value = strtoupper($value);

        // remplace tout ce qui n'est pas lettre/num par underscore
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value);

        // supprime les underscores multiples
        $value = preg_replace('/_+/', '_', $value);

        return trim($value, '_');
    }


    private function applyLibelleMapping(array $row, array $map): array
    {
        foreach ($row as $key => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            // 🔥 Normalisation de la clé
            // q5_1 → Q5-1
            $normalizedKey = strtoupper(str_replace('_', '-', $key));

            if (isset($map[$normalizedKey])) {

                // Cas valeur simple (ex: "3")
                if (is_numeric($value)) {
                    $num = (int)$value;

                    if (isset($map[$normalizedKey][$num])) {
                       // $row[$key] = $map[$normalizedKey][$num];
                        $row[$key] = $this->fixEncoding($map[$normalizedKey][$num]);
                    }
                }

                // 🔥 BONUS : gérer multi-choix (ex: "1;3;5")
                elseif (strpos($value, ';') !== false) {

                    $values = explode(';', $value);
                    $labels = [];

                    foreach ($values as $v) {
                        $v = trim($v);

                        if (is_numeric($v) && isset($map[$normalizedKey][(int)$v])) {
                            $labels[] = $map[$normalizedKey][(int)$v];
                        }
                    }

                    if (!empty($labels)) {
                        $row[$key] = implode('; ', $labels);
                    }
                }
            }
        }

        return $row;
    }

    private function fixEncoding($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Détecter si déjà UTF-8 valide
        if (mb_detect_encoding($value, 'UTF-8', true)) {
            return $value;
        }

        // Sinon convertir depuis Windows-1252 / ISO
        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
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


    /*public function importParametre(Request $request)
    {
        /************************************ */
     /*   $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';
        //  dd($zCheminParametreMdb);
        /************************************ */
        //$pdo = AccessService::connect("D:\DEVELOPPEMENT\PRODUCTION\NORMALISATION\STEFI MEDIAMETRIE\MED-08251-AVATAR-DFEDC-ADULTE\parametre.mdb",null,null);
    /*    $pdo = AccessService::connect($zCheminParametreMdb,null,null);

       // $sourceRows = $pdo->query(" SELECT idq FROM LIVRAISON ORDER BY ordreq ASC")->fetchAll(PDO::FETCH_ASSOC);

         /**DEBUT: Quelques dossiers dans n'utilise pas "ordreq" mais "ordref" dans la table livraison */
     /*       $stmt = $pdo->query("SELECT * FROM [LIVRAISON]");
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



   /*     $tableName = 'source';
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

        return response()->json(['message' => 'Table SOURCE importée avec succès !']);

    }*/

        public function importParametre(Request $request)
    {
        $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        $systemExploitation = env('SYSTEM_EXPLOITATION');

        $indexation = $request->input('indexation', []);
        $useIndexation = is_array($indexation) && count($indexation) > 0;


        $columns = [];
        $pdo = null;

        // 1. Détection des colonnes disponibles (ordreq/ordref et defaut)
        if ($systemExploitation === 'Windows') {
            $pdo = AccessService::connect($zCheminParametreMdb, null, null);
            $stmt = $pdo->query("SELECT TOP 1 * FROM [LIVRAISON]");
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = strtolower($meta['name']);
            }
        } else {
            $columns = AccessService::getColumns($zCheminParametreMdb, 'LIVRAISON');
        }

        // Détection dynamique du tri
        $orderBy = null;
        if (in_array('ordreq', $columns)) {
            $orderBy = 'ordreq';
        } elseif (in_array('ordref', $columns)) {
            $orderBy = 'ordref';
        }

        // Vérifier si la colonne 'defaut' existe pour l'inclure dans la requête
        $hasDefaultCol = in_array('defaut', $columns);
        $selectFields = $hasDefaultCol ? "[idq], [defaut]" : "[idq]";

        // 2. Construction et exécution de la requête SQL
        $sql = "SELECT $selectFields FROM [LIVRAISON]";
        if ($orderBy) {
            $sql .= " ORDER BY [$orderBy] ASC";
        }

        $sourceRows = [];
        if ($systemExploitation === 'Windows') {
            $sourceRows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $selectFields = $hasDefaultCol ? "idq, defaut" : "idq";
            $sql = "SELECT $selectFields FROM LIVRAISON";
            
            $rawRows = AccessService::query($zCheminParametreMdb, $sql);

            $rawRows = array_filter($rawRows, function ($value) {

                // Supprimer les valeurs vides
                if (trim($value) === '') {
                    return false;
                }

                // Supprimer "xx Rows retrieved"
                if (preg_match('/^\d+\s+Rows retrieved$/i', trim($value))) {
                    return false;
                }

                return true;
            });

            // Réindexer
            $rawRows = array_values($rawRows);

            // Trier A -> Z
            sort($rawRows, SORT_NATURAL | SORT_FLAG_CASE);

            //dd([$rawRows,$columns]);
            foreach ($rawRows as $rowLine) {
                if($rowLine !== ""){
                    $parts = explode('||', $rowLine);
                    $sourceRows[] = [
                        'idq' => trim($parts[0] ?? ''),
                        'defaut' => $hasDefaultCol ? trim($parts[1] ?? '') : null
                    ];
                }
            }
        }

        //dd($sourceRows);

        // 3. Création dynamique de la table SQL
        $tableName = 'source';
        Schema::dropIfExists($tableName);


        Schema::create($tableName, function (Blueprint $table) use ($sourceRows, $zDossier, $useIndexation) {
            $table->bigIncrements('id');
            $table->string('n_lot')->nullable()->default(null);
            $table->string('n_ima')->nullable()->default(null);
            $table->string('n_enr')->nullable()->default(null);

            if ($useIndexation) {
                $table->string('nom_fichier_indexe')->nullable()->default(null);
            }

            if (strtoupper(trim($zDossier)) === 'STEFI MEDIAMETRIE') {
                $table->string('ville')->nullable()->default(null);
                $table->string('seance')->nullable()->default(null);
            }

            foreach ($sourceRows as $row) {
                // Conversion de l'encodage du nom de la colonne
                $text_utf8 = mb_convert_encoding($row['idq'], 'UTF-8', 'Windows-1252');
                
                // Normalisation du nom (nettoyage caractères spéciaux)
                $colName = $this->normalizer->normalizeFieldName($text_utf8);
                
                // Gestion de la valeur par défaut
                $defaultVal = isset($row['defaut']) ? trim($row['defaut']) : "";

                if ($defaultVal !== "" && is_numeric($defaultVal)) {
                    $table->integer($colName)->default((int)$defaultVal);
                } else {
                    $table->text($colName)->nullable()->default(null);
                }
            }

            $table->timestamps();
        });

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
    $systemExploitation = env('SYSTEM_EXPLOITATION', 'Windows'); // Par défaut Windows si non défini

    $useLibelle = (int)($request->input('libelle', 0)) === 1;

    $indexation = $request->input('indexation', []);
    $useIndexation = is_array($indexation) && count($indexation) > 0;
    
    // Charger le mapping UNE SEULE FOIS
    $listeChoixMap = [];
    if ($useLibelle) {
        $listeChoixMap = $this->getListeChoix($request)->getData(true);
    }

    /************************************ */
    $zDossier = $request->nom_dossier ?? "";
    $zCode_dossier = $request->nom_code_dossier ?? "";

    $basepathProdcution = config('normalisation.mdb_base_path');
    $basePath = config('normalisation.base_path');

    $cheminLot = $basepathProdcution
        . DIRECTORY_SEPARATOR . $zDossier
        . DIRECTORY_SEPARATOR . $zCode_dossier . DIRECTORY_SEPARATOR;

    $cheminMDBCat = $basePath
        . DIRECTORY_SEPARATOR . $zDossier
        . DIRECTORY_SEPARATOR . $zCode_dossier
        . DIRECTORY_SEPARATOR . 'Parametre.cat';
    /************************************ */

    // Lecture du fichier parametre.cat
    $ini = parse_ini_file($cheminMDBCat);

    $extention = $ini['parametre']['normalisation'] ?? ($ini['normalisation'] ?? null);
    $passsword = $ini['parametre']['passe'] ?? ($ini['passe'] ?? null);

    /*************************RECUPERATION DES LOTS***************************** */
    $listLots = $this->listLots($cheminLot);
    $selectedLots = $request->input('selected_lots'); 

    if (!empty($selectedLots)) {
        $listLots = array_filter($listLots, function ($lotPath) use ($selectedLots) {
            return in_array(basename($lotPath), $selectedLots);
        });

        \Log::info('Lots filtrés selon la sélection', [
            'selected_lots_count' => count($selectedLots),
            'filtered_lots_count' => count($listLots),
        ]);
    }

    /*************************************************************************** */
    // Vider la table source
    DB::table('source')->truncate();

    /***********************************TRANFORMATION ET FORMATAGE****************** */
    if($systemExploitation === 'Windows'){
        $tMap = [
            'nom et prnoms' => 'nom_et_prenoms',
            'tiff'          => 'n_ima',
            'fichier'       => 'n_lot',
            'xordre'        => 'n_enr',
        ];
    }else{
        $tMap = [
            "Nom et Prénoms" => "nom_et_prenoms",
            "Tiff" => "n_ima",
            "Fichier" => "n_lot",
            'fichier'       => 'n_lot',
            "xOrdre" => "n_enr",
            'xordre'        => 'n_enr',
        ];
    }
   
    
    $regleFormat = [
        "n_enr" => fn($v) => sprintf('%04d', (int)$v),
    ];

    //$tMysqlSourceFields = self::getMysqlSourceFields();
    $tMysqlSourceFields = array_map('strtolower', self::getMysqlSourceFields());

    if ($useIndexation) {
        $tMysqlSourceFields[] = 'nom_fichier_indexe';
    }


    $sqlTravail = "SELECT * FROM Travail ORDER BY TIFF, XORDRE";

    foreach ($listLots as $lotPath) {
        $mdbFiles = $this->getOkMdbFile($lotPath, $extention);

        foreach ($mdbFiles as $filePath) {
            $batch = [];
            $tempPath = null;
            $cnnS = null;

            try {
                // === LECTURE DES DONNEES SELON L'OS ===
                $rowsToProcess = [];

                if ($systemExploitation === 'Windows') {
                    // METHODE WINDOWS (ODBC)
                    $result   = AccessService::mdbConnect($filePath, $passsword);
                    $cnnS     = $result['conn'];
                    $tempPath = $result['tempPath'];
                    
                    $rs = odbc_exec($cnnS, $sqlTravail);
                    
                    while ($row = odbc_fetch_array($rs)) {
                        $rowsToProcess[] = $row;
                    }
                } else {
                    // METHODE LINUX (mdbtools)
                    $sqlTravail = "SELECT * FROM Travail";
                    $rowsToProcess = AccessService::linuxQueryAssoc($filePath, 'Travail', $sqlTravail);
                }

                // dd([$rowsToProcess,$filePath,$systemExploitation]);
                // === TRAITEMENT UNIFIE (Identique pour Windows et Linux) ===
                $teste = [];
                //dd($rowsToProcess,$regleFormat,$tMap);
                foreach ($rowsToProcess as $row) {
                    
                    \Log::info('MYSQL FIELDS', $tMysqlSourceFields);

                    // 1. Filtrage et Normalisation initiale
                    $filtered = $this->tabFilter->filterAndNormalize(
                        self::getNewDataFormat($row, $regleFormat, $tMap),
                        $tMysqlSourceFields
                    );

                    // 🔥 normalisation forcée des clés finales
                    $filtered = array_change_key_case($filtered, CASE_LOWER);


                    // questionnaire_2 = même valeur que n_enr
                    /*if (
                        in_array('questionnaire_2', $tMysqlSourceFields) &&
                        isset($filtered['n_enr'])
                    ) {
                        $filtered['questionnaire_2'] = sprintf('%04d', (int)$filtered['n_enr']);
                    }*/

// Nouvelle règle :
// Si le champ questionnaire_2 existe dans la table source,
// alors n_enr = questionnaire
// et questionnaire_2 = questionnaire
// avec format sur 4 caractères (0001, 0123, etc.)

if (
    in_array('questionnaire_2', $tMysqlSourceFields) &&
    isset($filtered['questionnaire']) &&
    trim((string)$filtered['questionnaire']) !== ''
) {
    $questionnaireValue = sprintf(
        '%04d',
        (int) trim((string)$filtered['questionnaire'])
    );

    $filtered['n_enr'] = $questionnaireValue;
    $filtered['questionnaire_2'] = $questionnaireValue;
}


                    \Log::info('TEST NOM PRENOMS', [
                        'mapped' => $this->getNewDataFormat($row, $regleFormat, $tMap)
                    ]);
                    
                    // 2. Mapping des libellés (si activé)
                    if ($useLibelle) {
                        $filtered = $this->applyLibelleMapping($filtered, $listeChoixMap);
                    }

                    // 3. Correction d'encodage et nettoyage des chaines
                    foreach ($filtered as $key => $value) {
                        if (is_string($value)) {
                            $value = preg_replace('/^\s*b"/', '', $value);
                            $value = trim($value, '"');
                            $filtered[$key] = $this->fixEncoding($value);
                        }
                    }

                    // INDEXATION
                    if ($useIndexation) {

                        $indexParts = [];
                        $globalSeparator = '_';

                        // Trier par ordre
                        usort($indexation, function ($a, $b) {
                            return ($a['ordre'] ?? 0) <=> ($b['ordre'] ?? 0);
                        });

                        foreach ($indexation as $rule) {

                            // Séparateur personnalisé
                            if (!empty($rule['parametres']['separateur'])) {
                                $globalSeparator = $rule['parametres']['separateur'];
                            }

                            // Plusieurs champs possibles
                            $champs = $rule['champs'] ?? [];

                            foreach ($champs as $champ) {

                                // Normalisation du nom du champ
                                $champNormalise = strtolower(trim($champ));

                                // Vérifier si le champ existe
                                if (array_key_exists($champNormalise, $filtered)) {

                                    $value = trim((string)$filtered[$champNormalise]);

                                    if ($value !== '') {
                                        $indexParts[] = $value;
                                    }
                                }
                            }
                        }

                        // Construction finale
                        $nomFichier = implode($globalSeparator, array_filter($indexParts));

                        // Nettoyage caractères interdits Windows
                        $nomFichier = preg_replace('/[\\\\\\/:"*?<>|]+/', '_', $nomFichier);

                        // Ajout extension PDF
                        $filtered['nom_fichier_indexe'] = $nomFichier . '.pdf';
                    }
                    

                    // 4. Ajout au lot (Batch)
                    $batch[] = $filtered;
                    $teste[] = $filtered;

                    // 5. Insertion si on atteint 500
                    if (count($batch) >= 500) {
                        $this->insertBatchSafely($batch);
                        $batch = [];
                    }
                }

                //dd([$teste,$tMysqlSourceFields,$batch,$rowsToProcess]);


                // === INSERTION DU RESTE DU BATCH ===
                if (!empty($batch)) {
                    $this->insertBatchSafely($batch);
                }

            } finally {
                // Nettoyage spécifique Windows
                if ($systemExploitation === 'Windows') {
                    if ($cnnS) odbc_close($cnnS);
                    if ($tempPath && file_exists($tempPath)) unlink($tempPath);
                }
                
                // Laisser respirer le système
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
         \Log::info('CLE ACCESS', [
        'key' => $key,
        'hex' => bin2hex($key)
        ]);

        $normalizedKey = $this->normalizeKey($key);
        $newKey = $map[$normalizedKey] ?? $normalizedKey;
        $result[$newKey] = $value;

        if (isset($regleFormat[$newKey])) {
            $value = $regleFormat[$newKey]($value);
        }

        \Log::info('MAPPING', [
            'original'   => $key,
            'normalized' => $normalizedKey,
            'newKey'     => $newKey
        ]);

        
        }
        return $result;
    }

    public function normaliser(Request $request)
    {
        // 1. Validation
        $request->validate([
            'codification_id' => 'required|numeric',
        ]);

        $codification_id = $request->input('codification_id');
        $mapping = $request->input('mapping_file') ?? null;
        $payloadConsignes = $request->input('consignes') ?? null;

        // 2. Préparation des infos dossier
        $codification = Codification::findOrFail($codification_id);
        $codeDossier = $codification->code_dossier;
        $dossier = $codification->dossier;
        $filePath = 'Exports/' . $codeDossier . '.xlsx';
        $indexedFilePath = 'Exports/' . $codeDossier . '_indexe.xlsx';

        // 3. Décider si on utilise le payload du front ou la BDD
        if ($payloadConsignes && is_array($payloadConsignes) && !empty($payloadConsignes)) {
            // Utiliser le payload du front-end
            $consignes = $this->buildConsignesFromPayload($payloadConsignes, $codification_id);
        } else {
            // Charger depuis la BDD (fallback)
            $consignes = Consigne::with([
                'groupes' => function ($qg) use ($codification_id) {
                    $qg->with(['champs' => function ($qc) use ($codification_id) {
                        $qc->whereHas('champ', function ($qcc) use ($codification_id) {
                            $qcc->where('codification_id', $codification_id);
                    })->with('champ');
                }]);
            },
            'parametres' => function ($q) use ($codification_id) {
                $q->where('codification_id', $codification_id);
            }
        ])->where(function ($query) use ($codification_id) {
            // Charger les consignes avec des champs associés à cette codification
            $query->whereHas('groupes.champs.champ', function ($q) use ($codification_id) {
                $q->where('codification_id', $codification_id);
            })
            // OU charger INDEXER_DOCUMENTS seulement si elle a des paramètres pour cette codification
            ->orWhereHas('parametres', function ($q) use ($codification_id) {
                $q->where('codification_id', $codification_id);
            }, '>', 0);
        })->get();
        }

        // Séparer les consignes d'indexation pour les appliquer à la fin
        $indexationConsignes = $consignes->filter(fn ($consigne) => $consigne->code === 'INDEXER_DOCUMENTS')->values();
        $normalConsignes = $consignes->reject(fn ($consigne) => $consigne->code === 'INDEXER_DOCUMENTS')->values();
        $hasIndexation = $indexationConsignes->isNotEmpty();

        $executor = new ConsigneExecutor();
        $lignes = DB::table('source')->get();
        $rowsForExport = [];

        foreach ($lignes as $ligne) {
            $data = (array) $ligne;
            foreach ($normalConsignes as $consigne) {

                // Pour la consigne d'extraction de nom de lot, on doit traiter différemment car elle nécessite de regrouper les paramètres par champ_id
                if ($consigne->code === 'EXTRAIRE_NOM_LOT') {

                    $handler = $executor->getHandler($consigne->code);
                    foreach ($consigne->groupes as $groupe) {
                        // CHAMPS : IMPORTANT → champ_id => nom_champ
                        $champs = [];
                        foreach ($groupe->champs as $gc) {
                            $champs[$gc->champ->id] = strtolower($gc->champ->nom_champ);
                        }
                        // PARAMÈTRES GROUPÉS PAR champ_id
                        $parametres = [];
                        foreach ($consigne->parametres as $param) {
                            if ($param->codification_id != $codification_id) {
                                continue;
                            }
                            $parametres[$param->champ_id][] = [
                                'cle' => $param->cle,
                                'valeur' => $param->valeur
                            ];
                        }

                        $data = $handler->appliquer($data, $champs, $parametres);
                    }
                } else {
                    // Pour les autres consignes
                    $handler = $executor->getHandler($consigne->code);
                    foreach ($consigne->groupes as $groupe) {
                        $champs = $groupe->champs->map(fn($gc) => strtolower($gc->champ->nom_champ))->toArray();
                        $parametres = $consigne->parametres->pluck('valeur', 'cle')->toArray();
                        $data = $handler->appliquer($data, $champs, $parametres);
                    }
                }
            }
            $rowsForExport[] = $data;
        }

        // 4. Application du Mapping (si fourni par le Front)
        if (!empty($mapping)) {
            $mappingUpper = array_change_key_case($mapping, CASE_UPPER);
            $mappedRows = [];
            foreach ($rowsForExport as $row) {
                $newRow = [];
                foreach ($row as $key => $value) {
                    $upperKey = strtoupper($key);
                    // Si la clé existe dans le mapping, on renomme, sinon on garde l'original
                    $newKey = $mappingUpper[$upperKey] ?? $key;
                    $newRow[$newKey] = $value;
                }
                $mappedRows[] = $newRow;
            }
            $rowsForExport = $mappedRows;
        }

        $systemExploitation = env('SYSTEM_EXPLOITATION', 'Windows'); 

        // Déplacer nom_fichier_indexe à la dernière colonne
        $rowsForExport = array_map(function ($row) {

            $row = (array) $row;

            if (array_key_exists('nom_fichier_indexe', $row)) {

                $value = $row['nom_fichier_indexe'];

                unset($row['nom_fichier_indexe']);

                $row['nom_fichier_indexe'] = $value;
            }

            return $row;

        }, $rowsForExport);
        
        // 5. Export en Excel
            // =====================================================
            // EXPORT NORMAL
            // =====================================================

            Excel::store(
                new NormalisationExport($rowsForExport, $dossier),
                $filePath,
                'public'
            );

            $response = [
                'status'   => 'OK',
                'message'  => 'Fichier Excel généré',
                'url'      => route('api.normalisation.download', ['filename' => basename($filePath)]),
                'filename' => $codeDossier . '.xlsx'
            ];

            if ($hasIndexation) {
                $rowsForExportIndexed =  [];
                foreach ($rowsForExport as $row) {
                    $rowIndexed = $row;
                    foreach ($indexationConsignes as $consigne) {

                        $handler = $executor->getHandler($consigne->code);
                        // Regrouper TOUS les champs de TOUS les groupes
                        $champs = [];

                        foreach ($consigne->groupes as $groupe) {
                            foreach ($groupe->champs as $gc) {
                                $champs[$gc->champ->id] =
                                    strtolower($gc->champ->nom_champ);
                            }
                        }

                        // Paramètres de la consigne
                        $parametres = [];
                        foreach ($consigne->parametres as $param) {
                            if ($param->codification_id != $codification_id) {
                                continue;
                            }
                            $parametres[$param->champ_id][] = [
                                'cle'    => $param->cle,
                                'valeur' => $param->valeur
                            ];
                        }

                        $rowIndexed = $handler->appliquer(
                            $rowIndexed,
                            $champs,
                            $parametres
                        );
                    }
                    $rowsForExportIndexed[] = $rowIndexed;
                }

                // Déplacer nom_fichier_indexe à la dernière colonne aussi pour le fichier indexé
                $rowsForExportIndexed = array_map(function ($row) {
                    $row = (array) $row;

                    if (array_key_exists('nom_fichier_indexe', $row)) {
                        $value = $row['nom_fichier_indexe'];
                        unset($row['nom_fichier_indexe']);
                        $row['nom_fichier_indexe'] = $value;
                    }
                    return $row;
                }, $rowsForExportIndexed);

                Excel::store(
                    new NormalisationExport($rowsForExportIndexed, $dossier, true),
                    $indexedFilePath,
                    'public'
                );

                // =====================================================
                // COPIE + RENOMMAGE DES DOCUMENTS
                // =====================================================

                // Récupération des documents source
                $pdfs = \Storage::disk('local')
                    ->allFiles("fichier_indexe/{$dossier}/{$codeDossier}");

                // Construction du mapping N_IMA => NOM_FICHIER_INDEXE
                $indexMap = [];

                foreach ($rowsForExportIndexed as $row) {

                    $nIma = !empty($row['n_ima'])
                        ? pathinfo($row['n_ima'], PATHINFO_FILENAME)
                        : null;

                    $nomIndexe = $row['nom_fichier_indexe'] ?? null;

                    if (!empty($nIma) && !empty($nomIndexe)) {
                        $indexMap[$nIma] = $nomIndexe;
                    }
                }

                // Dossier destination
                $destinationDir = "fichier_indexe/{$codeDossier}";

                if (!\Storage::disk('local')->exists($destinationDir)) {
                    \Storage::disk('local')->makeDirectory($destinationDir);
                }

                // Copie + renommage
                foreach ($pdfs as $pdfPath) {

                    $filename = basename($pdfPath);

                    $nIma = pathinfo($filename, PATHINFO_FILENAME);

                    if (!isset($indexMap[$nIma])) {
                        continue;
                    }

                    $newName = trim($indexMap[$nIma]);

                    if ($newName === '') {
                        continue;
                    }

                    // Forcer extension PDF
                    $pathInfo = pathinfo($newName);

                    $baseName = $pathInfo['filename'];

                    $extension = 'pdf';

                    $finalName = $baseName . '.' . $extension;

                    // Gestion des doublons
                    $counter = 1;

                    while (
                        \Storage::disk('local')
                            ->exists($destinationDir . '/' . $finalName)
                    ) {
                        $finalName = $baseName . '_' . $counter . '.' . $extension;
                        $counter++;
                    }

                    $sourceFullPath = storage_path('app/' . $pdfPath);

                    $destinationFullPath = storage_path(
                        'app/' . $destinationDir . '/' . $finalName
                    );

                    \File::copy($sourceFullPath, $destinationFullPath);
                }

                // =====================================================
                // CREATION DU ZIP
                // =====================================================

                $zipName = $codeDossier . '_documents.zip';

                if (\Storage::disk('public')->exists('Exports/' . $zipName)) {
                    \Storage::disk('public')->delete('Exports/' . $zipName);
                }

                $zipPath = storage_path('app/public/Exports/' . $zipName);

                $zip = new \ZipArchive();

                if (
                    $zip->open(
                        $zipPath,
                        \ZipArchive::CREATE | \ZipArchive::OVERWRITE
                    ) !== true
                ) {
                    throw new \Exception("Impossible de créer le ZIP");
                }

                // Seulement les fichiers renommés
                $filesToZip = \Storage::disk('local')
                    ->allFiles($destinationDir);

                foreach ($filesToZip as $file) {

                    $fullPath = storage_path('app/' . $file);

                    if (file_exists($fullPath)) {
                        $zip->addFile($fullPath, basename($file));
                    }
                }

                $zip->close();

                // =====================================================
                // URL ZIP
                // =====================================================

                $response['zip_url'] = $zipName;
                $response['indexed_filename'] = $codeDossier . '_indexe.xlsx';

            }

            return response()->json($response);
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
                            //$value = mb_convert_encoding($value, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
                            $value = $this->fixEncoding($value);
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
                                $value = $this->fixEncoding($value);
                                //$value = mb_convert_encoding($value, 'UTF-8', ['Windows-1252', 'ISO-8859-1', 'UTF-8']);
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

                            $zipPath = $exportDir . DIRECTORY_SEPARATOR . $zipName;

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

    private function normalizeKey($key)
    {
        $key = trim($key);

        $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);

        return strtolower($key);
    }

    public function getListeChoix(Request $request)
    {
        $zDossier = $request->nom_dossier ?? "";
        $zCode_dossier = $request->nom_code_dossier ?? "";

        $basePath = config('normalisation.base_path');
        $zCheminParametreMdb = $basePath
            . DIRECTORY_SEPARATOR . $zDossier
            . DIRECTORY_SEPARATOR . $zCode_dossier
            . DIRECTORY_SEPARATOR . 'Parametre.mdb';

        $systemExploitation = env('SYSTEM_EXPLOITATION');
        $columns = [];
        $pdo = null;

        // 1. Détection des colonnes pour le tri (ordreq vs ordref)
        if ($systemExploitation === 'Windows') {
            $pdo = AccessService::connect($zCheminParametreMdb, null, null);
            $stmt = $pdo->query("SELECT TOP 1 * FROM [LIVRAISON]");
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                $columns[] = strtolower($meta['name']);
            }
        } else {
            $columns = AccessService::getColumns($zCheminParametreMdb, 'LIVRAISON');
        }

        $orderBy = null;
        if (in_array('ordreq', $columns)) {
            $orderBy = 'ordreq';
        } elseif (in_array('ordref', $columns)) {
            $orderBy = 'ordref';
        }

        // 2. Construction de la requête SQL
        $sql = "SELECT [idq], [listechoix] FROM [LIVRAISON]";
        if ($orderBy) {
            $sql .= " ORDER BY [$orderBy] ASC";
        }

        // 3. Récupération des données selon la plateforme
        $rows = [];
        if ($systemExploitation === 'Windows') {
            $rows = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
             $sql = "SELECT idq, listechoix FROM LIVRAISON";
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy ASC";
            }
            $rawRows = AccessService::query($zCheminParametreMdb, $sql);
            foreach ($rawRows as $line) {
                // mdb-sql avec le délimiteur '||'
                $parts = explode('||', $line);
                if (count($parts) >= 2) {
                    $rows[] = [
                        'idq' => trim($parts[0]),
                        'listechoix' => trim($parts[1])
                    ];
                }
            }
        }

        // 4. Traitement et formatage des données
        $result = [];
        foreach ($rows as $row) {
            // Encodage et nettoyage
            $idq = $this->fixEncoding($row['idq']);
            $listechoix = $row['listechoix'];

            if (empty($listechoix)) {
                continue;
            }

            $listechoix = $this->fixEncoding($listechoix);

            // Séparer les choix par #
            $choixArray = explode('#', $listechoix);

            foreach ($choixArray as $choix) {
                $choix = trim($choix);

                // Regex pour extraire "1. Texte"
                if (preg_match('/^(\d+)\.\s*(.*)$/', $choix, $matches)) {
                    $numero = (int)$matches[1];
                    $valeur = trim($matches[2]);

                    $result[$idq][$numero] = $valeur;
                }
            }
        }

        return response()->json($result);
    }

    public function importMappingClient(Request $request)
    {
        if ($request->hasFile('mapping_file')) {
            $file = $request->file('mapping_file');

            // 1. On crée l'instance
            $import = new \App\Imports\MappingImport();

            // 2. On lance l'import
            Excel::import($import, $file);

            // 3. On récupère les lignes grâce à la méthode qu'on a ajoutée
            $rows = $import->getImportedData();

            $mapping = [];
            if ($rows) {
                foreach ($rows as $row) {
                    // Attention : WithHeadingRow transforme les noms en "slug"
                    // (ex: "NOM CLIENT" devient "nom_client")
                    $prod = $row['prod'] ?? $row['PROD'] ?? null;
                    $client = $row['client'] ?? $row['CLIENT'] ?? null;

                    if ($prod && $client) {
                        // NORMALISATION IMPORTANTE
                        $prod = $this->normalizeKeyClient($prod);
                        $mapping[trim($prod)] = trim($client);
                    }
                }
            }

            session(['mapping_file' => $mapping]);
            session()->put('debug_session', 'La session fonctionne');
            session()->save();

            return response()->json([
                'success' => true,
                'count' => count($mapping),
                'debug_mapping' => $mapping // Pour voir immédiatement si c'est vide
            ]);
        }
    }

    /**
 * Fonction utilitaire pour insérer proprement le batch (try/catch ligne par ligne en cas d'erreur globale)
 */
private function insertBatchSafely(array $batch)
{
    try {
        DB::table('source')->insert($batch);
    } catch (\Illuminate\Database\QueryException $e) {
        // En cas d'erreur sur le bloc (ex: une ligne est corrompue), on insère ligne par ligne pour isoler l'erreur
        foreach ($batch as $row) {
            try {
                DB::table('source')->insert($row);
            } catch (\Illuminate\Database\QueryException $e2) {
                logger()->error('ERREUR INSERT LIGNE MDB', [
                    'message' => $e2->getMessage(),
                    'row'     => $row,
                ]);
            }
        }
    }
}

    /**
     * Construire une collection de Consignes à partir du payload du front-end
     */
    private function buildConsignesFromPayload(array $payloadConsignes, $codification_id)
    {
        $consignes = collect();
        $allChamps = Champ::where('codification_id', $codification_id)->get()->keyBy('nom_champ');

        foreach ($payloadConsignes as $consigneData) {
            $consigneId = $consigneData['consigne_id'] ?? null;
            $consigneCode = $consigneData['consigne_code'] ?? null;

            // Chercher la consigne en base pour les infos globales
            $consigneModel = Consigne::find($consigneId);
            if (!$consigneModel) {
                continue;
            }

            // Initialiser les collections de groupes et paramètres
            $consigneModel->setRelation('groupes', collect());
            $consigneModel->setRelation('parametres', collect());

            // Traiter les groupes
            if (isset($consigneData['groupes']) && is_array($consigneData['groupes'])) {
                foreach ($consigneData['groupes'] as $groupeData) {
                    $groupeModel = new \App\Models\Consigne_groupe([
                        'consigne_id' => $consigneId,
                        'ordre' => $groupeData['ordre'] ?? null,
                    ]);

                    // Traiter les champs du groupe
                    $champCollection = collect();
                    if (isset($groupeData['champs']) && is_array($groupeData['champs'])) {
                        foreach ($groupeData['champs'] as $champName) {
                            $champName = strtolower(trim($champName));
                            if (isset($allChamps[$champName])) {
                                $champModel = $allChamps[$champName];
                                $groupeChampModel = new \App\Models\Consigne_groupe_champ([
                                    'consigne_groupe_id' => null,
                                    'champ_id' => $champModel->id,
                                    'ordre' => 1,
                                ]);
                                $groupeChampModel->setRelation('champ', $champModel);
                                $champCollection->push($groupeChampModel);
                            }
                        }
                    }
                    $groupeModel->setRelation('champs', $champCollection);

                    // Traiter les paramètres du groupe
                    if (isset($groupeData['parametres']) && is_array($groupeData['parametres'])) {
                        foreach ($groupeData['parametres'] as $cle => $valeur) {
                            $paramModel = new \App\Models\Parametre_consigne([
                                'consigne_id' => $consigneId,
                                'codification_id' => $codification_id,
                                'cle' => $cle,
                                'valeur' => $valeur,
                                'champ_id' => null,
                            ]);
                            $consigneModel->parametres->push($paramModel);
                        }
                    }

                    $consigneModel->groupes->push($groupeModel);
                }
            }

            // Traiter les paramètres au niveau de la consigne (format liste d'objets)
            if (isset($consigneData['parametres']) && is_array($consigneData['parametres'])) {
                // Si c'est une liste (array_is_list), on traite chaque item avec son champ
                if (array_is_list($consigneData['parametres'])) {
                    foreach ($consigneData['parametres'] as $paramItem) {
                        if (!is_array($paramItem) || empty($paramItem['champ'])) {
                            continue;
                        }

                        $champName = strtolower(trim($paramItem['champ']));
                        $champModel = $allChamps[$champName] ?? null;
                        if (!$champModel) {
                            continue;
                        }

                        foreach ($paramItem as $cle => $valeur) {
                            if ($cle === 'champ') {
                                continue;
                            }

                            $paramModel = new \App\Models\Parametre_consigne([
                                'consigne_id' => $consigneId,
                                'codification_id' => $codification_id,
                                'champ_id' => $champModel->id,
                                'cle' => $cle,
                                'valeur' => $valeur,
                            ]);
                            $consigneModel->parametres->push($paramModel);
                        }
                    }
                }
                // Si c'est un objet associatif, on traite sans champ_id
                else {
                    foreach ($consigneData['parametres'] as $cle => $valeur) {
                        $paramModel = new \App\Models\Parametre_consigne([
                            'consigne_id' => $consigneId,
                            'codification_id' => $codification_id,
                            'cle' => $cle,
                            'valeur' => is_array($valeur) || is_object($valeur) ? json_encode($valeur) : $valeur,
                            'champ_id' => null,
                        ]);
                        $consigneModel->parametres->push($paramModel);
                    }
                }
            }

            $consignes->push($consigneModel);
        }

        return $consignes;
    }

    public function downloadZip($filename)
    {
        $path = storage_path("app/public/Exports/{$filename}");

        if (!file_exists($path)) {
            return response()->json([
                'message' => 'Fichier introuvable'
            ], 404);
        }

        // Exemple :
        // LOT001_documents.zip => LOT001
        $codeDossier = preg_replace(
            '/_documents\.zip$/i',
            '',
            $filename
        );

        // Recherche de la codification pour récupérer le dossier source
        $codification = \App\Models\Codification::where(
            'code_dossier',
            $codeDossier
        )->first();

        register_shutdown_function(function () use ($codeDossier, $codification) {

            try {

                // =====================================================
                // 1. Suppression du dossier temporaire contenant
                // les fichiers renommés
                // =====================================================
                \Storage::disk('local')->deleteDirectory(
                    "fichier_indexe/{$codeDossier}"
                );

                // =====================================================
                // 2. Suppression du dossier source contenant
                // les originaux
                // =====================================================
                if ($codification && !empty($codification->dossier)) {

                    \Storage::disk('local')->deleteDirectory(
                        "fichier_indexe/{$codification->dossier}/{$codeDossier}"
                    );
                }

            } catch (\Exception $e) {

                \Log::error(
                    'Erreur suppression après téléchargement ZIP',
                    [
                        'code_dossier' => $codeDossier,
                        'message' => $e->getMessage()
                    ]
                );
            }
        });

        return response()
            ->download($path)
            ->deleteFileAfterSend(true);
    }

}
