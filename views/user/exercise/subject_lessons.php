<?php
// ===============================
// 1. SESSION & AUTH
// ===============================
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

require_once '../../../config/connectdb.php';

// ===============================
// 2. LOAD DATA - Lấy dữ liệu theo Schema của bạn
// ===============================
$subjects = [];

/** * ĐÃ FIX: Lấy các cột đúng theo bảng 'subjects' của bạn:
 * subject_id, subject_name, description, credits
 */
$sql_all_subjects = "SELECT subject_id, subject_name, description, credits FROM subjects ORDER BY created_at DESC";
$result = $conn->query($sql_all_subjects);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Môn học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-[#f1f5f9] min-h-screen">

<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 p-2.5 rounded-xl shadow-lg shadow-blue-100">
                <i data-lucide="book-open" class="text-white w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Học phần trực tuyến</h1>
                <div class="flex items-center gap-2 text-slate-500 text-xs">
                    <span class="flex items-center gap-1"><i data-lucide="database" class="w-3 h-3"></i> <?= count($subjects) ?> Môn học</span>
                </div>
            </div>
        </div>
        
        <a href="../home.php" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-100 rounded-lg transition-all text-slate-600 text-sm font-semibold border border-transparent hover:border-slate-200">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            Quay lại
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto p-6">

    <?php if (empty($subjects)): ?>
        <div class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-20 text-center">
            <div class="bg-slate-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                <i data-lucide="folder-search" class="text-slate-300 w-12 h-12"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Kho dữ liệu trống</h2>
            <p class="text-slate-500 mt-2">Dữ liệu môn học đang được cập nhật, vui lòng quay lại sau.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($subjects as $subject): ?>
                <div class="group bg-white rounded-2xl border border-slate-200 hover:border-blue-400 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                    
                    <!-- Phần hiển thị số tín chỉ và icon -->
                    <div class="p-6 pb-0 flex justify-between items-start">
                        <div class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="award" class="w-3.5 h-3.5"></i>
                            <?= $subject['credits'] ?> Tín chỉ
                        </div>
                        <div class="text-slate-200 group-hover:text-blue-100 transition-colors">
                            <i data-lucide="graduation-cap" class="w-10 h-10"></i>
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition-colors leading-tight">
                            <?= htmlspecialchars($subject['subject_name']) ?>
                        </h3>
                        
                        <p class="text-slate-500 text-sm line-clamp-2 min-h-[40px] mb-6 italic">
                            <?= !empty($subject['description']) ? htmlspecialchars($subject['description']) : 'Chưa có mô tả chi tiết cho môn học này.' ?>
                        </p>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                            <a href="lesson_detail.php?subject_id=<?= $subject['subject_id'] ?>" 
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-blue-600 transition-all shadow-lg shadow-slate-200 hover:shadow-blue-200">
                                Vào học ngay
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
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