<?php
session_start();
require_once '../../../config/connectdb.php';

/* ================= AJAX SUBMIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_submit'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status'=>'error','message'=>'Chưa đăng nhập']);
        exit();
    }

    if (!isset($_FILES['submission_file'])) {
        echo json_encode(['status'=>'error','message'=>'Chưa chọn file']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $assignment_id = intval($_POST['assignment_id']);

    $uploadDir = "../../../uploads/submissions/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $file = $_FILES['submission_file'];
    if ($file['error'] !== 0) {
        echo json_encode(['status'=>'error','message'=>'File lỗi']);
        exit();
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = time().'_'.$user_id.'.'.$ext;
    $db_path = "uploads/submissions/".$newName;

    if (!move_uploaded_file($file['tmp_name'], $uploadDir.$newName)) {
        echo json_encode(['status'=>'error','message'=>'Upload thất bại']);
        exit();
    }

    $submission_files = json_encode([[
        'path' => $db_path,
        'original' => $file['name'],
        'size' => $file['size']
    ]], JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO submissions (assignment_id, user_id, submission_files, submitted_at)
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $assignment_id, $user_id, $submission_files);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status'=>'success','message'=>'🎉 Nộp bài thành công!']);
    exit();
}

/* ================= CHECK LOGIN ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/DangNhap.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$lesson_id = intval($_GET['lesson_id'] ?? 0);
if ($lesson_id <= 0) die("Thiếu lesson_id");

/* ================= LOAD LESSON ================= */
$sql = "SELECT lesson_name, lesson_files FROM lessons WHERE lesson_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$lesson) die("Bài giảng không tồn tại");

$lesson_files = json_decode($lesson['lesson_files'], true) ?? [];

/* ================= LOAD ASSIGNMENT ================= */
$sql = "SELECT assignment_id, title, description, attachment FROM assignments WHERE lesson_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$assignment_files = $assignment
    ? json_decode($assignment['attachment'], true) ?? []
    : [];

/* ================= HELPER ================= */
function getYoutubeId($url) {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:.*v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $m);
    return $m[1] ?? null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($lesson['lesson_name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-50 min-h-screen p-6">
<div class="max-w-6xl mx-auto space-y-6">

<!-- HEADER -->
<div class="bg-white p-6 rounded-xl shadow flex justify-between items-center">
    <h1 class="text-2xl font-bold">
        <i class="fas fa-book-open text-indigo-600 mr-2"></i>
        <?= htmlspecialchars($lesson['lesson_name']) ?>
    </h1>
    <a href="javascript:history.back()" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
        ← Quay lại
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">

<!-- LEFT -->
<div class="lg:col-span-2 space-y-6">

<!-- VIDEO -->
<?php foreach ($lesson_files as $f):
if (($f['type'] ?? '') === 'youtube'):
$yt = getYoutubeId($f['url']);
if ($yt): ?>
<div class="bg-white p-4 rounded-xl shadow">
    <div class="aspect-video">
        <iframe class="w-full h-full rounded"
                src="https://www.youtube.com/embed/<?= $yt ?>"
                allowfullscreen></iframe>
    </div>
</div>
<?php endif; endif; endforeach; ?>

<!-- FILES -->
<div class="bg-white p-6 rounded-xl shadow">
<h2 class="font-bold mb-4"><i class="fas fa-folder-open mr-2"></i>Tài liệu bài giảng</h2>

<?php
$files = array_filter($lesson_files, fn($f)=>isset($f['path']));
if (!$files):
?>
<p class="text-sm italic text-gray-500">Chưa có tài liệu.</p>
<?php else: ?>
<div class="grid sm:grid-cols-2 gap-4">
<?php foreach ($files as $f):
$path = "../../../".$f['path'];
$ext = strtolower(pathinfo($f['original'], PATHINFO_EXTENSION));
$isImg = in_array($ext,['jpg','jpeg','png','webp']);
?>
<div class="border rounded-xl p-4">
<p class="font-semibold text-sm truncate"><?= htmlspecialchars($f['original']) ?></p>

<div class="flex gap-2 mt-2">
<a href="<?= $path ?>" target="_blank" class="text-xs px-3 py-1 border rounded">Xem</a>
<a href="<?= $path ?>" download class="text-xs px-3 py-1 border rounded">Tải</a>
</div>

<?php if ($isImg): ?>
<img src="<?= $path ?>" class="mt-3 h-32 w-full object-cover rounded">
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>

<!-- RIGHT -->
<div class="space-y-6">
<div class="bg-white p-6 rounded-xl shadow border-t-4 border-green-500">
<h2 class="font-bold mb-3"><i class="fas fa-tasks mr-2"></i>Bài tập</h2>

<?php if (!$assignment): ?>
<p class="text-sm italic text-gray-500">Không có bài tập</p>
<?php else: ?>
<p class="font-semibold"><?= htmlspecialchars($assignment['title']) ?></p>
<p class="text-sm mt-2"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>

<?php if ($assignment_files): ?>
<div class="mt-3 space-y-2">
<?php foreach ($assignment_files as $f): ?>
<a href="../../../<?= htmlspecialchars($f['path']) ?>" target="_blank"
   class="block text-sm bg-indigo-50 p-3 rounded">
<i class="fas fa-paperclip mr-2"></i><?= htmlspecialchars($f['original']) ?>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form id="submitForm" class="mt-4 space-y-3">
    <input type="file" name="submission_file" required class="w-full border p-2 rounded">
    <input type="hidden" name="assignment_id" value="<?= $assignment['assignment_id'] ?>">
    <button class="bg-green-600 text-white px-4 py-2 rounded">
        <i class="fas fa-upload mr-1"></i> Nộp bài
    </button>
</form>
<?php endif; ?>
</div>
</div>

</div>
</div>

<div id="toast" class="fixed top-5 right-5 hidden px-4 py-3 rounded-lg shadow-lg text-white"></div>

<script>
const form = document.getElementById('submitForm');
const toast = document.getElementById('toast');

if (form) {
form.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    fd.append('ajax_submit','1');

    fetch('',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
        toast.classList.remove('hidden');
        toast.className = d.status==='success'
            ? 'fixed top-5 right-5 bg-green-600 px-4 py-3 rounded-lg shadow-lg text-white'
            : 'fixed top-5 right-5 bg-red-600 px-4 py-3 rounded-lg shadow-lg text-white';
        toast.innerText = d.message;
        if(d.status==='success') form.reset();
        setTimeout(()=>toast.classList.add('hidden'),3000);
    });
});
}
</script>

</body>
</html>
