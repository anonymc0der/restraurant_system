<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'staff') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$reservations = getReservations($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancel_reservation'])) {
        $reservation_id = $_POST['reservation_id'];
        if (updateReservationStatus($conn, $reservation_id, 'Cancelled')) {
            $query_time_msg = '';
            if (isset($_SESSION['query_time'])) {
                $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                unset($_SESSION['query_time']);
            }
            $success = "Cancelled" . $query_time_msg;
            $reservations = getReservations($conn);
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
    <title>Reservation Management</title>
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
            <div class="tab" data-tab="orders">Order Management</div>
            <div class="tab active" data-tab="reservations">Reservation Management</div>
            <div class="tab" data-tab="materials">Material Management</div>
            <div class="tab" data-tab="profile">Personal Information</div>
        </div>

        <div id="reservations-tab" class="tab-content active">
            <div class="card">
                <h2>Reservation Management</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e6ffe6; border-radius: 4px;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Table</th>
                            <th>People</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-reservations">
                        <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['ReservationID']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['customer_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($reservation['Reservation_Date']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['Reservation_Time']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['table_numbers'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($reservation['Number_of_Reserved_Guests']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['notes'] ?? ''); ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['ReservationID']; ?>">
                                    <button type="submit" name="cancel_reservation" class="btn-danger" 
                                            onclick="return confirm('Are you sure you want to cancel this reservation?')">Cancel</button>
                                </form>
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