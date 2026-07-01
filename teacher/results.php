<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel = new Teacher();
$studentModel = new Student();
$resultModel  = new ResultModel();
$notifModel   = new NotificationModel();
$error        = '';

$teacher  = $teacherModel->findByUserId(Auth::userId());
$courses  = $teacherModel->getAssignedCourses($teacher['teacher_id']);

// Unique batches this teacher teaches (for the batch-first selector)
$myBatches = [];
foreach ($courses as $c) { $myBatches[$c['batch_id']] = $c['batch_name']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_marks') {
        $studentId  = intval($_POST['student_id']  ?? 0);
        $courseId   = intval($_POST['course_id']   ?? 0);
        $attendance = floatval($_POST['attendance'] ?? 0);
        $mid1       = floatval($_POST['mid1']       ?? 0);
        $mid2       = floatval($_POST['mid2']       ?? 0);
        $mid3       = floatval($_POST['mid3']       ?? 0);

        $isAssigned = false;
        foreach ($courses as $c) { if ($c['course_id'] == $courseId) { $isAssigned = true; break; } }

        if (!$isAssigned) {
            $error = 'You are not assigned to this course.';
        } elseif (!$studentId) {
            $error = 'Please select a student.';
        } else {
            $result = $resultModel->upsert($studentId, $courseId, $attendance, $mid1, $mid2, $mid3, Auth::userId());
            $student = $studentModel->findById($studentId);
            if ($student) {
                $notifModel->create((int)$student['user_id'], 'Result Updated', 'Your marks for a course have been updated.', 'result');
            }
            Helper::setFlash('success', $result['message']);
            Helper::redirect('teacher/results.php?course_id=' . $courseId . '&batch_id=' . intval($_POST['batch_id'] ?? 0));
        }
    }

    if ($action === 'delete_marks') {
        $resultId = intval($_POST['result_id'] ?? 0);
        if ($resultModel->isOwner($resultId, Auth::userId())) {
            $resultModel->delete($resultId);
            Helper::setFlash('success', 'Marks deleted.');
        } else {
            Helper::setFlash('danger', 'You can only delete marks you entered.');
        }
        Helper::redirect('teacher/results.php?course_id=' . intval($_POST['course_id'] ?? 0) . '&batch_id=' . intval($_POST['batch_id'] ?? 0));
    }

    // Teacher result file upload — now correctly stores batch_id so students can see it
    if ($action === 'upload_file') {
        $courseId = intval($_POST['course_id_file'] ?? 0);
        $batchId  = intval($_POST['batch_id_file']  ?? 0);

        $isAssigned = false;
        foreach ($courses as $c) { if ($c['course_id'] == $courseId && $c['batch_id'] == $batchId) { $isAssigned = true; break; } }

        if (!$isAssigned) {
            Helper::setFlash('danger', 'You are not assigned to this course + batch combination.');
        } elseif (empty($_FILES['result_file']['name'])) {
            Helper::setFlash('danger', 'Please choose a file.');
        } else {
            $uploader = new FileUpload('results', ['pdf','jpg','jpeg','png']);
            $res = $uploader->upload($_FILES['result_file']);
            if ($res['success']) {
                $resultModel->addFile($batchId, $courseId, Auth::userId(), $res['path'], trim($_POST['file_title'] ?? 'Result File'));
                $students = $studentModel->byBatch($batchId);
                $notifModel->createBulk(array_column($students, 'user_id'), 'Result File Uploaded', trim($_POST['file_title'] ?? 'A new result file has been uploaded.'), 'result');
                Helper::setFlash('success', 'Result file uploaded — visible to students in this batch.');
            } else {
                Helper::setFlash('danger', $res['message']);
            }
        }
        Helper::redirect('teacher/results.php');
    }

    if ($action === 'edit_file') {
        $fileId = intval($_POST['file_id'] ?? 0);
        $title  = trim($_POST['file_title'] ?? '');
        if ($resultModel->isFileOwner($fileId, Auth::userId())) {
            $resultModel->updateFileTitle($fileId, $title ?: 'Result File');
            Helper::setFlash('success', 'File title updated.');
        } else {
            Helper::setFlash('danger', 'You can only edit your own uploaded files.');
        }
        Helper::redirect('teacher/results.php');
    }

    if ($action === 'delete_file') {
        $fileId = intval($_POST['file_id'] ?? 0);
        if ($resultModel->isFileOwner($fileId, Auth::userId())) {
            $file = $resultModel->deleteFile($fileId);
            if ($file) { (new FileUpload())->delete($file['file_path']); }
            Helper::setFlash('success', 'Result file deleted.');
        } else {
            Helper::setFlash('danger', 'You can only delete your own uploaded files.');
        }
        Helper::redirect('teacher/results.php');
    }
}

