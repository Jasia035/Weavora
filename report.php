<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();

if (!in_array($_SESSION['role'], ['admin', 'sales', 'inventory'])) {
    header("Location: index.php");
    exit();
}


$salesQuery = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%b %Y') as month, 
        SUM(total_amount) as total 
    FROM sales_orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at ASC
");
$salesData = $salesQuery->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$revenues = [];
foreach ($salesData as $row) {
    $months[] = $row['month'];
    $revenues[] = $row['total'];
}


$lowStockItems = $pdo->query("SELECT material_name, quantity FROM materials WHERE quantity < 10")->fetchAll();
$prodStats = $pdo->query("SELECT status, COUNT(*) as count FROM work_orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$recentLogs = $pdo->query("
    SELECT mt.*, m.material_name, u.username 
    FROM material_transactions mt 
    JOIN materials m ON mt.material_id = m.id 
    JOIN users u ON mt.user_id = u.id 
    ORDER BY mt.created_at DESC LIMIT 10
")->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Executive Report</h2>
        <p class="text-muted">Financial and operational performance for Weavora.</p>
    </div>
    <button class="btn btn-outline-dark shadow-sm" onclick="window.print()">
        <i class="bi bi-printer me-2"></i> Print PDF
    </button>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-dark">Revenue Trend (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                <h6 class="text-uppercase small fw-bold opacity-75">Total Revenue (Period)</h6>
                <h2 class="display-5 fw-bold mb-0">$<?php echo number_format(array_sum($revenues), 2); ?></h2>
                <hr class="opacity-25">
                <p class="small mb-0">Calculated from completed and pending sales orders.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-danger">Low Stock Warning</h6>
            </div>
            <div class="card-body">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold"><?php echo $item['material_name']; ?></span>
                        <span class="badge bg-danger-subtle text-danger"><?php echo $item['quantity']; ?> left</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold">Recent Material Issuance</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Material</th>
                            <th>Qty</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?php echo date('M d', strtotime($log['created_at'])); ?></td>
                            <td class="fw-bold"><?php echo $log['material_name']; ?></td>
                            <td><?php echo $log['quantity']; ?></td>
                            <td><?php echo $log['username']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Monthly Revenue ($)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>