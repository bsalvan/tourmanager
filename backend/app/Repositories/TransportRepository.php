<?php

namespace App\Repositories;


use PDO;


class TransportRepository
{

    public function __construct(
        private PDO $db
    ){}



    public function allByDay(int $dayId): array
    {

        $stmt =
        $this->db->prepare(
            "SELECT *
             FROM transports
             WHERE tour_day_id = ?
             ORDER BY departure_time"
        );


        $stmt->execute([$dayId]);


        return $stmt->fetchAll();

    }




    public function create(
        int $dayId,
        array $data
    ): int
    {

        $sql =
        "INSERT INTO transports
        (
            tour_day_id,
            type,
            departure,
            arrival,
            departure_time,
            arrival_time,
            provider,
            reference_number,
            notes
        )
        VALUES
        (
            :tour_day_id,
            :type,
            :departure,
            :arrival,
            :departure_time,
            :arrival_time,
            :provider,
            :reference_number,
            :notes
        )";


        $stmt =
        $this->db->prepare($sql);


        $stmt->execute([

            "tour_day_id"=>$dayId,
            "type"=>$data["type"],
            "departure"=>$data["departure"] ?? null,
            "arrival"=>$data["arrival"] ?? null,
            "departure_time"=>$data["departure_time"] ?? null,
            "arrival_time"=>$data["arrival_time"] ?? null,
            "provider"=>$data["provider"] ?? null,
            "reference_number"=>$data["reference_number"] ?? null,
            "notes"=>$data["notes"] ?? null

        ]);


        return (int)$this->db->lastInsertId();

    }

}
