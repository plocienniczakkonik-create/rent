<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Parametry filtrów
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    $product = $_GET['product'] ?? '';
    $status = $_GET['status'] ?? '';
    $location = $_GET['location'] ?? '';
    $reportType = $_GET['report_type'] ?? 'summary';

    // Bazowe zapytanie - używamy pickup_at dla filtrowania dat
    $whereConditions = ["pickup_at BETWEEN ? AND ?"];
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

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

    switch ($reportType) {
        case 'vehicles':
            $stmt = db()->prepare("
                SELECT 
                    r.vehicle_id,
                    v.vin,
                    v.registration_number,
                    p.name as model,
                    COUNT(*) as reservations,
                    SUM(r.total_gross) as revenue,
                    AVG(r.rental_days) as avg_days
                FROM reservations r
                LEFT JOIN vehicles v ON r.vehicle_id = v.id
                LEFT JOIN products p ON v.product_id = p.id
                $whereClause
                GROUP BY r.vehicle_id, v.vin, v.registration_number, p.name
                ORDER BY reservations DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'classes':
            $stmt = db()->prepare("
                SELECT 
                    p.category as class,
                    COUNT(*) as reservations,
                    SUM(r.total_gross) as revenue,
                    AVG(r.rental_days) as avg_days
                FROM reservations r
                LEFT JOIN vehicles v ON r.vehicle_id = v.id
                LEFT JOIN products p ON v.product_id = p.id
                $whereClause
                GROUP BY p.category
                ORDER BY reservations DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'incidents':
            $stmt = db()->prepare("
                SELECT 
                    vi.vehicle_id,
                    v.vin,
                    v.registration_number,
                    vi.incident_type,
                    COUNT(*) as incidents,
                    SUM(vi.cost) as total_cost
                FROM vehicle_incidents vi
                LEFT JOIN vehicles v ON vi.vehicle_id = v.id
                $whereClause
                GROUP BY vi.vehicle_id, v.vin, v.registration_number, vi.incident_type
                ORDER BY incidents DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'services':
            $stmt = db()->prepare("
                SELECT 
                    vs.vehicle_id,
                    v.vin,
                    v.registration_number,
                    vs.service_type,
                    COUNT(*) as services,
                    SUM(vs.cost) as total_cost
                FROM vehicle_services vs
                LEFT JOIN vehicles v ON vs.vehicle_id = v.id
                $whereClause
                GROUP BY vs.vehicle_id, v.vin, v.registration_number, vs.service_type
                ORDER BY services DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'revenue_daily':
            $stmt = db()->prepare("
                SELECT 
                    DATE(pickup_at) as date,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE(pickup_at)
                ORDER BY DATE(pickup_at)
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'revenue_monthly':
            $stmt = db()->prepare("
                SELECT 
                    DATE_FORMAT(pickup_at, '%Y-%m') as month,
                    DATE_FORMAT(pickup_at, '%M %Y') as month_label,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE_FORMAT(pickup_at, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'products':
            $stmt = db()->prepare("
                SELECT 
                    product_name as name,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue,
                    AVG(total_gross) as avg_value,
                    AVG(rental_days) as avg_days
                FROM reservations 
                $whereClause
                GROUP BY product_name
                ORDER BY reservations DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'locations':
            $stmt = db()->prepare("
                SELECT 
                    pickup_location as location,
                    COUNT(*) as pickups,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY pickup_location
                ORDER BY pickups DESC
                LIMIT 15
            ");
            $stmt->execute($params);
            $pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = db()->prepare("
                SELECT 
                    dropoff_location as location,
                    COUNT(*) as dropoffs
                FROM reservations 
                $whereClause
                GROUP BY dropoff_location
                ORDER BY dropoffs DESC
                LIMIT 15
            ");
            $stmt->execute($params);
            $dropoffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = ['pickups' => $pickups, 'dropoffs' => $dropoffs];
            break;

        case 'status':
            $stmt = db()->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'vehicles':
            $stmt = db()->query("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM vehicles 
                GROUP BY status
                ORDER BY count DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default: // summary
            // Podstawowe statystyki
            $stmt = db()->prepare("
                SELECT 
                    COUNT(*) as total_reservations,
                    SUM(total_gross) as total_revenue,
                    AVG(total_gross) as avg_revenue,
                    AVG(rental_days) as avg_days,
                    COUNT(DISTINCT product_name) as unique_products,
                    COUNT(DISTINCT pickup_location) as unique_locations
                FROM reservations 
                $whereClause
            ");
            $stmt->execute($params);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // Statystyki statusów
            $stmt = db()->prepare("
                SELECT status, COUNT(*) as count
                FROM reservations 
                $whereClause
                GROUP BY status
            ");
            $stmt->execute($params);
            $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top produkty
            $stmt = db()->prepare("
                SELECT product_name as name, COUNT(*) as count, SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY product_name
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute($params);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Przychód miesięczny dla wykresu
            $stmt = db()->prepare("
                SELECT 
                    DATE_FORMAT(pickup_at, '%Y-%m') as month,
                    DATE_FORMAT(pickup_at, '%M %Y') as month_label,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE_FORMAT(pickup_at, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute($params);
            $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'summary' => $summary,
                'status_stats' => $statusStats,
                'top_products' => $topProducts,
                'monthly_revenue' => $monthlyRevenue,
                'filters_applied' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'product' => $product,
                    'status' => $status,
                    'location' => $location
                ]
            ];
            break;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'product' => $product,
            'status' => $status,
            'location' => $location,
            'report_type' => $reportType
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
