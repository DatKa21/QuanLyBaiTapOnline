<?php
// Bắt đầu session và kiểm tra đăng nhập
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/DangNhap.php");
    exit();
}

require_once '../../../config/connectdb.php'; 

$user_id = $_SESSION['user_id'];
$lesson_id = $_GET['lesson_id'] ?? null;

$lesson_data = null;
$assignment = null;
$submission = null;
$submission_history = [];
$error_message = '';
$upload_success = '';

// --- Logic Xử lý Nộp bài ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $file_path = '';
    
    // 1. Kiểm tra file upload
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid('submission_') . '-' . basename($_FILES['assignment_file']['name']);
        $file_path_full = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $file_path_full)) {
            $file_path = 'uploads/submissions/' . $file_name; // Đường dẫn tương đối để lưu vào CSDL
        } else {
            $error_message = "Có lỗi khi di chuyển file đã upload.";
        }
    } else {
         $error_message = "Vui lòng chọn một file để nộp.";
    }

    // 2. Lưu vào CSDL
    if (empty($error_message) && !empty($file_path) && $lesson_id && $assignment) {
        // Giả sử $assignment['assignment_id'] đã được lấy từ trước
        $assignment_id = $assignment['assignment_id'];
        $submission_time = date('Y-m-d H:i:s');
        $status = 'pending'; // Trạng thái ban đầu

        // SQL để chèn bài nộp mới
        $sql_insert = "INSERT INTO submissions (assignment_id, user_id, submission_time, file_path, status) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql_insert)) {
            $stmt->bind_param("iiss", $assignment_id, $user_id, $submission_time, $file_path, $status);
            if ($stmt->execute()) {
                $upload_success = "Bài làm đã được nộp thành công!";
            } else {
                $error_message = "Lỗi CSDL khi lưu bài nộp: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Lỗi chuẩn bị truy vấn nộp bài: " . $conn->error;
        }
    }
}


// --- Logic Lấy dữ liệu ---

