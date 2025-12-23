<?php
// Bắt đầu hoặc khôi phục session
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Nếu muốn xóa session cookie, hãy xóa cả cookie session.
// Lưu ý: Việc này sẽ hủy session, không chỉ dữ liệu session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển hướng người dùng về trang đăng nhập (DangNhap.php nằm trong cùng thư mục views/login/)
header("Location: DangNhap.php");
exit();
?>