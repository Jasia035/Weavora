
<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


$stats = $pdo->query("SELECT 
    COUNT(*) as total_items,
    SUM(CASE WHEN quantity < 10 THEN 1 ELSE 0 END) as low_stock_count,
    SUM(quantity) as total_volume
    FROM materials")->fetch();

$cat_data = $pdo->query("SELECT category, COUNT(*) as item_count, SUM(quantity) as total_qty 
                         FROM materials GROUP BY category")->fetchAll();


$recent_items = $pdo->query("SELECT * FROM materials ORDER BY id DESC LIMIT 5")->fetchAll();


$labels = [];
$qty_data = [];
$count_data = [];
foreach($cat_data as $row) {
    $labels[] = $row['category'] ?: 'Uncategorized';
    $qty_data[] = (float)$row['total_qty'];
    $count_data[] = (int)$row['item_count'];
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <small class="text-uppercase fw-bold opacity-75">Active Materials</small>
                <h2 class="mb-0 fw-bold"><?php echo $stats['total_items']; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white p-3">
                <small class="text-uppercase fw-bold opacity-75">Low Stock Items</small>
                <h2 class="mb-0 fw-bold"><?php echo $stats['low_stock_count']; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-3">
                <small class="text-uppercase fw-bold opacity-75">Total Stock Units</small>
                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_volume'], 0); ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                    <h6 class="fw-bold mb-0">Stock Volume by Category</h6>
                    <i class="bi bi-bar-chart-line text-muted"></i>
                </div>
                <div class="card-body">
                    <canvas id="volumeChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                    <h6 class="fw-bold mb-0">Inventory Mix (Item Count)</h6>
                    <i class="bi bi-pie-chart text-muted"></i>
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="varietyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Recently Added Materials</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th class="ps-4">MATERIAL</th>
                                <th>CATEGORY</th>
                                <th>INITIAL QTY</th>
                                <th class="text-end pe-4">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_items as $item): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($item['material_name']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $item['category']; ?></span></td>
                                <td><?php echo number_format($item['quantity'], 2); ?> <?php echo $item['unit']; ?></td>
                                <td class="text-end pe-4">
                                    <a href="materials.php" class="btn btn-sm btn-link text-decoration-none">Manage</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = <?php echo json_encode($labels); ?>;
    
    
    new Chart(document.getElementById('volumeChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Quantity',
                data: <?php echo json_encode($qty_data); ?>,
                backgroundColor: '#0d6efd',
                borderRadius: 5
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    
    new Chart(document.getElementById('varietyChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: <?php echo json_encode($count_data); ?>,
                backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14']
            }]
        },
        options: { 
            responsive: true, 
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } } 
        }
    });
</script>

<?php include 'includes/footer.php'; ?>