<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel = new Student();
$routineModel = new RoutineModel();
$error        = '';
$uid          = Auth::userId();

$student = $studentModel->findByUserId($uid);
$batchId = $student['batch_id'];
$isCr    = (bool) $student['is_cr'];

// CR-only: create/edit/delete structured routine entries
if ($isCr && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $date    = trim($_POST['date'] ?? '') ?: null;
        $day     = trim($_POST['day']     ?? '');
        $course  = trim($_POST['course']  ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $start   = trim($_POST['start_time'] ?? '');
        $end     = trim($_POST['end_time']   ?? '');

        if (empty($day) || empty($course) || empty($start) || empty($end)) {
            $error = 'Day, course, start and end time are required.';
        } else {
            $routineModel->create($batchId, $date, $day, $course, $teacher, $start, $end, $uid);
            Helper::setFlash('success', 'Routine entry added — immediately visible to your batch.');
            Helper::redirect('student/routine.php');
        }
    }

    if ($action === 'edit') {
        $id      = intval($_POST['routine_id'] ?? 0);
        $date    = trim($_POST['date'] ?? '') ?: null;
        $day     = trim($_POST['day']     ?? '');
        $course  = trim($_POST['course']  ?? '');
        $teacher = trim($_POST['teacher'] ?? '');
        $start   = trim($_POST['start_time'] ?? '');
        $end     = trim($_POST['end_time']   ?? '');
        $routineModel->update($id, $date, $day, $course, $teacher, $start, $end);
        Helper::setFlash('success', 'Routine entry updated.');
        Helper::redirect('student/routine.php');
    }

    if ($action === 'delete') {
        $id = intval($_POST['routine_id'] ?? 0);
        $routineModel->delete($id);
        Helper::setFlash('success', 'Routine entry removed.');
        Helper::redirect('student/routine.php');
    }
}

$entries = $batchId ? $routineModel->getByBatch($batchId) : [];
$files   = $batchId ? $routineModel->getFiles($batchId) : $routineModel->getFiles();

$days  = ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
$byDay = [];
foreach ($entries as $r) { $byDay[$r['day']][] = $r; }
$today = date('l');

$editData = null;
if ($isCr && isset($_GET['edit'])) {
    $editData = $routineModel->findById(intval($_GET['edit']));
}

$pageTitle   = 'Class Routine';
$currentPage = 'Routine';
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
    <div><h2>🗓️ Class Routine</h2><div class="page-subtitle"><?= htmlspecialchars($student['batch_name'] ?? 'Your batch') ?> weekly schedule</div></div>
    <?php if ($isCr): ?><button class="btn btn-primary" onclick="openModal('addModal')">+ Add Entry (CR)</button><?php endif; ?>
</div>

<?php if ($isCr): ?>
<div class="alert alert-info">👑 As Class Representative, you can create/edit structured routine entries for your batch. Changes are visible immediately — no approval needed.</div>
<?php endif; ?>

<!-- Uploaded routine files -->
<?php if (!empty($files)): ?>
<h3 class="mb-2">📄 Official Routine File</h3>
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
<?php foreach ($files as $f): ?>
<div class="routine-file-card">
    <div class="routine-file-icon"><?= Helper::fileIcon($f['file_path']) ?></div>
    <div style="flex:1"><div style="font-weight:700">Class Routine</div><div style="font-size:0.78rem;color:var(--text-muted)">Uploaded <?= Helper::timeAgo($f['created_at']) ?></div></div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Structured routine -->
<h3 class="mb-2">📋 Weekly Schedule</h3>
<?php if (empty($entries)): ?>
    <div class="empty-state"><div class="empty-icon">🗓️</div><p>No structured routine entries yet.</p></div>
<?php else: foreach ($days as $day): if (!isset($byDay[$day])) continue; ?>
<div class="card mb-2" <?= $day===$today ? 'style="border:2px solid var(--accent)"' : '' ?>>
    <div class="card-header" style="<?= $day===$today ? 'background:var(--accent);color:white' : 'background:var(--primary);color:white' ?>">
        <span class="card-title" style="color:white">📅 <?= $day ?> <?php if($day===$today): ?><span style="font-size:0.7rem;margin-left:8px;background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:10px">Today</span><?php endif; ?></span>
    </div>
    <div class="card-body" style="padding:0">
    <div class="table-wrapper"><table>
        <thead><tr><th>Time</th><th>Course</th><th>Teacher</th><?php if($isCr): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($byDay[$day] as $r): ?>
        <tr>
            <td class="time-cell"><strong><?= Helper::formatTime($r['start_time']) ?></strong> – <?= Helper::formatTime($r['end_time']) ?></td>
            <td><strong><?= htmlspecialchars($r['course']) ?></strong></td>
            <td><?= htmlspecialchars($r['teacher'] ?: '—') ?></td>
            <?php if ($isCr): ?>
            <td>
                <div style="display:flex;gap:6px">
                    <a href="?edit=<?= $r['routine_id'] ?>" class="btn btn-sm btn-accent">✏️</a>
                    <form method="POST" onsubmit="return confirmAction('Remove this entry?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="routine_id" value="<?= $r['routine_id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    </div>
</div>
<?php endforeach; endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<?php if ($isCr): ?>
<div class="modal-overlay <?= $editData?'open':'' ?>" id="addModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title"><?= $editData?'✏️ Edit Entry':'+ Add Routine Entry' ?></span><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData?'edit':'add' ?>">
        <?php if ($editData): ?><input type="hidden" name="routine_id" value="<?= $editData['routine_id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Date (optional)</label><input type="date" name="date" class="form-control" value="<?= htmlspecialchars($editData['date']??'') ?>"></div>
            <div class="form-group">
                <label class="form-label">Day *</label>
                <select name="day" class="form-control" required>
                    <?php foreach ($days as $d): ?><option value="<?= $d ?>" <?= ($editData['day']??'')===$d?'selected':'' ?>><?= $d ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Course *</label><input type="text" name="course" class="form-control" required value="<?= htmlspecialchars($editData['course']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Teacher</label><input type="text" name="teacher" class="form-control" value="<?= htmlspecialchars($editData['teacher']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Start Time *</label><input type="time" name="start_time" class="form-control" required value="<?= htmlspecialchars($editData['start_time']??'08:00') ?>"></div>
            <div class="form-group"><label class="form-label">End Time *</label><input type="time" name="end_time" class="form-control" required value="<?= htmlspecialchars($editData['end_time']??'09:30') ?>"></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><?= $editData?'Update':'Add Entry' ?></button>
        </div>
    </form>
    </div>
</div>
</div>
<?php endif; ?>
