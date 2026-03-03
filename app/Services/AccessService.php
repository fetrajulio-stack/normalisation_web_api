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

    public static function mdbConnect($path, $password = '')
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
}
