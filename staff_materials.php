<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'staff') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$materials = getMaterials($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supply_material'])) {
    $material_id = $_POST['material_id'];
    $supply_amount = $_POST['supply_amount'];
    
    if (is_numeric($supply_amount) && $supply_amount > 0) {
        if (updateMaterialQuantity($conn, $material_id, $supply_amount)) {
            $query_time_msg = '';
            if (isset($_SESSION['query_time'])) {
                $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                unset($_SESSION['query_time']);
            }
            $success = "Success" . $query_time_msg;
            $materials = getMaterials($conn);
        } else {
            $error = "Failed";
        }
    } else {
        $error = "Please enter a valid quantity";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Material Management</title>
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
            <div class="tab" data-tab="reservations">Reservation Management</div>
            <div class="tab active" data-tab="materials">Material Management</div>
            <div class="tab" data-tab="profile">Personal Information</div>
        </div>

        <div id="materials-tab" class="tab-content active">
            <div class="card">
                <h2>Raw Material Inventory</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #ffe6e6; border-radius: 4px;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e6ffe6; border-radius: 4px;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Material Name</th>
                            <th>Stock Quantity</th>
                            <th>Unit</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Min Quantity</th>
                            <th>Supplementary Quantity</th>
                            <th>Operation</th>
                        </tr>
                    </thead>
                    <tbody id="materials-list">
                        <?php foreach ($materials as $material): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($material['Name']); ?></td>
                            <td><?php echo number_format($material['Inventory_Quantity'], 2); ?></td>
                            <td><?php echo htmlspecialchars($material['Unit'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($material['Supplier']); ?></td>
                            <td>
                                <span class="status-badge <?php 
                                    if ($material['Status'] == 'Sufficient') echo 'status-completed';
                                    else echo 'status-pending';
                                ?>">
                                    <?php echo htmlspecialchars($material['Status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($material['MinQuantity'], 2); ?></td>
                            <td>
                                <form method="POST" action="" style="display: flex; gap: 5px;">
                                    <input type="number" name="supply_amount" min="0" step="0.01" 
                                           placeholder="Enter quantity" style="width: 120px;">
                                    <input type="hidden" name="material_id" value="<?php echo $material['InventoryID']; ?>">
                                    <button type="submit" name="supply_material" class="btn-supply" 
                                            style="padding: 5px 10px; font-size: 12px;">Supplement</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="material_id" value="<?php echo $material['InventoryID']; ?>">
                                    <input type="hidden" name="supply_amount" value="10">
                                    <button type="submit" name="supply_material" class="btn-primary" 
                                            style="padding: 5px 10px; font-size: 12px;">+10</button>
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