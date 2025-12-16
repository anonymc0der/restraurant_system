<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'staff') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$orders = getOrders($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['complete_order'])) {
        $order_id = $_POST['order_id'];
        if (updateOrderStatus($conn, $order_id, 'Completed')) {
            $query_time_msg = '';
            if (isset($_SESSION['query_time'])) {
                $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                unset($_SESSION['query_time']);
            }
            $success = "Success" . $query_time_msg;
            $orders = getOrders($conn);
        } else {
            $error = "Failed";
        }
    } elseif (isset($_POST['cancel_order'])) {
        $order_id = $_POST['order_id'];
        if (updateOrderStatus($conn, $order_id, 'Cancelled')) {
            $query_time_msg = '';
            if (isset($_SESSION['query_time'])) {
                $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                unset($_SESSION['query_time']);
            }
            $success = "Cancelled" . $query_time_msg;
            $orders = getOrders($conn);
        } else {
            $error = "Failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Order Management</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="staff.css">
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">Staff</div>
            <div>
                <span id="staff-name" style="color:#fff;margin-right:12px">
                    <?php echo htmlspecialchars($currentUser['Name'] ?? 'Staff'); ?>
                </span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="tabs">
            <div class="tab active" data-tab="orders">Order Management</div>
            <div class="tab" data-tab="reservations">Reservation Management</div>
            <div class="tab" data-tab="materials">Material Management</div>
            <div class="tab" data-tab="profile">Personal Information</div>
        </div>

        <div id="orders-tab" class="tab-content active">
            <div class="card">
                <h2>Order Management</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e6ffe6; border-radius: 4px;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-orders">
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['items']); ?></td>
                            <td><?php echo number_format($order['total_amount'], 2); ?> RMB</td>
                            <td>
                                <span class="status-badge 
                                    <?php 
                                    $status = $order['order_status'];
                                    if ($status == 'Completed') echo 'status-completed';
                                    elseif ($status == 'Pending' || $status == 'Processing') echo 'status-pending';
                                    else echo 'status-cancelled';
                                    ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td style="max-width: 200px; word-wrap: break-word;">
                                <?php 
                                $notes = $order['special_requests'] ?? '';
                                echo !empty($notes) ? htmlspecialchars($notes) : 'None';
                                ?>
                            </td>
                            <td>
                                <?php if ($status == 'Pending' || $status == 'Processing'): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_number']; ?>">
                                    <button type="submit" name="complete_order" class="btn-success">Complete</button>
                                </form>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_number']; ?>">
                                    <button type="submit" name="cancel_order" class="btn-danger" 
                                            onclick="return confirm('Are you sure you want to cancel this order?')">Cancel</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab').forEach(tab=>{
            tab.addEventListener('click', function(){
                const tabId = this.getAttribute('data-tab');
                window.location.href = `staff_${tabId}.php`;
            });
        });
    </script>
</body>
</html>