<?php
session_start();
require_once '../../../config/connectdb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 0) {
    header("Location: ../../../login/DangNhap.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

$subjects = [];

$sql = "
    SELECT subject_id, subject_code, subject_name, credits, created_at
    FROM subjects
    WHERE created_by = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$subjects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách môn học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<!-- HEADER -->
<div class="max-w-6xl mx-auto mb-6">
    <h1 class="text-3xl font-bold text-gray-800">📚 Quản lý Môn học</h1>
    <p class="text-gray-500 mt-1">Chọn môn học để quản lý bài giảng</p>
    <a href="../home/home.php" class="text-blue-600 hover:underline">← Quản lý môn học</a>
</div>

<!-- CONTENT -->
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow p-6">

    <?php if (empty($subjects)): ?>
        <div class="text-center text-gray-500 py-10">
            Hiện chưa có môn học nào.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php foreach ($subjects as $subject): ?>
                <a
                    href="../subject/subject.php?subject_id=<?= $subject['subject_id'] ?>"
                    class="block border rounded-lg p-5 hover:shadow-lg hover:border-indigo-500 transition"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <i data-lucide="book-open" class="w-6 h-6 text-indigo-600"></i>
                        <h2 class="text-lg font-semibold text-gray-800">
                            <?= htmlspecialchars($subject['subject_name']) ?>
                        </h2>
                    </div>

                    <p class="text-sm text-gray-500 mb-2">
                        Mã môn: <strong><?= htmlspecialchars($subject['subject_code']) ?></strong>
                    </p>

                    <p class="text-sm text-gray-500">
                        Số tín chỉ: <?= $subject['credits'] ?>
                    </p>

                    <div class="mt-4 text-indigo-600 text-sm font-semibold flex items-center gap-1">
                        Xem bài học
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
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
