<?php
session_start();
require_once '../../../config/connectdb.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../login/DangNhap.php");
    exit();
}

$submission_id = intval($_GET['submission_id'] ?? 0);
if ($submission_id <= 0) die("Thiếu submission_id");

$sql = "
    SELECT 
        s.submission_id,
        s.submission_files,
        s.submitted_at,
        s.grade,
        s.feedback,
        u.full_name,
        a.title AS assignment_title
    FROM submissions s
    JOIN users u ON s.user_id = u.user_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    WHERE s.submission_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) die("Bài nộp không tồn tại");

$files = json_decode($data['submission_files'], true) ?? [];

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = trim($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $sql = "UPDATE submissions SET grade = ?, feedback = ? WHERE submission_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $stmt->execute();
    $stmt->close();

    $success = "✅ Chấm điểm thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chấm điểm bài nộp</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-100 min-h-screen p-6">

<div class="max-w-5xl mx-auto space-y-6">

<!-- HEADER -->
<div class="bg-white p-6 rounded-xl shadow flex justify-between items-center">
    <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-marker text-indigo-600 mr-2"></i>
        Chấm điểm bài nộp
    </h1>

    <a href="javascript:history.back()"
       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
    </a>
</div>

<!-- INFO -->
<div class="bg-white p-6 rounded-xl shadow space-y-2">
    <p><strong>Sinh viên:</strong> <?= htmlspecialchars($data['full_name']) ?></p>
    <p><strong>Bài tập:</strong> <?= htmlspecialchars($data['assignment_title']) ?></p>
    <p><strong>Thời gian nộp:</strong> <?= $data['submitted_at'] ?></p>
</div>

<!-- FILES -->
<div class="bg-white p-6 rounded-xl shadow">
    <h2 class="font-bold mb-4">
        <i class="fas fa-paperclip mr-2"></i> File sinh viên nộp
    </h2>

    <?php if (empty($files)): ?>
        <p class="italic text-gray-500">Không có file</p>
    <?php else: ?>
        <div class="space-y-3">
        <?php foreach ($files as $f): ?>
            <a href="../../../<?= htmlspecialchars($f['path']) ?>" target="_blank"
               class="block bg-indigo-50 hover:bg-indigo-100 p-4 rounded-lg">
                <i class="fas fa-file mr-2"></i>
                <?= htmlspecialchars($f['original']) ?>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- GRADING -->
<div class="bg-white p-6 rounded-xl shadow border-t-4 border-green-500">
    <h2 class="font-bold mb-4">
        <i class="fas fa-check-circle mr-2"></i> Chấm điểm
    </h2>

    <?php if ($success): ?>
        <div class="mb-4 bg-green-100 text-green-700 px-4 py-2 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block font-medium mb-1">Điểm</label>
            <input type="number" name="grade" step="0.1" required
                   value="<?= htmlspecialchars($data['grade'] ?? '') ?>"
                   class="w-full border rounded-lg p-2">
        </div>

        <div>
            <label class="block font-medium mb-1">Nhận xét</label>
            <textarea name="feedback" rows="4"
                      class="w-full border rounded-lg p-2"><?= htmlspecialchars($data['feedback'] ?? '') ?></textarea>
        </div>

        <button type="submit"
                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
            <i class="fas fa-save mr-1"></i> Lưu điểm
        </button>
    </form>
</div>

</div>
</body>
</html>
