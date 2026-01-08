<?php
// 1. SESSION & AUTH
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

require_once '../../../config/connectdb.php';

// 2. VALIDATE INPUT
if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    die("ID môn học không hợp lệ");
}

$subject_id = (int)$_GET['subject_id'];

// 3. LOAD SUBJECT INFO
$sql_subject = "SELECT subject_id, subject_name, description FROM subjects WHERE subject_id = ?";
$stmt = $conn->prepare($sql_subject);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$subject) {
    die("Không tìm thấy môn học");
}

// 4. LOAD LESSONS OF SUBJECT
$lessons = [];

$sql_lessons = "
    SELECT lesson_id, lesson_name, created_at
    FROM lessons
    WHERE subject_id = ?
    ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql_lessons);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $lessons[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($subject['subject_name']) ?> | Danh sách buổi học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">
                📘 <?= htmlspecialchars($subject['subject_name']) ?>
            </h1>
            <p class="text-slate-500 text-sm mt-1">
                Danh sách các buổi học
            </p>
        </div>

        <a href="subject_lessons.php"
           class="flex items-center gap-2 px-4 py-2 rounded-lg border text-slate-600 hover:bg-slate-100 text-sm font-semibold">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Quay lại
        </a>
    </div>
</header>

<!-- MAIN -->
<main class="max-w-6xl mx-auto p-6">

<?php if (empty($lessons)): ?>
    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-16 text-center">
        <i data-lucide="calendar-x" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
        <h2 class="text-xl font-bold text-slate-700">Chưa có buổi học</h2>
        <p class="text-slate-500 mt-2">Môn học này hiện chưa được cập nhật bài giảng.</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($lessons as $index => $lesson): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between hover:shadow-md transition">

                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
                        <?= $index + 1 ?>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-800">
                            <?= htmlspecialchars($lesson['lesson_name']) ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            📅 <?= date('d/m/Y H:i', strtotime($lesson['created_at'])) ?>
                        </p>
                    </div>
                </div>

                <!-- NÚT XEM CHI TIẾT BUỔI HỌC -->
                <a href="lesson_content.php?lesson_id=<?= $lesson['lesson_id'] ?>"
                   class="flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold hover:bg-blue-600 transition">
                    Xem bài học
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</main>

<script>
    lucide.createIcons();
</script>

</body>
</html>
