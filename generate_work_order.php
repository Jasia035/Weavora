<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';
checkLogin();

$so_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($so_id > 0) {
    try {
        
        $pdo->beginTransaction();

       
        $stmt = $pdo->prepare("SELECT status FROM sales_orders WHERE id = ?");
        $stmt->execute([$so_id]);
        $order = $stmt->fetch();

        if ($order && $order['status'] == 'pending') {
            
           
            $updateSO = $pdo->prepare("UPDATE sales_orders SET status = 'in-production' WHERE id = ?");
            $updateSO->execute([$so_id]);

         
            $scheduledDate = date('Y-m-d', strtotime('+7 days'));
            $insertWO = $pdo->prepare("INSERT INTO work_orders (sales_order_id, scheduled_date, status) VALUES (?, ?, 'IN_PROGRESS')");
            $insertWO->execute([$so_id, $scheduledDate]);

            $pdo->commit();
            header("Location: sales.php?msg=Work Order Generated Successfully");
            exit();
        } else {
            $pdo->rollBack();
            die("Error: Order is already in production or does not exist.");
        }

    } catch (Exception $e) {
       
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: sales.php");
    exit();
}