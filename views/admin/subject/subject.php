<?php
// 1. Kết nối Cơ sở Dữ liệu
require_once '../../../config/connectdb.php';

$lesson_id = $_GET['lesson_id'] ?? null; // Lấy ID bài giảng từ URL

$lesson_name = 'Đang tải...';
$subject_name = 'N/A';
$subject_id = null;
$assignment = null;
$submissions = [];
$current_submission = null;

// 2. Lấy thông tin Bài giảng, Môn học và Bài tập
if ($lesson_id) {
    $lesson_id = intval($lesson_id);

    // 2.1. Lấy thông tin Bài giảng và Môn học
    $sql_lesson_info = "SELECT 
        l.name AS lesson_name, 
        l.subject_id,
        l.assignment_files,
        s.name AS subject_name 
        FROM lessons l 
        JOIN subjects s ON l.subject_id = s.subject_id 
        WHERE l.lesson_id = ?";
        
    if ($stmt_info = $conn->prepare($sql_lesson_info)) {
        $stmt_info->bind_param("i", $lesson_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        if ($row = $result_info->fetch_assoc()) {
            $lesson_name = $row['lesson_name'];
            $subject_name = $row['subject_name'];
            $subject_id = $row['subject_id'];
            
            // Lấy thông tin bài tập (assignment) từ assignment_files
            $assignment_files = json_decode($row['assignment_files'], true);
            // Giả định chỉ có một bài tập cho mỗi bài giảng, lấy file đầu tiên
            if (!empty($assignment_files)) {
                $assignment = $assignment_files[0]; 
            }
        }
        $stmt_info->close();
    }
    
    // 2.2. Lấy danh sách Bài nộp (submissions)
    // Giả định bảng submissions chứa user_id, lesson_id, grade, feedback, submission_files (JSON)
    // Cần join với bảng users để lấy tên sinh viên
    $sql_submissions = "SELECT 
        sub.submission_id, 
        sub.grade, 
        sub.submission_files, 
        sub.submitted_at, 
        u.name AS student_name,
        u.user_id 
        FROM submissions sub
        JOIN users u ON sub.user_id = u.user_id
        WHERE sub.lesson_id = ?
        ORDER BY sub.submitted_at DESC";

    if ($stmt_subs = $conn->prepare($sql_submissions)) {
        $stmt_subs->bind_param("i", $lesson_id);
        $stmt_subs->execute();
        $result_subs = $stmt_subs->get_result();
        $submissions = $result_subs->fetch_all(MYSQLI_ASSOC);
        $stmt_subs->close();
    }

    // 2.3. Lấy chi tiết bài nộp đầu tiên để hiển thị mặc định
    if (!empty($submissions)) {
        // Lấy bài nộp mới nhất hoặc bài nộp đầu tiên trong danh sách
        $current_submission = $submissions[0]; 
    }
}

// 3. Helper function để format ngày giờ
function format_timestamp($timestamp) {
    return date('H:i, d/m/Y', strtotime($timestamp));
}

// 4. Helper function để trích xuất file nộp và nội dung text
function get_submission_content($submission_json) {
    $content = [
        'files' => [],
        'text_content' => null
    ];
    $files = json_decode($submission_json, true);

    if (!empty($files)) {
        foreach ($files as $file) {
            // Giả định file có cấu trúc {name, path, type, content (text)}
            if (isset($file['content']) && strpos($file['type'], 'text/') !== false) {
                // Đây là nội dung text được nộp trực tiếp
                $content['text_content'] = $file['content'];
            } else {
                // Đây là file đính kèm
                $content['files'][] = $file;
            }
        }
    }
    return $content;
}

$conn->close();

$lesson_list_url = $subject_id ? "../lesson/lesson.php?subject_id={$subject_id}" : "../home/home.php";
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Xem Bài nộp & Chấm điểm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f4f6f9;
      }
      .sticky-top-8 {
          top: 2rem; /* Tương đương top-8 trong Tailwind */
      }
    </style>
  </head>
  <body class="min-h-screen flex flex-col">
    <!-- Header / Navigation -->
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-6 py-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Quản lí Môn học</h1>
        <p class="text-sm text-gray-500 mt-1">
          Giao diện Chấm điểm & Xem Bài nộp
        </p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8 flex-grow">
      <!-- Nút Trở về Danh sách Bài giảng -->
      <a
        href="<?= $lesson_list_url ?>"
        class="text-blue-600 hover:text-blue-800 mb-6 flex items-center space-x-1"
      >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Danh sách Bài giảng (<?= htmlspecialchars($subject_name) ?>)</span>
      </a>

      <div class="grid lg:grid-cols-4 gap-8">
        <!-- Cột 1: Thông tin Bài tập -->
        <div
          class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg h-fit sticky sticky-top-8"
        >
          <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
            Bài tập của Bài giảng
          </h3>

          <div class="space-y-3">
            <p class="font-semibold text-sm text-gray-700">
              Môn: <?= htmlspecialchars($subject_name) ?>
            </p>
            
            <?php if ($assignment): ?>
                <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm font-medium text-blue-800">
                        Bài tập: <?= htmlspecialchars($lesson_name) ?>
                    </p>
                    <span class="text-xs text-gray-500">
                        Loại: <?= htmlspecialchars($assignment['type'] ?? 'File') ?>
                    </span>
                    <div class="flex space-x-2 mt-2">
                        <!-- Nút Sửa Bài tập (Giả định) -->
                        <a href="lesson_edit.php?lesson_id=<?= $lesson_id ?>" class="text-yellow-600 hover:underline text-xs">
                            Sửa Bài tập
                        </a>
                        <!-- Nút Xóa (Trong thực tế cần xác nhận) -->
                        <button class="text-red-600 hover:underline text-xs">
                            Xóa Bài tập
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-red-500 italic">Không tìm thấy bài tập nào cho bài giảng này.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cột 2 & 3: Danh sách Bài nộp -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
          <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
            Danh sách Học viên đã nộp
          </h3>
          <div class="mb-4">
            <p class="text-sm font-semibold">
              Bài giảng:
              <span class="text-blue-600">
                <?= htmlspecialchars($lesson_name) ?>
              </span>
            </p>
          </div>

          <div class="space-y-3 max-h-[70vh] overflow-y-auto">
            <?php if (!empty($submissions)): ?>
                <?php foreach ($submissions as $sub): 
                    $is_graded = !is_null($sub['grade']);
                    $bg_color = $is_graded ? 'bg-green-50' : 'bg-yellow-50';
                    $border_color = $is_graded ? 'border-green-300' : 'border-yellow-300';
                    $grade_display = $is_graded ? '<span class="text-2xl font-bold text-green-700">'. htmlspecialchars($sub['grade']) .'</span>' : '';
                    $status_label = $is_graded ? 'Đã chấm' : 'Chưa chấm';
                    $status_bg = $is_graded ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800';
                ?>
                <!-- Bài nộp Động -->
                <div
                  class="submission-item p-4 <?= $bg_color ?> rounded-lg border <?= $border_color ?> flex justify-between items-center hover:shadow-md cursor-pointer transition duration-150"
                  data-submission-id="<?= $sub['submission_id'] ?>"
                  onclick="loadSubmissionDetails(<?= htmlspecialchars(json_encode($sub)) ?>)"
                >
                  <div>
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($sub['student_name']) ?></p>
                    <span class="text-xs text-gray-500">
                      Nộp lúc: <?= format_timestamp($sub['submitted_at']) ?>
                    </span>
                  </div>
                  <div class="text-right flex items-center space-x-2">
                    <?= $grade_display ?>
                    <span
                      class="inline-block px-3 py-1 text-xs font-semibold rounded-full <?= $status_bg ?>"
                    >
                      <?= $status_label ?>
                    </span>
                  </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10 text-gray-500 border border-dashed rounded-lg bg-gray-50">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 text-gray-400"></i>
                    <p>Chưa có học viên nào nộp bài cho bài giảng này.</p>
                </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cột 4: Chấm điểm & Ghi chú -->
        <div
          class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg h-fit sticky sticky-top-8"
        >
            <h3 class="text-xl font-bold text-blue-600 mb-4 border-b pb-2">
                Chấm Điểm & Phản hồi
            </h3>

            <!-- Khu vực thông báo/loading -->
            <div id="loading-indicator" class="hidden text-center text-blue-500 py-4">
                <i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto"></i>
                <p class="text-sm mt-2">Đang tải bài nộp...</p>
            </div>
            
            <?php if ($current_submission): ?>
                <form id="grading-form" onsubmit="saveGrade(event)">
                    <input type="hidden" id="submission-id" name="submission_id" value="<?= $current_submission['submission_id'] ?>">
                    
                    <p class="text-sm font-medium mb-4">
                        Sinh viên đang chọn:
                        <span id="student-name-display" class="font-bold text-gray-800">
                            <?= htmlspecialchars($current_submission['student_name']) ?>
                        </span>
                    </p>

                    <!-- File nộp -->
                    <div id="submission-content" class="mb-4 p-3 bg-gray-100 rounded-lg">
                        <?php 
                        $content = get_submission_content($current_submission['submission_files']);
                        ?>
                        <p class="text-sm font-semibold mb-2">Tài liệu đã nộp:</p>
                        
                        <?php if (!empty($content['files'])): ?>
                            <?php foreach ($content['files'] as $file): ?>
                                <a
                                href="<?= htmlspecialchars($file['path'] ?? '#') ?>"
                                target="_blank"
                                class="text-indigo-500 hover:underline flex items-center space-x-1 text-sm mt-1"
                                title="Tải xuống file"
                                >
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Tải xuống: <?= htmlspecialchars($file['name'] ?? 'File đính kèm') ?> (<?= htmlspecialchars(strtoupper($file['type'])) ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Không có file đính kèm.</p>
                        <?php endif; ?>

                        <!-- Phần hiển thị Text (nếu có) -->
                        <div id="text-content-display" class="mt-2 text-xs italic text-gray-600 border-t pt-2 max-h-40 overflow-y-auto whitespace-pre-wrap">
                            <?php if ($content['text_content']): ?>
                                <?= nl2br(htmlspecialchars($content['text_content'])) ?>
                            <?php else: ?>
                                Không có nội dung text đính kèm.
                            <?php endif; ?>
                        </div>
                    </div>

                    <label for="grade" class="block text-sm font-medium text-gray-700"
                        >Điểm số (Max: 10)</label
                    >
                    <input
                        type="number"
                        id="grade"
                        name="grade"
                        step="0.5"
                        min="0"
                        max="10"
                        value="<?= htmlspecialchars($current_submission['grade'] ?? '') ?>"
                        class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0.0 - 10.0"
                    />

                    <label
                        for="feedback"
                        class="block text-sm font-medium text-gray-700 mt-4"
                        >Phản hồi / Ghi chú</label
                    >
                    <textarea
                        id="feedback"
                        name="feedback"
                        rows="4"
                        class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Viết nhận xét chi tiết..."
                    ><?= htmlspecialchars($current_submission['feedback'] ?? '') ?></textarea>

                    <button
                        type="submit"
                        class="w-full mt-6 bg-blue-600 text-white py-2 rounded-lg shadow-md hover:bg-blue-700 transition font-semibold"
                    >
                        Lưu Điểm & Phản hồi
                    </button>
                    <p id="save-status" class="text-center mt-2 text-sm hidden"></p>
                </form>
            <?php else: ?>
                <div class="text-center py-10 text-gray-500">
                    <i data-lucide="mouse-pointer-2" class="w-8 h-8 mx-auto mb-3 text-gray-400"></i>
                    <p>Chọn một bài nộp từ danh sách để xem và chấm điểm.</p>
                </div>
            <?php endif; ?>
        </div>
      </div>
    </main>

    <script>
      lucide.createIcons();
      
      // Khởi tạo submission data từ PHP
      let allSubmissions = <?= json_encode($submissions) ?>;

      // Hàm để cập nhật chi tiết bài nộp được chọn
      function loadSubmissionDetails(submission) {
          const form = document.getElementById('grading-form');
          const nameDisplay = document.getElementById('student-name-display');
          const submissionIdInput = document.getElementById('submission-id');
          const gradeInput = document.getElementById('grade');
          const feedbackInput = document.getElementById('feedback');
          const contentDisplay = document.getElementById('submission-content');
          
          if (!form) return; // Nếu chưa có bài nộp nào thì form không tồn tại

          // Cập nhật DOM
          nameDisplay.textContent = submission.student_name;
          submissionIdInput.value = submission.submission_id;
          gradeInput.value = submission.grade || '';
          feedbackInput.value = submission.feedback || '';

          // Xử lý nội dung nộp
          const submissionFiles = JSON.parse(submission.submission_files || '[]');
          
          let fileHtml = '<p class="text-sm font-semibold mb-2">Tài liệu đã nộp:</p>';
          let textContent = 'Không có nội dung text đính kèm.';
          let filesFound = false;

          submissionFiles.forEach(file => {
              // Giả định file có cấu trúc {name, path, type, content (text)}
              if (file.type && file.type.startsWith('text/')) {
                  // Nội dung text trực tiếp
                  textContent = file.content ? file.content.replace(/\n/g, '<br>') : 'Nội dung text rỗng.';
              } else {
                  // File đính kèm
                  filesFound = true;
                  const filePath = file.path || '#';
                  const fileName = file.name || 'File đính kèm';
                  const fileType = file.type ? file.type.toUpperCase() : 'UNKNOWN';
                  fileHtml += `
                      <a
                        href="${filePath}"
                        target="_blank"
                        class="text-indigo-500 hover:underline flex items-center space-x-1 text-sm mt-1"
                        title="Tải xuống file"
                      >
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Tải xuống: ${fileName} (${fileType})</span>
                      </a>
                  `;
              }
          });

          if (!filesFound) {
             fileHtml += '<p class="text-sm text-gray-500">Không có file đính kèm.</p>';
          }

          fileHtml += `<div id="text-content-display" class="mt-2 text-xs italic text-gray-600 border-t pt-2 max-h-40 overflow-y-auto whitespace-pre-wrap">${textContent}</div>`;
          contentDisplay.innerHTML = fileHtml;
          lucide.createIcons(); // Tạo lại icon nếu cần

          // Highlight bài nộp được chọn
          document.querySelectorAll('.submission-item').forEach(item => {
              item.classList.remove('ring-2', 'ring-blue-500', 'shadow-lg');
          });
          document.querySelector(`[data-submission-id="${submission.submission_id}"]`).classList.add('ring-2', 'ring-blue-500', 'shadow-lg');
      }

      // Tự động tải chi tiết bài nộp đầu tiên khi trang tải xong
      document.addEventListener('DOMContentLoaded', () => {
          if (allSubmissions.length > 0) {
              loadSubmissionDetails(allSubmissions[0]);
          }
      });
      
      // Hàm lưu điểm qua AJAX
      async function saveGrade(event) {
          event.preventDefault();
          
          const submissionId = document.getElementById('submission-id').value;
          const grade = document.getElementById('grade').value;
          const feedback = document.getElementById('feedback').value;
          const saveStatus = document.getElementById('save-status');
          const submitButton = event.target.querySelector('button[type="submit"]');

          saveStatus.textContent = 'Đang lưu...';
          saveStatus.classList.remove('hidden', 'text-green-600', 'text-red-600');
          saveStatus.classList.add('text-blue-500');
          submitButton.disabled = true;

          // Dữ liệu gửi đi
          const formData = new URLSearchParams();
          formData.append('submission_id', submissionId);
          formData.append('grade', grade);
          formData.append('feedback', feedback);

          try {
              // Giả định có một file xử lý AJAX (e.g., ../api/grade_submission.php)
              const response = await fetch('../api/grade_submission.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: formData.toString()
              });

              const result = await response.json();

              if (result.success) {
                  saveStatus.textContent = 'Lưu điểm thành công!';
                  saveStatus.classList.remove('text-blue-500', 'text-red-600');
                  saveStatus.classList.add('text-green-600');
                  
                  // Cập nhật lại danh sách bài nộp trên giao diện mà không cần tải lại trang
                  // Tìm và cập nhật bài nộp trong mảng JS
                  const updatedSubmission = allSubmissions.find(sub => sub.submission_id == submissionId);
                  if (updatedSubmission) {
                      updatedSubmission.grade = grade;
                      updatedSubmission.feedback = feedback;
                      // Tối ưu: Cập nhật trực tiếp thẻ HTML của bài nộp
                      const itemDiv = document.querySelector(`[data-submission-id="${submissionId}"]`);
                      if (itemDiv) {
                          const gradeDisplay = itemDiv.querySelector('.text-2xl');
                          const statusSpan = itemDiv.querySelector('.rounded-full');
                          const contentDiv = itemDiv.querySelector('div:first-child + div'); // Lấy div chứa điểm và status
                          
                          // Cập nhật màu sắc
                          itemDiv.classList.remove('bg-yellow-50', 'border-yellow-300', 'bg-green-50', 'border-green-300');
                          itemDiv.classList.add('bg-green-50', 'border-green-300');

                          // Cập nhật điểm và trạng thái
                          if (!gradeDisplay) {
                              contentDiv.innerHTML = `<span class="text-2xl font-bold text-green-700">${grade}</span> <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-200 text-green-800">Đã chấm</span>`;
                          } else {
                              gradeDisplay.textContent = grade;
                              statusSpan.textContent = 'Đã chấm';
                              statusSpan.classList.remove('bg-yellow-200', 'text-yellow-800');
                              statusSpan.classList.add('bg-green-200', 'text-green-800');
                          }
                      }
                  }

              } else {
                  saveStatus.textContent = 'Lỗi khi lưu điểm: ' + (result.message || 'Không rõ lỗi');
                  saveStatus.classList.remove('text-blue-500', 'text-green-600');
                  saveStatus.classList.add('text-red-600');
              }
          } catch (error) {
              console.error('Error saving grade:', error);
              saveStatus.textContent = 'Lỗi kết nối hoặc xử lý: Vui lòng thử lại.';
              saveStatus.classList.remove('text-blue-500', 'text-green-600');
              saveStatus.classList.add('text-red-600');
          } finally {
              submitButton.disabled = false;
              setTimeout(() => saveStatus.classList.add('hidden'), 5000); // Ẩn sau 5 giây
          }
      }
    </script>
  </body>
</html>