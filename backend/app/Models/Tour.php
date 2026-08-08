<?php

namespace App\Models;


class Tour
{
    public ?int $id = null;

    public string $name;

    public ?string $destination = null;

    public ?string $start_date = null;

    public ?string $end_date = null;


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'destination' => $this->destination,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date
        ];
    }
}