if ($lesson_id) {
    // 1. Lấy thông tin bài giảng
    $sql_lesson = "SELECT lesson_id, subject_id, lesson_name, content_url, file_path 
                   FROM lessons 
                   WHERE lesson_id = ?";
    if ($stmt = $conn->prepare($sql_lesson)) {
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $lesson_data = $result->fetch_assoc();
        $stmt->close();
    }

    if ($lesson_data) {
        // 2. Lấy thông tin bài tập (assignment) liên quan đến buổi học
        $sql_assignment = "SELECT assignment_id, file_path, due_date, description 
                           FROM assignments 
                           WHERE lesson_id = ?";
        if ($stmt = $conn->prepare($sql_assignment)) {
            $stmt->bind_param("i", $lesson_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $assignment = $result->fetch_assoc();
            $stmt->close();
        }

        if ($assignment) {
            // 3. Lấy bài nộp gần nhất của người dùng
            $sql_submission = "SELECT submission_id, submission_time, file_path, grade, instructor_feedback, status
                               FROM submissions 
                               WHERE assignment_id = ? AND user_id = ?
                               ORDER BY submission_time DESC LIMIT 1";
            
            if ($stmt = $conn->prepare($sql_submission)) {
                $stmt->bind_param("ii", $assignment['assignment_id'], $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $submission = $result->fetch_assoc();
                $stmt->close();
            }

            // 4. Lịch sử nộp bài
            $sql_history = "SELECT submission_time, file_path, grade, status
                            FROM submissions
                            WHERE assignment_id = ? AND user_id = ?
                            ORDER BY submission_time DESC";
            if ($stmt = $conn->prepare($sql_history)) {
                $stmt->bind_param("ii", $assignment['assignment_id'], $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $submission_history[] = $row;
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết: <?= htmlspecialchars($lesson_data['lesson_name'] ?? 'Bài giảng') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
        body { font-family: "Inter", sans-serif; background-color: #f1f5f9; }
        /* Tùy chỉnh để nhúng file PDF hoặc video dễ dàng */
        .content-embed { 
            width: 100%; 
            min-height: 600px; 
            border-radius: 0.75rem; 
            overflow: hidden; 
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="javascript:history.back()" class="text-indigo-600 hover:text-indigo-800 flex items-center space-x-1 mb-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="text-sm">Quay lại danh sách Buổi học</span>
            </a>
            <h1 class="text-3xl font-extrabold text-gray-900"><?= htmlspecialchars($lesson_data['lesson_name'] ?? 'Bài giảng chi tiết') ?></h1>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Cột Nội dung Bài giảng (Col-span 2) -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-indigo-600 mb-4 flex items-center space-x-2">
                    <i data-lucide="play-circle" class="w-6 h-6"></i>
                    <span>Nội dung Buổi học</span>
                </h2>
                
                <?php if ($lesson_data && !empty($lesson_data['content_url'])): ?>
                    <!-- Giả định content_url là link video (YouTube/Vimeo) hoặc file PDF -->
                    <iframe 
                        src="<?= htmlspecialchars($lesson_data['content_url']) ?>" 
                        frameborder="0" 
                        allowfullscreen 
                        class="content-embed aspect-video"
                    ></iframe>
                <?php elseif ($lesson_data && !empty($lesson_data['file_path'])): ?>
                    <p class="text-gray-600 mb-3">File Bài giảng:</p>
                    <a href="../../<?= htmlspecialchars($lesson_data['file_path']) ?>" target="_blank" class="text-teal-600 hover:text-teal-800 font-medium flex items-center space-x-1">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Tải về tài liệu Buổi học</span>
                    </a>
                <?php else: ?>
                    <p class="text-gray-500">Nội dung bài giảng đang được cập nhật.</p>
                <?php endif; ?>
            </div>

            <!-- Khu vực Nộp bài và Kết quả -->
            <div class="bg-white p-6 rounded-xl shadow-lg space-y-6">
                <h2 class="text-xl font-bold text-red-600 mb-4 flex items-center space-x-2">
                    <i data-lucide="send" class="w-6 h-6"></i>
                    <span>Bài tập và Nộp bài</span>
                </h2>

                <?php if ($assignment): ?>
                    
                    <!-- Thông tin Bài tập -->
                    <div class="border-b pb-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">1. Yêu cầu Bài tập</h3>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($assignment['description']) ?></p>
                        <p class="text-sm text-red-500 mt-2 flex items-center space-x-1">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>Hạn chót: <?= date('d/m/Y H:i', strtotime($assignment['due_date'])) ?></span>
                        </p>
                        <a href="../../<?= htmlspecialchars($assignment['file_path']) ?>" download class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center space-x-1 mt-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Tải về File Bài tập</span>
                        </a>
                    </div>
                    
                    <!-- Kết quả Bài nộp Gần nhất -->
                    <div class="border-b pb-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">2. Kết quả Bài nộp Gần nhất</h3>
                        <?php if ($submission): ?>
                            <div class="mt-2 p-4 rounded-lg <?= $submission['status'] === 'graded' ? 'bg-green-50 border border-green-300' : 'bg-yellow-50 border border-yellow-300' ?>">
                                <p class="font-medium flex justify-between">
                                    <span>Trạng thái: </span>
                                    <span class="<?= $submission['status'] === 'graded' ? 'text-green-700' : 'text-yellow-700' ?> font-bold uppercase">
                                        <?= $submission['status'] === 'graded' ? 'ĐÃ CHẤM ĐIỂM' : ($submission['status'] === 'pending' ? 'ĐANG CHỜ' : 'ĐÃ NỘP') ?>
                                    </span>
                                </p>
                                <p class="text-sm mt-1">Thời gian nộp: <?= date('d/m/Y H:i', strtotime($submission['submission_time'])) ?></p>
                                
                                <?php if ($submission['status'] === 'graded'): ?>
                                    <p class="text-2xl font-extrabold text-green-700 mt-2">Điểm: <?= htmlspecialchars($submission['grade']) ?? 'N/A' ?></p>
                                    <div class="mt-3 p-3 bg-white rounded-md shadow-inner">
                                        <p class="text-sm font-semibold text-gray-700">Phê bình/Nhận xét của GV:</p>
                                        <p class="text-sm italic text-gray-600"><?= htmlspecialchars($submission['instructor_feedback']) ?? 'Không có nhận xét.' ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 mt-2">Bạn chưa nộp bài tập này lần nào.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Lịch sử Nộp bài (Accordion) -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">3. Lịch sử Nộp bài (<?= count($submission_history) ?> lần)</h3>
                        <div id="history-accordion" class="space-y-2">
                            <?php if (empty($submission_history)): ?>
                                <p class="text-sm text-gray-500">Chưa có lịch sử nộp bài.</p>
                            <?php else: ?>
                                <?php foreach ($submission_history as $index => $history): ?>
                                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <button type="button" class="history-toggle w-full flex justify-between items-center text-left font-medium text-gray-700">
                                            <span>Lần nộp thứ <?= count($submission_history) - $index ?> - Ngày: <?= date('d/m/Y H:i', strtotime($history['submission_time'])) ?></span>
                                            <i data-lucide="chevron-down" class="w-5 h-5 transition-transform duration-200"></i>
                                        </button>
                                        <div class="history-content mt-2 hidden text-sm space-y-1 pl-4 border-l-2 border-indigo-300 pt-2">
                                            <p><strong>Trạng thái:</strong> <span class="uppercase text-xs font-bold px-2 py-0.5 rounded <?= $history['status'] === 'graded' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' ?>"><?= htmlspecialchars($history['status']) ?></span></p>
                                            <?php if ($history['status'] === 'graded'): ?>
                                                <p><strong>Điểm:</strong> <span class="text-lg font-bold text-green-600"><?= htmlspecialchars($history['grade']) ?></span></p>
                                            <?php endif; ?>
                                            <p><strong>File đã nộp:</strong> <a href="../../<?= htmlspecialchars($history['file_path']) ?>" target="_blank" class="text-indigo-600 hover:underline">Xem file</a></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>


                <?php else: ?>
                    <p class="text-gray-500">Buổi học này không có bài tập được giao.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cột Nộp bài (Col-span 1) -->
        <div class="lg:col-span-1">
            <div class="bg-indigo-50 p-6 rounded-xl shadow-xl sticky top-8">
                <h3 class="text-xl font-bold text-indigo-700 mb-4">Nộp Bài làm</h3>
                
                <!-- Hiển thị thông báo thành công hoặc lỗi -->
                <?php if (!empty($error_message)): ?>
                    <div class="p-3 mb-4 text-sm text-red-800 rounded-lg bg-red-200"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if (!empty($upload_success)): ?>
                    <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-200"><?= htmlspecialchars($upload_success) ?></div>
                <?php endif; ?>

                <?php if ($assignment): ?>
                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="submit_assignment" value="1">
                        
                        <div>
                            <label for="assignment_file" class="block text-sm font-medium text-gray-700 mb-2">Chọn File Bài làm</label>
                            <input 
                                type="file" 
                                name="assignment_file" 
                                id="assignment_file" 
                                required 
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200"
                            />
                            <p class="mt-1 text-xs text-gray-500">Chỉ chấp nhận file PDF, DOCX, ZIP (Tối đa 5MB).</p>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold shadow-md hover:bg-indigo-700 transition duration-150 flex items-center justify-center space-x-2"
                        >
                            <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                            <span>Nộp bài</span>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-gray-700">Không có bài tập để nộp cho buổi học này.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // Logic cho Accordion Lịch sử Nộp bài
        document.querySelectorAll('.history-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const icon = button.querySelector('i');
                
                // Đóng tất cả các content khác (optional: chỉ mở 1 cái 1 lúc)
                document.querySelectorAll('.history-content').forEach(c => {
                    if (c !== content) c.classList.add('hidden');
                });
                document.querySelectorAll('.history-toggle i').forEach(i => {
                    if (i !== icon) i.classList.remove('rotate-180');
                });

                // Mở/đóng content hiện tại
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
        });
    </script>
</body>
</html>