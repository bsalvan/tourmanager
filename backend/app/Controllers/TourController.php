<?php

namespace App\Controllers;

use App\Repositories\TourRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TourController
{
    public function __construct(
        private TourRepository $repo
    ){}

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ){
        $data = $this->repo->all();

        $response->getBody()
        ->write(
            json_encode($data)
        );

        return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    public function store(
        ServerRequestInterface $request,
        ResponseInterface $response
    ){
        $body =
        json_decode(
            $request->getBody()->getContents(),
            true
        );

        $id =
        $this->repo->create($body);

        $response->getBody()
        ->write(
            json_encode([
                "id"=>$id
            ])
        );

        return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
    }

    // --- NOUVELLE MÉTHODE INTÉGRÉE POUR L'ENVOI D'EMAIL ---
    public function sendEmail(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = [] // Paramètre standard PSR-7 (ex: Slim) pour récupérer les variables d'URL
    ){
        // 1. Récupération de l'ID de la tournée depuis l'URL
        // Adaptez 'id' ou 'tour_id' selon le nom de la variable définie dans votre routeur public/index.php
        $tourId = $args['id'] ?? $args['tour_id'] ?? null;

        // 2. Lecture du corps de la requête envoyée par Vue.js
        $body = json_decode($request->getBody()->getContents(), true);

        $targetType = $body['target_type'] ?? 'individual';
        $listId     = $body['list_id'] ?? null;
        $recipients = $body['recipients'] ?? [];
        $note       = $body['note'] ?? '';

        // 3. Déterminer la liste des emails destinataires
        $emailList = [];
        if ($targetType === 'list' && $listId) {
            // Optionnel : Logique pour récupérer les emails si on choisit une liste préétablie
            // $emailList = $this->repo->getEmailsByListId($listId);
        } else {
            $emailList = $recipients;
        }

        // Vérification si la liste est vide
        if (empty($emailList)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Aucun destinataire sélectionné.'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // 4. Appeler le service d'email (Brevo)
        // Note: Assurez-vous que le chemin du namespace correspond à votre autoloader.
        // D'après votre arborescence, le service est probablement dans \Services\EmailService ou \App\Services\EmailService
        $emailService = new \Services\EmailService();
        
        $result = $emailService->sendTourPdf($tourId, $emailList, $note);

        // 5. Retourner la réponse HTTP correspondante
        if ($result) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . count($emailList) . ' destinataire(s).'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => "Échec de l'envoi de l'email via Brevo."
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
