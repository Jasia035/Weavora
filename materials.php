
<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: materials.php?success=deleted");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_material'])) {
    $name     = trim($_POST['material_name']);
    $category = $_POST['category'];
    $unit     = $_POST['unit'];
    $qty      = (float)$_POST['quantity'];

    $stmt = $pdo->prepare("INSERT INTO materials (material_name, category, unit, quantity) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $category, $unit, $qty]);
    header("Location: materials.php?success=created");
    exit();
}

$search = $_GET['search'] ?? '';
$filter_cat = $_GET['category'] ?? '';

$query = "SELECT * FROM materials WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND material_name LIKE ?";
    $params[] = "%$search%";
}
if ($filter_cat) {
    $query .= " AND category = ?";
    $params[] = $filter_cat;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-layers-half me-2 text-primary"></i>Weavora Inventory</h2>
            <p class="text-muted mb-0">Centralized control for textile raw materials.</p>
        </div>
        <button class="btn btn-primary px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
            <i class="bi bi-plus-lg me-2"></i>New Material
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search materials..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <option value="Fabric" <?php echo $filter_cat == 'Fabric' ? 'selected' : ''; ?>>Fabric</option>
                        <option value="Yarn" <?php echo $filter_cat == 'Yarn' ? 'selected' : ''; ?>>Yarn</option>
                        <option value="Dyes" <?php echo $filter_cat == 'Dyes' ? 'selected' : ''; ?>>Dyes/Chemicals</option>
                        <option value="Accessories" <?php echo $filter_cat == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-outline-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Material Details</th>
                            <th>Category</th>
                            <th>Live Stock</th>
                            <th class="text-end pe-4">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $m): ?>
                        <?php 
                            $qty = $m['quantity']; 
                            $isLow = ($qty < 10);
                            $statusClass = $isLow ? 'text-danger' : 'text-success';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($m['material_name']); ?></div>
                                <small class="text-muted">ID: #<?php echo $m['id']; ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $m['category']; ?></span></td>
                            <td>
                                <span class="fw-bold <?php echo $statusClass; ?>"><?php echo number_format($qty, 2); ?></span>
                                <small class="text-muted text-uppercase ms-1"><?php echo $m['unit']; ?></small>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="edit_material.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-white border text-primary"><i class="bi bi-pencil-square"></i></a>
                                    <a href="materials.php?delete_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-white border text-danger" onclick="return confirm('Archive this material?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Register New Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small">MATERIAL NAME</label>
                    <input type="text" name="material_name" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small">CATEGORY</label>
                        <select name="category" class="form-select">
                            <option value="Fabric">Fabric</option>
                            <option value="Yarn">Yarn</option>
                            <option value="Dyes">Dyes/Chemicals</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small">UNIT</label>
                        <input type="text" name="unit" class="form-control" placeholder="Meters, KG..." required>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold small">OPENING STOCK</label>
                    <input type="number" step="0.01" name="quantity" class="form-control" value="0.00">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_material" class="btn btn-primary px-4 fw-bold">Save Material</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>