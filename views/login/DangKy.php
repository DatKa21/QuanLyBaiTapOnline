<?php
// Bắt đầu session để lưu thông báo
session_start();

// Kết nối Cơ sở Dữ liệu
// Giả định file này nằm trong thư mục views/auth/
require_once '../../config/connectdb.php';

$full_name = $email = $password = $confirm_password = '';
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy và làm sạch dữ liệu
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 2. Xác thực dữ liệu
    if (empty($full_name)) {
        $errors['full_name'] = "Tên đầy đủ không được để trống.";
    }
    
    if (empty($email)) {
        $errors['email'] = "Địa chỉ Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Địa chỉ Email không hợp lệ.";
    }
    
    if (empty($password)) {
        $errors['password'] = "Mật khẩu không được để trống.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Xác nhận mật khẩu không khớp.";
    }

    // 3. Kiểm tra Email đã tồn tại
    if (empty($errors)) {
        $sql_check = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql_check)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors['email'] = "Địa chỉ Email này đã được đăng ký.";
            }
            $stmt->close();
        } else {
             $errors['db'] = "Lỗi chuẩn bị truy vấn kiểm tra email: " . $conn->error;
        }
    }

    // 4. Lưu người dùng nếu không có lỗi
    if (empty($errors)) {
        // Băm mật khẩu trước khi lưu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // <-- Đã băm

        // Giả định role_id mặc định là 2 (Học viên/Student) - Đã sửa từ 0 thành 2
        $role_id = 2; 

        $sql_insert = "INSERT INTO users (role_id, name, email, password) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql_insert)) {
            // Lỗi ban đầu: Tham số thứ 4 là $password (mật khẩu thô).
            // Đã sửa: Sử dụng $hashed_password (mật khẩu đã băm).
            $stmt->bind_param("isss", $role_id, $full_name, $email, $hashed_password); 
            
            if ($stmt->execute()) {
                // Đăng ký thành công
                $_SESSION['success_message'] = "Đăng ký thành công! Vui lòng Đăng nhập.";
                // Chuyển hướng về trang Đăng nhập
                header("Location: DangNhap.php"); 
                exit();
            } else {
                $errors['db'] = "Lỗi thực thi: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors['db'] = "Lỗi chuẩn bị truy vấn đăng ký: " . $conn->error;
        }
    }
}

// Đóng kết nối CSDL
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng ký Tài khoản</title>
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tải Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f4f6f9;
        background: linear-gradient(135deg, #e0f7fa 0%, #80deea 100%);
      }
    </style>
  </head>
  <body class="min-h-screen flex items-center justify-center p-4">
    <!-- Register Card -->
    <div
      class="w-full max-w-md bg-white p-8 md:p-10 rounded-xl shadow-2xl transform hover:shadow-3xl transition duration-300"
    >
      <div class="text-center mb-8">
        <i
          data-lucide="user-plus"
          class="w-12 h-12 text-teal-600 mx-auto mb-3"
        ></i>
        <h1 class="text-3xl font-extrabold text-gray-900">Tạo Tài khoản</h1>
        <p class="text-sm text-gray-500 mt-1">Gia nhập cộng đồng học tập của chúng tôi</p>
      </div>
      
      <!-- Hiển thị lỗi chung -->
      <?php if (!empty($errors['db'])): ?>
          <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50">
              <strong>Lỗi hệ thống:</strong> <?= htmlspecialchars($errors['db']) ?>
          </div>
      <?php endif; ?>

      <form class="space-y-6" method="POST" action="">
        <!-- Tên đầy đủ -->
        <div>
          <label
            for="full-name"
            class="block text-sm font-medium text-gray-700 mb-1"
            >Tên đầy đủ</label
          >
          <input
            type="text"
            id="full-name"
            name="full_name"
            required
            placeholder="Ví dụ: Nguyễn Văn A"
            value="<?= htmlspecialchars($full_name) ?>"
            class="w-full p-3 border <?= isset($errors['full_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 transition"
          />
          <?php if (isset($errors['full_name'])): ?>
              <p class="mt-1 text-xs text-red-500"><?= htmlspecialchars($errors['full_name']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div>
          <label
            for="email"
            class="block text-sm font-medium text-gray-700 mb-1"
            >Địa chỉ Email</label
          >
          <input
            type="email"
            id="email"
            name="email"
            required
            placeholder="you@example.com"
            value="<?= htmlspecialchars($email) ?>"
            class="w-full p-3 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 transition"
          />
          <?php if (isset($errors['email'])): ?>
              <p class="mt-1 text-xs text-red-500"><?= htmlspecialchars($errors['email']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Password -->
        <div>
          <label
            for="password"
            class="block text-sm font-medium text-gray-700 mb-1"
            >Mật khẩu</label
          >
          <input
            type="password"
            id="password"
            name="password"
            required
            placeholder="Tạo mật khẩu mạnh (ít nhất 6 ký tự)"
            class="w-full p-3 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 transition"
          />
          <?php if (isset($errors['password'])): ?>
              <p class="mt-1 text-xs text-red-500"><?= htmlspecialchars($errors['password']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Xác nhận Password -->
        <div>
          <label
            for="confirm-password"
            class="block text-sm font-medium text-gray-700 mb-1"
            >Xác nhận Mật khẩu</label
          >
          <input
            type="password"
            id="confirm-password"
            name="confirm_password"
            required
            placeholder="Nhập lại mật khẩu"
            class="w-full p-3 border <?= isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500 transition"
          />
          <?php if (isset($errors['confirm_password'])): ?>
              <p class="mt-1 text-xs text-red-500"><?= htmlspecialchars($errors['confirm_password']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Nút Đăng ký -->
        <button
          type="submit"
          class="w-full py-3 bg-teal-600 text-white rounded-lg font-semibold shadow-md hover:bg-teal-700 transition duration-150 flex items-center justify-center space-x-2"
        >
          <i data-lucide="user-check" class="w-5 h-5"></i>
          <span>Đăng ký Tài khoản</span>
        </button>
      </form>

      <!-- Chuyển sang Đăng nhập -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Đã có tài khoản?
          <a
            href="DangNhap.php"
            class="font-medium text-teal-600 hover:text-teal-800 transition"
          >
            Đăng nhập
          </a>
        </p>
      </div>
    </div>

    <script>
      lucide.createIcons();
    </script>
  </body>
</html>