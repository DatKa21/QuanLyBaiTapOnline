<?php
session_start();

/* ========================
   INIT BIẾN
======================== */
$success_message = '';
$login_error = '';
$email = $password = '';

/* ========================
   FLASH MESSAGE
======================== */
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

/* ========================
   NẾU ĐÃ LOGIN → REDIRECT
======================== */
if (isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    if ($_SESSION['role_id'] == 0) {
        header("Location: ../admin/home/home.php");
    } else {
        header("Location: ../user/home.php");
    }
    exit();
}

/* ========================
   CONNECT DB
======================== */
require_once '../../config/connectdb.php';

/* ========================
   HANDLE LOGIN
======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $login_error = 'Vui lòng nhập đầy đủ Email và Mật khẩu';
    }

    if ($login_error === '') {
        $sql = "
            SELECT user_id, username, email, password, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // SET SESSION
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['name']     = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role_id']  = (int)$user['role'];

                // REDIRECT
                if ($_SESSION['role_id'] === 0) {
                    header("Location: ../admin/home/home.php");
                } else {
                    header("Location: ../user/home.php");
                }
                exit();

            } else {
                $login_error = 'Email hoặc Mật khẩu không chính xác';
            }
        } else {
            $login_error = 'Email hoặc Mật khẩu không chính xác';
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
    <title>Đăng nhập</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-300 to-blue-600">

<div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md">
    <h2 class="text-2xl font-bold text-center mb-6">Đăng nhập</h2>

    <?php if ($login_error): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <?= htmlspecialchars($login_error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="email" name="email" placeholder="Email"
               value="<?= htmlspecialchars($email) ?>"
               class="w-full p-3 border rounded">

        <input type="password" name="password" placeholder="Mật khẩu"
               class="w-full p-3 border rounded">

        <button class="w-full bg-indigo-600 text-white py-3 rounded font-semibold">
            Đăng nhập
        </button>
    </form>

    <p class="mt-4 text-center text-sm">
        Chưa có tài khoản?
        <a href="DangKy.php" class="text-indigo-600 font-medium">Đăng ký</a>
    </p>
</div>

</body>
</html>
