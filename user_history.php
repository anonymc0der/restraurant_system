<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$userOrders = getOrders($conn, $_SESSION['user_id']);
$userReservations = getReservations($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Order History</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">Restaurant System</div>
            <div class="user-actions">
                <div id="nav-home" style="color:white;margin-right:10px"><?php echo htmlspecialchars($currentUser['Name'] ?? $currentUser['username'] ?? ''); ?></div>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>My Points 
                <span id="member-level" class="member-level <?php 
                    $memberInfo = getMemberLevel($currentUser['Point'] ?? $currentUser['points'] ?? 0); 
                    echo $memberInfo['class']; 
                ?>"><?php echo $memberInfo['level']; ?></span>
            </h2>
            <div id="user-points" style="font-size:24px;font-weight:700;color:#2c3e50;margin:10px 0">
                <?php echo ($currentUser['Point'] ?? $currentUser['points'] ?? 0) . ' points'; ?>
            </div>
            <p>Points Rule: Earn 1 point for every 1 yuan spent</p>
        </div>

        <div class="tabs">
            <div class="tab" data-tab="order">Order</div>
            <div class="tab" data-tab="reservation">Table Reservation</div>
            <div class="tab" data-tab="redeem">Points Redemption</div>
            <div class="tab active" data-tab="history">Order History</div>
        </div>

        <div id="history-tab" class="tab-content active">
            <div class="card">
                <h2>Order History</h2>
                <div class="tabs" style="margin-bottom:10px">
                    <div class="tab active" data-subtab="product-orders">Product Orders</div>
                    <div class="tab" data-subtab="reservation-orders">Reservation Records</div>
                </div>
                <div id="product-orders-tab" class="tab-content active">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="order-history">
                            <?php if (!empty($userOrders)): ?>
                                <?php foreach ($userOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number'] ?? $order['order_id'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['order_time'] ?? $order['order_date'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($order['items'] ?? ''); ?></td>
                                    <td><?php echo ($order['total_amount'] ?? 0); ?> RMB</td>
                                    <td>
                                        <span class="status-badge 
                                            <?php 
                                            $status = $order['order_status'] ?? $order['status'] ?? '';
                                            if ($status == 'Completed') echo 'status-completed';
                                            elseif ($status == 'Processing') echo 'status-pending';
                                            else echo 'status-pending';
                                            ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center">No order records yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="reservation-orders-tab" class="tab-content" style="display:none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Table</th>
                                <th>People</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="reservation-history">
                            <?php if (!empty($userReservations)): ?>
                                <?php foreach ($userReservations as $reservation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['ReservationID'] ?? $reservation['reservation_id'] ?? ''); ?></td>
                                    <td><?php echo $reservation['Reservation_Date'] ?? $reservation['reservation_date'] ?? ''; ?></td>
                                    <td><?php echo $reservation['Reservation_Time'] ?? $reservation['reservation_time'] ?? ''; ?></td>
                                    <td><?php echo htmlspecialchars($reservation['table_numbers'] ?? $reservation['table_number'] ?? ''); ?></td>
                                    <td><?php echo $reservation['Number_of_Reserved_Guests'] ?? $reservation['people_count'] ?? 0; ?></td>
                                    <td><?php echo !empty($reservation['notes']) ? htmlspecialchars($reservation['notes']) : 'None'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center">No reservation records yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab').forEach(tab=>{
            tab.addEventListener('click', function(){
                const tabId = this.getAttribute('data-tab');
                if (tabId) {
                    window.location.href = `user_${tabId}.php`;
                }
                
                const sub = this.getAttribute('data-subtab');
                if (sub) {
                    document.querySelectorAll('[data-subtab]').forEach(t=>t.classList.remove('active'));
                    this.classList.add('active');
                    
                    if (sub === 'product-orders') {
                        document.getElementById('product-orders-tab').style.display = '';
                        document.getElementById('reservation-orders-tab').style.display = 'none';
                    } else {
                        document.getElementById('product-orders-tab').style.display = 'none';
                        document.getElementById('reservation-orders-tab').style.display = '';
                    }
                }
            });
        });
    </script>
</body>
</html>