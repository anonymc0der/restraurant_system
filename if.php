<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if ($conn->connect_error) {
    die("Fail: " . $conn->connect_error);
}

function getProductMaterialsMap() {
    return [
        'Coffee' => [
            'Coffee Beans' => 0.02,
            'Milk' => 0.1,
            'Sugar' => 0.01
        ],
        'Sandwich' => [
            'Bread' => 0.05,
            'Fruits' => 0.02,
            'Vegetables' => 0.02
        ],
        'Salad' => [
            'Fruits' => 0.05,
            'Vegetables' => 0.1
        ],
        'Cake' => [
            'Bread' => 0.03,
            'Fruits' => 0.01,
            'Sugar' => 0.01
        ],
        'Juice' => [
            'Fruits' => 0.1
        ],
        'Burger' => [
            'Bread' => 0.05,
            'Fruits' => 0.01,
            'Vegetables' => 0.02
        ]
    ];
}

function getUserData($conn, $user_id, $user_type) {
    if ($user_type == 'user') {
        $sql = "SELECT c.*, mr.Level FROM Customer c 
                LEFT JOIN Membership_Rule mr ON c.Rule_ID = mr.Rule_ID 
                WHERE c.CustomerID = ?";
    } else {
        $sql = "SELECT * FROM Staff WHERE StaffID = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user['type'] = $user_type;
        return $user;
    }
    
    return null;
}

function getOrders($conn, $customer_id = null, $status = null) {
    $sql = "SELECT 
                o.OrderID as order_number,
                MIN(o.ID) as first_id,
                GROUP_CONCAT(CONCAT(p.Name, ' x ', c.Quantity)) as items,
                SUM(o.Total_Amount) as total_amount,
                MIN(o.Order_Time) as order_time,
                MIN(o.Order_Status) as order_status,
                MAX(CASE WHEN o.Special_Requests != '' AND o.Special_Requests != 'None' THEN o.Special_Requests ELSE NULL END) as special_requests,
                cust.Name as customer_name,
                COUNT(DISTINCT o.ID) as item_count
            FROM `Order` o 
            LEFT JOIN placed_by pb ON o.ID = pb.OrderID
            LEFT JOIN Customer cust ON pb.CustomerID = cust.CustomerID
            LEFT JOIN contain c ON o.ID = c.ID
            LEFT JOIN Product p ON c.ProductID = p.ProductID
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($customer_id) {
        $sql .= " AND pb.CustomerID = ?";
        $params[] = $customer_id;
        $types .= "i";
    }
    
    if ($status) {
        $sql .= " AND o.Order_Status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " GROUP BY o.OrderID, cust.Name
              ORDER BY MIN(o.Order_Time) DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        if (empty($row['special_requests'])) {
            $row['special_requests'] = 'None';
        }
        $orders[] = $row;
    }
    
    return $orders;
}

function getReservations($conn, $customer_id = null, $status = null) {
    $sql = "SELECT r.*, c.Name as customer_name, 
                   GROUP_CONCAT(t.Table_Number) as table_numbers
            FROM Reservation r
            LEFT JOIN Customer c ON r.CustomerID = c.CustomerID
            LEFT JOIN reserve rs ON r.ReservationID = rs.ReservationID
            LEFT JOIN `Table` t ON rs.TableID = t.TableID
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($customer_id) {
        $sql .= " AND r.CustomerID = ?";
        $params[] = $customer_id;
        $types .= "i";
    }
    
    $sql .= " GROUP BY r.ReservationID";
    $sql .= " ORDER BY r.Reservation_Date DESC, r.Reservation_Time DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    return $reservations;
}

function getMaterials($conn) {
    $sql = "SELECT * FROM Inventory ORDER BY Name";
    $result = $conn->query($sql);
    
    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $row['Inventory_Quantity'] = (float)$row['Inventory_Quantity'];
        $row['MinQuantity'] = (float)$row['MinQuantity'];
        $materials[] = $row;
    }
    
    return $materials;
}

