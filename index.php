<?php
// Bắt đầu Session
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_id'])) {
    
    // Nếu ĐÃ đăng nhập, kiểm tra Vai trò (Role)
    $role_id = $_SESSION['role_id'] ?? 99; // Lấy role_id, nếu không có thì gán giá trị mặc định

    if ($role_id == 0) {
        // Vai trò Admin: Chuyển đến Dashboard Admin
        header("Location: views/admin/home/home.php");
    } else {
        // Vai trò User/Student (role_id khác 0): Chuyển đến Trang chủ User
        header("Location: views/user/home.php");
    }
    
} else {
    // Nếu CHƯA đăng nhập: Chuyển đến Trang Đăng nhập
    header("Location: views/auth/DangNhap.php");
}

exit();
?>