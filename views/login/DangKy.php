<?php
session_start();
require_once '../../config/connectdb.php';

$username = $email = $password = $confirm_password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. LẤY DỮ LIỆU
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 2. VALIDATE
    if ($username === '') {
        $errors['username'] = 'Tên người dùng không được để trống';
    }

    if ($email === '') {
        $errors['email'] = 'Email không được để trống';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    }

    if (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu tối thiểu 6 ký tự';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
    }

    // 3. CHECK EMAIL TỒN TẠI
    if (empty($errors)) {
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email đã tồn tại';
        }
        $stmt->close();
    }

    // 4. INSERT USER (ROLE = 1)
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 1; // USER THƯỜNG

        $sql = "
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
            header("Location: DangNhap.php");
            exit();
        } else {
            $errors['db'] = $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Đăng ký</h2>

    <form method="POST" class="space-y-4">

        <input type="text" name="username" placeholder="Tên người dùng"
               value="<?= htmlspecialchars($username) ?>"
               class="w-full p-3 border rounded">

        <input type="email" name="email" placeholder="Email"
               value="<?= htmlspecialchars($email) ?>"
               class="w-full p-3 border rounded">

        <input type="password" name="password" placeholder="Mật khẩu"
               class="w-full p-3 border rounded">

        <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu"
               class="w-full p-3 border rounded">

        <?php foreach ($errors as $e): ?>
            <p class="text-red-500 text-sm"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>

        <button class="w-full bg-indigo-600 text-white py-3 rounded font-semibold">
            Đăng ký
        </button>
    </form>

    <p class="mt-4 text-center text-sm">
        Đã có tài khoản?
        <a href="DangNhap.php" class="text-indigo-600 font-medium">Đăng nhập</a>
    </p>
</div>

</body>
</html>
