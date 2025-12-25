<?php
session_start();
require_once '../../../config/connectdb.php';

/* ===== CHECK LOGIN & ROLE ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/DangNhap.php");
    exit();
}

// Giả định role_id của Admin trong DB của bạn là 0
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 0) {
    header("Location: ../../user/home.php");
    exit();
}

/* ===== GET SUBJECT ===== */
$subject_id = intval($_GET['subject_id'] ?? 0);
$subject_name = '';
$subject_code = '';
$message = '';

if ($subject_id <= 0) {
    die("Thiếu subject_id");
}

$sql = "SELECT subject_name, subject_code FROM subjects WHERE subject_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $subject_name = $row['subject_name'];
    $subject_code = $row['subject_code'];
} else {
    die("Môn học không tồn tại");
}
$stmt->close();

/* ===== UPLOAD FUNCTION ===== */
function upload_multiple_files($input_name, $target_dir) {
    $files_data = [];

    // Tự động tạo thư mục nếu chưa có (Giải quyết vấn đề bạn lo lắng)
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (!empty($_FILES[$input_name]['name'][0])) {
        foreach ($_FILES[$input_name]['name'] as $i => $name) {
            if ($_FILES[$input_name]['error'][$i] === UPLOAD_ERR_OK) {

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $new_name = uniqid() . '.' . $ext;
                $target_path = $target_dir . $new_name;

                if (move_uploaded_file($_FILES[$input_name]['tmp_name'][$i], $target_path)) {
                    $files_data[] = [
                        'type'     => 'file',
                        'original' => $name,
                        'file'     => $new_name,
                        'path'     => str_replace('../../../', '', $target_path),
                        'size'     => $_FILES[$input_name]['size'][$i]
                    ];
                }
            }
        }
    }

    return $files_data;
}

/* ===== HANDLE POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lesson_name = trim($_POST['lesson_name'] ?? '');
    $youtube_link = trim($_POST['youtube_link'] ?? '');
    $assignment_desc = trim($_POST['assignment_desc'] ?? '');

    if ($lesson_name === '') {
        $message = "❌ Vui lòng nhập tên bài giảng";
    } else {

        // 1. Upload tài liệu file vật lý
        $lesson_files = upload_multiple_files(
            'lesson_materials',
            '../../../uploads/lessons/'
        );

        // 2. Nếu có link YouTube, thêm vào mảng lesson_files
        if (!empty($youtube_link)) {
            $lesson_files[] = [
                'type' => 'youtube',
                'url'  => $youtube_link
            ];
        }

        // 3. Insert bài giảng (Cột lesson_files là JSON)
        $sql = "INSERT INTO lessons (subject_id, lesson_name, lesson_files) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $lesson_files_json = json_encode($lesson_files, JSON_UNESCAPED_UNICODE);
        $stmt->bind_param("iss", $subject_id, $lesson_name, $lesson_files_json);

        if ($stmt->execute()) {
            $lesson_id = $stmt->insert_id;

            // 4. Xử lý bài tập (Assignment) nếu có
            // Lưu ý: Chỉ tạo assignment nếu có mô tả hoặc có file
            if (!empty($assignment_desc) || !empty($_FILES['assignment_files']['name'][0])) {
                
                $assignment_files = upload_multiple_files(
                    'assignment_files',
                    '../../../uploads/assignments/'
                );

                $sql2 = "INSERT INTO assignments (lesson_id, title, description, attachment) VALUES (?, ?, ?, ?)";
                $stmt2 = $conn->prepare($sql2);

                $title = "Bài tập: " . $lesson_name;
                $attach_json = json_encode($assignment_files, JSON_UNESCAPED_UNICODE);

                $stmt2->bind_param("isss", $lesson_id, $title, $assignment_desc, $attach_json);
                $stmt2->execute();
                $stmt2->close();
            }

            // Thành công -> Chuyển hướng
            header("Location: subject.php?subject_id=" . $subject_id);
            exit();

        } else {
            $message = "❌ Lỗi SQL: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bài giảng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen p-4 md:p-10">

<div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8 border-b pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <?= htmlspecialchars($subject_code) ?> – <?= htmlspecialchars($subject_name) ?>
            </h1>
            <p class="text-indigo-600 font-medium">Tạo bài giảng mới</p>
        </div>

        <a href="subject.php?subject_id=<?= $subject_id ?>"
           class="flex items-center text-gray-500 hover:text-indigo-600 transition font-medium">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Quay lại
        </a>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" enctype="multipart/form-data" class="space-y-8">

        <!-- Section 1: Thông tin bài giảng -->
        <div class="bg-gray-50 p-6 rounded-lg space-y-4">
            <h2 class="text-lg font-bold text-gray-700 flex items-center">
                <span class="bg-indigo-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-2 text-xs">1</span>
                Nội dung bài giảng
            </h2>
            
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Tên bài giảng <span class="text-red-500">*</span></label>
                <input name="lesson_name" required placeholder="Ví dụ: Chương 1: Tổng quan về PHP"
                       class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Link video YouTube</label>
                <input type="url" name="youtube_link"
                       placeholder="https://www.youtube.com/watch?v=..."
                       class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-red-500 outline-none bg-white">
                <p class="text-xs text-gray-500 mt-1 italic">Hệ thống sẽ lưu link này cùng với tài liệu bài học.</p>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Tài liệu bài giảng (File)</label>
                <input type="file" name="lesson_materials[]" multiple
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>
        </div>

        <!-- Section 2: Bài tập đi kèm -->
        <div class="bg-gray-50 p-6 rounded-lg space-y-4">
            <h2 class="text-lg font-bold text-gray-700 flex items-center">
                <span class="bg-green-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-2 text-xs">2</span>
                Bài tập đi kèm (Tùy chọn)
            </h2>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Mô tả bài tập</label>
                <textarea name="assignment_desc" rows="3" placeholder="Yêu cầu sinh viên cần làm gì..."
                          class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none"></textarea>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">File đề bài tập</label>
                <input type="file" name="assignment_files[]" multiple
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center gap-4 pt-4 border-t">
            <button type="submit"
                    class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 shadow-md transition duration-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Lưu bài giảng
            </button>

            <a href="subject.php?subject_id=<?= $subject_id ?>"
               class="px-8 py-3 rounded-lg border border-gray-300 text-gray-600 font-bold hover:bg-gray-100 transition duration-200">
                Hủy bỏ
            </a>
        </div>

    </form>
</div>

</body>
</html>