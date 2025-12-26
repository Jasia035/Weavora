<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


$wo_id = isset($_GET['wo_id']) ? (int)$_GET['wo_id'] : 0;

if ($wo_id <= 0) {
    header("Location: work_orders.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_qc'])) {
    $status  = $_POST['qc_status']; 
    $remarks = trim($_POST['remarks']);
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

      
        $stmt = $pdo->prepare("INSERT INTO quality_checks (work_order_id, inspector_id, status, remarks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$wo_id, $user_id, $status, $remarks]);

        if ($status == 'passed') {
            
            $stmt = $pdo->prepare("UPDATE work_orders SET status = 'COMPLETED' WHERE id = ?");
            $stmt->execute([$wo_id]);

            
            $getSO = $pdo->prepare("SELECT sales_order_id FROM work_orders WHERE id = ?");
            $getSO->execute([$wo_id]);
            $so_id = $getSO->fetchColumn();

            $stmt = $pdo->prepare("UPDATE sales_orders SET status = 'completed' WHERE id = ?");
            $stmt->execute([$so_id]);
            
            $success = "Inspection PASSED. Order has been marked as Completed.";
        } else {
            
            $stmt = $pdo->prepare("UPDATE work_orders SET status = 'IN_PROGRESS' WHERE id = ?");
            $stmt->execute([$wo_id]);
            $error = "Inspection FAILED. Order returned to Production for rework.";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "System Error: " . $e->getMessage();
    }
}


$stmt = $pdo->prepare("SELECT wo.*, so.product_name, so.quantity, so.customer_name 
                       FROM work_orders wo 
                       JOIN sales_orders so ON wo.sales_order_id = so.id 
                       WHERE wo.id = ?");
$stmt->execute([$wo_id]);
$wo = $stmt->fetch();

include 'includes/header.php';
?>

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="work_orders.php">Production</a></li>
            <li class="breadcrumb-item active">Quality Inspection</li>
        </ol>
    </nav>
    <h2><i class="bi bi-check2-all text-warning me-2"></i>Quality Control Inspection</h2>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Inspection Form: WO-0<?php echo $wo_id; ?></h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Inspection Result</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="qc_status" id="pass" value="passed" required>
                                <label class="btn btn-outline-success w-100 py-3" for="pass">
                                    <i class="bi bi-hand-thumbs-up fs-4 d-block"></i> PASS
                                </label>
                            </div>
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="qc_status" id="fail" value="failed" required>
                                <label class="btn btn-outline-danger w-100 py-3" for="fail">
                                    <i class="bi bi-hand-thumbs-down fs-4 d-block"></i> FAIL
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Inspector Notes / Remarks</label>
                        <textarea name="remarks" class="form-control" rows="5" placeholder="Document any defects, measurements, or compliance checks..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="submit_qc" class="btn btn-primary btn-lg">Submit Final Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Order Summary</h6>
                <h4 class="mb-1"><?php echo htmlspecialchars($wo['product_name']); ?></h4>
                <p class="mb-3 text-secondary">Customer: <?php echo htmlspecialchars($wo['customer_name']); ?></p>
                <div class="d-flex justify-content-between">
                    <span>Quantity: <strong><?php echo $wo['quantity']; ?></strong></span>
                    <span>Status: <span class="badge bg-secondary"><?php echo $wo['status']; ?></span></span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Inspection History</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php
                $history = $pdo->prepare("SELECT qc.*, u.username FROM quality_checks qc JOIN users u ON qc.inspector_id = u.id WHERE work_order_id = ? ORDER BY check_date DESC");
                $history->execute([$wo_id]);
                $logs = $history->fetchAll();
                
                foreach ($logs as $log): 
                    $color = ($log['status'] == 'passed') ? 'text-success' : 'text-danger';
                    $icon = ($log['status'] == 'passed') ? 'bi-check-circle' : 'bi-x-circle';
                ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold <?php echo $color; ?> text-uppercase">
                                <i class="bi <?php echo $icon; ?> me-1"></i> <?php echo $log['status']; ?>
                            </span>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($log['check_date'])); ?></small>
                        </div>
                        <p class="small mb-1 text-dark"><?php echo htmlspecialchars($log['remarks']); ?></p>
                        <small class="text-muted">Inspector: <?php echo $log['username']; ?></small>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($logs)): ?>
                    <div class="list-group-item text-center py-4 text-muted small">
                        No prior inspections recorded.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>