function getProducts($conn) {
    $sql = "SELECT * FROM Product ORDER BY Name";
    $result = $conn->query($sql);
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

function getTables($conn) {
    $sql = "SELECT * FROM `Table` ORDER BY Table_Number";
    $result = $conn->query($sql);
    
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row;
    }
    
    return $tables;
}

function getRedeemItems($conn) {
    $sql = "SELECT * FROM Gift ORDER BY Points_Required_for_Redemption";
    $result = $conn->query($sql);
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function isTableOccupied($conn, $table_number, $date, $time) {
    $sql = "SELECT COUNT(*) as count FROM Reservation r
            JOIN reserve rs ON r.ReservationID = rs.ReservationID
            JOIN `Table` t ON rs.TableID = t.TableID
            WHERE t.Table_Number = ? 
            AND r.Reservation_Date = ? 
            AND r.Reservation_Time = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return true;
    }
    
    $stmt->bind_param("sss", $table_number, $date, $time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
    
    return false;
}

function createOrder($conn, $customer_id, $customer_name, $items, $total_amount, $special_requests = '') {
    $result = $conn->query("SELECT MAX(OrderID) as max_order_id FROM `Order`");
    $row = $result->fetch_assoc();
    $order_number = ($row['max_order_id'] ?? 0) + 1;
    
    $item_pattern = '/([^,]+)\s*x\s*(\d+)/';
    $matches = [];
    
    if (preg_match_all($item_pattern, $items, $matches, PREG_SET_ORDER)) {
        $conn->begin_transaction();
        
        try {
            $order_ids = [];
            
            foreach ($matches as $match) {
                $start_time = microtime(true);
                $product_name = trim($match[1]);
                $quantity = intval($match[2]);
                
                $product_sql = "SELECT ProductID, Price FROM Product WHERE LOWER(Name) = LOWER(?)";
                $product_stmt = $conn->prepare($product_sql);
                $product_stmt->bind_param("s", $product_name);
                $product_stmt->execute();
                $product_result = $product_stmt->get_result();
                
                if ($product_result->num_rows > 0) {
                    $product = $product_result->fetch_assoc();
                    $product_id = $product['ProductID'];
                    $price = $product['Price'];
                    
                    $item_total = $price * $quantity;
                    
                    $order_sql = "INSERT INTO `Order` (OrderID, Order_Status, Special_Requests, Total_Amount, Order_Time) 
                                 VALUES (?, 'Pending', ?, ?, NOW())";
                    $order_stmt = $conn->prepare($order_sql);
                    $order_stmt->bind_param("isd", $order_number, $special_requests, $item_total);
                    $order_stmt->execute();
                    $order_db_id = $order_stmt->insert_id;
                    
                    $order_ids[] = $order_db_id;
                    
                    $contain_sql = "INSERT INTO contain (ID, ProductID, Quantity) VALUES (?, ?, ?)";
                    $contain_stmt = $conn->prepare($contain_sql);
                    $contain_stmt->bind_param("iii", $order_db_id, $product_id, $quantity);
                    $contain_stmt->execute();
                }
            }
            
            foreach ($order_ids as $order_db_id) {
                $placed_by_sql = "INSERT INTO placed_by (OrderID, CustomerID) VALUES (?, ?)";
                $placed_by_stmt = $conn->prepare($placed_by_sql);
                $placed_by_stmt->bind_param("ii", $order_db_id, $customer_id);
                $placed_by_stmt->execute();
            }
            
            $update_points_sql = "UPDATE Customer 
                                  SET Point = Point + (
                                      SELECT CAST(SUM(Total_Amount) AS UNSIGNED)
                                      FROM `Order` 
                                      WHERE OrderID = ?
                                  ) 
                                  WHERE CustomerID = ?";
            $update_points_stmt = $conn->prepare($update_points_sql);
            $update_points_stmt->bind_param("ii", $order_number, $customer_id);
            $update_points_stmt->execute();
            
            $conn->commit();
            $end_time = microtime(true);
            $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
            error_log("Query time set in session: " . $_SESSION['query_time'] . " ms");
            return $order_number;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

function createReservation($conn, $customer_id, $customer_name, $date, $time, $table_numbers, $people_count, $notes = '') {
    $start_time = microtime(true);
    $table_numbers_array = explode(',', $table_numbers);
    foreach ($table_numbers_array as $table_number) {
        $table_number = trim($table_number);
        if (isTableOccupied($conn, $table_number, $date, $time)) {
            return false;
        }
    }
    
    $sql = "INSERT INTO Reservation (Number_of_Reserved_Guests, Reservation_Time, Reservation_Date, CustomerID, notes) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issis", $people_count, $time, $date, $customer_id, $notes);
    
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        foreach ($table_numbers_array as $table_number) {
            $table_number = trim($table_number);
            
            $table_sql = "SELECT TableID FROM `Table` WHERE Table_Number = ?";
            $table_stmt = $conn->prepare($table_sql);
            $table_stmt->bind_param("s", $table_number);
            $table_stmt->execute();
            $table_result = $table_stmt->get_result();
            
            if ($table_result->num_rows > 0) {
                $table = $table_result->fetch_assoc();
                $table_id = $table['TableID'];
                
                $reserve_sql = "INSERT INTO reserve (ReservationID, TableID) VALUES (?, ?)";
                $reserve_stmt = $conn->prepare($reserve_sql);
                $reserve_stmt->bind_param("ii", $reservation_id, $table_id);
                $reserve_stmt->execute();
            }
        }
        $end_time = microtime(true);
        $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
        error_log("Reservation query time set in session: " . $_SESSION['query_time'] . " ms");
        return $reservation_id;
    } else {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
}

function updateMaterialQuantity($conn, $material_id, $quantity_to_add) {
    $start_time = microtime(true);
    $sql = "UPDATE Inventory SET Inventory_Quantity = Inventory_Quantity + ? WHERE InventoryID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("di", $quantity_to_add, $material_id);
    
    if ($stmt->execute()) {
        $check_sql = "SELECT Inventory_Quantity, MinQuantity FROM Inventory WHERE InventoryID = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $material_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_quantity = (float)$row['Inventory_Quantity'];
            $min_quantity = (float)$row['MinQuantity'];
            $new_status = ($current_quantity < $min_quantity) ? 'Need Replenishment' : 'Sufficient';
            
            $update_sql = "UPDATE Inventory SET Status = ? WHERE InventoryID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $material_id);
            $update_stmt->execute();
        }
        $end_time = microtime(true);
        $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
        error_log("Update material quantity query time set in session: " . $_SESSION['query_time'] . " ms");
        return true;
    } else {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
}

function redeemPoints($conn, $customer_id, $item_id, $points_required) {
    $start_time = microtime(true);
    $check_sql = "SELECT Point FROM Customer WHERE CustomerID = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['Point'] >= $points_required) {
            $update_sql = "UPDATE Customer SET Point = Point - ? WHERE CustomerID = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                error_log("Prepare failed: " . $conn->error);
                return false;
            }
            
            $update_stmt->bind_param("ii", $points_required, $customer_id);
            
            if ($update_stmt->execute()) {
                $exchange_sql = "INSERT INTO Exchange (GiftID, CustomerID, Time) VALUES (?, ?, NOW())";
                $exchange_stmt = $conn->prepare($exchange_sql);
                $exchange_stmt->bind_param("ii", $item_id, $customer_id);
                $exchange_stmt->execute();
                
                $end_time = microtime(true);
                $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
                error_log("Redemption query time set in session: " . $_SESSION['query_time'] . " ms");
                return true;
            }
        }
    }
    
    return false;
}

