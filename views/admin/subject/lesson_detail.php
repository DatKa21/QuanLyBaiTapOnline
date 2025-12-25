<?php
session_start();
require_once '../../../config/connectdb.php';

/* ===== CHECK LOGIN ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/DangNhap.php");
    exit();
}

/* ===== GET lesson_id ===== */
$lesson_id = intval($_GET['lesson_id'] ?? 0);
if ($lesson_id <= 0) {
    die("Thiếu lesson_id");
}

/* ===== LẤY BÀI GIẢNG ===== */
$sql = "SELECT lesson_name, lesson_files FROM lessons WHERE lesson_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lesson) {
    die("Bài giảng không tồn tại");
}

$lesson_files = json_decode($lesson['lesson_files'], true) ?? [];

/* ===== LẤY BÀI TẬP (NẾU CÓ) ===== */
$sql = "SELECT title, description, attachment FROM assignments WHERE lesson_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$assignment_files = [];
if ($assignment) {
    $assignment_files = json_decode($assignment['attachment'], true) ?? [];
}

/* ===== HELPER: LẤY ID YOUTUBE ===== */
function getYoutubeId($url) {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return $match[1] ?? null;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài giảng: <?= htmlspecialchars($lesson['lesson_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">

<div class="max-w-6xl mx-auto space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div>
            <nav class="text-sm text-gray-500 mb-1">Quản lý bài giảng &rsaquo; Chi tiết</nav>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                <span class="text-indigo-600 mr-3"><i class="fas fa-book-open"></i></span>
                <?= htmlspecialchars($lesson['lesson_name']) ?>
            </h1>
        </div>

        <a href="javascript:history.back()"
           class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- CỘT TRÁI: TÀI LIỆU & VIDEO -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- VIDEO YOUTUBE -->
            <?php 
            foreach ($lesson_files as $file): 
                if (isset($file['type']) && $file['type'] === 'youtube'): 
                    $ytId = getYoutubeId($file['url']);
                    if ($ytId):
            ?>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold mb-4 flex items-center text-red-600">
                    <i class="fab fa-youtube mr-2 text-2xl"></i> Video bài giảng
                </h2>
                <div class="aspect-video w-full rounded-lg overflow-hidden shadow-inner bg-black">
                    <iframe class="w-full h-full" 
                            src="https://www.youtube.com/embed/<?= $ytId ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
            <?php 
                    endif;
                endif; 
            endforeach; 
            ?>

            <!-- TÀI LIỆU FILE & ẢNH -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold mb-6 flex items-center text-gray-800 border-b pb-3">
                    <i class="fas fa-folder-open mr-2 text-indigo-500"></i> Tài liệu học tập
                </h2>

                <?php 
                // Lọc bỏ video YouTube để chỉ hiện file
                $only_files = array_filter($lesson_files, function($f) { return isset($f['path']); });
                ?>

                <?php if (empty($only_files)): ?>
                    <div class="text-center py-10">
                        <i class="fas fa-file-invoice text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-500">Chưa có file tài liệu nào đính kèm.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($only_files as $file): 
                            $filePath = "../../../" . htmlspecialchars($file['path']);
                            $ext = strtolower(pathinfo($file['original'], PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        ?>
                            <div class="group relative border rounded-xl p-4 hover:border-indigo-400 hover:bg-indigo-50 transition duration-200">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center bg-gray-100 rounded-lg group-hover:bg-white transition">
                                        <?php if ($isImage): ?>
                                            <i class="fas fa-image text-green-500 text-xl"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-lines text-indigo-500 text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($file['original']) ?>">
                                            <?= htmlspecialchars($file['original']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 uppercase mt-1"><?= $ext ?> • <?= round($file['size'] / 1024, 1) ?> KB</p>
                                        
                                        <div class="mt-3 flex gap-2">
                                            <a href="<?= $filePath ?>" target="_blank" class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition">
                                                Xem
                                            </a>
                                            <a href="<?= $filePath ?>" download class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-800 hover:text-white transition">
                                                Tải về
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Ảnh nếu là file ảnh -->
                                <?php if ($isImage): ?>
                                <div class="mt-4 rounded-lg overflow-hidden border shadow-sm">
                                    <img src="<?= $filePath ?>" alt="preview" class="w-full h-32 object-cover">
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT PHẢI: BÀI TẬP -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-t-4 border-t-green-500">
                <h2 class="text-lg font-bold mb-4 flex items-center text-gray-800">
                    <i class="fas fa-tasks mr-2 text-green-500"></i> Bài tập về nhà
                </h2>

                <?php if (!$assignment): ?>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-gray-500 text-sm italic">Không có yêu cầu bài tập cho buổi học này.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <div class="p-4 bg-green-50 rounded-lg">
                            <h3 class="font-bold text-green-800 mb-2"><?= htmlspecialchars($assignment['title']) ?></h3>
                            <p class="text-sm text-gray-700 leading-relaxed">
                                <?= nl2br(htmlspecialchars($assignment['description'])) ?>
                            </p>
                        </div>

                        <?php if (!empty($assignment_files)): ?>
                            <div class="space-y-2 mt-4">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">File đính kèm bài tập:</p>
                                <?php foreach ($assignment_files as $file): ?>
                                    <a href="../../../<?= htmlspecialchars($file['path']) ?>" 
                                       target="_blank"
                                       class="flex items-center p-3 text-sm text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition border border-indigo-100">
                                        <i class="fas fa-paperclip mr-2"></i>
                                        <span class="truncate flex-1"><?= htmlspecialchars($file['original']) ?></span>
                                        <i class="fas fa-external-link-alt text-xs ml-2 opacity-50"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>