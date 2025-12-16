<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Fail: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function insertInitialData($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM Membership_Rule");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $levels = ['Bronze', 'Silver', 'Gold'];
        foreach ($levels as $level) {
            $sql = "INSERT INTO Membership_Rule (Level) VALUES ('$level')";
            $conn->query($sql);
        }
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM Product");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $products = [
            "('Coffee', 25, '☕')",
            "('Sandwich', 18, '🥪')",
            "('Salad', 22, '🥗')",
            "('Cake', 30, '🍰')",
            "('Juice', 15, '🧃')",
            "('Burger', 28, '🍔')"
        ];
        
        $sql = "INSERT INTO Product (Name, Price, Image) VALUES " . implode(',', $products);
        $conn->query($sql);
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM Inventory");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $materials = [
            "('Coffee Beans', 50, 'Yunnan Coffee Supplier', 'kg', 'Sufficient', 10)",
            "('Milk', 30, 'Local Dairy', 'L', 'Sufficient', 10)",
            "('Bread', 20, 'City Bakery', 'kg', 'Sufficient', 10)",
            "('Sugar', 5, 'Southern Sugar Industry', 'kg', 'Need Replenishment', 10)",
            "('Vegetables', 15, 'Green Farm', 'kg', 'Sufficient', 10)",
            "('Fruits', 8, 'Fresh Orchard', 'kg', 'Need Replenishment', 10)"
        ];
        
        $sql = "INSERT INTO Inventory (Name, Inventory_Quantity, Supplier, Unit, Status, MinQuantity) VALUES " . implode(',', $materials);
        $conn->query($sql);
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM `Table`");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $tables = [
            "('A1', 2, 'available')",
            "('A2', 2, 'available')",
            "('A3', 4, 'available')",
            "('A4', 4, 'occupied')",
            "('B1', 6, 'available')",
            "('B2', 6, 'available')",
            "('B3', 6, 'occupied')",
            "('C1', 10, 'available')"
        ];
        
        $sql = "INSERT INTO `Table` (Table_Number, Capacity, Occupancy_Status) VALUES " . implode(',', $tables);
        $conn->query($sql);
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM Gift");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $items = [
            "('5 RMB Coupon', 50, 'Can deduct 5 RMB on next purchase')",
            "('10 RMB Coupon', 90, 'Can deduct 10 RMB on next purchase')",
            "('Free Coffee', 100, 'Redeem a free coffee')",
            "('Birthday Cake', 500, 'Can redeem a cake on birthday')"
        ];
        
        $sql = "INSERT INTO Gift (Name, Points_Required_for_Redemption, Description) VALUES " . implode(',', $items);
        $conn->query($sql);
    }

     $result = $conn->query("SELECT COUNT(*) as count FROM made_from");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $sql = "
        INSERT INTO made_from (InventoryID, ProductID, Quantity) VALUES
        -- Coffee
        (1, 1, 0.02),   -- Coffee Beans
        (2, 1, 0.10),   -- Milk
        (4, 1, 0.01),   -- Sugar

        -- Sandwich
        (3, 2, 0.05),   -- Bread slice
        (5, 2, 0.02),   -- Vegetables
        (6, 2, 0.02),   -- Fruits

        -- Salad
        (5, 3, 0.10),   -- Vegetables
        (6, 3, 0.05),   -- Fruits

        -- Cake
        (3, 4, 0.03),   -- Bread / flour base
        (4, 4, 0.01),   -- Sugar
        (2, 4, 0.01),   -- Milk

        -- Juice
        (6, 5, 0.3),   -- Fruits

        -- Burger
        (3, 6, 0.05),   -- Bread
        (6, 6, 0.01),   -- Fruits
        (5, 6, 0.02);   -- Vegetables
        ";
        $conn->query($sql);
    }
}

insertInitialData($conn);
$result = $conn->query("SELECT COUNT(*) as count FROM Staff");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $staff_members = [
            "('123456', 'staff', '13800138000')"
        ];
        
        $sql = "INSERT INTO Staff (Password, Name, Phone) VALUES " . implode(',', $staff_members);
        $conn->query($sql);
    }

function authenticateUser($conn, $username, $password, $user_type) {
    if ($user_type == 'user') {
        $sql = "SELECT c.*, mr.Level FROM Customer c 
                LEFT JOIN Membership_Rule mr ON c.Rule_ID = mr.Rule_ID 
                WHERE c.Name = ?";
    } else {
        $sql = "SELECT * FROM Staff WHERE Name = ?";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (strval($user['Password']) === $password) {
            if ($user_type == 'user') {
                $_SESSION['user_id'] = $user['CustomerID'];
                $_SESSION['points'] = $user['Point'];
                $_SESSION['member_level'] = $user['Level'] ?? 'Bronze';
            } else {
                $_SESSION['user_id'] = $user['StaffID'];
            }
            $_SESSION['user_type'] = $user_type;
            $_SESSION['username'] = $user['Name'];
            return $user;
        }
    }
    return false;
}

function registerUser($conn, $username, $password, $phone, $user_type) {
    if ($user_type == 'user') {
        $bronze_id = getRuleIdByLevel($conn, 'Bronze');
        
        $check_sql = "SELECT CustomerID FROM Customer WHERE Name = ? OR Phone = ?";
        $insert_sql = "INSERT INTO Customer (Name, Password, Phone, CreatedTime, Rule_ID) VALUES (?, ?, ?, NOW(), ?)";
    } else {
        $check_sql = "SELECT StaffID FROM Staff WHERE Name = ? OR Phone = ?";
        $insert_sql = "INSERT INTO Staff (Name, Password, Phone) VALUES (?, ?, ?)";
    }
    
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        return "Database error";
    }
    $check_stmt->bind_param("ss", $username, $phone);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        return "Username or phone number already exists";
    }
    
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        return "Database error";
    }
    
    $password_int = intval($password);
    
    if ($user_type == 'user') {
        $insert_stmt->bind_param("sisi", $username, $password_int, $phone, $bronze_id);
    } else {
        $insert_stmt->bind_param("sis", $username, $password_int, $phone);
    }
    
    if ($insert_stmt->execute()) {
        return true;
    } else {
        return "Registration failed: " . $insert_stmt->error;
    }
}

function getRuleIdByLevel($conn, $level) {
    $sql = "SELECT Rule_ID FROM Membership_Rule WHERE Level = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $level);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Rule_ID'];
    }
    
    $sql = "SELECT Rule_ID FROM Membership_Rule LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Rule_ID'];
    }
    
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getMemberLevel($points) {
    if ($points >= 1000) {
        return ['level' => 'Gold', 'class' => 'member-gold'];
    } elseif ($points >= 500) {
        return ['level' => 'Silver', 'class' => 'member-silver'];
    } else {
        return ['level' => 'Bronze', 'class' => 'member-bronze'];
    }
}

function logout() {
    session_destroy();
    redirect('login.php');
}
?>
