<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$products = getProducts($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $items_json = $_POST['items'] ?? '';
    $total = $_POST['total'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    if (!empty($items_json) && $total > 0) {
        $items_array = json_decode($items_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($items_array)) {
            $error = "Invalid items data";
        } elseif (!empty($items_array)) {
            $items_text = implode(', ', array_map(function($item) {
                return $item['name'] . ' x ' . $item['quantity'];
            }, $items_array));

            if (!isset($_SESSION['user_id'])) {
                $error = "User not logged in.";
            } elseif (!isset($currentUser['Name'])) {
                $error = "User information not available.";
            } else {
                $order_id = createOrder($conn, $_SESSION['user_id'], $currentUser['Name'], $items_text, $total, $notes);
                
                if ($order_id) {
                    $currentUser = getCurrentUser($conn);
                    $query_time_msg = '';
                    if (isset($_SESSION['query_time'])) {
                        $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                        unset($_SESSION['query_time']);
                    }
                    $success = "Order submitted successfully! Earned $total points" . $query_time_msg;
                    $cart = [];
                } else {
                    $error = "Failed to submit order";
                }
            }
        } else {
            $error = "Shopping cart is empty";
        }
    } else {
        $error = "Shopping cart is empty";
    }
}

$cart = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Order</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="user.css">
    <style>
        /* 购物车样式 */
        .cart-items-container {
            margin: 15px 0;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-info {
            flex: 2;
        }
        
        .item-name {
            display: block;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .item-price {
            color: #666;
            font-size: 14px;
        }
        
        .cart-item-controls {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
        display: flex;
        align-items: center;  /* 垂直居中 */
        justify-content: center; /* 水平居中 */
        padding: 0;
}
        
        .qty-btn:hover {
            background-color: #f5f5f5;
        }
        
        .item-qty {
            min-width: 30px;
            text-align: center;
            font-weight: 500;
        }
        
        .remove-btn {
            padding: 5px 12px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .remove-btn:hover {
            background-color: #ff5252;
        }
        
        .item-total {
            flex: 0.5;
            text-align: right;
            font-weight: 500;
            color: #2c3e50;
        }
        
        /* 按钮样式 */
        .cart-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        
        .cart-buttons button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            flex: 1;
        }
        
        .clear-cart-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .clear-cart-btn:hover {
            background-color: #c82333;
        }
        
        .submit-order-btn {
            background-color: #3498db;
            color: white;
        }
        
        .submit-order-btn:hover {
            background-color: #2980b9;
        }
        
        .product-card .add-to-cart {
            width: 100%;
            padding: 10px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .product-card .add-to-cart:hover {
            background-color: #27ae60;
        }
        
        .empty-cart {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">Restaurant System</div>
            <div class="user-actions">
                <div id="nav-home" style="color:white;margin-right:10px">
                    <?php echo htmlspecialchars($currentUser['Name']); ?>
                </div>
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
            <div class="tab active" data-tab="order">Order</div>
            <div class="tab" data-tab="reservation">Table Reservation</div>
            <div class="tab" data-tab="redeem">Points Redemption</div>
            <div class="tab" data-tab="history">Order History</div>
        </div>

        <div id="order-tab" class="tab-content active">
            <div class="card">
                <h2>Product List</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #ffe6e6; border-radius: 4px;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center; padding: 10px; background-color: #e6ffe6; border-radius: 4px;">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <div class="grid" id="product-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image"><?php echo $product['Image']; ?></div>
                        <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                        <p>Price: <?php echo $product['Price']; ?> RMB</p>
                        <button class="add-to-cart" 
                                data-id="<?php echo $product['ProductID']; ?>"
                                data-name="<?php echo htmlspecialchars($product['Name']); ?>" 
                                data-price="<?php echo $product['Price']; ?>">
                            Add to Cart
                        </button>
                    </div>
                <?php endforeach; ?>
                </div>

                <form method="POST" action="" class="order-summary" id="order-form">
                    <h3>Order Summary</h3>
                    <div id="order-items" class="cart-items-container">
                        <div class="empty-cart">Cart is empty</div>
                    </div>
                    
                    <p style="font-size: 18px; font-weight: bold; margin: 15px 0;">
                        Total: <span id="order-total">0</span> RMB
                    </p>
                    
                    <div class="cart-buttons">
                        <button type="button" class="clear-cart-btn" onclick="clearCart()">Clear All</button>
                        <button type="submit" name="place_order" class="submit-order-btn" id="place-order">Submit Order</button>
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="order-notes">Notes (Special Requests)</label>
                        <textarea id="order-notes" name="notes" rows="3" maxlength="200" 
                                  placeholder="Any special requests for your order? (e.g., less spicy, extra sauce, etc.)"
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"></textarea>
                    </div>
                    
                    <input type="hidden" name="items" id="items-input" value="">
                    <input type="hidden" name="total" id="total-input" value="0">
                </form>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        
        // 添加到购物车
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                
                // 检查是否已在购物车中
                const existingIndex = cart.findIndex(item => item.id === id);
                if (existingIndex !== -1) {
                    cart[existingIndex].quantity += 1;
                } else {
                    cart.push({ 
                        id: id, 
                        name: name, 
                        price: price, 
                        quantity: 1 
                    });
                }
                
                updateCart();
            });
        });
        
        // 更新购物车显示
        function updateCart() {
            const orderItems = document.getElementById('order-items');
            const orderTotal = document.getElementById('order-total');
            const itemsInput = document.getElementById('items-input');
            const totalInput = document.getElementById('total-input');
            const submitBtn = document.getElementById('place-order');
            
            // 清空购物车显示
            orderItems.innerHTML = '';
            let total = 0;
            
            if (cart.length === 0) {
                orderItems.innerHTML = '<div class="empty-cart">Cart is empty</div>';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                
                // 显示购物车中的每个商品
            cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    
                    const div = document.createElement('div');
                    div.className = 'cart-item';
                    div.innerHTML = `
                        <div class="cart-item-info">
                            <span class="item-name">${item.name}</span>
                            <span class="item-price">${item.price} RMB</span>
                        </div>
                        <div class="cart-item-controls">
                            <button class="qty-btn minus" style="color: #333; font-size: 16px;">-</button>
                            <span class="item-qty">${item.quantity}</span>
                            <button class="qty-btn plus" style="color: #333; font-size: 16px;">+</button>
                            <button class="remove-btn">Remove</button>
                        </div>
                        <div class="item-total">${itemTotal} RMB</div>
                    `;
                    
                    // 添加事件监听
                    div.querySelector('.minus').onclick = () => changeQuantity(index, -1);
                    div.querySelector('.plus').onclick = () => changeQuantity(index, 1);
                    div.querySelector('.remove-btn').onclick = () => removeItem(index);
                    
                    orderItems.appendChild(div);
                });
            }
            // 更新总额
            orderTotal.textContent = total;
            itemsInput.value = JSON.stringify(cart);
            totalInput.value = total;
        }
        
        // 修改商品数量
        function changeQuantity(index, change) {
            if (cart[index]) {
                cart[index].quantity += change;
                
                // 如果数量为0或更少，移除商品
                if (cart[index].quantity <= 0) {
                    cart.splice(index, 1);
                }
                
                updateCart();
            }
        }
        
        // 移除单个商品
        function removeItem(index) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                cart.splice(index, 1);
                updateCart();
            }
        }
        
        // 清空整个购物车
        function clearCart() {
            if (cart.length === 0) {
                alert('Your cart is already empty!');
                return;
            }
            
            if (confirm('Are you sure you want to clear all items from your cart?')) {
                cart = [];
                updateCart();
            }
        }
        
        // 标签页切换
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `user_${tabId}.php`;
            });
        });
        
        // 页面加载时更新购物车显示
        document.addEventListener('DOMContentLoaded', updateCart);
        
        // 表单提交前的验证
        document.getElementById('order-form').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Please add items to your cart before submitting an order.');
                return false;
            }
            
            if (confirm('Are you sure you want to place this order?')) {
                return true;
            } else {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>