// Batch-first then course selection for mark entry
$selectedBatchId  = intval($_GET['batch_id']  ?? 0);
$selectedCourseId = intval($_GET['course_id'] ?? 0);

// Courses available for the selected batch (only this teacher's own assignments)
$coursesForBatch = [];
if ($selectedBatchId) {
    foreach ($courses as $c) { if ($c['batch_id'] == $selectedBatchId) $coursesForBatch[] = $c; }
}

$students = [];
$existingResults = [];
if ($selectedCourseId && $selectedBatchId) {
    $students = $studentModel->byBatch($selectedBatchId);
    $resultsForCourse = $resultModel->byCourse($selectedCourseId);
    foreach ($resultsForCourse as $r) { $existingResults[$r['student_id']] = $r; }
}

$myResultFiles = $resultModel->getFilesByTeacher(Auth::userId());

$pageTitle   = 'Result Entry';
$currentPage = 'Result Entry';
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
    <div><h2>📈 Result Entry</h2><div class="page-subtitle">Select a batch, then a course, to enter or edit student marks</div></div>
    <button class="btn btn-outline" onclick="openModal('fileUploadModal')">📤 Upload Result File</button>
</div>

<!-- Batch → Course selector -->
<form method="GET" class="search-bar" id="filterForm">
    <select name="batch_id" id="batchSelect" class="form-control" style="max-width:260px" onchange="document.getElementById('courseSelect').value=''; document.getElementById('filterForm').submit();">
        <option value="">— Select Batch —</option>
        <?php foreach ($myBatches as $bid => $bname): ?>
        <option value="<?= $bid ?>" <?= $selectedBatchId==$bid?'selected':'' ?>><?= htmlspecialchars($bname) ?></option>
        <?php endforeach; ?>
    </select>

    <?php if ($selectedBatchId): ?>
    <select name="course_id" id="courseSelect" class="form-control" style="max-width:320px" onchange="document.getElementById('filterForm').submit();">
        <option value="">— Select Course —</option>
        <?php foreach ($coursesForBatch as $c): ?>
        <option value="<?= $c['course_id'] ?>" <?= $selectedCourseId==$c['course_id']?'selected':'' ?>><?= htmlspecialchars($c['course_code'].' — '.$c['course_title']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
</form>

<?php if ($selectedCourseId && $selectedBatchId): ?>
<div class="card">
<div class="card-body" style="padding:0">
<div class="table-wrapper">
<table id="dataTable">
    <thead><tr><th>Roll</th><th>Name</th><th>Attendance</th><th>Mid-1</th><th>Mid-2</th><th>Mid-3</th><th>Total</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (empty($students)): ?>
        <tr><td colspan="8" class="text-center" style="padding:30px;color:var(--text-muted)">No students in this batch.</td></tr>
    <?php else: foreach ($students as $s): ?>
    <?php $existing = $existingResults[$s['student_id']] ?? null; $total = $existing ? $existing['total'] : 0; ?>
    <tr>
        <td><?= htmlspecialchars($s['roll']) ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= $existing ? htmlspecialchars($existing['attendance']) : '—' ?></td>
        <td><?= $existing ? htmlspecialchars($existing['mid1']) : '—' ?></td>
        <td><?= $existing ? htmlspecialchars($existing['mid2']) : '—' ?></td>
        <td><?= $existing ? htmlspecialchars($existing['mid3']) : '—' ?></td>
        <td><span class="total-badge <?= $total>=60?'total-good':($total>=40?'total-ok':'total-low') ?>"><?= $existing ? number_format($total,1) : '—' ?></span></td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm btn-accent" onclick="openMarkModal(<?= $s['student_id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= htmlspecialchars($s['roll']) ?>', <?= $existing['attendance'] ?? 0 ?>, <?= $existing['mid1'] ?? 0 ?>, <?= $existing['mid2'] ?? 0 ?>, <?= $existing['mid3'] ?? 0 ?>)"><?= $existing ? '✏️ Edit' : '+ Add' ?></button>
                <?php if ($existing): ?>
                <form method="POST" onsubmit="return confirmAction('Delete these marks?')">
                    <input type="hidden" name="action" value="delete_marks">
                    <input type="hidden" name="result_id" value="<?= $existing['result_id'] ?>">
                    <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                    <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>
</div>
<?php elseif ($selectedBatchId): ?>
<div class="empty-state"><div class="empty-icon">📚</div><p>Select a course above to view and enter student marks.</p></div>
<?php else: ?>
<div class="empty-state"><div class="empty-icon">📈</div><p>Select a batch above to begin.</p></div>
<?php endif; ?>

<!-- My uploaded result files -->
<h3 class="mb-2 mt-3">📤 My Uploaded Result Files</h3>
<?php if (empty($myResultFiles)): ?>
    <div class="empty-state"><div class="empty-icon">📄</div><p>No result files uploaded yet.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px">
<?php foreach ($myResultFiles as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700"><?= htmlspecialchars($f['title'] ?: 'Result File') ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)">
            <?= htmlspecialchars(($f['course_code']??'').' '.($f['course_title']??'')) ?>
            <?php if (!empty($f['batch_name'])): ?> · <span class="badge badge-primary"><?= htmlspecialchars($f['batch_name']) ?></span><?php endif; ?>
            · <?= Helper::timeAgo($f['created_at']) ?>
        </div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
    <button class="btn btn-sm btn-accent" onclick="openEditFileModal(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['title']??'')) ?>')">✏️</button>
    <form method="POST" onsubmit="return confirmAction('Delete this result file? Students will no longer be able to see it.')">
        <input type="hidden" name="action" value="delete_file">
        <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
        <button class="btn btn-sm btn-danger">🗑</button>
    </form>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Mark entry modal -->
<div class="modal-overlay" id="markModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title" id="markModalTitle">Enter Marks</span><button class="modal-close" onclick="closeModal('markModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="save_marks">
        <input type="hidden" name="student_id" id="mm_student_id">
        <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
        <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
        <div class="form-group"><label class="form-label">Student</label><input type="text" id="mm_student_display" class="form-control" disabled></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Attendance (0-10)</label><input type="number" name="attendance" id="mm_attendance" class="form-control" step="0.5" min="0" max="10" required></div>
            <div class="form-group"><label class="form-label">Mid-1 (0-20)</label><input type="number" name="mid1" id="mm_mid1" class="form-control" step="0.5" min="0" max="20" required></div>
            <div class="form-group"><label class="form-label">Mid-2 (0-20)</label><input type="number" name="mid2" id="mm_mid2" class="form-control" step="0.5" min="0" max="20" required></div>
            <div class="form-group"><label class="form-label">Mid-3 (0-20)</label><input type="number" name="mid3" id="mm_mid3" class="form-control" step="0.5" min="0" max="20" required></div>
        </div>
        <div class="form-group">
            <label class="form-label">Total (auto-calculated)</label>
            <div id="totalPreview" class="total-badge total-ok" style="font-size:1.2rem;padding:8px 16px;width:fit-content">0.0</div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('markModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Marks</button>
        </div>
    </form>
    </div>
</div>
</div>

<!-- File upload modal -->
<div class="modal-overlay" id="fileUploadModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">📤 Upload Result File</span><button class="modal-close" onclick="closeModal('fileUploadModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_file">
        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id_file" id="ufBatchSelect" class="form-control" required onchange="updateFileUploadCourses()">
                <option value="">Select batch</option>
                <?php foreach ($myBatches as $bid => $bname): ?>
                <option value="<?= $bid ?>"><?= htmlspecialchars($bname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id_file" id="ufCourseSelect" class="form-control" required>
                <option value="">Select batch first</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="file_title" class="form-control" placeholder="e.g. Mid Term Result"></div>
        <div class="form-group"><label class="form-label">File (PDF/Image) *</label><input type="file" name="result_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
        <div class="form-hint mb-2">ℹ️ Students in the selected batch will be able to view this file and will be notified.</div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('fileUploadModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>

<!-- Edit file title modal -->
<div class="modal-overlay" id="editFileModal">
<div class="modal-box" style="max-width:420px">
    <div class="modal-header"><span class="modal-title">✏️ Edit Result File Title</span><button class="modal-close" onclick="closeModal('editFileModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="edit_file">
        <input type="hidden" name="file_id" id="ef_file_id">
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="file_title" id="ef_title" class="form-control" required></div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('editFileModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
    </div>
</div>
</div>

<script>
// All teacher's course assignments, for the file-upload batch→course cascading dropdown
const teacherCourseAssignments = <?= json_encode(array_map(fn($c) => ['course_id'=>$c['course_id'],'batch_id'=>$c['batch_id'],'label'=>$c['course_code'].' — '.$c['course_title']], $courses)) ?>;

function updateFileUploadCourses() {
    const batchId = document.getElementById('ufBatchSelect').value;
    const courseSelect = document.getElementById('ufCourseSelect');
    courseSelect.innerHTML = '';
    if (!batchId) {
        courseSelect.innerHTML = '<option value="">Select batch first</option>';
        return;
    }
    const matches = teacherCourseAssignments.filter(c => String(c.batch_id) === String(batchId));
    courseSelect.innerHTML = '<option value="">Select course</option>' +
        matches.map(c => `<option value="${c.course_id}">${c.label}</option>`).join('');
}

function openMarkModal(id, name, roll, attendance, mid1, mid2, mid3) {
    document.getElementById('mm_student_id').value = id;
    document.getElementById('mm_student_display').value = name + ' (Roll: ' + roll + ')';
    document.getElementById('mm_attendance').value = attendance;
    document.getElementById('mm_mid1').value = mid1;
    document.getElementById('mm_mid2').value = mid2;
    document.getElementById('mm_mid3').value = mid3;
    document.getElementById('markModalTitle').textContent = 'Enter Marks — ' + name;
    updateTotalPreview();
    openModal('markModal');
}
function updateTotalPreview() {
    const a = parseFloat(document.getElementById('mm_attendance').value) || 0;
    const m1 = parseFloat(document.getElementById('mm_mid1').value) || 0;
    const m2 = parseFloat(document.getElementById('mm_mid2').value) || 0;
    const m3 = parseFloat(document.getElementById('mm_mid3').value) || 0;
    const total = a + m1 + m2 + m3;
    const el = document.getElementById('totalPreview');
    el.textContent = total.toFixed(1);
    el.className = 'total-badge ' + (total >= 60 ? 'total-good' : (total >= 40 ? 'total-ok' : 'total-low'));
}
['mm_attendance','mm_mid1','mm_mid2','mm_mid3'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateTotalPreview);
});

function openEditFileModal(id, title) {
    document.getElementById('ef_file_id').value = id;
    document.getElementById('ef_title').value = title;
    openModal('editFileModal');
}
</script>
