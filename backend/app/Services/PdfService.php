<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db;
    }

    /**
     * Génère le fichier PDF de la tournée avec Dompdf et retourne son chemin absolu.
     *
     * @param int $tourId
     * @return string|null Chemin absolu du PDF généré ou null en cas d'échec
     */
    public function generateTourPdf(int $tourId): ?string
    {
        // 1. Récupération des données de la tournée
        $tourData = $this->getTourDataFromDb($tourId);

        // 2. Récupération des événements de la tournée
        $events = [];
        if ($this->db) {
            try {
                $sqlEvents = "
                    SELECT
                        tde.id,
                        tde.day_id,
                        td.date AS event_date,
                        tde.event_time,
                        tde.event_type,
                        tde.title,
                        tde.notes
                    FROM tour_days td
                    INNER JOIN tour_day_events tde ON tde.day_id = td.id
                    WHERE td.tour_id = :tour_id
                    ORDER BY td.date ASC, tde.event_time ASC
                ";
                $stmtEvents = $this->db->prepare($sqlEvents);
                $stmtEvents->execute(['tour_id' => $tourId]);
                $events = $stmtEvents->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Exception $e) {
                error_log("ERREUR SQL EVENTS PDF : " . $e->getMessage());
            }
        }

        // 3. Détermination des dates clés pour le nom du fichier
        $firstDate = date('Y-m-d');
        if (!empty($events)) {
            $firstDate = $events[0]['event_date'] ?? date('Y-m-d');
        }

        $rawTourName = $tourData['name'] ?? 'World Tour 2027';
        $slugTourName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $rawTourName);
        $slugTourName = preg_replace('/-+/', '-', $slugTourName);

        // Date de modification (basée sur la date actuelle ou la dernière date d'événement)
        $timestampUpdate = time();
        $updateFormattedForFile = date('d-m-Y_H-i', $timestampUpdate);

        $pdfFilename = sprintf('Roadbook_%s_%s_%s.pdf', $firstDate, $slugTourName, $updateFormattedForFile);

        // 4. Nettoyage des anciens fichiers de cette tournée dans le stockage
        $storageDir = __DIR__ . '/../../storage/tours';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        array_map('unlink', glob($storageDir . '/Roadbook_*_tour_' . $tourId . '_*.pdf'));
        array_map('unlink', glob($storageDir . '/tour_' . $tourId . '.pdf'));
        array_map('unlink', glob($storageDir . '/Roadbook_*.pdf'));

        $pdfPath = $storageDir . '/' . $pdfFilename;

        // 5. Regroupement par jour
        $groupedEvents = [];
        foreach ($events as $event) {
            $dateKey = $event['event_date'] ?? date('Y-m-d');
            $groupedEvents[$dateKey][] = $event;
        }

        // 6. Construction du HTML des événements avec icônes
        $contentHtml = '';
        $publicDir = realpath(__DIR__ . '/../../public');

        if (!empty($groupedEvents)) {
            foreach ($groupedEvents as $dateVal => $dayEvents) {
                $timestamp = strtotime($dateVal);
                $dateFormatted = $timestamp ? $this->formatFrenchDate($timestamp) : $dateVal;

                // Bandeau noir du jour
                $contentHtml .= '<div class="day-banner">' . $dateFormatted . '</div>';
                $contentHtml .= '<table class="events-table">';

                foreach ($dayEvents as $evt) {
                    $timeFormatted = !empty($evt['event_time']) ? substr($evt['event_time'], 0, 5) : '';
                    $typeRaw = strtolower(trim($evt['event_type'] ?? 'default'));
                    $typeFormatted = mb_strtoupper($evt['event_type'] ?? '');
                    $title = htmlspecialchars($evt['title'] ?? '');
                    $notes = !empty($evt['notes']) ? htmlspecialchars($evt['notes']) : '';

                    // Sélection de l'icône
                    $iconFilename = 'default.png';
                    if (str_contains($typeRaw, 'flight') || str_contains($typeRaw, 'vol')) {
                        $iconFilename = 'flight.png';
                    } elseif (str_contains($typeRaw, 'hotel') || str_contains($typeRaw, 'hebergement')) {
                        $iconFilename = 'hotel.png';
                    } elseif (str_contains($typeRaw, 'show') || str_contains($typeRaw, 'concert')) {
                        $iconFilename = 'show.png';
                    } elseif (str_contains($typeRaw, 'train')) {
                        $iconFilename = 'train.png';
                    } elseif (str_contains($typeRaw, 'road') || str_contains($typeRaw, 'limousine') || str_contains($typeRaw, 'transfert')) {
                        $iconFilename = 'road.png';
                    }

                    $iconPath = $publicDir . '/assets/icons/' . $iconFilename;
                    if (!file_exists($iconPath)) {
                        $iconPath = $publicDir . '/assets/icons/default.png';
                    }

                    $iconImgTag = '';
                    if (file_exists($iconPath)) {
                        $iconData = base64_encode(file_get_contents($iconPath));
                        $iconImgTag = '<img src="data:image/png;base64,' . $iconData . '" class="event-icon" />';
                    }

                    $contentHtml .= '
                    <tr>
                        <td class="col-time">' . $timeFormatted . '</td>
                        <td class="col-icon">' . $iconImgTag . '</td>
                        <td class="col-type">' . $typeFormatted . '</td>
                        <td class="col-title">' . $title . '</td>
                        <td class="col-notes">' . $notes . '</td>
                    </tr>';
                }
                $contentHtml .= '</table>';
            }
        } else {
            $contentHtml = '<p style="text-align: center; color: #777; margin-top: 30px;">Aucune étape enregistrée pour cette tournée.</p>';
        }

        $tourName = htmlspecialchars($rawTourName);
        $destination = htmlspecialchars($tourData['destination'] ?? 'Europe');
        $generatedAt = date('d/m/Y à H:i');
        $updatedAt = date('d/m/Y à H:i', $timestampUpdate);

        // Encodage propre du logo en Base64 (agrandi)
        $logoPath = $publicDir . '/assets/logo.png';
        $logoHtml = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoHtml = '<img src="data:image/png;base64,' . $logoData . '" class="top-logo" />';
        } else {
            $logoHtml = '<div class="logo-text">ROADLINE<br><span class="brand-sub">MGT</span></div>';
        }

        // 7. Template HTML global
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 0; }
                .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
                .logo-section { width: 40%; vertical-align: top; }
                .top-logo { max-width: 190px; height: auto; display: block; }
                .logo-text { font-weight: bold; font-size: 15px; color: #111; letter-spacing: 0.5px; }
                .brand-sub { font-size: 22px; font-weight: bold; color: #6f42c1; letter-spacing: 1px; }
                
                .title-section { width: 60%; text-align: right; vertical-align: top; }
                .tour-title { font-size: 20px; font-weight: bold; color: #111827; text-transform: uppercase; margin: 0; }
                .tour-subtitle { font-size: 12px; color: #374151; margin-top: 3px; font-weight: bold; }
                .tour-update { font-size: 10px; color: #6b7280; margin-top: 2px; }
                
                .green-line { width: 100%; height: 3px; background-color: #10b981; margin-top: 8px; margin-bottom: 20px; border: none; }
                
                .day-banner { background-color: #111827; color: #ffffff; padding: 6px 10px; font-weight: bold; font-size: 11px; text-transform: uppercase; margin-top: 15px; margin-bottom: 0; letter-spacing: 0.5px; }
                
                .events-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                .events-table td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
                .col-time { width: 12%; font-weight: bold; color: #059669; font-size: 12px; }
                .col-icon { width: 6%; text-align: center; }
                .event-icon { width: 16px; height: 16px; vertical-align: middle; }
                .col-type { width: 22%; font-weight: bold; color: #4b5563; font-size: 10px; text-transform: uppercase; }
                .col-title { width: 30%; font-weight: bold; color: #111827; font-size: 12px; }
                .col-notes { width: 30%; color: #4b5563; font-style: italic; font-size: 11px; }
            </style>
        </head>
        <body>
            <table class="header-table">
                <tr>
                    <td class="logo-section">
                        ' . $logoHtml . '
                    </td>
                    <td class="title-section">
                        <div class="tour-title">' . $tourName . '</div>
                        <div class="tour-subtitle">Destination : ' . $destination . '</div>
                        <div class="tour-update">Généré le ' . $generatedAt . '</div>
                        <div class="tour-update">Dernière modification : ' . $updatedAt . '</div>
                    </td>
                </tr>
            </table>
            <div class="green-line"></div>

            ' . $contentHtml . '
        </body>
        </html>';

        try {
            $options = new Options();
            $options->set('defaultFont', 'Helvetica');
            $options->setIsRemoteEnabled(true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($pdfPath, $dompdf->output());

            if (file_exists($pdfPath)) {
                return $pdfPath;
            }

        } catch (\Exception $e) {
            error_log("ERREUR CRITIQUE DOMPDF : " . $e->getMessage());
        }

        return null;
    }

    /**
     * Formate une date en français sans dépendre de l'extension intl
     */
    private function formatFrenchDate(int $timestamp): string
    {
        $days = ['DIMANCHE', 'LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI'];
        $months = ['', 'JANVIER', 'FEVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOUT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DECEMBRE'];

        $dayName = $days[date('w', $timestamp)];
        $dayNum = date('j', $timestamp);
        $monthName = $months[date('n', $timestamp)];
        $year = date('Y', $timestamp);

        return "{$dayName} {$dayNum} {$monthName} {$year}";
    }

    private function getTourDataFromDb(int $tourId): array
    {
        if (!$this->db) {
            return ['name' => 'World Tour 2027', 'destination' => 'Europe'];
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM tours WHERE id = ?");
            $stmt->execute([$tourId]);
            $tour = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $tour ?: ['name' => 'World Tour 2027', 'destination' => 'Europe'];
        } catch (\Exception $e) {
            return ['name' => 'World Tour 2027', 'destination' => 'Europe'];
        }
    }
}
