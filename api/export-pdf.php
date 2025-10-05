<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';

// Sprawdź czy TCPDF jest zainstalowane
if (!class_exists('TCPDF')) {
    // Fallback - użyj prostego HTML->PDF
    header('Content-Type: text/html; charset=utf-8');

    $dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-2 months'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    $reportType = $_GET['report_type'] ?? 'summary';

    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Raport - ' . htmlspecialchars($reportType) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .filters { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .text-center { text-align: center; }
            .text-end { text-align: right; }
            .stats { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
            .stat-card { flex: 1; min-width: 200px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .stat-value { font-size: 24px; font-weight: bold; color: #007bff; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Raport Systemu Wynajmu</h1>
            <p>Typ raportu: ' . htmlspecialchars($reportType) . '</p>
            <p>Okres: ' . htmlspecialchars($dateFrom) . ' - ' . htmlspecialchars($dateTo) . '</p>
            <p>Wygenerowano: ' . date('Y-m-d H:i:s') . '</p>
        </div>
        
        <div class="no-print">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                🖨️ Drukuj / Zapisz jako PDF
            </button>
        </div>';

    try {
        // Pobierz dane - używamy pickup_at dla filtrowania dat
        $whereConditions = ["pickup_at BETWEEN ? AND ?"];
        $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

        $product = $_GET['product'] ?? '';
        $status = $_GET['status'] ?? '';
        $location = $_GET['location'] ?? '';

        if (!empty($product)) {
            $whereConditions[] = "product_name LIKE ?";
            $params[] = '%' . $product . '%';
        }

        if (!empty($status)) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
        }

        if (!empty($location)) {
            $whereConditions[] = "pickup_location LIKE ?";
            $params[] = '%' . $location . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        // Podstawowe statystyki
        $stmt = db()->prepare("
            SELECT 
                COUNT(*) as total_reservations,
                SUM(total_gross) as total_revenue,
                AVG(total_gross) as avg_revenue,
                AVG(rental_days) as avg_days
            FROM reservations 
            $whereClause
        ");
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<div class="filters">
            <h3>Filtry zastosowane:</h3>
            <p><strong>Okres:</strong> ' . htmlspecialchars($dateFrom) . ' - ' . htmlspecialchars($dateTo) . '</p>';
        if ($product) echo '<p><strong>Produkt:</strong> ' . htmlspecialchars($product) . '</p>';
        if ($status) echo '<p><strong>Status:</strong> ' . htmlspecialchars($status) . '</p>';
        if ($location) echo '<p><strong>Lokalizacja:</strong> ' . htmlspecialchars($location) . '</p>';
        echo '</div>';

        echo '<div class="stats">
            <div class="stat-card">
                <div>Łączna liczba rezerwacji</div>
                <div class="stat-value">' . number_format($summary['total_reservations']) . '</div>
            </div>
            <div class="stat-card">
                <div>Łączny przychód</div>
                <div class="stat-value">' . number_format($summary['total_revenue'], 2, ',', ' ') . ' PLN</div>
            </div>
            <div class="stat-card">
                <div>Średnia wartość rezerwacji</div>
                <div class="stat-value">' . number_format($summary['avg_revenue'], 2, ',', ' ') . ' PLN</div>
            </div>
            <div class="stat-card">
                <div>Średnia liczba dni</div>
                <div class="stat-value">' . number_format($summary['avg_days'], 1) . '</div>
            </div>
        </div>';

        // Top produkty
        $stmt = db()->prepare("
            SELECT 
                product_name as name,
                COUNT(*) as reservations,
                SUM(total_gross) as revenue,
                AVG(total_gross) as avg_value
            FROM reservations 
            $whereClause
            GROUP BY product_name
            ORDER BY reservations DESC
            LIMIT 20
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($products)) {
            echo '<h3>Top Produkty</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produkt</th>
                        <th class="text-center">Rezerwacje</th>
                        <th class="text-end">Przychód (PLN)</th>
                        <th class="text-end">Średnia wartość</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($products as $i => $product) {
                echo '<tr>
                    <td>' . ($i + 1) . '</td>
                    <td>' . htmlspecialchars($product['name']) . '</td>
                    <td class="text-center">' . $product['reservations'] . '</td>
                    <td class="text-end">' . number_format($product['revenue'], 2, ',', ' ') . '</td>
                    <td class="text-end">' . number_format($product['avg_value'], 2, ',', ' ') . '</td>
                </tr>';
            }

            echo '</tbody></table>';
        }

        // Przychód miesięczny
        $stmt = db()->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                DATE_FORMAT(created_at, '%M %Y') as month_label,
                COUNT(*) as reservations,
                SUM(total_gross) as revenue
            FROM reservations 
            $whereClause
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute($params);
        $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($monthly)) {
            echo '<h3>Przychód miesięczny</h3>
            <table>
                <thead>
                    <tr>
                        <th>Miesiąc</th>
                        <th class="text-center">Rezerwacje</th>
                        <th class="text-end">Przychód (PLN)</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($monthly as $month) {
                echo '<tr>
                    <td>' . htmlspecialchars($month['month_label']) . '</td>
                    <td class="text-center">' . $month['reservations'] . '</td>
                    <td class="text-end">' . number_format($month['revenue'], 2, ',', ' ') . '</td>
                </tr>';
            }

            echo '</tbody></table>';
        }
    } catch (Exception $e) {
        echo '<div style="color: red; padding: 20px; border: 1px solid red; margin: 20px 0;">
            <h3>Błąd generowania raportu</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
        </div>';
    }

    echo '<div class="footer">
        <p>Raport wygenerowany przez System Zarządzania Wynajmem</p>
        <p>© ' . date('Y') . ' - Wszystkie prawa zastrzeżone</p>
    </div>
    
    </body>
    </html>';
} else {
    // Tu można dodać TCPDF jeśli jest zainstalowane
    echo json_encode(['error' => 'TCPDF not available. Use print view instead.']);
}
