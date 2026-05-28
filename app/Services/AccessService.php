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
        // dd($path, $sql,escapeshellarg($sql));
        $command = "echo " . escapeshellarg($sql) . " | mdb-sql -H -P -d '||' " . escapeshellarg($path);

        exec($command, $output, $returnCode);
        //dd($command, $output, $returnCode);

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
        // dd($path, $table, $sql);
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
        //dd($columns, $output);

        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line)) continue;

             // 2. IGNORER LE MESSAGE DE MDB-SQL ICI :
            if (strpos($line, 'Rows retrieved') !== false ||strpos($line, 'Row retrieved') !== false) {
                continue; // On passe à la ligne suivante (ou on termine la boucle)
            }

            $values = explode($delimiter, $line);
            $values = array_map('trim', $values);

            if ($colCount > 0 && count($values) === $colCount) {
                $results[] = array_combine($columns, $values);
            } else {
                // Eviter de renvoyer un array numérique : sinon le pipeline ne peut plus mapper les clés.
                // On retourne quand même un mapping best-effort sur les min-colonnes.
                $min = min($colCount, count($values));
                if ($min > 0) {
                    $results[] = array_combine(array_slice($columns, 0, $min), array_slice($values, 0, $min));
                }
            }
        }

        //dd($columns, $results);

        return $results;
    }

    public static function getColumns($path, $table)
    {
        // Récupère uniquement la première ligne (les noms des colonnes) via mdb-export
        $command = "mdb-export " . escapeshellarg($path) . " " . escapeshellarg($table) . " | head -n 1";
        $line = shell_exec($command);
        if (!$line) return [];

        $line = trim(preg_replace("/\r\n?|\n/", "", $line));
        $headers = str_getcsv($line);

        // Normalisation stricte des noms de colonnes pour matcher les clés attendues
        return array_map(function ($h) {
            $h = trim((string)$h);
            $h = strtolower($h);
            $h = preg_replace('/\s+/', '_', $h);
            $h = preg_replace('/[^a-z0-9_\-]/', '', $h);
            return $h;
        }, $headers);
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
