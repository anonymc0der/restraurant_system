<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    $user = authenticateUser($conn, $username, $password, $user_type);
    
    if ($user) {
        if ($user_type == 'user') {
            redirect('user_order.php');
        } else {
            redirect('staff_orders.php');
        }
    } else {
        $error = "Username or password incorrect, or user type mismatch!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - Restaurant System</title>
    <link rel="stylesheet" href="common.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div style="font-size:20px;font-weight:700">Restaurant System</div>
        </div>
    </header>

    <div class="container">
        <div id="login-page" class="login-container">
            <div class="card">
                <h2>User Login</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="login-form" method="POST" action="">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input type="text" id="login-username" name="username" required />
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required />
                    </div>
                    <div class="user-type">
                        <div class="user-type-option active" data-type="user">User</div>
                        <div class="user-type-option" data-type="staff">Staff</div>
                    </div>
                    <input type="hidden" name="user_type" id="user_type" value="user">
                    <button type="submit">Login</button>
                </form>
                <p style="margin-top:15px;text-align:center">Don't have an account? <a href="register.php" id="show-register">Register Now</a></p>
                <p style="margin-top:8px;color:#666;font-size:13px">Test accounts: User user / 123456, Staff staff / 123456</p>
            </div>
        </div>
    </div>

    <script>
        let userType = 'user';
        document.querySelectorAll('.user-type-option').forEach(opt=>{
            opt.addEventListener('click', function(){
                document.querySelectorAll('.user-type-option').forEach(o=>o.classList.remove('active'));
                this.classList.add('active');
                userType = this.getAttribute('data-type');
                document.getElementById('user_type').value = userType;
            });
        });
    </script>
</body>
</html>