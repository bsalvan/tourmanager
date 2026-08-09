<?php

namespace App\Services;

class EmailService
{
    private string $apiKey;
    private string $senderEmail;
    private string $senderName;

    public function __construct()
    {
        $envFile = '/var/www/tour-app/backend/.env';
        $env = parse_ini_file($envFile);
        
        // Debug rapide
        if (empty($env['BREVO_API_KEY'])) {
            error_log("DEBUG: Clé vide. Contenu env: " . print_r($env, true));
        }
        
        // Récupération des paramètres via $_ENV, getenv ou lecture directe du .env
        $this->apiKey = $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?? ($env['BREVO_API_KEY'] ?? '');
        $this->senderEmail = $_ENV['BREVO_SENDER_EMAIL'] ?? getenv('BREVO_SENDER_EMAIL') ?? ($env['BREVO_SENDER_EMAIL'] ?? 'contact@roadline.com');
        $this->senderName = $_ENV['BREVO_SENDER_NAME'] ?? getenv('BREVO_SENDER_NAME') ?? ($env['BREVO_SENDER_NAME'] ?? 'Roadline MGT');
    }

    /**
     * Envoie un email transactionnel avec ou sans pièce jointe PDF via l'API Brevo v3.
     *
     * @param array $recipients Liste de tableaux [['email' => '...', 'name' => '...']]
     * @param string $subject Sujet du mail
     * @param string $htmlContent Contenu de l'email au format HTML
     * @param string|null $pdfPath Chemin absolu du fichier PDF à joindre
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function sendEmailWithPdf(array $recipients, string $subject, string $htmlContent, ?string $pdfPath = null): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error'   => 'Clé API Brevo non configurée dans le fichier .env.'
            ];
        }

        // Formatage standardisé des destinataires pour Brevo
        $formattedTo = [];
        foreach ($recipients as $recipient) {
            if (is_array($recipient) && !empty($recipient['email'])) {
                $formattedTo[] = [
                    'email' => trim($recipient['email']),
                    'name'  => !empty($recipient['name']) ? trim($recipient['name']) : trim($recipient['email'])
                ];
            } elseif (is_string($recipient) && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $formattedTo[] = [
                    'email' => trim($recipient),
                    'name'  => trim($recipient)
                ];
            }
        }

        if (empty($formattedTo)) {
            return [
                'success' => false,
                'error'   => 'Aucune adresse e-mail valide fournie.'
            ];
        }

        // Payload de base pour l'API Brevo v3
        $payload = [
            'sender' => [
                'name'  => $this->senderName,
                'email' => $this->senderEmail
            ],
            'to'          => $formattedTo,
            'subject'     => $subject,
            'htmlContent' => $htmlContent
        ];

        // Traitement sécurisé de la pièce jointe PDF si fournie
        if ($pdfPath) {
            if (!file_exists($pdfPath)) {
                error_log("ERREUR PDF : Le fichier n'existe pas à l'emplacement : " . $pdfPath);
            } elseif (!is_readable($pdfPath)) {
                error_log("ERREUR PDF : Le fichier n'est pas lisible (problème de permissions) : " . $pdfPath);
            } else {
                $pdfContent = file_get_contents($pdfPath);
                if ($pdfContent !== false) {
                    $fileName = basename($pdfPath);
                    $payload['attachment'] = [
                        [
                            'name'    => $fileName,
                            'content' => base64_encode($pdfContent)
                        ]
                    ];
                } else {
                    error_log("ERREUR PDF : Impossible de lire le contenu du fichier : " . $pdfPath);
                }
            }
        }

        // Appel API HTTP via cURL
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json'
            ],
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error'   => 'Erreur cURL : ' . $error
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true];
        }

        $responseData = json_decode($response, true);
        $errorMessage = $responseData['message'] ?? $responseData['code'] ?? 'Erreur lors de l\'envoi via Brevo (HTTP ' . $httpCode . ')';

        return [
            'success' => false,
            'error'   => $errorMessage
        ];
    }
}
