<?php
// Bắt đầu session
session_start();

// Kiểm tra đăng nhập. Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
// Cập nhật: DangNhap.php hiện đang ở cùng thư mục login/
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/DangNhap.php"); 
    exit();
}

// Kết nối CSDL
// Đảm bảo đường dẫn này đúng theo cấu trúc: /views/subject/ -> /config/
require_once '../../config/connectdb.php'; 

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// --- 1. Lấy dữ liệu mẫu ---
// ... (các hàm getFeaturedSubjects, getUserLearningSubjects, getNotifications không thay đổi) ...

// Giả định hàm này lấy danh sách các môn học nổi bật
function getFeaturedSubjects($conn) {
    // Truy vấn ví dụ: Lấy 3 môn học có nhiều bài giảng nhất
    $sql = "SELECT s.subject_id, s.subject_name, s.description, COUNT(l.lesson_id) AS lesson_count
            FROM subjects s
            LEFT JOIN lessons l ON s.subject_id = l.subject_id
            GROUP BY s.subject_id
            ORDER BY lesson_count DESC
            LIMIT 3";
    
    $result = $conn->query($sql);
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

// Giả định hàm này lấy danh sách các môn học mà người dùng đang theo dõi/đã đăng ký
function getUserLearningSubjects($conn, $user_id) {
    // Đây là truy vấn giả định. Trong thực tế, cần có bảng user_subjects để theo dõi
    // Tạm thời lấy tất cả các môn học
    $sql = "SELECT subject_id, subject_name FROM subjects LIMIT 5";
    $result = $conn->query($sql);
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

// Giả định hàm này lấy thông báo về việc chấm bài
function getNotifications($conn, $user_id) {
    // Giả định có bảng 'notifications'
    // Tạm thời tạo dữ liệu giả
    return [
        ['message' => 'Bài tập Lập trình Web đã được chấm. Điểm: 9/10.', 'time' => '10 phút trước', 'link' => '#'],
        ['message' => 'Giảng viên đã phê bình bài làm Môn Cơ sở Dữ liệu.', 'time' => '2 giờ trước', 'link' => '#'],
    ];
}

$featured_subjects = getFeaturedSubjects($conn);
$learning_subjects = getUserLearningSubjects($conn, $user_id);
$notifications = getNotifications($conn, $user_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ | Học Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
        body { font-family: "Inter", sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body class="min-h-screen">

    <!-- Navbar/Header (Tạm thời, có thể chuyển ra file layout sau) -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-indigo-600">Học Online</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700 font-medium hidden sm:block">Xin chào, <?= htmlspecialchars($user_name) ?></span>
                <!-- Cập nhật đường dẫn Đăng xuất -->
                <a href="../login/logout.php" class="text-sm font-medium text-red-500 hover:text-red-700 transition">
                    Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Cột Chính (Tìm kiếm và Môn học) -->
            <div class="lg:col-span-2 space-y-10">

                <!-- 1. Thanh Tìm kiếm -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-2xl font-extrabold text-gray-800 mb-4">Tìm kiếm Môn học</h2>
                    <form action="search_results.php" method="GET" class="flex space-x-2">
                        <div class="relative flex-grow">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                            <input
                                type="search"
                                name="q"
                                placeholder="Nhập tên môn học (VD: Lập trình Web, Cơ sở Dữ liệu)"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 flex items-center space-x-2"
                        >
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>

                <!-- 2. Môn học Đang/Đã Học (Tiến độ của User) -->
                <div class="space-y-4">
                    <h2 class="text-2xl font-extrabold text-gray-800 border-b-2 border-indigo-200 pb-2">Môn học của bạn</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if (empty($learning_subjects)): ?>
                            <p class="text-gray-500 col-span-2">Bạn chưa đăng ký môn học nào. Hãy khám phá bên dưới!</p>
                        <?php else: ?>
                            <?php foreach ($learning_subjects as $subject): ?>
                                <a href="exercise/subject_lessons.php?subject_id=<?= $subject['subject_id'] ?>" class="block bg-white p-5 rounded-xl shadow-md hover:shadow-lg transition duration-200 border-l-4 border-indigo-500">
                                    <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1">Đang học (Ví dụ: 3/10 bài giảng đã hoàn thành)</p>
                                    <!-- Thanh tiến độ đơn giản -->
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div class="bg-indigo-500 h-2.5 rounded-full" style="width: 30%"></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Môn học Tiêu biểu (Khám phá) -->
                <div class="space-y-4 pt-4">
                    <h2 class="text-2xl font-extrabold text-gray-800 border-b-2 border-teal-200 pb-2">Khám phá các Môn học Tiêu biểu</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php if (empty($featured_subjects)): ?>
                            <p class="text-gray-500 col-span-3">Hiện chưa có môn học tiêu biểu nào được hiển thị.</p>
                        <?php else: ?>
                            <?php foreach ($featured_subjects as $subject): ?>
                                <a href="exercise/subject_lessons.php?subject_id=<?= $subject['subject_id'] ?>" class="block bg-white p-5 rounded-xl shadow-md hover:shadow-lg transition duration-200 border-t-4 border-teal-500">
                                    <i data-lucide="book-open" class="w-6 h-6 text-teal-600 mb-2"></i>
                                    <h3 class="text-lg font-semibold text-gray-800 truncate"><?= htmlspecialchars($subject['subject_name']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($subject['description']) ?></p>
                                    <p class="text-xs text-indigo-400 mt-2"><?= $subject['lesson_count'] ?> Bài giảng</p>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cột Bên (Thông báo) -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-lg space-y-4 sticky top-8">
                    <h2 class="text-xl font-extrabold text-gray-800 flex items-center space-x-2 border-b pb-2">
                        <i data-lucide="bell" class="w-6 h-6 text-red-500"></i>
                        <span>Thông báo Chấm bài</span>
                    </h2>

                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                            <p class="text-sm">Hộp thư thông báo của bạn trống.</p>
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-100">
                            <?php foreach ($notifications as $notif): ?>
                                <li class="py-3">
                                    <a href="<?= htmlspecialchars($notif['link']) ?>" class="block hover:bg-gray-50 p-2 rounded-lg transition">
                                        <p class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($notif['message']) ?></p>
                                        <p class="text-xs text-red-500 mt-0.5 flex items-center space-x-1">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span><?= htmlspecialchars($notif['time']) ?></span>
                                        </p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>