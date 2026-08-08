<?php

namespace App;


use PDO;


class Database
{

    public static function connect(): PDO
    {

        $config = parse_ini_file(
            __DIR__ . '/../.env'
        );


        $dsn =
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4";


        return new PDO(
            $dsn,
            $config['DB_USER'],
            $config['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC
            ]
        );

    }

}
