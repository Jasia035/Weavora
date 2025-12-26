<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();


if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $user_id])) {
        $success = "Staff role updated successfully.";
    }
}


if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
   
    if ($id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: users_list.php?msg=deleted");
        exit();
    }
}


$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username LIKE ? ORDER BY role ASC");
$stmt->execute([$search]);
$users = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark">Staff Management</h2>
        <p class="text-muted">Control departmental access for all Weavora users.</p>
    </div>
    <form class="d-flex" method="GET">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<?php if ($success || isset($_GET['msg'])): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success ?: "Operation completed successfully."; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3">User</th>
                        <th>Department / Role</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person text-primary fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-secondary-subtle text-secondary x-small">YOU</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $roleColors = [
                                    'admin' => 'bg-dark',
                                    'sales' => 'bg-primary',
                                    'production' => 'bg-info text-dark',
                                    'inventory' => 'bg-success',
                                    'qc' => 'bg-warning text-dark'
                                ];
                                $color = $roleColors[$user['role']] ?? 'bg-light text-dark';
                            ?>
                            <span class="badge <?php echo $color; ?> px-3 py-2 text-uppercase" style="font-size: 0.7rem;">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-white border shadow-sm" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                <i class="bi bi-shield-lock me-1"></i> Permissions
                            </button>
                            
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users_list.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger ms-2" onclick="return confirm('Remove this user? They will lose all access immediately.')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-sm modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header border-0 pb-0">
                                    <h6 class="modal-title fw-bold">Update Role</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <p class="text-muted small mb-3">Assign <strong><?php echo $user['username']; ?></strong> to a new department.</p>
                                        <select name="new_role" class="form-select bg-light">
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="sales" <?php echo $user['role'] == 'sales' ? 'selected' : ''; ?>>Sales</option>
                                            <option value="production" <?php echo $user['role'] == 'production' ? 'selected' : ''; ?>>Production</option>
                                            <option value="inventory" <?php echo $user['role'] == 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                                            <option value="qc" <?php echo $user['role'] == 'qc' ? 'selected' : ''; ?>>Quality Control</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="submit" name="update_role" class="btn btn-primary w-100">Apply Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>