<?php

namespace App\Repositories;

use PDO;


class TourRepository
{

    public function __construct(
        private PDO $db
    )
    {}


    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM tours ORDER BY start_date"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public function create(array $data): int
    {

        $sql =
        "INSERT INTO tours
        (
            name,
            destination,
            start_date,
            end_date
        )
        VALUES
        (
            :name,
            :destination,
            :start_date,
            :end_date
        )";


        $stmt = $this->db->prepare($sql);


        $stmt->execute($data);


        return (int)$this->db->lastInsertId();

    }

}
