<?php
// Bắt đầu session để lưu thông tin người dùng
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, chuyển hướng ngay lập tức
if (isset($_SESSION['user_id'])) {
    // Lấy role_id một cách an toàn
    $role_id_check = $_SESSION['role_id'] ?? 99; 
    
    // Logic kiểm tra nhanh nếu đã đăng nhập
    if ($role_id_check == 0) {
        header("Location: ../admin/home/home.php"); // Chuyển hướng Admin (role_id = 0)
    } else {
        // Chuyển hướng cho User/Student (role_id = 1 hoặc 2)
        header("Location: ../user/home.php"); 
    }
    exit();
}

// Kết nối Cơ sở Dữ liệu
require_once '../../config/connectdb.php';

$email = $password = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy và làm sạch dữ liệu
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 2. Xác thực cơ bản
    if (empty($email) || empty($password)) {
        $login_error = "Vui lòng nhập đầy đủ Email và Mật khẩu.";
    }

    // 3. Kiểm tra thông tin đăng nhập trong CSDL
    if (empty($login_error)) {
        // Truy vấn lấy tất cả thông tin cần thiết từ bảng users
        $sql = "SELECT user_id, name, role_id, email, password FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // 4. Xác minh mật khẩu
                if (password_verify($password, $user['password'])) {
                    
                    // 5. Đăng nhập thành công: Tạo Session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role_id'] = $user['role_id']; // Lưu role_id vào session
                    
                    // 6. Chuyển hướng dựa trên role_id
                    if ($user['role_id'] == 0 ){
                        header("Location: ../admin/home/home.php"); // Admin (role_id = 0)
                    } else {
                        header("Location: ../user/home.php"); // User/Student (role_id != 0)
                    }
                    exit();
                    
                } else {
                    $login_error = "Email hoặc Mật khẩu không chính xác.";
                }
            } else {
                $login_error = "Email hoặc Mật khẩu không chính xác.";
            }
            $stmt->close();
        } else {
            $login_error = "Lỗi hệ thống: Không thể chuẩn bị truy vấn.";
        }
    }
}

// Lấy thông báo thành công sau khi đăng ký (từ DangKy.php)
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

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
    <title>Đăng nhập Tài khoản</title>
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tải Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f4f6f9;
        background: linear-gradient(135deg, #a7e6ff 0%, #10a2d3 100%);
      }
    </style>
  </head>
  <body class="min-h-screen flex items-center justify-center p-4">
    <!-- Login Card -->
    <div
      class="w-full max-w-md bg-white p-8 md:p-10 rounded-xl shadow-2xl transform hover:shadow-3xl transition duration-300"
    >
      <div class="text-center mb-8">
        <i
          data-lucide="log-in"
          class="w-12 h-12 text-indigo-600 mx-auto mb-3"
        ></i>
        <h1 class="text-3xl font-extrabold text-gray-900">Chào mừng trở lại</h1>
        <p class="text-sm text-gray-500 mt-1">Đăng nhập để tiếp tục học tập</p>
      </div>
      
      <!-- Hiển thị thông báo LỖI -->
      <?php if (!empty($login_error)): ?>
          <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-400">
              <strong>Lỗi:</strong> <?= htmlspecialchars($login_error) ?>
          </div>
      <?php endif; ?>

      <!-- Hiển thị thông báo THÀNH CÔNG -->
      <?php if (!empty($success_message)): ?>
          <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-400">
              <strong>Thành công!</strong> <?= htmlspecialchars($success_message) ?>
          </div>
      <?php endif; ?>

      <form class="space-y-6" method="POST" action="">
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
            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition"
          />
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
            placeholder="Mật khẩu của bạn"
            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition"
          />
        </div>

        <!-- Nút Đăng nhập -->
        <button
          type="submit"
          class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold shadow-md hover:bg-indigo-700 transition duration-150 flex items-center justify-center space-x-2"
        >
          <i data-lucide="log-in" class="w-5 h-5"></i>
          <span>Đăng nhập</span>
        </button>
      </form>

      <!-- Chuyển sang Đăng ký -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Chưa có tài khoản?
          <a
            href="DangKy.php"
            class="font-medium text-indigo-600 hover:text-indigo-800 transition"
          >
            Đăng ký ngay
          </a>
        </p>
      </div>
    </div>

    <script>
      lucide.createIcons();
    </script>
  </body>
</html>