<?php

namespace App\Repositories;


use PDO;


class TourDayRepository
{

    public function __construct(
        private PDO $db
    ){}



    public function allByTour(int $tourId): array
    {

        $stmt =
        $this->db->prepare(
            "SELECT *
             FROM tour_days
             WHERE tour_id = ?
             ORDER BY day_number"
        );


        $stmt->execute([$tourId]);


        return $stmt->fetchAll();

    }



    public function create(
        int $tourId,
        array $data
    ): int
    {

        $sql =
        "INSERT INTO tour_days
        (
            tour_id,
            day_number,
            date,
            country,
            city,
            venue,
            notes
        )
        VALUES
        (
            :tour_id,
            :day_number,
            :date,
            :country,
            :city,
            :venue,
            :notes
        )";


        $stmt =
        $this->db->prepare($sql);


        $stmt->execute([

            "tour_id"=>$tourId,
            "day_number"=>$data["day_number"],
            "date"=>$data["date"],
            "country"=>$data["country"] ?? null,
            "city"=>$data["city"] ?? null,
            "venue"=>$data["venue"] ?? null,
            "notes"=>$data["notes"] ?? null

        ]);


        return (int)$this->db->lastInsertId();

    }

}
