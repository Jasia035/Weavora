<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


if (!in_array($_SESSION['role'], ['admin', 'inventory', 'production'])) {
    header("Location: index.php");
    exit();
}

$success = "";
$error = "";

$workOrders = $pdo->query("SELECT id FROM work_orders WHERE status IN ('PENDING', 'IN_PROGRESS')")->fetchAll();

$materials = $pdo->query("SELECT id, material_name, quantity FROM materials WHERE quantity > 0")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $work_order_id = (int)$_POST['work_order_id'];
    $material_id   = (int)$_POST['material_id'];
    $qty_to_issue  = (float)$_POST['quantity'];

    $checkStmt = $pdo->prepare("SELECT material_name, quantity FROM materials WHERE id = ?");
    $checkStmt->execute([$material_id]);
    $mat = $checkStmt->fetch();

    if ($mat && $mat['quantity'] >= $qty_to_issue) {
        try {
            $pdo->beginTransaction();

           
            $updateStock = $pdo->prepare("UPDATE materials SET quantity = quantity - ? WHERE id = ?");
            $updateStock->execute([$qty_to_issue, $material_id]);

            
            $logStmt = $pdo->prepare("INSERT INTO material_transactions (material_id, work_order_id, quantity, type, user_id) VALUES (?, ?, ?, 'ISSUE', ?)");
            $logStmt->execute([$material_id, $work_order_id, $qty_to_issue, $_SESSION['user_id']]);

            $pdo->commit();
            $success = "Successfully issued $qty_to_issue units of " . $mat['material_name'] . " to Work Order #$work_order_id.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Insufficient stock! Only " . ($mat['quantity'] ?? 0) . " units available.";
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex align-items-center mb-4">
            <a href="materials.php" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h2 class="fw-bold mb-0">Issue Materials</h2>
                <p class="text-muted mb-0">Warehouse to Production Transfer</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Work Order</label>
                        <select name="work_order_id" class="form-select bg-light" required>
                            <option value="" selected disabled>Select production job...</option>
                            <?php foreach ($workOrders as $wo): ?>
                                <option value="<?php echo $wo['id']; ?>">WO #<?php echo $wo['id']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Material</label>
                        <select name="material_id" class="form-select bg-light" required>
                            <option value="" selected disabled>Select material...</option>
                            <?php foreach ($materials as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['material_name']); ?> (Available: <?php echo $m['quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Quantity to Issue</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="quantity" class="form-control bg-light" placeholder="0.00" required>
                            <span class="input-group-text">Units</span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">Confirm Issuance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

