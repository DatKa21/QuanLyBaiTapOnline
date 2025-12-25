<?php
session_start();
require_once '../../../config/connectdb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 0) {
    header("Location: ../../../login/DangNhap.php");
    exit();
}

$subject_id = intval($_GET['subject_id'] ?? 0);
$subject = null;
$lessons = [];

if ($subject_id > 0) {
    // LẤY TÊN MÔN HỌC
    $sql = "SELECT subject_name FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // LẤY DANH SÁCH BÀI GIẢNG (KHÔNG description)
    $sql = "
        SELECT lesson_id, lesson_name
        FROM lessons
        WHERE subject_id = ?
        ORDER BY lesson_id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách bài giảng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            📘 <?= htmlspecialchars($subject['subject_name'] ?? 'Không tìm thấy môn học') ?>
        </h1>
        <p class="text-gray-500 mt-1">Danh sách bài giảng</p>
    </div>

    <?php if ($subject): ?>
        <a href="subject_add.php?subject_id=<?= $subject_id ?>"
           class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition">
            ➕ Thêm bài giảng
        </a>
    <?php endif; ?>
</div>

</div>

<div class="max-w-6xl mx-auto mb-6">
    <a href="../lesson/lesson.php" class="text-indigo-600 flex items-center gap-1 mb-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại môn học
    </a>

    <h1 class="text-3xl font-bold text-gray-800">
        📘 <?= htmlspecialchars($subject['subject_name'] ?? 'Không tìm thấy môn học') ?>
    </h1>
    <p class="text-gray-500 mt-1">Danh sách bài giảng</p>
</div>
</div>

<div class="max-w-6xl mx-auto bg-white rounded-lg shadow p-6">

<?php if (!$subject): ?>
    <p class="text-red-600 font-semibold">Môn học không tồn tại.</p>

<?php elseif (empty($lessons)): ?>
    <p class="text-gray-500">Môn học này chưa có bài giảng.</p>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($lessons as $index => $lesson): ?>
            <a href="lesson_detail.php?lesson_id=<?= $lesson['lesson_id'] ?>"
               class="block border p-4 rounded hover:border-indigo-500 hover:bg-indigo-50 transition">

                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                        <?= $index + 1 ?>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800">
                        <?= htmlspecialchars($lesson['lesson_name']) ?>
                    </h3>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