function updateOrderStatus($conn, $order_number, $new_status) {
    $start_time = microtime(true);
    $check_sql = "SELECT COUNT(*) as count FROM `Order` WHERE OrderID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $order_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();

    if ($row['count'] == 0) {
        return false;
    }

    $order_sql = "SELECT MIN(Order_Status) AS Order_Status 
                  FROM `Order` 
                  WHERE OrderID = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("i", $order_number);
    $order_stmt->execute();
    $result = $order_stmt->get_result();
    $order = $result->fetch_assoc();
    $old_status = $order['Order_Status'];

    $conn->begin_transaction();

    try {

        if ($old_status !== 'Completed' && $new_status === 'Completed') {
            reduceMaterialsForOrder($conn, $order_number);
        }

        $update_sql = "UPDATE `Order` SET Order_Status = ? WHERE OrderID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $order_number);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status");
        }

        $conn->commit();
        $end_time = microtime(true);
        $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
        error_log("Update order status query time set in session: " . $_SESSION['query_time'] . " ms");
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating order status: " . $e->getMessage());
        return false;
    }
}

function updateReservationStatus($conn, $reservation_id, $new_status) {
    $start_time = microtime(true);
    if ($new_status == 'Cancelled') {
        $sql = "DELETE FROM Reservation WHERE ReservationID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            $end_time = microtime(true);
            $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2); // 毫秒
            error_log("Update reservation status query time set in session: " . $_SESSION['query_time'] . " ms");
            return true;
        } else {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
    }
    
    return false;
}

