<?php
// 1. SESSION & AUTH
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/DangNhap.php");
    exit();
}

// Admin thì đá sang trang admin
if (isset($_SESSION['role']) && $_SESSION['role'] == 0) {
    header("Location: ../admin/home/home.php");
    exit();
}

// 2. DATA FROM SESSION
$user_id    = $_SESSION['user_id'];
$user_name  = $_SESSION['username'] ?? 'Học viên';
$user_email = $_SESSION['email'] ?? '';
$user_role  = $_SESSION['role'] ?? 1;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Chủ Học Viên</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>

<body class="min-h-screen bg-slate-50">
<!-- NAVBAR -->
<nav class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <i data-lucide="graduation-cap" class="w-8 h-8 text-indigo-600"></i>
            <span class="text-xl font-bold text-gray-900">WEB PHP</span>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">
                Xin chào, <strong><?= htmlspecialchars($user_name) ?></strong>
            </span>
            <a href="../login/logout.php" class="text-gray-500 hover:text-red-600">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </a>
        </div>
    </div>
</nav>
<!-- MAIN -->
<main class="max-w-7xl mx-auto px-6 py-10">

    <div class="bg-white rounded-2xl shadow p-8">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Bảng Tin Học Viên</h1>
                <p class="text-gray-500 mt-1">Chào mừng bạn quay trở lại hệ thống học tập.</p>
            </div>
            <span class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full text-sm font-semibold">
                Role: Học viên
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-6">

            <!-- CARD MÔN HỌC -->
            <div class="p-6 rounded-xl border hover:shadow-md transition">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="book-open" class="text-indigo-600 w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Môn học của tôi</h3>
                <p class="text-gray-600 text-sm mb-4">
                    Xem danh sách các môn học, bài giảng và bài tập.
                </p>
                <a href="exercise/subject_lessons.php" class="text-indigo-600 font-semibold hover:underline inline-flex items-center">
                    Xem môn học <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                </a>
            </div>

            <!-- CARD THÔNG BÁO -->
            <div class="p-6 rounded-xl border hover:shadow-md transition">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="bell" class="text-emerald-600 w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Thông báo</h3>
                <p class="text-gray-600 text-sm mb-4">
                    Thông báo từ giảng viên và hệ thống.
                </p>
                <span class="text-gray-400 text-sm">Chưa có thông báo</span>
            </div>
        </div>

        <!-- PROFILE -->
        <div class="mt-10 pt-6 border-t flex items-center gap-4">
            <div class="w-16 h-16 bg-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-bold">
                <?= mb_substr($user_name, 0, 1) ?>
            </div>
            <div>
                <p class="font-bold"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($user_email) ?></p>
            </div>
        </div>

    </div>
</main>

<script>
    lucide.createIcons();
</script>

</body>
</html>
