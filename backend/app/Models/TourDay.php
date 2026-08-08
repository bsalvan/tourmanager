<?php

namespace App\Models;


class TourDay
{

    public ?int $id = null;

    public int $tour_id;

    public int $day_number;

    public string $date;

    public ?string $country = null;

    public ?string $city = null;

    public ?string $venue = null;

    public ?string $notes = null;


    public function toArray(): array
    {
        return [
            "id"=>$this->id,
            "tour_id"=>$this->tour_id,
            "day_number"=>$this->day_number,
            "date"=>$this->date,
            "country"=>$this->country,
            "city"=>$this->city,
            "venue"=>$this->venue,
            "notes"=>$this->notes
        ];
    }

}
