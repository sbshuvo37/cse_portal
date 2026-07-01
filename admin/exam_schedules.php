<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$examModel   = new ExamModel();
$batchModel  = new BatchModel();
$courseModel = new CourseModel();
$studentModel= new Student();
$notifModel  = new NotificationModel();
$error       = '';
$editData    = null;

$db = Database::getInstance()->getConnection();

// Self-healing database: Ensure 'end_time' column exists [Point 3]
try {
    $db->query("SELECT end_time FROM exam_schedules LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE exam_schedules ADD COLUMN end_time TIME NULL;");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $batchId   = intval($_POST['batch_id']   ?? 0);
        $courseId  = intval($_POST['course_id']  ?? 0);
        $date      = trim($_POST['exam_date']    ?? '');
        $time      = trim($_POST['exam_time']    ?? '');
        $endTime   = trim($_POST['end_time']     ?? '');
        $eid       = intval($_POST['exam_id']    ?? 0);

        if (!$batchId || !$courseId || !$date || !$time) {
            $error = 'Batch, Course, Date, and Time are required.';
        } else {
            if ($action === 'add') {
                // Modified query to include file_path as null (to keep with standard schema)
                $stmt = $db->prepare("INSERT INTO exam_schedules (batch_id, course_id, exam_date, exam_time, end_time) VALUES (?,?,?,?,?)");
                $stmt->execute([$batchId, $courseId, $date, $time, $endTime ?: null]);
                
                $students = $studentModel->byBatch($batchId);
                $course = $courseModel->findById($courseId);
                $notifModel->createBulk(array_column($students,'user_id'), 'Exam Schedule Updated', $course['course_title'].' exam on '.$date, 'exam');
                Helper::setFlash('success', 'Exam schedule added.');
            } else {
                $stmt = $db->prepare("UPDATE exam_schedules SET batch_id=?, course_id=?, exam_date=?, exam_time=?, end_time=? WHERE exam_id=?");
                $stmt->execute([$batchId, $courseId, $date, $time, $endTime ?: null, $eid]);
                Helper::setFlash('success', 'Exam schedule updated.');
            }
            Helper::redirect('admin/exam_schedules.php');
        }
    }

    if ($action === 'delete') {
        $eid = intval($_POST['exam_id'] ?? 0);
        $examModel->delete($eid);
        Helper::setFlash('success', 'Exam schedule deleted.');
        Helper::redirect('admin/exam_schedules.php');
    }

    if ($action === 'upload_file') {
        $batchId = intval($_POST['batch_id'] ?? 0);
        $title   = trim($_POST['title'] ?? 'Final Exam Schedule');

        if (!$batchId) {
            $error = 'Please select a batch.';
        } elseif (empty($_FILES['exam_file']['name'])) {
            $error = 'Please choose a PDF or Image file.';
        } else {
            $uploader = new FileUpload('exams', ['pdf','jpg','jpeg','png']);
            $res = $uploader->upload($_FILES['exam_file']);
            if ($res['success']) {
                $stmt = $db->prepare("INSERT INTO exam_schedule_files (batch_id, file_path, title, uploaded_by) VALUES (?,?,?,?)");
                $stmt->execute([$batchId, $res['path'], $title, Auth::userId()]);
                
                $students = $studentModel->byBatch($batchId);
                $notifModel->createBulk(array_column($students,'user_id'), 'Official Exam Schedule Uploaded', $title, 'exam');
                
                Helper::setFlash('success', 'Exam schedule file uploaded successfully.');
                Helper::redirect('admin/exam_schedules.php');
            } else {
                $error = $res['message'];
            }
        }
    }

    if ($action === 'delete_file') {
        $id = intval($_POST['file_id'] ?? 0);
        $stmt = $db->prepare("SELECT file_path FROM exam_schedule_files WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            (new FileUpload())->delete($row['file_path']);
            $del = $db->prepare("DELETE FROM exam_schedule_files WHERE id = ?");
            $del->execute([$id]);
            Helper::setFlash('success', 'File schedule removed.');
        }
        Helper::redirect('admin/exam_schedules.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $examModel->findById(intval($_GET['edit']));
}

$exams     = $examModel->all();
$batches   = $batchModel->all();

// Query actual database assignments for cascading course select [Point 3]
$assignStmt = $db->query("SELECT ca.batch_id, ca.course_id, c.course_code, c.course_title 
                         FROM course_assignments ca 
                         JOIN courses c ON ca.course_id = c.course_id 
                         WHERE c.status='active'");
$assignedCoursesData = $assignStmt->fetchAll();

$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$files_stmt = $db->query("SELECT f.*, b.batch_name FROM exam_schedule_files f JOIN batches b ON f.batch_id=b.batch_id ORDER BY f.created_at DESC");
$uploadedFiles = $files_stmt->fetchAll();

$pageTitle   = 'Exam Schedules';
$currentPage = 'Exam Schedules';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<?php $flash = Helper::getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="page-header">
    <div><h2>📝 Exam Schedules</h2><div class="page-subtitle">Manage exam schedules and files</div></div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-outline" onclick="openModal('uploadFileModal')">📤 Upload Exam File</button>
        <a href="?action=add" class="btn btn-primary">+ Add Single Exam</a>
    </div>
</div>

<!-- SECTION 1: MASTER EXAM SCHEDULE FILES -->
<div class="section-wrapper-primary">
    <div class="section-header-primary">
        <span class="card-title" style="color: var(--primary-dark);">📄 Uploaded Exam Schedule Files</span>
    </div>
    <div class="card-body">
        <?php if (empty($uploadedFiles)): ?>
            <div class="empty-state"><div class="empty-icon">📁</div><p>No master files uploaded yet.</p></div>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($uploadedFiles as $f): ?>
        <div style="border: 1px solid var(--border); padding: 16px; border-radius: var(--radius); display:flex; align-items:center; justify-content:space-between; background: #fbfcfd;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="font-size:1.8rem;"><?= Helper::fileIcon($f['file_path']) ?></div>
                <div>
                    <div style="font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($f['title']) ?></div>
                    <div style="font-size:0.78rem; color:var(--text-muted);">Batch: <?= htmlspecialchars($f['batch_name']) ?> · Uploaded <?= Helper::timeAgo($f['created_at']) ?></div>
                </div>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View File</a>
                <form method="POST" style="margin:0;" onsubmit="return confirmAction('Delete this file schedule?')">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑 Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 2: SINGLE EXAMS SCHEDULE LIST -->
<div class="section-wrapper-accent">
    <div class="section-header-accent">
        <span class="card-title" style="color: var(--accent-dark);">📋 Single Exam Entries</span>
    </div>
    <div class="card-body">
        <div style="display:flex; flex-direction:column; gap:14px;">
        <?php if (empty($exams)): ?>
            <div class="empty-state"><div class="empty-icon">📅</div><p>No exams entries added yet.</p></div>
        <?php else: foreach ($exams as $e): ?>
        <div class="exam-card" style="border: 1px solid var(--border); padding: 16px; border-radius: var(--radius); display:flex; align-items:center; justify-content:space-between; background: #fbfcfd;">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="exam-date-box" style="width: 56px; height: 60px; background: var(--primary); border-radius: var(--radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                    <div class="exam-date-day" style="font-size: 1.4rem; font-weight: 800; color: white; line-height: 1;"><?= date('d', strtotime($e['exam_date'])) ?></div>
                    <div class="exam-date-month" style="font-size: 0.65rem; color: rgba(255,255,255,0.7); font-weight: 700; text-transform: uppercase;"><?= date('M', strtotime($e['exam_date'])) ?></div>
                </div>
                <div>
                    <div class="exam-info-title" style="font-size:0.95rem; font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($e['course_title']) ?></div>
                    <div class="exam-info-meta" style="font-size:0.78rem; color:var(--text-muted); margin-top:3px;">
                        <span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span>
                        🎓 <?= htmlspecialchars($e['batch_name']) ?> &nbsp;|&nbsp; 
                        🕐 <?= Helper::formatTime($e['exam_time']) ?> - <?= !empty($e['end_time']) ? Helper::formatTime($e['end_time']) : 'End' ?>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:6px;">
                <a href="?edit=<?= $e['exam_id'] ?>" class="btn btn-sm btn-accent">✏️ Edit</a>
                <form method="POST" style="margin:0;" onsubmit="return confirmAction('Delete entry?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exam_id" value="<?= $e['exam_id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑 Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Upload File Schedule -->
<div class="modal-overlay" id="uploadFileModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title">+ Upload Exam File</span>
        <button class="modal-close" onclick="closeModal('uploadFileModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_file">
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. 3rd Semester Exam Routine">
        </div>
        <div class="form-group">
            <label class="form-label">Exam File (PDF/Image) *</label>
            <input type="file" name="exam_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('uploadFileModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>

<!-- Modal: Single Entry with Cascading assigned courses & Start/End time range [Point 3] -->
<div class="modal-overlay <?= $showModal?'open':'' ?>" id="examModal">
<div class="modal-box">
    <div class="modal-header">
        <span class="modal-title"><?= $editData?'✏️ Edit Exam Schedule':'+ Add Exam Schedule' ?></span>
        <button class="modal-close" onclick="closeModal('examModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="exam_id" value="<?= $editData['exam_id'] ?>"><?php endif; ?>
        
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" id="examBatchSelect" class="form-control" required onchange="filterExamCoursesCascading()">
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>" <?= ($editData['batch_id']??0)==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Course *</label>
            <!-- Only assigned courses are loaded dynamically in JS -->
            <select name="course_id" id="examCourseSelect" class="form-control" required>
                <option value="">Select Batch First</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Exam Date *</label>
            <input type="date" name="exam_date" class="form-control" required value="<?= htmlspecialchars($editData['exam_date']??'') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Start Time *</label>
                <input type="time" name="exam_time" class="form-control" required value="<?= htmlspecialchars($editData['exam_time']??'09:00') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($editData['end_time']??'') ?>">
            </div>
        </div>
        
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('examModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update':'Add Schedule' ?></button>
        </div>
    </form>
    </div>
</div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<script>
// JSON representation of courses assigned to each batch [Point 3]
const assignedCoursesDataset = <?= json_encode($assignedCoursesData) ?>;
const initialSelectedCourse = <?= json_encode($editData['course_id'] ?? 0) ?>;

function filterExamCoursesCascading() {
    const batchId = document.getElementById('examBatchSelect').value;
    const courseSelect = document.getElementById('examCourseSelect');
    courseSelect.innerHTML = '';

    if (!batchId) {
        courseSelect.innerHTML = '<option value="">Select Batch First</option>';
        return;
    }

    const matches = assignedCoursesDataset.filter(c => String(c.batch_id) === String(batchId));
    
    if (matches.length === 0) {
        courseSelect.innerHTML = '<option value="">No courses assigned to this batch</option>';
        return;
    }

    let options = '<option value="">Select Course</option>';
    matches.forEach(c => {
        const selected = String(c.course_id) === String(initialSelectedCourse) ? 'selected' : '';
        options += `<option value="${c.course_id}" ${selected}>${c.course_code} — ${c.course_title}</option>`;
    });
    courseSelect.innerHTML = options;
}

// Automatically load on page load if editing
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('examBatchSelect').value) {
        filterExamCoursesCascading();
    }
});
</script>