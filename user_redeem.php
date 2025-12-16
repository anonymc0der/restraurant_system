<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$redeemItems = getRedeemItems($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_item'])) {
    $item_id = $_POST['item_id'];
    
    $item = null;
    foreach ($redeemItems as $redeemItem) {
        if ($redeemItem['GiftID'] == $item_id || (isset($redeemItem['id']) && $redeemItem['id'] == $item_id)) {
            $item = $redeemItem;
            break;
        }
    }
    
    if ($item) {
        $points_required = $item['Points_Required_for_Redemption'] ?? $item['points_required'] ?? 0;
        $user_points = $currentUser['Point'] ?? $currentUser['points'] ?? 0;
        
        if ($user_points >= $points_required) {
            if (redeemPoints($conn, $_SESSION['user_id'], $item_id, $points_required)) {
                $query_time_msg = '';
                if (isset($_SESSION['query_time'])) {
                    $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                    unset($_SESSION['query_time']);
                }
                $success = "Redemption successful! You have obtained " . ($item['Name'] ?? $item['name'] ?? '') . $query_time_msg;
                $currentUser = getCurrentUser($conn);
            } else {
                $error = "Redemption failed";
            }
        } else {
            $error = "Insufficient points, cannot redeem";
        }
    } else {
        $error = "Item not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Points Redemption</title>
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
            <div class="tab active" data-tab="redeem">Points Redemption</div>
            <div class="tab" data-tab="history">Order History</div>
        </div>

        <div id="redeem-tab" class="tab-content active">
            <div class="card">
                <h2>Points Redemption</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #ffe6e6; border-radius: 4px;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e6ffe6; border-radius: 4px;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="grid" id="redeem-items">
                    <?php foreach ($redeemItems as $item): ?>
                    <div class="product-card">
                        <h3><?php echo htmlspecialchars($item['Name'] ?? $item['name'] ?? ''); ?></h3>
                        <p><?php echo htmlspecialchars($item['Description'] ?? $item['description'] ?? ''); ?></p>
                        <p>Required Points: <?php echo $item['Points_Required_for_Redemption'] ?? $item['points_required'] ?? 0; ?></p>
                        <form method="POST" action="">
                            <input type="hidden" name="item_id" value="<?php echo $item['GiftID'] ?? $item['id'] ?? ''; ?>">
                            <button type="submit" name="redeem_item" class="redeem-item" 
                                    onclick="return confirm('Are you sure you want to redeem <?php echo addslashes($item['Name'] ?? $item['name'] ?? ''); ?>? Will consume <?php echo $item['Points_Required_for_Redemption'] ?? $item['points_required'] ?? 0; ?> points')">
                                Redeem
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `user_${tabId}.php`;
            });
        });
    </script>
</body>
</html>