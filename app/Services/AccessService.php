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
