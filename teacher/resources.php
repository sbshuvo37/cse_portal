<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('teacher');

$teacherModel  = new Teacher();
$resourceModel = new ResourceModel();
$notifModel    = new NotificationModel();
$studentModel  = new Student();
$error         = '';

$teacher = $teacherModel->findByUserId(Auth::userId());
$courses = $teacherModel->getAssignedCourses($teacher['teacher_id']);

// Get unique batches for cascading selection
$myBatches = [];
foreach ($courses as $c) {
    $myBatches[$c['batch_id']] = $c['batch_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $courseId = intval($_POST['course_id'] ?? 0);
        $batchId  = intval($_POST['batch_id'] ?? 0);
        $title    = trim($_POST['title']       ?? '');
        $desc     = trim($_POST['description'] ?? '');

        $isAssigned = false;
        foreach ($courses as $c) {
            if ($c['course_id'] == $courseId && $c['batch_id'] == $batchId) {
                $isAssigned = true;
                break;
            }
        }

        if (!$isAssigned) {
            $error = 'Invalid course or batch assignment.';
        } elseif (empty($title) || empty($_FILES['resource_file']['name'])) {
            $error = 'Title and file are required.';
        } else {
            $uploader = new FileUpload('resources');
            $res = $uploader->upload($_FILES['resource_file']);
            if ($res['success']) {
                $resourceModel->create($courseId, $teacher['teacher_id'], $title, $desc, $res['path']);
                $students = $studentModel->byBatch($batchId);
                $notifModel->createBulk(array_column($students,'user_id'), 'New Resource Added', $title, 'resource');
                Helper::setFlash('success', 'Resource uploaded successfully.');
            } else {
                $error = $res['message'];
            }
        }
        if (!$error) Helper::redirect('teacher/resources.php');
    }

    if ($action === 'delete') {
        $rid = intval($_POST['resource_id'] ?? 0);
        if ($resourceModel->isOwner($rid, $teacher['teacher_id'])) {
            $resource = $resourceModel->findById($rid);
            $resourceModel->delete($rid);
            if ($resource) { (new FileUpload())->delete($resource['file_path']); }
            Helper::setFlash('success', 'Resource deleted.');
        }
        Helper::redirect('teacher/resources.php');
    }
}

$myResources = $resourceModel->byTeacher($teacher['teacher_id']);
$showModal   = (isset($_GET['action']) && $_GET['action']==='add');

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
    <div><h2>📁 Resource Library</h2><div class="page-subtitle">Upload and manage course materials</div></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Upload Resource</button>
</div>

<?php if (empty($myResources)): ?>
    <div class="empty-state"><div class="empty-icon">📁</div><p>No resources shared yet.</p></div>
<?php else: ?>
<div style="display:flex; flex-direction:column; gap:12px;">
<?php foreach ($myResources as $r): ?>
<div class="resource-card">
    <div class="resource-icon"><?= Helper::fileIcon($r['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700; color:var(--primary-dark);"><?= htmlspecialchars($r['title']) ?></div>
        <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($r['course_code'].' — '.$r['course_title']) ?> · <?= Helper::timeAgo($r['created_at']) ?></div>
    </div>
    <div style="display:flex; gap:6px;">
        <a href="<?= UPLOAD_URL . htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
        <form method="POST" style="margin:0;" onsubmit="return confirmAction('Delete this resource?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="resource_id" value="<?= $r['resource_id'] ?>">
            <button class="btn btn-sm btn-danger">🗑 Delete</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<!-- Upload Resource Modal (Cascading) -->
<div class="modal-overlay" id="addModal">
<div class="modal-box">
    <div class="modal-header"><span class="modal-title">+ Upload Course Resource</span><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        
        <!-- STEP 1: Select Batch -->
        <div class="form-group">
            <label class="form-label">1. Target Batch *</label>
            <select id="batchSelect" name="batch_id" class="form-control" required onchange="filterCoursesForResource()">
                <option value="">— Select Batch —</option>
                <?php foreach ($myBatches as $bid => $bname): ?>
                <option value="<?= $bid ?>"><?= htmlspecialchars($bname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- STEP 2: Select Course (Filtered by Batch via JS) -->
        <div class="form-group">
            <label class="form-label">2. Course *</label>
            <select name="course_id" id="courseSelect" class="form-control" required>
                <option value="">Select Batch First</option>
            </select>
        </div>

        <div class="form-group"><label class="form-label">3. Resource Title *</label><input type="text" name="title" class="form-control" required placeholder="e.g. Lecture 1 Introduction"></div>
        <div class="form-group"><label class="form-label">4. Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label class="form-label">5. File *</label><input type="file" name="resource_file" class="form-control" required></div>
        
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
    </div>
</div>
</div>

<script>
const assignedCourses = <?= json_encode(array_map(fn($c) => ['course_id'=>$c['course_id'],'batch_id'=>$c['batch_id'],'label'=>$c['course_code'].' — '.$c['course_title']], $courses)) ?>;

function filterCoursesForResource() {
    const batchId = document.getElementById('batchSelect').value;
    const courseSelect = document.getElementById('courseSelect');
    courseSelect.innerHTML = '';
    
    if (!batchId) {
        courseSelect.innerHTML = '<option value="">Select Batch First</option>';
        return;
    }
    
    const matches = assignedCourses.filter(c => String(c.batch_id) === String(batchId));
    courseSelect.innerHTML = '<option value="">— Select Course —</option>' +
        matches.map(c => `<option value="${c.course_id}">${c.label}</option>`).join('');
}
</script>