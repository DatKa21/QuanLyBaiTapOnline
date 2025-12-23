<?php
// Bắt đầu session và kiểm tra bảo mật
session_start();

// 1. Kiểm tra Đăng nhập & Vai trò Admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

$admin_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? 99;

if ($role_id != 0 || $admin_id === null) {
    header("Location: ../../user/home.php");
    exit();
}

// 2. Kết nối Cơ sở Dữ liệu
// Điều chỉnh đường dẫn đến connectdb.php nếu cần thiết
require_once '../../../config/connectdb.php';

$subject_id = $_GET['subject_id'] ?? null; // Lấy ID môn học từ URL
$subject_name = 'Không xác định';
$subject_code = 'N/A';
$lessons = [];

// 3. Lấy thông tin Môn học và Bài giảng
if ($subject_id) {
    $subject_id = intval($subject_id);

    // 3.1. Lấy thông tin Môn học
    $sql_subject = "SELECT name, code FROM subjects WHERE subject_id = ?";
    if ($stmt_subject = $conn->prepare($sql_subject)) {
        $stmt_subject->bind_param("i", $subject_id);
        $stmt_subject->execute();
        $result_subject = $stmt_subject->get_result();
        if ($row = $result_subject->fetch_assoc()) {
            $subject_name = $row['name'];
            $subject_code = $row['code'];
        }
        $stmt_subject->close();
    }

    // 3.2. Lấy danh sách Bài giảng
    // CẬP NHẬT QUAN TRỌNG: Đổi cột 'name' thành 'lesson_name'
    $sql_lessons = "SELECT lesson_id, lesson_name, material_files, assignment_files FROM lessons WHERE subject_id = ? ORDER BY lesson_id ASC";
    if ($stmt_lessons = $conn->prepare($sql_lessons)) {
        $stmt_lessons->bind_param("i", $subject_id);
        $stmt_lessons->execute();
        $result_lessons = $stmt_lessons->get_result();
        $lessons = $result_lessons->fetch_all(MYSQLI_ASSOC);
        $stmt_lessons->close();
    }
}

