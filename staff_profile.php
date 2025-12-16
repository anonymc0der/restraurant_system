<?php
require_once 'config.php';
require_once 'if.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'staff') {
    redirect('login.php');
}

$currentUser = getCurrentUser($conn);
$avatar_data = getStaffAvatar($conn, $currentUser['StaffID']);

// 处理头像上传
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 2 * 1024 * 1024) { // 最大2MB
            $avatar_content = file_get_contents($_FILES['avatar']['tmp_name']);
            
            if (updateStaffAvatar($conn, $currentUser['StaffID'], $avatar_content)) {
                $query_time_msg = '';
                if (isset($_SESSION['query_time'])) {
                    $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                    unset($_SESSION['query_time']);
                }
                $success = "Avatar uploaded successfully!" . $query_time_msg;
                $avatar_data = $avatar_content; // 更新本地变量
            } else {
                $error = "Failed to upload avatar";
            }
        } else {
            $error = "Invalid file type or file too large (max 2MB). Allowed types: JPEG, PNG, GIF";
        }
    }
    
    if (isset($_POST['delete_avatar'])) {
        if (deleteStaffAvatar($conn, $currentUser['StaffID'])) {
            $query_time_msg = '';
            if (isset($_SESSION['query_time'])) {
                $query_time_msg = " (Query Time: " . $_SESSION['query_time'] . " ms)";
                unset($_SESSION['query_time']);
            }
            $success = "Avatar deleted successfully!" . $query_time_msg;
            $avatar_data = null;
        } else {
            $error = "Failed to delete avatar";
        }
    }
}

// 生成头像的data URL用于显示
$avatar_src = '';
if ($avatar_data) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($avatar_data);
    if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
        $avatar_src = 'data:' . $mime_type . ';base64,' . base64_encode($avatar_data);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Personal Information</title>
    <link rel="stylesheet" href="common.css">
    <link rel="stylesheet" href="staff.css">
    <style>
        .avatar-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .avatar-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            border: 3px solid #3498db;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .avatar-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            font-size: 48px;
            color: #bdc3c7;
        }
        
        .avatar-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .avatar-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-upload {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .avatar-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 10px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #2980b9;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .form-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        .avatar-preview {
            max-width: 100px;
            max-height: 100px;
            margin: 10px auto;
            display: none;
        }
    </style>
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
            <div class="tab" data-tab="materials">Material Management</div>
            <div class="tab active" data-tab="profile">Personal Information</div>
        </div>

        <div id="profile-tab" class="tab-content active">
            <div class="card">
                <h2>Personal Information</h2>
                
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
                
                <div class="avatar-section">
                    <div class="avatar-container">
                        <?php if ($avatar_src): ?>
                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="Avatar" class="avatar-image">
                        <?php else: ?>
                            <div class="avatar-placeholder">👤</div>
                        <?php endif; ?>
                </div>
                    
                    <div class="avatar-actions">
                        <button type="button" class="avatar-btn btn-upload" onclick="toggleUploadForm()">
                            Upload Avatar
                        </button>
                        <?php if ($avatar_src): ?>
                        <button type="button" class="avatar-btn btn-delete" onclick="confirmDelete()">
                            Delete Avatar
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="upload-form" class="avatar-form" style="display: none;">
                        <form method="POST" action="" enctype="multipart/form-data" id="avatar-form">
                            <div class="file-input-wrapper">
                                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif" 
                                       class="file-input" onchange="previewImage(this)">
                                <label for="avatar" class="file-input-label">Choose Image File</label>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" name="upload_avatar" class="btn-submit">Upload</button>
                                <button type="button" class="btn-cancel" onclick="toggleUploadForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if ($avatar_src): ?>
                    <form method="POST" action="" id="delete-form" style="display: none;">
                        <input type="hidden" name="delete_avatar" value="1">
                    </form>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['Name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Staff ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['StaffID'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['Phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">User Type</div>
                        <div class="info-value">Staff</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleUploadForm() {
            const form = document.getElementById('upload-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function previewImage(input) {
            const preview = document.getElementById('avatar-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your avatar?')) {
                document.getElementById('delete-form').submit();
            }
        }
        
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                window.location.href = `staff_${tabId}.php`;
            });
        });
        
        // 表单提交前的验证
        document.getElementById('avatar-form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('avatar');
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please select an image file.');
                return false;
            }
            
            const file = fileInput.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File size exceeds 2MB limit. Please choose a smaller image.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>