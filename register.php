<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    if (!preg_match('/^\d{11}$/', $phone)) {
    $error = 'Please enter an 11-digit phone number';
} elseif ($password !== $password_confirm) {
        $error = 'The passwords entered twice are inconsistent';
    } elseif (empty($username) || empty($password) || empty($phone)) {
        $error = 'Please fill in the required fields';
    } else {
        $result = registerUser($conn, $username, $password, $phone, $user_type);
        
        if ($result === true) {
            $success = "Registered successfully! Username: $username, Type: " . ($user_type == 'user' ? 'User' : 'Staff');
            $user = authenticateUser($conn, $username, $password, $user_type);
            if ($user) {
                if ($user_type == 'user') {
                    redirect('user_order.php');
                } else {
                    redirect('staff_orders.php');
                }
            }
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Register - Restaurant System</title>
    <link rel="stylesheet" href="common.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div style="font-size:20px;font-weight:700">Restaurant System</div>
        </div>
    </header>

    <div class="container">
        <div class="login-container">
            <div class="card">
                <h2>User Registration</h2>
                
                <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="register-form" method="POST" action="">
                    <div class="form-group">
                        <label for="register-username">Username</label>
                        <input type="text" id="register-username" name="username" required />
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required />
                    </div>
                    <div class="form-group">
                        <label for="register-password-confirm">Confirm Password</label>
                        <input type="password" id="register-password-confirm" name="password_confirm" required />
                    </div>
                    <div class="form-group">
                        <label for="register-phone">Phone Number</label>
                        <input type="tel" id="register-phone" name="phone" pattern="[0-9]{11}" maxlength="11" placeholder="Please enter an 11-digit phone number" required />
                    </div>
                    <div class="user-type">
                        <div class="user-type-option active" data-type="user">User</div>
                        <div class="user-type-option" data-type="staff">Staff</div>
                    </div>
                    <input type="hidden" name="user_type" id="user_type" value="user">
                    <button type="submit">Register</button>
                </form>
                <p style="margin-top:15px;text-align:center">Already have an account? <a href="login.php" id="show-login">Login Now</a></p>
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

        document.getElementById('register-phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '');
        });
    </script>
</body>
</html>