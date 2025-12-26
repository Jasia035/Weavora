
<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: materials.php"); exit; }


$stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) { header("Location: materials.php"); exit; }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['material_name'];
    $cat  = $_POST['category'];
    $unit = $_POST['unit'];
    $qty  = (float)$_POST['quantity'];

    $update = $pdo->prepare("UPDATE materials SET material_name=?, category=?, unit=?, quantity=? WHERE id=?");
    $update->execute([$name, $cat, $unit, $qty, $id]);
    header("Location: materials.php?success=updated");
    exit();
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Edit: <?php echo htmlspecialchars($m['material_name']); ?></h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">MATERIAL NAME</label>
                            <input type="text" name="material_name" class="form-control" value="<?php echo $m['material_name']; ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">CATEGORY</label>
                                <select name="category" class="form-select">
                                    <option value="Fabric" <?php echo $m['category'] == 'Fabric' ? 'selected' : ''; ?>>Fabric</option>
                                    <option value="Yarn" <?php echo $m['category'] == 'Yarn' ? 'selected' : ''; ?>>Yarn</option>
                                    <option value="Dyes" <?php echo $m['category'] == 'Dyes' ? 'selected' : ''; ?>>Dyes/Chemicals</option>
                                    <option value="Accessories" <?php echo $m['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">UNIT</label>
                                <input type="text" name="unit" class="form-control" value="<?php echo $m['unit']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">LIVE QUANTITY</label>
                            <input type="number" step="0.01" name="quantity" class="form-control" value="<?php echo $m['quantity']; ?>" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="materials.php" class="btn btn-light px-4">Back</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Update Inventory</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>