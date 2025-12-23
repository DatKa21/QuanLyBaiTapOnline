<?php
// Bắt đầu session và kiểm tra bảo mật (thêm logic này để tránh truy cập trái phép)
session_start();

// 1. Kiểm tra Đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

// 2. Kiểm tra Vai trò (Chỉ Admin role_id = 0 mới được truy cập)
$admin_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? 99;

if ($role_id != 0 || $admin_id === null) {
    header("Location: ../../user/home.php");
    exit();
}

// 3. Kết nối Cơ sở Dữ liệu
// Điều chỉnh đường dẫn đến connectdb.php nếu cần thiết
require_once '../../../config/connectdb.php';

$message = ''; // Biến lưu thông báo
$subject_id = $_GET['subject_id'] ?? null; // Lấy ID môn học từ URL
$subject_name = 'Không xác định';
$subject_code = 'N/A';

// 4. Kiểm tra và lấy thông tin Môn học
if ($subject_id) {
    $subject_id = intval($subject_id);
    // CẬP NHẬT: Sử dụng subject_id làm khóa chính
    $sql_subject = "SELECT name, code FROM subjects WHERE subject_id = ?";
    if ($stmt_subject = $conn->prepare($sql_subject)) {
        $stmt_subject->bind_param("i", $subject_id);
        $stmt_subject->execute();
        $result_subject = $stmt_subject->get_result();
        
        if ($row = $result_subject->fetch_assoc()) {
            $subject_name = $row['name'];
            $subject_code = $row['code'];
        } else {
            $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                            <strong class="font-bold">Cảnh báo:</strong>
                            <span class="block sm:inline">Không tìm thấy môn học.</span>
                        </div>';
            $subject_id = null; // Vô hiệu hóa việc thêm bài giảng
        }
        $stmt_subject->close();
    }
}

// 5. Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && $subject_id !== null) {
    // 5.1. Lấy dữ liệu bài giảng
    $lesson_name = $conn->real_escape_string($_POST['lesson_name'] ?? '');
    
    // 5.2. Xử lý File Upload (Lưu ý: Chỉ lưu tên file, không xử lý việc di chuyển file)
    
    // Helper function để xử lý mảng $_FILES và trả về tên file dưới dạng JSON
    function handle_file_array($file_key) {
        $file_details = [];
        if (isset($_FILES[$file_key]) && is_array($_FILES[$file_key]['name'])) {
            $count = count($_FILES[$file_key]['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES[$file_key]['error'][$i] == UPLOAD_ERR_OK) {
                    // Tạm thời chỉ lấy tên file, không xử lý thư mục đích
                    $file_details[] = [
                        'name' => basename($_FILES[$file_key]['name'][$i]),
                        'size' => $_FILES[$file_key]['size'][$i],
                        'type' => $_FILES[$file_key]['type'][$i],
                        // SAU NÀY: Thêm 'path' => '/uploads/lessons/' . $new_filename
                    ];
                    // Logic thực tế để di chuyển file: move_uploaded_file($_FILES[$file_key]['tmp_name'][$i], $target_path);
                }
            }
        }
        return json_encode($file_details, JSON_UNESCAPED_UNICODE); // Đảm bảo mã hóa Unicode
    }

    $material_files_json = handle_file_array('lesson_materials');
    $assignment_files_json = handle_file_array('assignment_files');

    // 5.3. Chuẩn bị câu lệnh SQL INSERT
    if (!empty($lesson_name)) {
        // CẬP NHẬT QUAN TRỌNG: Đổi cột 'name' thành 'lesson_name' để đồng bộ với CSDL
        $sql_lesson = "INSERT INTO lessons (subject_id, admin_id, lesson_name, material_files, assignment_files) 
                         VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_lesson = $conn->prepare($sql_lesson)) {
            // Tham số: i (subject_id), i (admin_id), s (lesson_name), s (material_files), s (assignment_files)
            $stmt_lesson->bind_param("iisss", $subject_id, $admin_id, $lesson_name, $material_files_json, $assignment_files_json);

            if ($stmt_lesson->execute()) {
                $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                                <strong class="font-bold">Thành công!</strong>
                                <span class="block sm:inline">Bài giảng "' . htmlspecialchars($lesson_name) . '" đã được thêm thành công.</span>
                            </div>';
                // Xóa dữ liệu form
                $_POST = []; 
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                                <strong class="font-bold">Lỗi SQL:</strong>
                                <span class="block sm:inline">Không thể thêm bài giảng. Lỗi: ' . $stmt_lesson->error . '</span>
                            </div>';
            }
            $stmt_lesson->close();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                            <strong class="font-bold">Lỗi chuẩn bị câu lệnh:</strong>
                            <span class="block sm:inline">Không thể chuẩn bị câu lệnh SQL.</span>
                        </div>';
        }
    } else {
           $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg shadow-md mb-6" role="alert">
                        <strong class="font-bold">Cảnh báo:</strong>
                        <span class="block sm:inline">Vui lòng điền Tên Bài giảng.</span>
                    </div>';
    }
}

