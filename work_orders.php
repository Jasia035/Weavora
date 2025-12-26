<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();

// Fetch Work Orders with joined Sales data
$sql = "SELECT wo.*, so.product_name, so.quantity, so.customer_name 
        FROM work_orders wo 
        JOIN sales_orders so ON wo.sales_order_id = so.id 
        ORDER BY wo.scheduled_date ASC";
$stmt = $pdo->query($sql);
$workOrders = $stmt->fetchAll();

include 'includes/header.php';

// Helper to calculate progress percentage
function getProgress($status) {
    switch ($status) {
        case 'IN_PROGRESS': return ['percent' => 50, 'color' => 'bg-primary'];
        case 'QC_PENDING':  return ['percent' => 80, 'color' => 'bg-info text-dark'];
        case 'COMPLETED':   return ['percent' => 100, 'color' => 'bg-success'];
        default:            return ['percent' => 10, 'color' => 'bg-secondary'];
    }
}
?>

<div class="mb-4">
    <h2><i class="bi bi-gear-wide-connected me-2"></i>Production Schedule</h2>
    <p class="text-muted">Track the live manufacturing status of active work orders.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">WO #</th>
                        <th>Product & Customer</th>
                        <th>Quantity</th>
                        <th style="width: 25%;">Production Progress</th>
                        <th>Scheduled</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workOrders as $wo): 
                        $prog = getProgress($wo['status']);
                    ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-light text-dark border">WO-0<?php echo $wo['id']; ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($wo['product_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($wo['customer_name']); ?></small>
                        </td>
                        <td><?php echo $wo['quantity']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1" style="height: 10px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo $prog['color']; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $prog['percent']; ?>%">
                                    </div>
                                </div>
                                <span class="ms-2 small fw-bold"><?php echo $prog['percent']; ?>%</span>
                            </div>
                            <small class="text-uppercase" style="font-size: 0.7rem;"><?php echo str_replace('_', ' ', $wo['status']); ?></small>
                        </td>
                        <td><i class="bi bi-calendar3 me-1"></i> <?php echo date('M d', strtotime($wo['scheduled_date'])); ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="issue_material.php?wo_id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Issue Materials">
                                    <i class="bi bi-box-arrow-right"></i>
                                </a>
                                <a href="quality_check.php?wo_id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-outline-warning" title="Quality Check">
                                    <i class="bi bi-check2-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>