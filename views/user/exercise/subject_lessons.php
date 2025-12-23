<?php
// Bắt đầu session và kiểm tra đăng nhập
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

require_once '../../../config/connectdb.php'; 

$subject_id = $_GET['subject_id'] ?? null;
$subject_name = 'Môn học không tồn tại';
$lessons = [];

if ($subject_id) {
    // 1. Lấy tên môn học
    $sql_subject = "SELECT subject_name FROM subjects WHERE subject_id = ?";
    if ($stmt_sub = $conn->prepare($sql_subject)) {
        $stmt_sub->bind_param("i", $subject_id);
        $stmt_sub->execute();
        $result_sub = $stmt_sub->get_result();
        if ($result_sub->num_rows > 0) {
            $subject_name = $result_sub->fetch_assoc()['subject_name'];
        }
        $stmt_sub->close();
    }
    
    // 2. Lấy danh sách các buổi học/bài giảng (lessons) của môn học này
    $sql_lessons = "SELECT lesson_id, lesson_name, description 
                    FROM lessons 
                    WHERE subject_id = ? 
                    ORDER BY lesson_id ASC";

    if ($stmt_les = $conn->prepare($sql_lessons)) {
        $stmt_les->bind_param("i", $subject_id);
        $stmt_les->execute();
        $result_les = $stmt_les->get_result();
        if ($result_les->num_rows > 0) {
            while ($row = $result_les->fetch_assoc()) {
                $lessons[] = $row;
            }
        }
        $stmt_les->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài giảng: <?= htmlspecialchars($subject_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
        body { font-family: "Inter", sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body class="min-h-screen">
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="../home.php" class="text-indigo-600 hover:text-indigo-800 flex items-center space-x-1 mb-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="text-sm">Quay lại Trang chủ</span>
            </a>
            <h1 class="text-3xl font-extrabold text-gray-900"><?= htmlspecialchars($subject_name) ?></h1>
            <p class="text-md text-gray-500 mt-1">Danh sách các Buổi học và Bài tập</p>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="space-y-6">
            <?php if (empty($lessons)): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                    <i data-lucide="alert-triangle" class="w-10 h-10 mx-auto mb-3 text-yellow-500"></i>
                    <p class="text-gray-600">Môn học này hiện chưa có bài giảng nào.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg divide-y divide-gray-100">
                    <?php foreach ($lessons as $index => $lesson): ?>
                        <!-- Mỗi buổi học -->
                        <div class="p-5 hover:bg-gray-50 transition duration-150">
                            <a 
                                href="lesson_detail.php?lesson_id=<?= $lesson['lesson_id'] ?>" 
                                class="flex justify-between items-center group"
                            >
                                <div class="flex items-start space-x-4">
                                    <div class="bg-indigo-100 text-indigo-600 w-10 h-10 flex items-center justify-center rounded-full font-bold flex-shrink-0">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 group-hover:text-indigo-600">
                                            <?= htmlspecialchars($lesson['lesson_name']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($lesson['description']) ?></p>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>