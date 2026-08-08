<?php

namespace App\Controllers;


use App\Repositories\TourDayRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class TourDayController
{

    public function __construct(
        private TourDayRepository $repo
    ){}



    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    )
    {

        $days =
        $this->repo->allByTour(
            (int)$args["id"]
        );


        $response->getBody()
        ->write(
            json_encode($days)
        );


        return $response
        ->withHeader(
            "Content-Type",
            "application/json"
        );

    }





    public function store(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    )
    {

        $data =
        json_decode(
            $request->getBody()->getContents(),
            true
        );


        $id =
        $this->repo->create(
            (int)$args["id"],
            $data
        );


        $response->getBody()
        ->write(
            json_encode([
                "id"=>$id
            ])
        );


        return $response
        ->withHeader(
            "Content-Type",
            "application/json"
        );

    }


}