function getUserOrders($conn, $user_id) {
    $sql = "SELECT 
                o.OrderID as order_number,
                GROUP_CONCAT(CONCAT(p.Name, ' x ', c.Quantity)) as items,
                SUM(o.Total_Amount) as total_amount,
                MIN(o.Order_Time) as order_time,
                MIN(o.Order_Status) as order_status,
                COUNT(DISTINCT o.ID) as item_count
            FROM `Order` o 
            JOIN placed_by pb ON o.ID = pb.OrderID
            JOIN contain c ON o.ID = c.ID
            JOIN Product p ON c.ProductID = p.ProductID
            WHERE pb.CustomerID = ? 
            GROUP BY o.OrderID
            ORDER BY MIN(o.Order_Time) DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}

function getUserReservations($conn, $user_id) {
    $sql = "SELECT r.*, GROUP_CONCAT(t.Table_Number) as table_numbers
            FROM Reservation r
            LEFT JOIN reserve rs ON r.ReservationID = rs.ReservationID
            LEFT JOIN `Table` t ON rs.TableID = t.TableID
            WHERE r.CustomerID = ?
            GROUP BY r.ReservationID
            ORDER BY r.Reservation_Date DESC, r.Reservation_Time DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    return $reservations;
}

function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function getCurrentUser($conn) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];
        
        return getUserData($conn, $user_id, $user_type);
    }
    return null;
}

function getOrderDetails($conn, $order_number) {
    $sql = "SELECT 
                p.Name, 
                p.Price, 
                c.Quantity, 
                o.Total_Amount as item_total,
                o.Order_Status as item_status,
                (p.Price * c.Quantity) as calculated_total
            FROM `Order` o
            JOIN contain c ON o.ID = c.ID
            JOIN Product p ON c.ProductID = p.ProductID
            WHERE o.OrderID = ?
            ORDER BY o.ID";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $order_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $details = [];
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
        $total_amount += $row['item_total'];
    }
    
    if (!empty($details)) {
        $details['summary'] = [
            'order_number' => $order_number,
            'total_amount' => $total_amount,
            'item_count' => count($details),
            'status' => $details[0]['item_status']
        ];
    }
    
    return $details;
}