// 6. Đóng kết nối CSDL
$conn->close();

// Tạo URL trở về chi tiết bài giảng
$back_url = $subject_id ? "lesson.php?subject_id={$subject_id}" : "lesson.php";

?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thêm Bài giảng và Bài tập</title>
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
      /* Style cho nút input file để trông đẹp hơn */
      .file-input-style {
        @apply w-full text-sm text-gray-900 bg-white p-3 border border-gray-300 rounded-lg shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500
                         file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200;
      }
      .assignment-file-input {
        @apply file:bg-red-100 file:text-red-700 hover:file:bg-red-200;
      }
    </style>
  </head>
  <body class="min-h-screen flex flex-col">
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-6 py-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Quản lí Bài giảng</h1>
        <p class="text-sm text-gray-500 mt-1">
          Thêm Bài giảng mới cho môn 
          <!-- THAY DỮ LIỆU TĨNH BẰNG DỮ LIỆU ĐỘNG PHP -->
          <strong class="text-gray-800">
            <?= htmlspecialchars($subject_code) ?> - <?= htmlspecialchars($subject_name) ?>
          </strong>
        </p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8 flex-grow">
      <!-- Nút Trở về Chi tiết Môn học (lesson.php) -->
      <a
        href="<?= $back_url ?>"
        class="text-blue-600 hover:text-blue-800 mb-6 flex items-center space-x-1"
      >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Trở về Chi tiết Bài giảng</span>
      </a>

      <!-- Form Thêm Bài giảng -->
      <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-2xl">
        
        <!-- Hiển thị thông báo (thành công/lỗi) -->
        <?php echo $message; ?>

        <h2
          class="text-2xl font-bold text-gray-800 mb-6 flex items-center space-x-2 border-b pb-3"
        >
          <i data-lucide="library" class="w-7 h-7 text-indigo-600"></i>
          <span>Thêm Bài giảng Mới</span>
        </h2>

        <!-- THAY ĐỔI: Thêm method="POST" và enctype="multipart/form-data" -->
        <form method="POST" class="space-y-8" enctype="multipart/form-data">
          <!-- PHẦN 1: THÔNG TIN BÀI GIẢNG -->
          <div class="border p-6 rounded-lg bg-gray-50/50">
            <h3
              class="text-xl font-semibold text-gray-700 mb-4 flex items-center space-x-2"
            >
              <i data-lucide="monitor" class="w-5 h-5 text-indigo-500"></i>
              <span>Thông tin Bài giảng</span>
            </h3>

            <div>
              <label
                for="lesson-name"
                class="block text-sm font-medium text-gray-700 mb-1"
                >Tên Bài giảng</label
              >
              <input
                type="text"
                id="lesson-name"
                name="lesson_name"
                required
                placeholder="Ví dụ: Bài 1: Làm quen với HTML"
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                value="<?= htmlspecialchars($_POST['lesson_name'] ?? '') ?>"
              />
            </div>
          </div>

          <!-- PHẦN 2: TÀI LIỆU BÀI GIẢNG (ĐỌC FILE) -->
          <div class="border p-6 rounded-lg">
            <h3
              class="text-xl font-semibold text-gray-700 mb-4 flex items-center space-x-2"
            >
              <i data-lucide="files" class="w-5 h-5 text-blue-500"></i>
              <span>Tài liệu Bài giảng (Slide, Video, Tài liệu tham khảo)</span>
            </h3>

            <div>
              <label
                for="lesson-materials"
                class="block text-sm font-medium text-gray-700 mb-1"
                >Tải tài liệu môn học</label
              >
              <input
                type="file"
                id="lesson-materials"
                name="lesson_materials[]"
                multiple
                accept=".doc,.docx,image/*,video/*,application/pdf"
                class="file-input-style"
              />
            </div>
          </div>

          <!-- PHẦN 3: BÀI TẬP VÀ ĐÁNH GIÁ -->
          <div class="border p-6 rounded-lg bg-red-50/50">
            <h3