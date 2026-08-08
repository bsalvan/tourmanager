<?php
namespace App\Repositories;

use PDO;

class CompanyRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getSettings(): array {
        $sth = $this->db->query("SELECT * FROM company_settings WHERE id = 1");
        $settings = $sth->fetch(PDO::FETCH_ASSOC);
        return $settings ?: [
            'company_name' => 'Roadline MGT',
            'email' => '',
            'phone' => '',
            'address' => '',
            'footer_text' => ''
        ];
    }

    public function updateSettings(array $data): bool {
        $sth = $this->db->prepare("
            UPDATE company_settings 
            SET company_name = :company_name, email = :email, phone = :phone, address = :address, footer_text = :footer_text
            WHERE id = 1
        ");
        return $sth->execute([
            'company_name' => $data['company_name'],
            'email'        => $data['email'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'address'      => $data['address'] ?? null,
            'footer_text'  => $data['footer_text'] ?? null,
        ]);
    }
}
