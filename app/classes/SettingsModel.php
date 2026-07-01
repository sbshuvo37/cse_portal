<?php
/**
 * SettingsModel — settings table operations (single row)
 * CSE Department Portal
 */
class SettingsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function get(): array
    {
        $stmt = $this->db->query("SELECT * FROM settings ORDER BY id LIMIT 1");
        $row = $stmt->fetch();
        if (!$row) {
            // Fallback defaults if settings row missing
            return [
                'portal_name'     => 'CSE Department Portal',
                'university_name' => 'Jatiya Kabi Kazi Nazrul Islam University',
                'department_name' => 'Department of Computer Science and Engineering',
                'description'     => '',
                'contact_info'    => '',
                'logo'            => null,
            ];
        }
        return $row;
    }

    public function update(string $portalName, string $universityName, string $departmentName, ?string $description, ?string $contactInfo, ?string $logo = null): bool
    {
        $existing = $this->db->query("SELECT id FROM settings LIMIT 1")->fetch();

        if ($existing) {
            if ($logo !== null) {
                $stmt = $this->db->prepare(
                    "UPDATE settings SET portal_name=?, university_name=?, department_name=?, description=?, contact_info=?, logo=? WHERE id=?"
                );
                return $stmt->execute([$portalName, $universityName, $departmentName, $description, $contactInfo, $logo, $existing['id']]);
            }
            $stmt = $this->db->prepare(
                "UPDATE settings SET portal_name=?, university_name=?, department_name=?, description=?, contact_info=? WHERE id=?"
            );
            return $stmt->execute([$portalName, $universityName, $departmentName, $description, $contactInfo, $existing['id']]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO settings (portal_name, university_name, department_name, description, contact_info, logo) VALUES (?,?,?,?,?,?)"
        );
        return $stmt->execute([$portalName, $universityName, $departmentName, $description, $contactInfo, $logo]);
    }
}
