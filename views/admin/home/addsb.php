<?php
// 1. Kết nối Cơ sở Dữ liệu
// Đảm bảo đường dẫn chính xác từ file addsb.php tới config/connectdb.php
// Đảm bảo file config/connectdb.php đã được tạo với kết nối MySQLi Hướng đối tượng (OO)
require_once '../../../config/connectdb.php';

// Tạm thời hardcode admin_id là 1 cho giảng viên đầu tiên
// SAU NÀY: Thay thế bằng $_SESSION['user_id'] khi có chức năng Đăng nhập/Session
$admin_id = 1;

$message = ''; // Biến để lưu thông báo thành công hoặc lỗi

// 2. Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2.1. Lấy dữ liệu từ form và sanitize (làm sạch)
    // Dùng $conn->real_escape_string để ngăn chặn các ký tự đặc biệt gây lỗi SQL
    $name        = $conn->real_escape_string($_POST['course_name'] ?? '');
    $code        = $conn->real_escape_string($_POST['course_code'] ?? '');
    $description = $conn->real_escape_string($_POST['course_description'] ?? '');
    $credits     = intval($_POST['credits'] ?? 0);
    $duration    = intval($_POST['duration'] ?? 0);

    // 2.2. Kiểm tra các trường bắt buộc
    if (!empty($name) && !empty($code) && $credits > 0 && $duration > 0) {
        // 2.3. Chuẩn bị câu lệnh SQL INSERT
        // Câu lệnh này INSERT vào bảng 'subjects'
        $sql = "INSERT INTO subjects (name, code, description, credits, duration_weeks, admin_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        // Sử dụng Prepared Statement để ngăn chặn SQL Injection
        if ($stmt = $conn->prepare($sql)) {
            // Liên kết các biến với placeholder (?)
            // Tham số: s (string), s (string), s (string), i (integer), i (integer), i (integer)
            $stmt->bind_param("sssiis", $name, $code, $description, $credits, $duration, $admin_id);

            // 2.4. Thực thi câu lệnh
            if ($stmt->execute()) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                                <strong class="font-bold">Thành công!</strong>
                                <span class="block sm:inline">Môn học "' . htmlspecialchars($name) . '" đã được thêm thành công.</span>
                            </div>';
                
                // Xóa dữ liệu cũ trong form sau khi thêm thành công (tùy chọn)
                // unset($_POST['course_name'], $_POST['course_code'], $_POST['course_description']);

            } else {
                // Xử lý lỗi trùng lặp code (Lỗi 1062 - Unique Constraint)
                if ($conn->errno == 1062) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                                    <strong class="font-bold">Lỗi:</strong>
                                    <span class="block sm:inline">Mã môn học "' . htmlspecialchars($code) . '" đã tồn tại. Vui lòng chọn mã khác.</span>
                                </div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                                    <strong class="font-bold">Lỗi SQL:</strong>
                                    <span class="block sm:inline">Không thể thêm môn học. Lỗi: ' . $stmt->error . '</span>
                                </div>';
                }
            }
            $stmt->close();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                            <strong class="font-bold">Lỗi chuẩn bị câu lệnh:</strong>
                            <span class="block sm:inline">Không thể chuẩn bị câu lệnh SQL.</span>
                        </div>';
        }
    } else {
        $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                        <strong class="font-bold">Cảnh báo:</strong>
                        <span class="block sm:inline">Vui lòng điền đầy đủ Tên, Mã môn học, Tín chỉ và Thời lượng.</span>
                    </div>';
    }
}

