<?php
// Désactiver l'affichage des erreurs en production/API pour ne pas polluer le JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
error_log("URI reçue : " . $_SERVER['REQUEST_URI']);
session_start();

// En-têtes CORS et Content-Type JSON
// Autoriser l'origine du frontend (ou '*' pour tout autoriser en développement)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Lecture manuelle du .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// --- SÉCURITÉ : BLOCAGE D'ACCÈS DIRECT AUX FICHIERS SENSIBLES ---
if (preg_match('/\.(env|ini|csv|log)$/i', $uri) || str_contains($uri, '/logs/')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit']);
    exit();
}

// Connexion à la BDD via Variables d'environnement (.env)
try {
    $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'tourmanager';
    $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'touruser';
    $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    // TRACE DE DEBUG : Écrit les paramètres dans le fichier error.log de PHP
    error_log("DEBUG BDD -> Host: $dbHost | Port: $dbPort | DB: $dbName | User: $dbUser | Pass length: " . strlen($dbPass));

    $db = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // Renvoie l'erreur exacte au navigateur pour le test
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur de connexion BDD : ' . $e->getMessage(),
        'debug' => "Host: $dbHost, Port: $dbPort, DB: $dbName, User: $dbUser"
    ]);
    exit();
}

$userRepo = new \App\Repositories\UserRepository($db);

// --- ROUTE 1: CONNEXION & DÉCONNEXION ---
if (($uri === '/api/login' || $uri === '/api/login/') && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
        exit();
    }

    $user = $userRepo->authenticate($email, $password);
    if ($user) {
        $_SESSION['user'] = $user;
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants ou mot de passe incorrects']);
    }
    exit();
}

if ($uri === '/api/logout' && $method === 'POST') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit();
}

// --- ROUTE 2: MEMBRES / UTILISATEURS / CONTACTS ---
if (
    str_starts_with($uri, '/api/users') ||
    str_starts_with($uri, '/api/contacts') ||
    str_starts_with($uri, '/api/persons') ||
    str_starts_with($uri, '/api/members')
) {
    $pathParts = explode('/', trim($uri, '/'));
    $entityId = (isset($pathParts[2]) && is_numeric($pathParts[2])) ? (int)$pathParts[2] : null;

    if ($method === 'GET') {
        try {
            if ($entityId) {
                $stmt = $db->prepare("SELECT id, name, email, phone, role, 'contact' AS source FROM contacts WHERE id = :id");
                $stmt->execute(['id' => $entityId]);
                $item = $stmt->fetch();

                if (!$item) {
                    $stmt = $db->prepare("SELECT id, name, email, phone, role, 'user' AS source FROM users WHERE id = :id");
                    $stmt->execute(['id' => $entityId]);
                    $item = $stmt->fetch();
                }

                echo json_encode($item ?: new stdClass());
            } else {
                $sql = "
                    SELECT id, name, email, phone, role, 'user' AS source FROM users
                    UNION ALL
                    SELECT id, name, email, phone, role, 'contact' AS source FROM contacts
                    ORDER BY name ASC
                ";
                $stmt = $db->query($sql);
                $members = $stmt->fetchAll() ?: [];

                echo json_encode($members);
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'POST' && !$entityId) {
        $input = json_decode(file_get_contents('php://input'), true);

        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $role  = trim($input['role'] ?? 'other');

        if (empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Le nom et l\'email sont requis.']);
            exit();
        }

        try {
            $stmtCheck = $db->prepare("
                SELECT id FROM contacts WHERE email = :email
                UNION
                SELECT id FROM users WHERE email = :email
            ");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé par un autre contact ou utilisateur.']);
                exit();
            }
        } catch (\PDOException $e) {}

        try {
            $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, role) VALUES (:name, :email, :phone, :role)");
            $stmt->execute([
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'role'  => $role
            ]);
            $newId = $db->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'id'    => (int)$newId,
                'name'   => $name,
                'email'  => $email,
                'phone'  => $phone,
                'role'   => $role,
                'source' => 'contact'
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du contact : ' . $e->getMessage()]);
        }
        exit();
    }

    if (($method === 'PUT' || $method === 'POST') && $entityId) {
        $input = json_decode(file_get_contents('php://input'), true);

        $name   = trim($input['name'] ?? '');
        $email  = trim($input['email'] ?? '');
        $phone  = trim($input['phone'] ?? '');
        $role   = trim($input['role'] ?? '');

        $source = str_contains($uri, '/users') ? 'users' : 'contacts';

        if (empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom et l\'email sont obligatoires']);
            exit();
        }

        try {
            $sql = "UPDATE {$source} SET name = :name, email = :email, phone = :phone";
            $params = ['name' => $name, 'email' => $email, 'phone' => $phone, 'id' => $entityId];

            if (!empty($role)) {
                $sql .= ", role = :role";
                $params['role'] = $role;
            }
            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Membre mis à jour avec succès']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit();
    }
}

