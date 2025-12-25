<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? -1) != 0) {
    header("Location: ../../../login/DangNhap.php");
    exit();
}
require_once '../../../config/connectdb.php';
$admin_id   = $_SESSION['user_id'];
$teacher_name = $_SESSION['name'] ?? 'Admin';
// Tổng môn học
$total_subjects = 0;
$stmt = $conn->prepare("
    SELECT COUNT(subject_id)
    FROM subjects
    WHERE created_by = ?
");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($total_subjects);
$stmt->fetch();
$stmt->close();

// Tổng bài giảng
$total_lessons = 0;
$stmt = $conn->prepare("
    SELECT COUNT(l.lesson_id)
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.subject_id
    WHERE s.created_by = ?
");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($total_lessons);
$stmt->fetch();
$stmt->close();

$conn->close();
$stats = [
    ['title' => 'Môn học quản lý', 'value' => $total_subjects, 'icon' => 'book-open'],
    ['title' => 'Bài giảng', 'value' => $total_lessons, 'icon' => 'layers'],
    ['title' => 'Sinh viên', 'value' => rand(100, 500), 'icon' => 'users'],
    ['title' => 'Đánh giá TB', 'value' => '4.2 / 5.0', 'icon' => 'star'],
];

$management_areas = [
    [
        'title' => 'Quản lý Môn học',
        'description' => 'Thêm, sửa, xóa môn học',
        'icon' => 'package',
        'link' => '../lesson/lesson.php'
    ],
    [
        'title' => 'Chấm Bài tập',
        'description' => 'Duyệt & chấm bài sinh viên',
        'icon' => 'clipboard-check',
        'link' => '../subject/grade_submission.php'
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Quản Trị</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-slate-100 min-h-screen">

<header class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold flex items-center gap-2">
        <i data-lucide="graduation-cap"></i> TEACHER
    </h1>
    <div class="flex items-center gap-4">
        <span>Chào mừng, <b><?= htmlspecialchars($teacher_name) ?></b></span>
        <a href="../../login/logout.php" class="text-red-600 font-semibold">Đăng xuất</a>
    </div>
</header>

<main class="container mx-auto px-6 py-8">

<h2 class="text-2xl font-bold mb-6">Thống kê tổng quan</h2>

<div class="grid md:grid-cols-4 gap-6 mb-10">
<?php foreach ($stats as $s): ?>
    <div class="bg-white p-6 rounded shadow">
        <i data-lucide="<?= $s['icon'] ?>" class="w-6 h-6 text-indigo-600"></i>
        <p class="text-gray-500 mt-2"><?= $s['title'] ?></p>
        <p class="text-3xl font-bold"><?= $s['value'] ?></p>
    </div>
<?php endforeach; ?>
</div>

<h2 class="text-2xl font-bold mb-4">Quản lý nội dung</h2>

<div class="grid md:grid-cols-2 gap-6">
<?php foreach ($management_areas as $a): ?>
    <a href="<?= $a['link'] ?>" class="bg-white p-6 rounded shadow hover:shadow-lg transition">
        <i data-lucide="<?= $a['icon'] ?>" class="w-8 h-8 text-indigo-600"></i>
        <h3 class="text-xl font-bold mt-2"><?= $a['title'] ?></h3>
        <p class="text-gray-600"><?= $a['description'] ?></p>
    </a>
<?php endforeach; ?>
</div>

</main>

<script>lucide.createIcons();</script>
</body>
</html>
