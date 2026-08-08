<?php

namespace App\Controllers;


use App\Repositories\TransportRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;



class TransportController
{

    public function __construct(
        private TransportRepository $repo
    ){}



    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    )
    {

        $data =
        $this->repo->allByDay(
            (int)$args["id"]
        );


        $response->getBody()
        ->write(
            json_encode($data)
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
