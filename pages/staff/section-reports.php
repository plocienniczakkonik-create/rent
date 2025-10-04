<?php
// expects: $reports 
$reports = $reports ?? [
    'revenue_today' => 0.0,
    'orders_today' => 0,
    'top_product' => 'Brak danych'
];
?>
<div class="card section-reports">
    <div class="card-header">
        <h2 class="h6 mb-0">Raporty</h2>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Przychód dziś</div>
                        <div class="fs-4 fw-semibold"><?= number_format((float)$reports['revenue_today'], 2, ',', ' ') ?> PLN</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Zamówienia dziś</div>
                        <div class="fs-4 fw-semibold"><?= (int)$reports['orders_today'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Top produkt</div>
                        <div class="fs-6 fw-semibold"><?= htmlspecialchars($reports['top_product']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="alert alert-info mt-3 mb-0 small">
            W kolejnych krokach dorzucimy wykresy (miesięczny przychód, liczba rezerwacji, średnia wartość koszyka).
        </div>
    </div>
</div>