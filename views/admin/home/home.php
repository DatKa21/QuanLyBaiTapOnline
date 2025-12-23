<?php
// Bắt đầu session
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa và có phải là Giáo viên/Admin (role_id = 0) không
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 0) {
    // Nếu chưa đăng nhập hoặc không phải vai trò quản lý, chuyển hướng về trang đăng nhập
    // Đường dẫn: /views/admin/home/ -> /auth/DangNhap.php
    header("Location: ../../../login/DangNhap.php"); 
    exit();
}

// 1. Kết nối Cơ sở Dữ liệu
// Đường dẫn: /views/admin/home/ -> /config/connectdb.php (3 cấp lên)
require_once '../../../config/connectdb.php'; 

// Lấy thông tin Giáo viên từ Session
$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'] ?? 'Giáo viên'; 

// --- 2. Truy vấn dữ liệu thống kê ---

// a. Tổng số Môn học do giáo viên này quản lý
$total_subjects = 0;
// ĐÃ SỬA: Thay 'teacher_id' thành 'created_by'
$sql_subjects = "SELECT COUNT(subject_id) AS total_subjects FROM subjects WHERE created_by = ?";
if ($stmt = $conn->prepare($sql_subjects)) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_subjects = $row['total_subjects'];
    }
    $stmt->close();
}

// b. Tổng số Bài giảng (Lessons) trong các môn học này
$total_lessons = 0;
// Cần JOIN: Lấy COUNT(lessons) WHERE subject.created_by = teacher_id
// ĐÃ SỬA: Thay 's.teacher_id' thành 's.created_by'
$sql_lessons = "SELECT COUNT(l.lesson_id) AS total_lessons 
                FROM lessons l 
                JOIN subjects s ON l.subject_id = s.subject_id 
                WHERE s.created_by = ?";
if ($stmt = $conn->prepare($sql_lessons)) {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_lessons = $row['total_lessons'];
    }
    $stmt->close();
}

// c. Dữ liệu giả lập cho các thẻ khác
$total_students_mock = rand(100, 5000); // Tổng số sinh viên đã đăng ký các khóa học này (Dữ liệu giả lập)
$avg_rating_mock = number_format(4 + (rand(0, 9) / 10), 1); // Đánh giá trung bình (Dữ liệu giả lập)

$conn->close();

// --- Dữ liệu hiển thị trong các thẻ Thống kê ---
$stats = [
    ['title' => 'Môn học Đang quản lý', 'value' => $total_subjects, 'icon' => 'book-open', 'color' => 'bg-indigo-100 text-indigo-600'],
    ['title' => 'Tổng số Bài giảng', 'value' => $total_lessons, 'icon' => 'graduation-cap', 'color' => 'bg-green-100 text-green-600'],
    ['title' => 'Sinh viên Đã đăng ký', 'value' => $total_students_mock, 'icon' => 'users', 'color' => 'bg-yellow-100 text-yellow-600'],
    ['title' => 'Đánh giá trung bình', 'value' => $avg_rating_mock . ' / 5.0', 'icon' => 'star', 'color' => 'bg-red-100 text-red-600'],
];

// --- Danh sách khu vực quản lý ---
$management_areas = [
    [
        'title' => 'Quản lý Môn học',
        'description' => 'Thêm, sửa, xóa và xem danh sách các môn học bạn đã đăng tải.',
        'icon' => 'package-2',
        'color' => 'bg-indigo-600',
        'link' => '../subject/index.php' 
    ],
    [
        'title' => 'Quản lý Bài giảng & Nội dung',
        'description' => 'Sắp xếp, thêm nội dung và chỉnh sửa các bài giảng của từng môn.',
        'icon' => 'file-text',
        'color' => 'bg-green-600',
        'link' => '../lesson/lesson.php' // Giả định đường dẫn
    ],
    [
        'title' => 'Duyệt & Chấm Bài tập',
        'description' => 'Duyệt, chấm điểm và phản hồi bài tập đã nộp của sinh viên.',
        'icon' => 'clipboard-check',
        'color' => 'bg-teal-600',
        'link' => '../exercise/exercise_review.php' // Giả định đường dẫn
    ],
    [
        'title' => 'Quản lý Phản hồi',
        'description' => 'Xem và phản hồi các bình luận, đánh giá từ sinh viên.',
        'icon' => 'message-square',
        'color' => 'bg-yellow-600',
        'link' => '../feedback/index.php' // Giả định đường dẫn
    ]
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | Giáo viên Quản lý Nội dung</title>
    <!-- Tải Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tải Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
        body {
            font-family: "Inter", sans-serif;
            background-color: #f4f7fa;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header / Navigation -->
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i data-lucide="book-check" class="w-7 h-7 text-indigo-600"></i>
                <h1 class="text-3xl font-extrabold text-gray-900">TEACHER DASHBOARD</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700 font-medium hidden sm:block">Chào mừng, <?= htmlspecialchars($teacher_name) ?></span>
                <!-- Nút Đăng xuất -->
                <a
                    href="../../../auth/logout.php"
                    class="text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg font-medium transition flex items-center space-x-1"
                >
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-10 flex-grow">
        <h2 class="text-3xl font-bold text-gray-800 mb-8">Thống kê Tổng quan Khóa học</h2>

        <!-- Khu vực Thống kê Dữ liệu Động -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($stats as $stat): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-indigo-500">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider"><?= htmlspecialchars($stat['title']) ?></p>
                        <div class="p-2 rounded-full <?= htmlspecialchars($stat['color']) ?>">
                            <i data-lucide="<?= htmlspecialchars($stat['icon']) ?>" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mt-2"><?= htmlspecialchars($stat['value']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="text-3xl font-bold text-gray-800 mb-6">Các Khu vực Quản lý Nội dung</h2>

        <!-- Khu vực Chức năng chính -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($management_areas as $area): ?>
                <a 
                    href="<?= htmlspecialchars($area['link']) ?>"
                    class="block p-6 rounded-xl shadow-lg bg-white hover:shadow-xl transform hover:scale-[1.02] transition duration-300 group relative overflow-hidden border-t-4 <?= str_replace('bg', 'border', $area['color']) ?>"
                >
                    <div class="p-3 rounded-xl <?= htmlspecialchars($area['color']) ?> text-white w-fit mb-3 transition duration-300 group-hover:rotate-6">
                        <i data-lucide="<?= htmlspecialchars($area['icon']) ?>" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                        <?= htmlspecialchars($area['title']) ?>
                    </h3>
                    <p class="text-sm text-gray-600">
                        <?= htmlspecialchars($area['description']) ?>
                    </p>
                    <div class="mt-4 flex items-center text-sm font-semibold <?= str_replace('600', '700', str_replace('bg', 'text', $area['color'])) ?>">
                        <span>Truy cập</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
    </main>

    <script>
        // Khởi tạo Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>