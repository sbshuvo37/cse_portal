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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $batchId  = intval($_POST['batch_id']  ?? 0);
        $courseId = intval($_POST['course_id'] ?? 0);
        $date     = trim($_POST['exam_date']   ?? '');
        $time     = trim($_POST['exam_time']   ?? '');
        $eid      = intval($_POST['exam_id']   ?? 0);

        if (!$batchId || !$courseId || !$date || !$time) {
            $error = 'All fields are required.';
        } else {
            if ($action === 'add') {
                $examModel->create($batchId, $courseId, $date, $time);
                $students = $studentModel->byBatch($batchId);
                $course = $courseModel->findById($courseId);
                $notifModel->createBulk(array_column($students,'user_id'), 'Exam Schedule Updated', $course['course_title'].' exam on '.$date, 'exam');
                Helper::setFlash('success', 'Exam schedule added.');
            } else {
                $examModel->update($eid, $batchId, $courseId, $date, $time);
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
}

if (isset($_GET['edit'])) {
    $editData = $examModel->findById(intval($_GET['edit']));
}

$exams     = $examModel->all();
$batches   = $batchModel->all();
$courses   = $courseModel->allActive();
$showModal = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

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
    <div><h2>📝 Exam Schedules</h2><div class="page-subtitle">Manage examination dates and times</div></div>
    <a href="?action=add" class="btn btn-primary">+ Add Schedule</a>
</div>

<div style="display:flex;flex-direction:column;gap:14px">
<?php if (empty($exams)): ?>
    <div class="empty-state"><div class="empty-icon">📅</div><p>No exam schedules added yet.</p></div>
<?php else: foreach ($exams as $e): ?>
<div class="exam-card">
    <div class="exam-date-box">
        <div class="exam-date-day"><?= date('d', strtotime($e['exam_date'])) ?></div>
        <div class="exam-date-month"><?= date('M', strtotime($e['exam_date'])) ?></div>
    </div>
    <div style="flex:1">
        <div class="exam-info-title"><?= htmlspecialchars($e['course_title']) ?></div>
        <div class="exam-info-meta">
            <span class="badge badge-info"><?= htmlspecialchars($e['course_code']) ?></span>
            🎓 <?= htmlspecialchars($e['batch_name']) ?> &nbsp;|&nbsp; 🕐 <?= Helper::formatTime($e['exam_time']) ?> &nbsp;|&nbsp; 📅 <?= Helper::formatDate($e['exam_date'], 'l, F j, Y') ?>
        </div>
    </div>
    <div style="display:flex;gap:6px;flex-shrink:0">
        <a href="?edit=<?= $e['exam_id'] ?>" class="btn btn-sm btn-accent">✏️</a>
        <form method="POST" onsubmit="return confirmAction('Delete this exam schedule?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="exam_id" value="<?= $e['exam_id'] ?>">
            <button class="btn btn-sm btn-danger">🗑</button>
        </form>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

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
            <select name="batch_id" class="form-control" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                <option value="<?= $b['batch_id'] ?>" <?= ($editData['batch_id']??0)==$b['batch_id']?'selected':'' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>" <?= ($editData['course_id']??0)==$c['course_id']?'selected':'' ?>><?= htmlspecialchars($c['course_code'].' — '.$c['course_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Exam Date *</label><input type="date" name="exam_date" class="form-control" required value="<?= htmlspecialchars($editData['exam_date']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Exam Time *</label><input type="time" name="exam_time" class="form-control" required value="<?= htmlspecialchars($editData['exam_time']??'09:00') ?>"></div>
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
<script><?php if ($showModal): ?>document.addEventListener('DOMContentLoaded',()=>openModal('examModal'));<?php endif; ?></script>
