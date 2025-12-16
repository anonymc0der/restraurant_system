<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$tables = getTables($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_reservation'])) {
    $table_number = $_POST['table_number'];
    $date = $_POST['reservation_date'];
    $time = $_POST['reservation_time'];
    $people = $_POST['reservation_people'];
    $notes = $_POST['reservation_notes'];
    
    if (empty($table_number) || empty($date) || empty($time) || empty($people)) {
        $error = 'Please fill in complete reservation information';
    } else {
        $table_capacity = 0;
        foreach ($tables as $table) {
            if ($table['Table_Number'] == $table_number) {
                $table_capacity = $table['Capacity'];
                break;
            }
        }
        
        if ($people > $table_capacity) {
            $error = "Selected table can accommodate up to $table_capacity people, please choose a larger table";
        } else {
            if (isTableOccupied($conn, $table_number, $date, $time)) {
                $error = "Table $table_number is already occupied at $time on $date. Please choose another table or time.";
            } else {
                $reservation_id = createReservation($conn, $_SESSION['user_id'], $currentUser['Name'], 
                                                    $date, $time, $table_number, $people, $notes);
                
                if ($reservation_id) {
                    $query_time_msg = '';
                    if (isset($_SESSION['query_time'])) {
                        $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                        unset($_SESSION['query_time']);
                    }
                    $success = "Reservation successful! Reservation ID: $reservation_id" . $query_time_msg;
                } else {
                    $error = "Failed to make reservation";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Table Reservation</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="user.css">
    <style>
        .table-item {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .table-item:hover {
            background-color: #f5f5f5;
        }
        
        .table-item.selected {
            background-color: #bbdefb;
            color: #1565c0;
            border-color: #64b5f6;
            box-shadow: 0 0 0 2px #2196f3;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">Restaurant System</div>
            <div class="user-actions">
                <div id="nav-home" style="color:white;margin-right:10px"><?php echo htmlspecialchars($currentUser['Name']); ?></div>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>My Points 
                <span id="member-level" class="member-level <?php 
                    $memberInfo = getMemberLevel($currentUser['Point'] ?? 0); 
                    echo $memberInfo['class']; 
                ?>"><?php echo $memberInfo['level']; ?></span>
            </h2>
            <div id="user-points" style="font-size:24px;font-weight:700;color:#2c3e50;margin:10px 0">
                <?php echo ($currentUser['Point'] ?? 0) . ' points'; ?>
            </div>
            <p>Points Rule: Earn 1 point for every 1 yuan spent</p>
        </div>

        <div class="tabs">
            <div class="tab" data-tab="order">Order</div>
            <div class="tab active" data-tab="reservation">Table Reservation</div>
            <div class="tab" data-tab="redeem">Points Redemption</div>
            <div class="tab" data-tab="history">Order History</div>
        </div>

        <div id="reservation-tab" class="tab-content active">
            <div class="card">
                <h2>Table Reservation</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #ffebee; border-radius: 4px;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e8f5e9; border-radius: 4px;">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <div class="tables-grid" id="tables-list">
                    <?php foreach ($tables as $table): ?>
                    <div class="table-item <?php echo isset($_POST['table_number']) && $_POST['table_number'] == $table['Table_Number'] ? 'selected' : ''; ?>"
                         data-table="<?php echo $table['Table_Number']; ?>"
                         data-capacity="<?php echo $table['Capacity']; ?>"
                         onclick="selectTable(this)">
                        <div style="font-weight:700"><?php echo $table['Table_Number']; ?></div>
                        <div style="color:#7f8c8d">Capacity: <?php echo $table['Capacity']; ?> people</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" action="" class="reservation-form">
                    <h3>Reservation Information</h3>
                    <input type="hidden" name="table_number" id="selected-table" value="<?php echo $_POST['table_number'] ?? ''; ?>">
                    
                    <div style="display:flex;gap:15px;margin-bottom:15px">
                        <div style="flex:1" class="form-group">
                            <label>Reservation Date</label>
                            <input type="date" id="reservation-date" name="reservation_date" 
                                   value="<?php echo $_POST['reservation_date'] ?? ''; ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required />
                        </div>
                        <div style="flex:1" class="form-group">
                            <label>Reservation Time</label>
                            <select id="reservation-time" name="reservation_time" required>
                                <option value="">Select Time</option>
                                <option value="10:00" <?php echo ($_POST['reservation_time'] ?? '') == '10:00' ? 'selected' : ''; ?>>10:00</option>
                                <option value="11:00" <?php echo ($_POST['reservation_time'] ?? '') == '11:00' ? 'selected' : ''; ?>>11:00</option>
                                <option value="12:00" <?php echo ($_POST['reservation_time'] ?? '') == '12:00' ? 'selected' : ''; ?>>12:00</option>
                                <option value="13:00" <?php echo ($_POST['reservation_time'] ?? '') == '13:00' ? 'selected' : ''; ?>>13:00</option>
                                <option value="14:00" <?php echo ($_POST['reservation_time'] ?? '') == '14:00' ? 'selected' : ''; ?>>14:00</option>
                                <option value="17:00" <?php echo ($_POST['reservation_time'] ?? '') == '17:00' ? 'selected' : ''; ?>>17:00</option>
                                <option value="18:00" <?php echo ($_POST['reservation_time'] ?? '') == '18:00' ? 'selected' : ''; ?>>18:00</option>
                                <option value="19:00" <?php echo ($_POST['reservation_time'] ?? '') == '19:00' ? 'selected' : ''; ?>>19:00</option>
                                <option value="20:00" <?php echo ($_POST['reservation_time'] ?? '') == '20:00' ? 'selected' : ''; ?>>20:00</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Number of People</label>
                        <input type="number" id="reservation-people" name="reservation_people" 
                               min="1" max="20" value="<?php echo $_POST['reservation_people'] ?? ''; ?>" required />
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="reservation-notes" name="reservation_notes" rows="3"><?php echo $_POST['reservation_notes'] ?? ''; ?></textarea>
                    </div>
                    <button type="submit" name="place_reservation" id="place-reservation">Submit Reservation</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedTable = null;
        
        function selectTable(element) {
            document.querySelectorAll('.table-item').forEach(i => i.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selected-table').value = element.dataset.table;
            selectedTable = element;
            
            const capacity = parseInt(element.dataset.capacity);
            document.getElementById('reservation-people').value = capacity;
            document.getElementById('reservation-people').max = capacity;
        }
        
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `user_${tabId}.php`;
            });
        });
        
        document.getElementById('place-reservation').addEventListener('click', function(e) {
            if (!document.getElementById('selected-table').value) {
                alert('Please select a table');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>