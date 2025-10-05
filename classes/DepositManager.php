<?php

/**
 * DepositManager - Zarządzanie kaucjami zwrotnymi
 * 
 * Klasa odpowiedzialna za:
 * - Obliczanie kaucji dla produktów (stała kwota lub procent)
 * - Sprawdzanie czy system kaucji jest włączony
 * - Zarządzanie kaucjami w rezerwacjach
 * - Określanie sposobu wyświetlania kaucji (wliczona w płatność czy osobno)
 */

class DepositManager
{
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? db();
    }

    /**
     * Sprawdza czy system kaucji jest włączony
     */
    public function isEnabled(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'deposit_system_enabled'");
            $stmt->execute();
            return ($stmt->fetchColumn() ?? '0') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sprawdza czy kaucja ma być wliczona w płatność
     */
    public function isIncludedInPayment(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'deposit_include_in_payment'");
            $stmt->execute();
            return ($stmt->fetchColumn() ?? '0') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera ustawienia systemu kaucji
     */
    public function getSettings(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT setting_key, setting_value 
                FROM shop_settings 
                WHERE setting_key LIKE 'deposit_%'
            ");
            $stmt->execute();

            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Sprawdza czy produkt ma włączoną kaucję
     */
    public function isDepositEnabledForProduct(string $productSku): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT deposit_enabled 
                FROM products 
                WHERE sku = ?
            ");
            $stmt->execute([$productSku]);
            return ($stmt->fetchColumn() ?? 0) == 1;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera ustawienia kaucji dla produktu
     */
    public function getProductDepositSettings(string $productSku): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT deposit_enabled, deposit_type, deposit_amount
                FROM products 
                WHERE sku = ?
            ");
            $stmt->execute([$productSku]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['deposit_enabled']) {
                return null;
            }

            return $result;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Oblicza kaucję dla produktu na podstawie ceny wynajmu
     */
    public function calculateDeposit(string $productSku, float $rentalTotal, int $rentalDays = 1): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'amount' => 0,
                'type' => 'none',
                'description' => 'System kaucji wyłączony'
            ];
        }

        $productSettings = $this->getProductDepositSettings($productSku);

        if (!$productSettings) {
            return [
                'enabled' => false,
                'amount' => 0,
                'type' => 'none',
                'description' => 'Kaucja nieaktywna dla tego produktu'
            ];
        }

        $depositAmount = 0;
        $description = '';

        if ($productSettings['deposit_type'] === 'fixed') {
            $depositAmount = (float)$productSettings['deposit_amount'];
            $description = 'Kaucja stała: ' . number_format($depositAmount, 2) . ' PLN';
        } elseif ($productSettings['deposit_type'] === 'percentage') {
            $percentage = (float)$productSettings['deposit_amount'];
            $depositAmount = ($rentalTotal * $percentage) / 100;
            $description = "Kaucja {$percentage}% od wynajmu: " . number_format($depositAmount, 2) . ' PLN';
        }

        return [
            'enabled' => true,
            'amount' => round($depositAmount, 2),
            'type' => $productSettings['deposit_type'],
            'percentage' => $productSettings['deposit_type'] === 'percentage' ? (float)$productSettings['deposit_amount'] : null,
            'description' => $description,
            'included_in_payment' => $this->isIncludedInPayment()
        ];
    }

    /**
     * Zapisuje kaucję dla rezerwacji
     */
    public function saveReservationDeposit(int $reservationId, array $depositData): bool
    {
        try {
            if (!$depositData['enabled'] || $depositData['amount'] <= 0) {
                return true; // Brak kaucji do zapisania
            }

            $stmt = $this->db->prepare("
                INSERT INTO reservation_deposits 
                (reservation_id, deposit_amount, deposit_type, included_in_payment, status) 
                VALUES (?, ?, ?, ?, 'pending')
                ON DUPLICATE KEY UPDATE
                deposit_amount = VALUES(deposit_amount),
                deposit_type = VALUES(deposit_type),
                included_in_payment = VALUES(included_in_payment)
            ");

            $stmt->execute([
                $reservationId,
                $depositData['amount'],
                $depositData['type'],
                $depositData['included_in_payment'] ? 1 : 0
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera kaucję dla rezerwacji
     */
    public function getReservationDeposit(int $reservationId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reservation_deposits 
                WHERE reservation_id = ?
            ");
            $stmt->execute([$reservationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Aktualizuje status kaucji
     */
    public function updateDepositStatus(int $reservationId, string $status): bool
    {
        try {
            $validStatuses = ['pending', 'paid', 'returned', 'withheld'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE reservation_deposits 
                SET status = ? 
                WHERE reservation_id = ?
            ");
            $stmt->execute([$status, $reservationId]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera domyślne ustawienia kaucji z shop_settings
     */
    public function getDefaultDepositSettings(): array
    {
        try {
            $settings = $this->getSettings();

            return [
                'type' => $settings['deposit_default_type'] ?? 'fixed',
                'amount' => (float)($settings['deposit_default_amount'] ?? 500.00),
                'display_mode' => $settings['deposit_display_mode'] ?? 'separate'
            ];
        } catch (Exception $e) {
            return [
                'type' => 'fixed',
                'amount' => 500.00,
                'display_mode' => 'separate'
            ];
        }
    }

    /**
     * Aktualizuje ustawienia kaucji dla produktu
     */
    public function updateProductDepositSettings(string $productSku, bool $enabled, string $type = 'fixed', float $amount = 0): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE products 
                SET deposit_enabled = ?, deposit_type = ?, deposit_amount = ?
                WHERE sku = ?
            ");

            $stmt->execute([
                $enabled ? 1 : 0,
                $type,
                $amount,
                $productSku
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera listę produktów z ustawieniami kaucji
     */
    public function getProductsWithDeposits(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id, name, sku, price,
                    deposit_enabled, deposit_type, deposit_amount
                FROM products 
                WHERE deposit_enabled = 1 
                ORDER BY name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Pobiera statystyki kaucji
     */
    public function getDepositStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_deposits,
                    SUM(deposit_amount) as total_amount,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_deposits,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_deposits,
                    COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_deposits,
                    COUNT(CASE WHEN status = 'withheld' THEN 1 END) as withheld_deposits
                FROM reservation_deposits
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Formatuje kwotę kaucji do wyświetlenia
     */
    public function formatDepositAmount(float $amount, string $type = 'fixed', ?float $percentage = null): string
    {
        if ($type === 'percentage' && $percentage !== null) {
            return number_format($amount, 2) . ' PLN (' . number_format($percentage, 1) . '%)';
        }

        return number_format($amount, 2) . ' PLN';
    }
}