// --- ROUTE 3: PROFIL DE L'AGENCE (company_settings) ---
if (str_starts_with($uri, '/api/company') || str_starts_with($uri, '/api/companies')) {
    if ($method === 'GET') {
        try {
            $stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
            $company = $stmt->fetch();

            if ($company) {
                $company['name'] = $company['company_name'] ?? $company['name'] ?? '';
                echo json_encode($company);
            } else {
                echo json_encode(new stdClass());
            }
        } catch (\PDOException $e) {
            echo json_encode(new stdClass());
        }
        exit();
    }

    if ($method === 'PUT' || $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $stmt = $db->prepare("
                UPDATE company_settings
                SET company_name = :name, email = :email, phone = :phone, address = :address
                WHERE id = 1
            ");
            $stmt->execute([
                'name'    => $input['company_name'] ?? $input['name'] ?? '',
                'email'   => $input['email'] ?? '',
                'phone'   => $input['phone'] ?? '',
                'address' => $input['address'] ?? ''
            ]);
            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

// --- ROUTE 4: TOURNÉES (TOURS) ET ENVOI EMAIL DE TOURNEE ---

// --- SOUS-ROUTE : TÉLÉCHARGEMENT DU PDF DE LA TOURNÉE (GET /api/tours/{id}/download) ---
if (preg_match('#^/api/tours/(\d+)/download$#', $uri, $matches) && $method === 'GET') {
    $tourId = (int)$matches[1];

    $pdfPath = null;
    try {
        if (class_exists('\\App\\Services\\PdfService')) {
            $pdfService = new \App\Services\PdfService($db);
            $pdfPath = $pdfService->generateTourPdf($tourId);
        }
    } catch (\Exception $e) {
        error_log("Erreur lors de la génération du PDF pour téléchargement : " . $e->getMessage());
    }

    if ($pdfPath && file_exists($pdfPath)) {
        $filename = basename($pdfPath);
        
        // Annuler les en-têtes JSON définis au début du fichier pour laisser place au PDF
        header_remove('Content-Type');
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdfPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($pdfPath);
        exit();
    } else {
        http_response_code(404);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Impossible de générer ou trouver le fichier PDF de la tournée.']);
        exit();
    }
}

if (str_starts_with($uri, '/api/tours')) {

    // SOUS-ROUTE : Envoi d'e-mail spécifique pour une tournée (POST /api/tours/{id}/send-email)
    if (preg_match('#^/api/tours/(\d+)/send-email$#', $uri, $matches) && $method === 'POST') {
        $tourId = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);

        $listId = $input['list_id'] ?? null;
        $recipientsInput = $input['recipients'] ?? [];
        $note = $input['note'] ?? '';
        $subject = "Roadline MGT - Feuille de Route (Tournée #{$tourId})";

        // Construction du message HTML intégrant la note personnalisée si elle existe
        $message = '<p>Veuillez trouver ci-joint la feuille de route de la tournée.</p>';
        if (!empty(trim($note))) {
            $message .= '<p><strong>Message personnalisé :</strong><br>' . nl2br(htmlspecialchars($note)) . '</p>';
        }

        // Récupération des destinataires si une liste est sélectionnée
        $recipients = [];
        if ($listId && empty($recipientsInput)) {
            $stmt = $db->prepare("
                SELECT name, email FROM (
                    SELECT u.name, u.email, dlc.list_id FROM users u JOIN distribution_list_contacts dlc ON u.id = dlc.contact_id AND dlc.entity_type = 'user'
                    UNION
                    SELECT c.name, c.email, dlc.list_id FROM contacts c JOIN distribution_list_contacts dlc ON c.id = dlc.contact_id AND dlc.entity_type = 'contact'
                ) AS recipients_list
                WHERE list_id = :list_id AND email IS NOT NULL AND TRIM(email) != ''
            ");
            $stmt->execute(['list_id' => $listId]);
            $rawRecipients = $stmt->fetchAll();

            // Nettoyage et validation stricte pour chaque e-mail
            foreach ($rawRecipients as $r) {
                $cleanEmail = filter_var(trim($r['email']), FILTER_SANITIZE_EMAIL);
                if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = [
                        'name' => trim($r['name'] ?? '') ?: 'Destinataire',
                        'email' => $cleanEmail
                    ];
                }
            }
        } elseif (!empty($recipientsInput)) {
            // Si ce sont des emails saisis individuellement
            foreach ($recipientsInput as $email) {
                $cleanEmail = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
                if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = [
                        'name' => '',
                        'email' => $cleanEmail
                    ];
                }
            }
        }

        if (empty($recipients)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Aucun destinataire valide trouvé pour cet envoi.']);
            exit();
	}

	// --- GESTION DE LA PIÈCE JOINTE (PDF DE LA TOURNÉE) ---
        $pdfPath = null;
        $potentialPath = __DIR__ . "/../storage/tours/tour_{$tourId}.pdf";

        error_log("Vérification du PDF pour la tournée #{$tourId} à l'chemin : " . $potentialPath);

        if (file_exists($potentialPath)) {
            $pdfPath = $potentialPath;
            error_log("Succès : Le fichier PDF existe déjà sur le disque.");
        } else {
            error_log("Information : Le PDF n'existe pas, tentative de génération...");
            try {
                if (class_exists('\\App\\Services\\PdfService')) {
                    $pdfService = new \App\Services\PdfService($db);
                    $pdfPath = $pdfService->generateTourPdf($tourId);
                    error_log("Génération du PDF réussie, chemin retourné : " . ($pdfPath ?? 'null'));
                } else {
                    error_log("Erreur : La classe PdfService n'existe pas.");
                }
            } catch (\Exception $e) {
                error_log("Exception lors de la génération du PDF : " . $e->getMessage());
            }
        }

        $emailService = new \App\Services\EmailService();
        $result = $emailService->sendEmailWithPdf($recipients, $subject, $message, $pdfPath);

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Email et planning envoyés avec succès via Brevo !']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Erreur inconnue lors de l\'envoi Brevo']);
        }
        exit();
    }

    $pathParts = explode('/', trim($uri, '/'));
    $tourId = null;

    if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
        $tourId = (int)$pathParts[2];
    } elseif (isset($_GET['id'])) {
        $tourId = (int)$_GET['id'];
    }

    if ($method === 'GET') {
        try {
            if ($tourId) {
                $stmt = $db->prepare("SELECT * FROM tours WHERE id = :id");
                $stmt->execute(['id' => $tourId]);
                $tour = $stmt->fetch();

                if ($tour) {
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
                        $stmtEvents = $db->prepare($sqlEvents);
                        $stmtEvents->execute(['tour_id' => $tourId]);
                        $tour['events'] = $stmtEvents->fetchAll() ?: [];
                    } catch (\PDOException $e) {
                        $tour['events'] = [];
                    }
                }
                echo json_encode($tour ?: new stdClass());
            } else {
                $stmt = $db->query("SELECT * FROM tours ORDER BY id DESC");
                $tours = $stmt->fetchAll();
                echo json_encode($tours ?: []);
            }
        } catch (\PDOException $e) {
            echo json_encode([]);
        }
        exit();
    }
}

