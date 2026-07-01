<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel  = new Teacher();
$resourceModel = new ResourceModel();
$notifModel    = new NotificationModel();
$studentModel  = new Student();
$error         = '';
$editData      = null;

$teacher = $teacherModel->findByUserId(Auth::userId());
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $courseId = intval($_POST['course_id'] ?? 0);
        $title    = trim($_POST['title']       ?? '');
        $desc     = trim($_POST['description'] ?? '');

        $isAssigned = false;
        $assignedBatchId = null;
        foreach ($courses as $c) { if ($c['course_id']==$courseId) { $isAssigned = true; $assignedBatchId = $c['batch_id']; break; } }

        if (!$isAssigned) {
            $error = 'You can only upload resources for your assigned courses.';
        } elseif (empty($title) || empty($_FILES['resource_file']['name'])) {
            $error = 'Title and file are required.';
        } else {
            $uploader = new FileUpload('resources');
            $res = $uploader->upload($_FILES['resource_file']);
            if ($res['success']) {
                $resourceModel->create($courseId, $teacher['teacher_id'], $title, $desc, $res['path']);
                if ($assignedBatchId) {
                    $students = $studentModel->byBatch($assignedBatchId);
                    $notifModel->createBulk(array_column($students,'user_id'), 'New Resource Added', $title, 'resource');
                }
                Helper::setFlash('success', 'Resource uploaded successfully.');
            } else {
                $error = $res['message'];
            }
        }
        if (!$error) Helper::redirect('teacher/resources.php');
    }

    if ($action === 'edit') {
        $rid   = intval($_POST['resource_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if ($resourceModel->isOwner($rid, $teacher['teacher_id'])) {
            $resourceModel->update($rid, $title, $desc);
            Helper::setFlash('success', 'Resource updated.');
        } else {
            Helper::setFlash('danger', 'You can only edit your own resources.');
        }
        Helper::redirect('teacher/resources.php');
    }

    if ($action === 'delete') {
        $rid = intval($_POST['resource_id'] ?? 0);
        if ($resourceModel->isOwner($rid, $teacher['teacher_id'])) {
            $resource = $resourceModel->findById($rid);
            $resourceModel->delete($rid);
            if ($resource) { (new FileUpload())->delete($resource['file_path']); }
            Helper::setFlash('success', 'Resource deleted.');
        } else {
            Helper::setFlash('danger', 'You can only delete your own resources.');
        }
        Helper::redirect('teacher/resources.php');
    }
}

if (isset($_GET['edit'])) {
    $editData = $resourceModel->findById(intval($_GET['edit']));
}

$myResources = $resourceModel->byTeacher($teacher['teacher_id']);
$showModal   = (isset($_GET['action']) && $_GET['action']==='add') || $editData;

$pageTitle   = 'Resource Library';
$currentPage = 'Resources';
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
    <div><h2>📁 Resource Library</h2><div class="page-subtitle">Upload course materials for your students</div></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Upload Resource</button>
</div>

<?php if (empty($myResources)): ?>
    <div class="empty-state"><div class="empty-icon">📁</div><p>No resources uploaded yet.</p></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($myResources as $r): ?>
<div class="resource-card">
    <div class="resource-icon"><?= Helper::fileIcon($r['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700;color:var(--primary-dark)"><?= htmlspecialchars($r['title']) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['course_code'].' — '.$r['course_title']) ?> · <?= Helper::timeAgo($r['created_at']) ?></div>
        <?php if ($r['description']): ?><div style="font-size:0.82rem;margin-top:4px"><?= htmlspecialchars($r['description']) ?></div><?php endif; ?>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
    <button class="btn btn-sm btn-accent" onclick="openEditModal(<?= $r['resource_id'] ?>, '<?= htmlspecialchars(addslashes($r['title'])) ?>', '<?= htmlspecialchars(addslashes($r['description']??'')) ?>')">✏️</button>
    <form method="POST" onsubmit="return confirmAction('Delete this resource?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="resource_id" value="<?= $r['resource_id'] ?>">
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

<!-- Add resource modal -->
<div class="modal-overlay" id="addModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ Upload Resource</span><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" required>
                <option value="">Select your assigned course</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'].' — '.$c['course_title'].' ('.$c['batch_name'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        <div class="form-group"><label class="form-label">File *</label><input type="file" name="resource_file" class="form-control" required></div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>

<!-- Edit resource modal -->
<div class="modal-overlay" id="editModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">✏️ Edit Resource</span><button class="modal-close" onclick="closeModal('editModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="resource_id" id="er_id">
        <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" id="er_title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="er_desc" class="form-control" rows="3"></textarea></div>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
    </div>
</div>
</div>

<script>
function openEditModal(id, title, desc) {
    document.getElementById('er_id').value = id;
    document.getElementById('er_title').value = title;
    document.getElementById('er_desc').value = desc;
    openModal('editModal');
}
<?php if ($showModal): ?>document.addEventListener('DOMContentLoaded', () => openModal('addModal'));<?php endif; ?>
</script>
