<?php

namespace App\Models;


class Transport
{

    public ?int $id = null;

    public int $tour_day_id;

    public string $type;

    public ?string $departure = null;

    public ?string $arrival = null;

    public ?string $departure_time = null;

    public ?string $arrival_time = null;

    public ?string $provider = null;

    public ?string $reference_number = null;

    public ?string $notes = null;



    public function toArray(): array
    {
        return [
            "id"=>$this->id,
            "tour_day_id"=>$this->tour_day_id,
            "type"=>$this->type,
            "departure"=>$this->departure,
            "arrival"=>$this->arrival,
            "departure_time"=>$this->departure_time,
            "arrival_time"=>$this->arrival_time,
            "provider"=>$this->provider,
            "reference_number"=>$this->reference_number,
            "notes"=>$this->notes
        ];
    }

}