// 3. Đóng kết nối CSDL
$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thêm Môn học Mới</title>
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tải Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f4f6f9;
      }
    </style>
  </head>
  <body class="min-h-screen flex flex-col">
    <!-- Header / Navigation -->
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-6 py-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Quản lí Môn học</h1>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8 flex-grow">
      <!-- CẬP NHẬT: home.html thành home.php -->
      <a
        href="home.php" 
        class="text-blue-600 hover:text-blue-800 mb-6 flex items-center space-x-1"
      >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Trở về Danh sách Môn học</span>
      </a>

      <!-- Form Thêm Môn học -->
      <div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow-2xl">
        <h2
          class="text-2xl font-bold text-gray-800 mb-6 flex items-center space-x-2 border-b pb-3"
        >
          <i data-lucide="book-open-check" class="w-7 h-7 text-indigo-600"></i>
          <span>Thông tin Môn học Mới</span>
        </h2>
        
        <!-- Hiển thị thông báo (thành công/lỗi) -->
        <?php echo $message; ?>

        <!-- Form: THAY ĐỔI METHOD="POST" và thêm enctype cho upload file -->
        <form method="POST" class="space-y-6" enctype="multipart/form-data">
          <!-- Tên Môn học -->
          <div>
            <label
              for="course-name"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Tên Môn học</label
            >
            <input
              type="text"
              id="course-name"
              name="course_name"
              required
              placeholder="Ví dụ: Lập trình Web"
              class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>"
            />
          </div>

          <!-- Mã Môn học -->
          <div>
            <label
              for="course-code"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Mã Môn học (Ngắn gọn, duy nhất)</label
            >
            <input
              type="text"
              id="course-code"
              name="course_code"
              required
              placeholder="Ví dụ: WEB211"
              class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              value="<?php echo htmlspecialchars($_POST['course_code'] ?? ''); ?>"
            />
          </div>

          <!-- Mô tả -->
          <div>
            <label
              for="course-description"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Mô tả Môn học</label
            >
            <textarea
              id="course-description"
              name="course_description"
              rows="4"
              required
              placeholder="Mô tả chi tiết nội dung môn học"
              class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 resize-none"
            ><?php echo htmlspecialchars($_POST['course_description'] ?? ''); ?></textarea>
          </div>

          <!-- Số lượng tín chỉ và Thời lượng -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label
                for="credits"
                class="block text-sm font-medium text-gray-700 mb-1"
                >Số lượng Tín chỉ</label
              >
              <input
                type="number"
                id="credits"
                name="credits"
                min="1"
                max="20"
                value="<?php echo htmlspecialchars($_POST['credits'] ?? '3'); ?>"
                required
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
            <div>
              <label
                for="duration"
                class="block text-sm font-medium text-gray-700 mb-1"
                >Thời lượng (Tuần)</label
              >
              <input
                type="number"
                id="duration"
                name="duration"
                min="8"
                max="20"
                value="<?php echo htmlspecialchars($_POST['duration'] ?? '9'); ?>"
                required
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
          </div>

          <!-- Tải Tài liệu Môn học -->
          <div>
            <label
              for="course-materials"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Tải tài liệu Môn học (Word, Ảnh, Video)</label
            >
            <input
              type="file"
              id="course-materials"
              name="course_materials[]"
              multiple
              accept=".doc,.docx,image/*,video/*,application/pdf"
              class="w-full text-sm text-gray-900 bg-gray-50 p-3 border border-gray-300 rounded-lg shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200"
            />
            <p class="mt-1 text-xs text-gray-500">
                (Lưu ý: Chức năng upload file và lưu đường dẫn vào CSDL sẽ được bổ sung sau.)
            </p>
          </div>

          <!-- Footer nút bấm -->
          <div class="pt-6 border-t mt-6 flex justify-end space-x-4">
            <!-- Nút Hủy -->
            <a
              href="home.php"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition font-medium"
            >
              Hủy
            </a>
            <!-- Nút Lưu -->
            <button
              type="submit"
              class="px-6 py-2 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition font-semibold flex items-center space-x-2"
            >
              <i data-lucide="save" class="w-5 h-5"></i>
              <span>Lưu Môn học</span>
            </button>
          </div>
        </form>
      </div>
    </main>

    <script>
      // Khởi tạo Lucide Icons
      lucide.createIcons();
    </script>
  </body>
</html>