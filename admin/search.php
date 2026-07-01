<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$term = trim($_GET['q'] ?? '');
$results = ['students'=>[], 'teachers'=>[], 'courses'=>[], 'notices'=>[], 'resources'=>[]];

if ($term !== '') {
    $results['students']  = (new Student())->search($term);
    $results['teachers']  = (new Teacher())->search($term);
    $results['resources'] = (new ResourceModel())->search($term);

    $db = Database::getInstance()->getConnection();
    $like = "%$term%";

    $stmt = $db->prepare("SELECT * FROM courses WHERE course_title LIKE ? OR course_code LIKE ?");
    $stmt->execute([$like, $like]);
    $results['courses'] = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT n.*, u.name AS poster FROM notices n JOIN users u ON n.posted_by=u.id WHERE n.title LIKE ? OR n.description LIKE ?");
    $stmt->execute([$like, $like]);
    $results['notices'] = $stmt->fetchAll();
}

$totalResults = array_sum(array_map('count', $results));

$pageTitle   = 'Global Search';
$currentPage = 'Global Search';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>🔍 Search Results</h2><div class="page-subtitle"><?= $term ? 'Results for "'.htmlspecialchars($term).'"' : 'Use the search box in the header above' ?></div></div>
</div>

<?php if ($term === ''): ?>
    <div class="empty-state"><div class="empty-icon">🔍</div><p>Use the search icon in the header to begin.</p></div>
<?php elseif ($totalResults === 0): ?>
    <div class="empty-state"><div class="empty-icon">🤷</div><p>No results found for "<?= htmlspecialchars($term) ?>".</p></div>
<?php else: ?>

<?php if (!empty($results['students'])): ?>
<h3 class="mb-2">🎓 Students (<?= count($results['students']) ?>)</h3>
<div class="card mb-3"><div class="card-body" style="padding:0"><div class="table-wrapper"><table>
<thead><tr><th>Name</th><th>Roll</th><th>Batch</th><th>Status</th></tr></thead><tbody>
<?php foreach ($results['students'] as $s): ?>
<tr><td><?= htmlspecialchars($s['name']) ?></td><td><?= htmlspecialchars($s['roll']) ?></td><td><?= htmlspecialchars($s['batch_name']??'—') ?></td><td><?= $s['status']==='active'?'<span class="badge badge-success">Active</span>':'<span class="badge badge-danger">Inactive</span>' ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if (!empty($results['teachers'])): ?>
<h3 class="mb-2">👨‍🏫 Teachers (<?= count($results['teachers']) ?>)</h3>
<div class="card mb-3"><div class="card-body" style="padding:0"><div class="table-wrapper"><table>
<thead><tr><th>Name</th><th>Designation</th><th>Status</th></tr></thead><tbody>
<?php foreach ($results['teachers'] as $t): ?>
<tr><td><?= htmlspecialchars($t['name']) ?></td><td><?= htmlspecialchars($t['designation']??'—') ?></td><td><?= $t['status']==='active'?'<span class="badge badge-success">Active</span>':'<span class="badge badge-danger">Inactive</span>' ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if (!empty($results['courses'])): ?>
<h3 class="mb-2">📚 Courses (<?= count($results['courses']) ?>)</h3>
<div class="card mb-3"><div class="card-body" style="padding:0"><div class="table-wrapper"><table>
<thead><tr><th>Code</th><th>Title</th><th>Semester</th></tr></thead><tbody>
<?php foreach ($results['courses'] as $c): ?>
<tr><td><span class="badge badge-info"><?= htmlspecialchars($c['course_code']) ?></span></td><td><?= htmlspecialchars($c['course_title']) ?></td><td><?= htmlspecialchars($c['semester']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if (!empty($results['notices'])): ?>
<h3 class="mb-2">📢 Notices (<?= count($results['notices']) ?>)</h3>
<div class="notice-list mb-3">
<?php foreach ($results['notices'] as $n): ?>
<div class="notice-card">
    <div class="notice-card-title"><?= htmlspecialchars($n['title']) ?></div>
    <div class="notice-card-meta">By <?= htmlspecialchars($n['poster']) ?> · <?= Helper::formatDate($n['created_at']) ?></div>
    <div class="notice-card-body"><?= htmlspecialchars(substr($n['description'],0,150)) ?>…</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($results['resources'])): ?>
<h3 class="mb-2">📁 Resources (<?= count($results['resources']) ?>)</h3>
<div style="display:flex;flex-direction:column;gap:10px">
<?php foreach ($results['resources'] as $r): ?>
<div class="resource-card">
    <div class="resource-icon"><?= Helper::fileIcon($r['file_path']) ?></div>
    <div style="flex:1">
        <div style="font-weight:700"><?= htmlspecialchars($r['title']) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['course_code']) ?> — <?= htmlspecialchars($r['course_title']) ?></div>
    </div>
    <a href="<?= UPLOAD_URL . htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
