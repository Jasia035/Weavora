<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_order'])) {
    $customer = trim($_POST['customer_name']);
    $product  = trim($_POST['product_name']);
    $qty      = (int)$_POST['quantity'];

    if(!empty($customer) && !empty($product) && $qty > 0) {
        $sql = "INSERT INTO sales_orders (customer_name, product_name, quantity, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer, $product, $qty]);
        header("Location: sales.php?success=1");
        exit();
    }
}


$stmt = $pdo->query("SELECT * FROM sales_orders ORDER BY id DESC");
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Sales Order Management</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
        <i class="bi bi-plus-circle me-1"></i> New Sales Order
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Order added successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="ps-3">#<?php echo $order['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>
                            <?php 
                                $badgeClass = ($order['status'] == 'pending') ? 'bg-warning text-dark' : 'bg-info text-dark';
                                if($order['status'] == 'completed') $badgeClass = 'bg-success';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?> text-uppercase">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <?php if ($order['status'] == 'pending'): ?>
                                <a href="generate_work_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-success">
                                    Generate Work Order
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light" disabled>In Production</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOrderModalLabel">Create New Sales Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="sales.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="e.g. Acme Corp" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" class="form-control" placeholder="e.g. Industrial Pump" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_order" class="btn btn-primary">Place Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>