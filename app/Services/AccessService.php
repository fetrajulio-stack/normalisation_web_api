<?php
namespace App\Services;

class AccessService
{
    public static function connect($path,$username,$password)
    {
        return new \PDO(
            "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$path;",
            $username,
            $password
        );
    }

     public static function query($path, $sql)
    {
        // $path = storage_path('app/test3.mdb');
        // On utilise mdb-sql.
        // -H : pas d'entêtes
        // -P : pas de pagination (une seule ligne continue)
        // -d : délimiteur personnalisé pour le split
        $command = "echo " . escapeshellarg($sql) . " | mdb-sql -H -P -d '||' " . escapeshellarg($path);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [];
        }

        // On nettoie le résultat pour en faire un tableau associatif
        // Note: mdb-sql avec -H ne renvoie pas les noms des colonnes.
        return $output;
    }
     /**
     * LINUX: Exécute une requête SQL et retourne un tableau associatif
     */
    public static function linuxQueryAssoc($path, $table, $sql)
    {
        $columns = self::getColumns($path, $table);
        $delimiter = '|#|'; 
        
        $command = "echo " . escapeshellarg($sql) . " | mdb-sql -H -P -d " . escapeshellarg($delimiter) . " " . escapeshellarg($path);
        exec($command, $output, $returnCode);

        // dd($command, $output, $returnCode);
        $results = [];
        if ($returnCode !== 0 || empty($output)) {
            return $results;
        }

        $colCount = count($columns);

        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $values = explode($delimiter, $line);
            $values = array_map('trim', $values);

            if ($colCount > 0 && count($values) === $colCount) {
                $results[] = array_combine($columns, $values);
            } else {
                $results[] = $values;
            }
        }

        return $results;
    }

    public static function getColumns($path, $table)
    {
        // Récupère uniquement la première ligne (les noms des colonnes) via mdb-export
        $command = "mdb-export -i " . escapeshellarg($path) . " " . escapeshellarg($table) . " | head -n 1";
        $line = shell_exec($command);
        if (!$line) return [];

        return array_map('strtolower', str_getcsv(trim($line)));
    }

    public static function mdbConnect_old($path, $password = '')
    {
        $connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$path;";

       // $conn = odbc_connect($connStr, '', $password);

        $conn = odbc_connect(
            $connStr .
            "Dbq=$path;" .
            "PWD=$password;",
            '',
            ''
        );

        if (!$conn) {
            throw new \Exception(odbc_errormsg());
        }

        return $conn;
    }

    public static function mdbConnect($path, $password = '')
    {
        $tempPath = null;

        // Vérifier si le chemin contient des caractères accentués
        if (preg_match('/[^\x00-\x7F]/', $path)) {
            // Créer un fichier temporaire sans accents
            $tempDir  = sys_get_temp_dir();
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'temp_mdb_' . uniqid() . '.mdb';

            if (!copy($path, $tempPath)) {
                throw new \Exception("Impossible de copier le fichier MDB: $path");
            }

            $pathToUse = $tempPath;
        } else {
            $pathToUse = $path;
        }

        try {
            $connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$pathToUse;PWD=$password;";
            $conn = odbc_connect($connStr, '', '');

            if (!$conn) {
                throw new \Exception("Echec connexion MDB: " . odbc_errormsg());
            }

            return [
                'conn'     => $conn,
                'tempPath' => $tempPath, // pour supprimer après usage
            ];

        } catch (\Exception $e) {
            // Nettoyer le fichier temp en cas d'erreur
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }
}
