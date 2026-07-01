<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('student');

$studentModel  = new Student();
$courseModel   = new CourseModel();
$resourceModel = new ResourceModel();

$student = $studentModel->findByUserId(Auth::userId());
$batchId = $student['batch_id'];
$myCourses = $batchId ? $courseModel->getCoursesForBatch($batchId) : [];

$term = trim($_GET['q'] ?? '');
$results = ['courses'=>[], 'notices'=>[], 'resources'=>[]];

if ($term !== '') {
    foreach ($myCourses as $c) {
        if (stripos($c['course_title'], $term) !== false || stripos($c['course_code'], $term) !== false) {
            $results['courses'][] = $c;
        }
    }

    $db = Database::getInstance()->getConnection();
    $like = "%$term%";
    $stmt = $db->prepare("SELECT n.*, u.name AS poster FROM notices n JOIN users u ON n.posted_by=u.id WHERE (n.title LIKE ? OR n.description LIKE ?) AND (n.target='all_students' OR n.batch_id=?)");
    $stmt->execute([$like, $like, $batchId]);
    $results['notices'] = $stmt->fetchAll();

    if ($batchId) {
        foreach ($resourceModel->forBatch($batchId) as $r) {
            if (stripos($r['title'], $term) !== false) $results['resources'][] = $r;
        }
    }
}

$totalResults = array_sum(array_map('count', $results));

$pageTitle   = 'Search';
$currentPage = '';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div><h2>🔍 Search Results</h2><div class="page-subtitle"><?= $term ? 'Results for "'.htmlspecialchars($term).'"' : 'Use the search box above' ?></div></div>
</div>

<?php if ($term === ''): ?>
    <div class="empty-state"><div class="empty-icon">🔍</div><p>Type something in the search bar above to begin.</p></div>
<?php elseif ($totalResults === 0): ?>
    <div class="empty-state"><div class="empty-icon">🤷</div><p>No results found for "<?= htmlspecialchars($term) ?>".</p></div>
<?php else: ?>

<?php if (!empty($results['courses'])): ?>
<h3 class="mb-2">📚 My Courses (<?= count($results['courses']) ?>)</h3>
<div class="card mb-3"><div class="card-body" style="padding:0"><div class="table-wrapper"><table>
<thead><tr><th>Code</th><th>Title</th></tr></thead><tbody>
<?php foreach ($results['courses'] as $c): ?>
<tr><td><span class="badge badge-info"><?= htmlspecialchars($c['course_code']) ?></span></td><td><?= htmlspecialchars($c['course_title']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>

<?php if (!empty($results['notices'])): ?>
<h3 class="mb-2">📢 Notices (<?= count($results['notices']) ?>)</h3>
<div class="notice-list mb-3">
<?php foreach ($results['notices'] as $n): ?>
<div class="notice-card"><div class="notice-card-title"><?= htmlspecialchars($n['title']) ?></div><div class="notice-card-meta">By <?= htmlspecialchars($n['poster']) ?> · <?= Helper::formatDate($n['created_at']) ?></div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($results['resources'])): ?>
<h3 class="mb-2">📁 Resources (<?= count($results['resources']) ?>)</h3>
<div style="display:flex;flex-direction:column;gap:10px">
<?php foreach ($results['resources'] as $r): ?>
<div class="resource-card"><div class="resource-icon"><?= Helper::fileIcon($r['file_path']) ?></div><div style="flex:1"><?= htmlspecialchars($r['title']) ?></div><a href="<?= UPLOAD_URL . htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>
