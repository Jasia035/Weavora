<?php
require_once 'config/init.php';
require_once 'includes/auth_check.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (password_verify($current_pwd, $user['password'])) {
        if ($new_pwd === $confirm_pwd) {
            $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed_pwd, $user_id]);
            $success = "Password updated successfully!";
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center p-5">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p class="text-muted text-uppercase small fw-bold mt-2"><?php echo $_SESSION['role']; ?> Department</p>
                <hr>
                <div class="text-start mt-3">
                    <p class="small mb-1 text-muted">Account Status</p>
                    <p class="fw-bold text-success"><i class="bi bi-patch-check-fill me-1"></i> Active / Verified</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">Security Settings</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary px-4">
                        Update Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4 bg-light">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-shield-lock me-2"></i>Access Level: <?php echo ucfirst($_SESSION['role']); ?></h6>
                <p class="small text-muted mb-0">
                    Your account has permission to access the <strong><?php echo $_SESSION['role']; ?></strong> module of Weavora. 
                    If you believe this is incorrect, please contact your System Administrator.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>