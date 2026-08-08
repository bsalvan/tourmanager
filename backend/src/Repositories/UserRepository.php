<?php
namespace App\Repositories;

use PDO;

class UserRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function getAll(): array {
        $stmt = $this->db->query("SELECT id, name, email, role, permissions, created_at FROM users ORDER BY name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $user['permissions'] = $user['permissions'] ? json_decode($user['permissions'], true) : [];
        }

        return $users;
    }

    /**
     * Authentifie un utilisateur via son email et mot de passe
     */
    public function authenticate(string $email, string $password): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, password_hash, role, permissions FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // LOG 1 : Est-ce qu'on trouve l'utilisateur en BDD ?
        if (!$user) {
            // error_log("--> BDD: Aucun utilisateur trouvé avec l'email: " . $email);
            return null;
        }

        // error_log("--> BDD: Utilisateur trouvé! Hash en BDD: [" . $user['password_hash'] . "]");

        // LOG 2 : Test de vérification du mot de passe
        $isValid = password_verify($password, $user['password_hash']);
        // error_log("--> TEST HASH: Le mot de passe match ? " . ($isValid ? 'OUI' : 'NON'));

        if ($isValid) {
            unset($user['password_hash']);
            $user['permissions'] = $user['permissions'] ? json_decode($user['permissions'], true) : [];
            return $user;
        }

        return null;
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser(array $data): int {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $permissionsJson = isset($data['permissions']) ? json_encode($data['permissions']) : json_encode([]);

        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role, permissions) 
            VALUES (:name, :email, :password_hash, :role, :permissions)
        ");
        
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $hashedPassword,
            'role' => $data['role'] ?? 'tm',
            'permissions' => $permissionsJson
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser(int $id, array $data): bool {
        $fields = ["name = :name", "email = :email", "role = :role"];
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role']
        ];

        if (!empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (isset($data['permissions'])) {
            $fields[] = "permissions = :permissions";
            $params['permissions'] = json_encode($data['permissions']);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