// --- ROUTE 5: PARAMÈTRES (SETTINGS) ---
if (str_starts_with($uri, '/api/settings') && $method === 'GET') {
    try {
        $stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
        $settings = $stmt->fetch();
        if ($settings) {
            $settings['name'] = $settings['company_name'] ?? '';
        }
        echo json_encode($settings ?: new stdClass());
    } catch (\PDOException $e) {
        echo json_encode(new stdClass());
    }
    exit();
}

// --- ROUTE 6: GESTION DES LISTES DE DISTRIBUTION ---
if (str_starts_with($uri, '/api/distribution-lists')) {
    $pathParts = explode('/', trim($uri, '/'));
    $listIdFromPath = (isset($pathParts[2]) && is_numeric($pathParts[2])) ? (int)$pathParts[2] : null;
    $listId = $_GET['id'] ?? $listIdFromPath;

    $extractFormattedContacts = function($rawContacts) use ($db) {
        $items = [];
        if (is_array($rawContacts)) {
            foreach ($rawContacts as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $items[] = [
                        'id'   => (int)$item['id'],
                        'type' => (isset($item['type']) && $item['type'] === 'user') ? 'user' : 'contact'
                    ];
                } elseif (is_numeric($item)) {
                    $id = (int)$item;
                    $type = 'contact';
                    try {
                        $stmtCheckU = $db->prepare("SELECT id FROM users WHERE id = :id");
                        $stmtCheckU->execute(['id' => $id]);
                        if ($stmtCheckU->fetch()) {
                            $type = 'user';
                        }
                    } catch (\PDOException $e) {}
                    $items[] = ['id' => $id, 'type' => $type];
                }
            }
        }
        return $items;
    };

    if ($method === 'GET') {
        try {
            $stmt = $db->query("
                SELECT dl.id, dl.name, dl.description, dl.created_at, dl.updated_at
                FROM distribution_lists dl
                ORDER BY dl.id DESC
            ");
            $lists = $stmt->fetchAll();

            foreach ($lists as &$list) {
                if (!empty($list['created_at'])) {
                    $dt = new DateTime($list['created_at']);
                    $list['formatted_created_at'] = $dt->format('d/m/Y à H:i');
                } else {
                    $list['formatted_created_at'] = null;
                }

                if (!empty($list['updated_at'])) {
                    $dt = new DateTime($list['updated_at']);
                    $list['formatted_updated_at'] = $dt->format('d/m/Y à H:i');
                } else {
                    $list['formatted_updated_at'] = null;
                }

                $members = [];
                try {
                    $stmtC = $db->prepare("
                        SELECT c.id, c.name, c.email, c.phone, COALESCE(c.role, 'Contact') as role, 'contact' as source
                        FROM contacts c
                        INNER JOIN distribution_list_contacts dlc ON c.id = dlc.contact_id
                        WHERE dlc.list_id = :list_id AND dlc.entity_type = 'contact'
                    ");
                    $stmtC->execute(['list_id' => $list['id']]);
                    $members = array_merge($members, $stmtC->fetchAll() ?: []);
                } catch (\PDOException $e) {}

                try {
                    $stmtU = $db->prepare("
                        SELECT u.id, u.name, u.email, u.phone, COALESCE(u.role, 'Membre') as role, 'user' as source
                        FROM users u
                        INNER JOIN distribution_list_contacts dlc ON u.id = dlc.contact_id
                        WHERE dlc.list_id = :list_id AND dlc.entity_type = 'user'
                    ");
                    $stmtU->execute(['list_id' => $list['id']]);
                    $members = array_merge($members, $stmtU->fetchAll() ?: []);
                } catch (\PDOException $e) {}

                $list['contacts'] = $members;
                $list['contacts_count'] = count($members);
            }

            echo json_encode($lists ?: []);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
            exit();
        }

        try {
            $stmtCheck = $db->prepare("SELECT id FROM distribution_lists WHERE name = :name");
            $stmtCheck->execute(['name' => $name]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Une liste portant ce nom existe déjà.']);
                exit();
            }
        } catch (\PDOException $e) {}

        $rawContacts = $input['contact_ids'] ?? $input['contacts'] ?? [];
        $formattedContacts = $extractFormattedContacts($rawContacts);

        try {
            $stmt = $db->prepare("INSERT INTO distribution_lists (name, description) VALUES (:name, :description)");
            $stmt->execute(['name' => $name, 'description' => $description]);
            $newListId = $db->lastInsertId();

            if (!empty($formattedContacts)) {
                $stmtLink = $db->prepare("INSERT INTO distribution_list_contacts (list_id, contact_id, entity_type) VALUES (:list_id, :contact_id, :entity_type)");
                foreach ($formattedContacts as $c) {
                    $stmtLink->execute([
                        'list_id'     => $newListId,
                        'contact_id'  => $c['id'],
                        'entity_type' => $c['type']
                    ]);
                }
            }

            echo json_encode(['success' => true, 'id' => $newListId]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $targetId = $listId ?? $input['id'] ?? null;

        if (!$targetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de liste manquant']);
            exit();
        }

        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
            exit();
        }

        try {
            $stmtCheck = $db->prepare("SELECT id FROM distribution_lists WHERE name = :name AND id != :id");
            $stmtCheck->execute(['name' => $name, 'id' => $targetId]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Une autre liste porte déjà ce nom.']);
                exit();
            }
        } catch (\PDOException $e) {}

        $rawContacts = $input['contact_ids'] ?? $input['contacts'] ?? [];
        $formattedContacts = $extractFormattedContacts($rawContacts);

        try {
            $stmt = $db->prepare("UPDATE distribution_lists SET name = :name, description = :description WHERE id = :id");
            $stmt->execute(['name' => $name, 'description' => $description, 'id' => $targetId]);

            $stmtDel = $db->prepare("DELETE FROM distribution_list_contacts WHERE list_id = :list_id");
            $stmtDel->execute(['list_id' => $targetId]);

            if (!empty($formattedContacts)) {
                $stmtLink = $db->prepare("INSERT INTO distribution_list_contacts (list_id, contact_id, entity_type) VALUES (:list_id, :contact_id, :entity_type)");
                foreach ($formattedContacts as $c) {
                    $stmtLink->execute([
                        'list_id'     => $targetId,
                        'contact_id'  => $c['id'],
                        'entity_type' => $c['type']
                    ]);
                }
            }

            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'DELETE') {
        $targetId = $listId;

        if (!$targetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de liste manquant']);
            exit();
        }

        try {
            $stmtDelPivot = $db->prepare("DELETE FROM distribution_list_contacts WHERE list_id = :list_id");
            $stmtDelPivot->execute(['list_id' => $targetId]);

            $stmtDelList = $db->prepare("DELETE FROM distribution_lists WHERE id = :id");
            $stmtDelList->execute(['id' => $targetId]);

            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

// --- ROUTE 7: DISTRIBUTION EMAIL BREVO (POST /api/send-pdf) ---
if (str_starts_with($uri, '/api/send-pdf') && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $listId = $input['list_id'] ?? null;
    $recipientsInput = $input['recipients'] ?? [];
    $subject = $input['subject'] ?? 'Roadline MGT - Feuille de Route';
    $message = $input['message'] ?? '<p>Veuillez trouver ci-joint le document au format PDF.</p>';
    $pdfPath = $input['pdf_path'] ?? null;

    if ($pdfPath) {
        $realPath = realpath($pdfPath);
        $allowedDir = realpath(__DIR__ . '/../storage');

        if (!$realPath || !$allowedDir || !str_starts_with($realPath, $allowedDir)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chemin de fichier PDF non autorisé.']);
            exit();
        }
    }

    // Récupération des destinataires si une liste est sélectionnée
    $recipients = [];
    if ($listId && empty($recipientsInput)) {
        $stmt = $db->prepare("
            SELECT name, email FROM (
                SELECT u.name, u.email, dlc.list_id FROM users u JOIN distribution_list_contacts dlc ON u.id = dlc.contact_id AND dlc.entity_type = 'user'
                UNION
                SELECT c.name, c.email, dlc.list_id FROM contacts c JOIN distribution_list_contacts dlc ON c.id = dlc.contact_id AND dlc.entity_type = 'contact'
            ) AS recipients_list
            WHERE list_id = :list_id AND email IS NOT NULL AND TRIM(email) != ''
        ");
        $stmt->execute(['list_id' => $listId]);
        $rawRecipients = $stmt->fetchAll();

        foreach ($rawRecipients as $r) {
            $cleanEmail = filter_var(trim($r['email']), FILTER_SANITIZE_EMAIL);
            if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'name' => trim($r['name'] ?? '') ?: 'Destinataire',
                    'email' => $cleanEmail
                ];
            }
        }
    } elseif (!empty($recipientsInput)) {
        foreach ($recipientsInput as $email) {
            $cleanEmail = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'name' => '',
                    'email' => $cleanEmail
                ];
            }
        }
    }

    if (empty($recipients)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun destinataire valide trouvé pour cet envoi.']);
        exit();
    }

    $emailService = new \App\Services\EmailService();
    $result = $emailService->sendEmailWithPdf($recipients, $subject, $message, $pdfPath);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Email envoyé avec succès via Brevo !']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Erreur inconnue']);
    }
    exit();
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Route introuvable']);