function getExchangeRecords($conn, $customer_id = null) {
    $sql = "SELECT e.*, g.Name as gift_name, g.Points_Required_for_Redemption, c.Name as customer_name
            FROM Exchange e
            JOIN Gift g ON e.GiftID = g.GiftID
            JOIN Customer c ON e.CustomerID = c.CustomerID";
    
    $params = [];
    $types = "";
    
    if ($customer_id) {
        $sql .= " WHERE e.CustomerID = ?";
        $params[] = $customer_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY e.Time DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

function reduceMaterialsForOrder($conn, $order_number) {
    $order_items_sql = "SELECT p.ProductID, c.Quantity, p.Name as ProductName
                        FROM `Order` o
                        JOIN contain c ON o.ID = c.ID
                        JOIN Product p ON c.ProductID = p.ProductID
                        WHERE o.OrderID = ?";
    $order_items_stmt = $conn->prepare($order_items_sql);
    $order_items_stmt->bind_param("i", $order_number);
    $order_items_stmt->execute();
    $order_items_result = $order_items_stmt->get_result();

    while ($item = $order_items_result->fetch_assoc()) {
        $product_name = $item['ProductName'];
        $ordered_quantity = $item['Quantity'];
        $product_materials = getProductMaterialsMap()[$product_name] ?? [];

        foreach ($product_materials as $material_name => $material_ratio) {
            $material_sql = "SELECT InventoryID, Inventory_Quantity, MinQuantity FROM Inventory WHERE Name = ?";
            $material_stmt = $conn->prepare($material_sql);
            $material_stmt->bind_param("s", $material_name);
            $material_stmt->execute();
            $material_result = $material_stmt->get_result();

            if ($material_result->num_rows > 0) {
                $material = $material_result->fetch_assoc();
                $inventory_id = $material['InventoryID'];
                $current_inventory = $material['Inventory_Quantity'];
                $min_quantity = $material['MinQuantity'];

                $quantity_to_reduce = $ordered_quantity * $material_ratio;
                $new_inventory = $current_inventory - $quantity_to_reduce;

                $update_inventory_sql = "UPDATE Inventory SET Inventory_Quantity = ? WHERE InventoryID = ?";
                $update_inventory_stmt = $conn->prepare($update_inventory_sql);
                $update_inventory_stmt->bind_param("di", $new_inventory, $inventory_id);
                $update_inventory_stmt->execute();

                // Update status if below min quantity
                $new_status = ($new_inventory < $min_quantity) ? 'Need Replenishment' : 'Sufficient';
                $update_status_sql = "UPDATE Inventory SET Status = ? WHERE InventoryID = ?";
                $update_status_stmt = $conn->prepare($update_status_sql);
                $update_status_stmt->bind_param("si", $new_status, $inventory_id);
                $update_status_stmt->execute();
            }
        }
    }
}
function updateStaffAvatar($conn, $staff_id, $avatar_data) {
    $start_time = microtime(true);
    
    // 检查头像数据是否有效
    if ($avatar_data === null || $avatar_data === '') {
        return false;
    }
    
    $sql = "UPDATE Staff SET Avatar = ? WHERE StaffID = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    // 使用 'b' 参数表示二进制数据
    $null = NULL;
    $stmt->bind_param("bi", $null, $staff_id);
    $stmt->send_long_data(0, $avatar_data);
    
    if ($stmt->execute()) {
        $end_time = microtime(true);
        $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2);
        error_log("Update avatar query time: " . $_SESSION['query_time'] . " ms");
        return true;
    } else {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
}

function getStaffAvatar($conn, $staff_id) {
    $sql = "SELECT Avatar FROM Staff WHERE StaffID = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($avatar);
        $stmt->fetch();
        return $avatar;
    }
    
    return null;
}

function deleteStaffAvatar($conn, $staff_id) {
    $start_time = microtime(true);
    
    $sql = "UPDATE Staff SET Avatar = NULL WHERE StaffID = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $staff_id);
    
    if ($stmt->execute()) {
        $end_time = microtime(true);
        $_SESSION['query_time'] = round(($end_time - $start_time) * 1000, 2);
        return true;
    } else {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
}
?>