// 4. Helper function để xác định icon và mô tả tài liệu
function get_material_info($material_json) {
    // Đảm bảo xử lý trường hợp JSON rỗng/null an toàn
    $materials = json_decode($material_json, true);
    if (empty($materials)) {
        return ['icon' => 'file', 'description' => 'Không có tài liệu'];
    }
    
    $types = array_map(function($m) { return $m['type']; }, $materials);
    
    // Kiểm tra loại file để gán icon phù hợp
    if (count(array_filter($types, fn($t) => str_starts_with($t, 'video/'))) > 0) {
        return ['icon' => 'monitor-play', 'description' => 'Tài liệu: Video Clip'];
    }
    if (count(array_filter($types, fn($t) => $t == 'application/pdf')) > 0) {
           return ['icon' => 'file-text', 'description' => 'Tài liệu: PDF'];
    }
    if (count($materials) == 1) {
        // Lấy đuôi file hoặc loại file đầu tiên
        $name = $materials[0]['name'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        return ['icon' => 'file-text', 'description' => 'Tài liệu: File '. strtoupper($ext)];
    }
    if (count($materials) > 0) {
        return ['icon' => 'files', 'description' => 'Tài liệu: '. count($materials) .' File đính kèm'];
    }
    return ['icon' => 'file', 'description' => 'Tài liệu: Đính kèm'];
}

$conn->close();

$add_lesson_url = $subject_id ? "lesson_add.php?subject_id={$subject_id}" : "lesson_add.php";
$assignment_tab_url = $subject_id ? "../subject/subject_assignments.php?subject_id={$subject_id}" : "../subject/subject_assignments.php";
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chi tiết Bài giảng</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        <p class="text-sm text-gray-500 mt-1">
          Môn học:
          <span class="font-semibold text-blue-600">
              <!-- DỮ LIỆU ĐỘNG: Tên và mã môn học -->
              <?= htmlspecialchars($subject_code) ?> - <?= htmlspecialchars($subject_name) ?>
          </span>
        </p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8 flex-grow">
      <!-- Nút Trở về Home -->
      <!-- THAY ĐỔI: Chuyển về home.php -->
      <a
        href="../home/home.php"
        class="text-blue-600 hover:text-blue-800 mb-6 flex items-center space-x-1"
      >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Quản lý môn học</span>
      </a>

      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-6 -mb-px">
          <!-- Tab Bài giảng (Đang active) -->
          <button
            class="py-3 px-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600"
          >
            Quản lý Bài Giảng
          </button>
          <!-- Tab Quản lý Bài tập -->
          <!-- THAY ĐỔI: Chuyển sang subject_assignments.php và truyền subject_id -->
          <a
            href="<?= $assignment_tab_url ?>"
            class="py-3 px-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
          >
            Quản lý Bài Tập
          </a>
        </nav>
      </div>

      <!-- Quản lý Bài Giảng Content -->
      <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">
            Danh sách Bài Giảng
          </h3>
          <!-- Nút Thêm Bài Giảng -->
          <!-- THAY ĐỔI: Link đến lesson_add.php và truyền subject_id -->
          <a
            href="<?= $add_lesson_url ?>"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center space-x-2 text-sm"
          >
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Thêm Bài Giảng</span>
          </a>
        </div>

        <div class="space-y-4">
          <?php if (!empty($lessons)): ?>
              <?php foreach ($lessons as $lesson): 
                  $info = get_material_info($lesson['material_files']);
                  // Kiểm tra xem bài giảng có file bài tập đính kèm không
                  $has_assignment = !empty($lesson['assignment_files']) && json_decode($lesson['assignment_files'], true);
              ?>
                <!-- Bài Giảng Động -->
                <div
                  class="p-4 bg-gray-50 rounded-lg border border-l-4 border-green-500 flex justify-between items-center transition duration-150 hover:shadow-md"
                >
                  <div class="flex items-center space-x-3">
                    <!-- Icon dựa trên loại tài liệu -->
                    <i data-lucide="<?= $info['icon'] ?>" class="w-6 h-6 text-green-600"></i>
                    <div>
                      <p class="font-medium text-gray-800">
                        <!-- CẬP NHẬT: Đổi $lesson['name'] thành $lesson['lesson_name'] -->
                        <?= htmlspecialchars($lesson['lesson_name']) ?>
                      </p>
                      <span class="text-xs text-gray-500 italic">
                        <?= htmlspecialchars($info['description']) ?>
                      </span>
                    </div>
                  </div>
                  <div class="flex items-center space-x-4">
                    <?php if ($has_assignment): ?>
                        <!-- Nút Bài Tập (Chuyển trang chấm điểm) -->
                        <a
                          href="submission_grading.php?lesson_id=<?= $lesson['lesson_id'] ?>"
                          class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold hover:bg-blue-700 transition flex items-center"
                        >
                          Bài Tập
                          <i data-lucide="chevron-right" class="w-4 h-4 inline ml-1"></i>
                        </a>
                    <?php else: ?>
                        <!-- Không có bài tập -->
                        <span class="text-xs text-gray-400 font-medium px-3 py-1">
                            Không có Bài tập
                        </span>
                    <?php endif; ?>

                    <!-- Nút Sửa (Edit) -->
                    <!-- Sẽ link đến lesson_edit.php?lesson_id=... -->
                    <a 
                        href="lesson_edit.php?lesson_id=<?= $lesson['lesson_id'] ?>"
                        class="text-gray-500 hover:text-yellow-600"
                        title="Sửa bài giảng"
                    >
                      <i data-lucide="edit" class="w-5 h-5"></i>
                    </a>
                    <!-- Nút Xóa (Delete) -->
                    <!-- Sẽ gọi hàm xóa bằng JS/AJAX -->
                    <button 
                        data-lesson-id="<?= $lesson['lesson_id'] ?>"
                        class="text-gray-500 hover:text-red-600"
                        title="Xóa bài giảng"
                    >
                      <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
          <?php else: ?>
              <!-- Thông báo khi không có bài giảng nào -->
              <div class="text-center py-10 text-gray-500 border border-dashed rounded-lg bg-gray-50">
                  <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 text-gray-400"></i>
                  <p class="mb-3">Không tìm thấy bài giảng nào cho môn học này.</p>
                  <a href="<?= $add_lesson_url ?>" class="text-indigo-600 hover:underline font-medium flex items-center justify-center space-x-1">
                      <i data-lucide="plus" class="w-4 h-4"></i>
                      <span>Thêm Bài Giảng đầu tiên</span>
                  </a>
              </div>
          <?php endif; ?>
          
          <!-- Xóa Form mẫu HTML tĩnh không cần thiết -->
        </div>
      </div>
    </main>

    <script>
      lucide.createIcons();
    </script>
  </body>
</html>