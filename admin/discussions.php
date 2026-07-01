<?php
// PHP Error Reporting enabled for safe debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../includes/auth_check.php';
Auth::requireRole('admin');

$discussionModel = new DiscussionModel();
$batchModel      = new BatchModel();
$studentModel    = new Student();
$teacherModel    = new Teacher();
$uid             = Auth::userId();
$error           = '';

$db = Database::getInstance()->getConnection();

// Self-healing DB: Create discussion_group_members table if not exists [Point 5]
try {
    $db->query("SELECT 1 FROM discussion_group_members LIMIT 1");
} catch (PDOException $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS discussion_group_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        FOREIGN KEY (group_id) REFERENCES discussion_groups(group_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Self-healing DB: Modify table schema to support flexible custom group creation
try {
    $db->exec("ALTER TABLE discussion_groups MODIFY course_id INT NULL;");
} catch (PDOException $e) {
    // Column might already be modified
}
try {
    $db->exec("ALTER TABLE discussion_groups DROP INDEX uniq_group;");
} catch (PDOException $e) {
    // Unique key might already be dropped
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_group_advanced') {
        $title    = trim($_POST['title'] ?? '');
        $selectedStudents = $_POST['student_users'] ?? []; // User IDs of students
        $selectedTeachers = $_POST['teacher_users'] ?? []; // User IDs of teachers

        // Check if total members is less than 2 [Requirement: At least 2 members]
        $totalMembersCount = count($selectedStudents) + count($selectedTeachers);

        if ($totalMembersCount < 2) {
            $error = 'At least 2 members (Students or Teachers) must be selected to create a custom group.';
        } else {
            // If title is empty, generate a generic title
            if (empty($title)) {
                $title = "Discussion Group — " . date('M d, Y');
            }

            // Create discussion group (setting course_id to NULL as requested)
            $result = $discussionModel->createGroup(null, null, $uid, $title);
            if ($result['success']) {
                $groupId = $result['group_id'];
                
                $membersToInsert = [];

                // Collect manual Student & Teacher User IDs
                foreach ($selectedStudents as $mUid) { $membersToInsert[] = (int)$mUid; }
                foreach ($selectedTeachers as $mUid) { $membersToInsert[] = (int)$mUid; }

                // Uniquely insert all members
                $membersToInsert = array_unique($membersToInsert);
                if (!empty($membersToInsert)) {
                    $insStmt = $db->prepare("INSERT INTO discussion_group_members (group_id, user_id) VALUES (?,?)");
                    foreach ($membersToInsert as $mUid) {
                        if ($mUid > 0) {
                            $insStmt->execute([$groupId, $mUid]);
                        }
                    }
                }

                // Add creator too
                $checkStmt = $db->prepare("SELECT id FROM discussion_group_members WHERE group_id=? AND user_id=?");
                $checkStmt->execute([$groupId, $uid]);
                if (!$checkStmt->fetch()) {
                    $db->prepare("INSERT INTO discussion_group_members (group_id, user_id) VALUES (?,?)")->execute([$groupId, $uid]);
                }

                Helper::setFlash('success', 'Custom discussion group created successfully.');
                Helper::redirect('admin/discussions.php?group=' . $groupId);
            } else {
                $error = $result['message'];
            }
        }
    }

    if ($action === 'post_message') {
        $groupId = intval($_POST['group_id'] ?? 0);
        $body    = trim($_POST['body'] ?? '');
        $hasFile = !empty($_FILES['attachment']['name']);

        if (empty($body) && !$hasFile) {
            Helper::setFlash('danger', 'Write something or attach a file.');
        } else {
            $attachment = null;
            if ($hasFile) {
                $uploader = new FileUpload('resources');
                $res = $uploader->upload($_FILES['attachment']);
                if ($res['success']) $attachment = $res['path'];
            }
            $discussionModel->postMessage($groupId, $uid, $body !== '' ? $body : null, $attachment);
        }
        Helper::redirect('admin/discussions.php?group=' . $groupId);
    }

    if ($action === 'delete_message') {
        $messageId = intval($_POST['message_id'] ?? 0);
        $groupId   = intval($_POST['group_id'] ?? 0);
        $discussionModel->deleteMessage($messageId);
        Helper::redirect('admin/discussions.php?group=' . $groupId);
    }

    if ($action === 'delete_group') {
        $groupId = intval($_POST['group_id'] ?? 0);
        $discussionModel->deleteGroup($groupId);
        Helper::setFlash('success', 'Discussion group deleted.');
        Helper::redirect('admin/discussions.php');
    }
}

$groups   = $discussionModel->allGroups();
$batches  = $batchModel->all();
$students = $studentModel->all();
$teachers = $teacherModel->all();

$activeGroupId = intval($_GET['group'] ?? 0);
$activeGroup   = $activeGroupId ? $discussionModel->findGroupById($activeGroupId) : null;
$messages      = $activeGroupId ? $discussionModel->getMessages($activeGroupId) : [];

$pageTitle   = 'Discussion';
$currentPage = 'Discussion';
include '../includes/header.php';
?>
<div class="portal-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
<?php include '../includes/navbar.php'; ?>
<div class="page-content">

<?php $flash = Helper::getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($activeGroup) { ?>
<!-- Group Chat View -->
<div class="page-header">
    <div><h2>💡 <?= htmlspecialchars($activeGroup['title'] ?: 'Custom Admin Group') ?></h2>
    <div class="page-subtitle"><?= htmlspecialchars($activeGroup['batch_name'] ?? 'Custom Group') ?> · Group Discussion</div></div>
    <a href="discussions.php" class="btn btn-outline">← Back to Groups</a>
</div>

<div class="group-chat-panel">
    <div class="group-chat-header">
        <div>
            <div style="font-weight:700;color:var(--primary-dark)">👥 <?= htmlspecialchars($activeGroup['title'] ?: 'Custom Admin Group') ?> Group</div>
            <div style="font-size:0.76rem;color:var(--text-muted)">Created by <?= htmlspecialchars($activeGroup['creator_name']) ?></div>
        </div>
        <form method="POST" onsubmit="return confirmAction('Delete this entire group and all its messages?')">
            <input type="hidden" name="action" value="delete_group">
            <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
            <button class="btn btn-sm btn-danger">🗑 Delete Group</button>
        </form>
    </div>
    <div class="group-chat-feed">
        <?php if (empty($messages)): ?>
            <div class="empty-state"><div class="empty-icon">💬</div><p>No messages yet.</p></div>
        <?php else: foreach ($messages as $m): ?>
        <?php $isMe = (int)$m['user_id'] === $uid; ?>
        <div class="group-msg-row <?= $isMe?'own':'' ?>">
            <div class="group-msg-avatar"><?= Helper::initials($m['author']) ?></div>
            <div class="group-msg-bubble">
                <div class="group-msg-author"><?= htmlspecialchars($m['author']) ?> <span class="badge badge-gray" style="font-size:0.6rem"><?= htmlspecialchars($m['author_role']) ?></span></div>
                <div class="group-msg-content">
                    <?php if (!empty($m['body'])): ?><?= nl2br(htmlspecialchars($m['body'])) ?><?php endif; ?>
                    <?php if ($m['attachment']): ?><a href="<?= UPLOAD_URL . htmlspecialchars($m['attachment']) ?>" target="_blank" class="chat-attachment-link">📎 Download File</a><?php endif; ?>
                </div>
                <div class="group-msg-time"><?= Helper::formatDate($m['created_at'], 'M j, g:i A') ?></div>
            </div>
            <form method="POST" onsubmit="return confirmAction('Delete this message?')">
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
                <button class="group-msg-delete">✕</button>
            </form>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" class="group-chat-input" id="groupChatForm">
        <input type="hidden" name="action" value="post_message">
        <input type="hidden" name="group_id" value="<?= $activeGroup['group_id'] ?>">
        <input type="text" name="body" id="groupMsgInput" class="form-control" placeholder="Message the group...">
        <label class="btn btn-sm btn-outline" style="cursor:pointer">📎<input type="file" name="attachment" id="groupAttachInput" style="display:none"></label>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>

<script>
(() => {
    const fileInput = document.getElementById('groupAttachInput');
    const nameDisplay = document.getElementById('groupAttachName');
    const msgInput = document.getElementById('groupMsgInput');
    const form = document.getElementById('groupChatForm');
    fileInput.addEventListener('change', () => { nameDisplay.textContent = fileInput.files[0] ? '📎 ' + fileInput.files[0].name : ''; });
    const feed = document.querySelector('.group-chat-feed');
    if (feed) feed.scrollTop = feed.scrollHeight;
})();
</script>

<?php } else { ?>
<!-- Group List View -->
<div class="page-header">
    <div><h2>💡 Discussion Groups</h2><div class="page-subtitle">Customizable communications channel</div></div>
    <button class="btn btn-primary" onclick="openModal('newGroupModal')">+ New Custom Group</button>
</div>

<!-- Search/Filter inside Group List -->
<div style="margin-bottom: 18px; max-width: 320px;">
    <input type="text" id="groupSearchInput" class="form-control" placeholder="🔍 Search groups..." oninput="filterGroupsList()">
</div>

<div class="group-list" id="groupsContainer">
<?php foreach ($groups as $g) { ?>
<a href="?group=<?= $g['group_id'] ?>" class="group-card">
    <div class="group-icon">💬</div>
    <div class="group-info">
        <div class="group-name"><?= htmlspecialchars($g['title'] ?: ($g['course_code'] ? $g['course_code'].' — '.$g['course_title'] : 'Custom Admin Group')) ?></div>
        <div class="group-meta">
            <span class="badge badge-primary"><?= htmlspecialchars($g['batch_name'] ?? 'Custom Group') ?></span>
            Created by <?= htmlspecialchars($g['creator_name']) ?> ·
            <?= $g['message_count'] ?> messages
        </div>
    </div>
</a>
<?php } ?>
</div>

<!-- Create Group Modal with Student, Teacher and Group Name only [Point 5, 6] -->
<div class="modal-overlay" id="newGroupModal">
<div class="modal-box modal-wide" style="max-width: 760px;">
    <div class="modal-header">
        <span class="modal-title">+ Create Custom Discussion Group</span>
        <button class="modal-close" onclick="closeModal('newGroupModal')">✕</button>
    </div>
    <div class="modal-body">
    <form method="POST" id="advancedGroupForm" onsubmit="return validateGroupSubmission()">
        <input type="hidden" name="action" value="create_group_advanced">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Column 1: Add Students (with dynamic box & add buttons) -->
            <div>
                <div style="border:1px solid var(--border); padding:16px; border-radius:var(--radius); background:#fafbfc; margin-bottom:15px; height: 100%;">
                    <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--primary-dark);">🎓 1. Add Students (Optional Batch selection)</h4>
                    <div class="form-group" style="margin-bottom:12px;">
                        <select id="groupBatchSelect" name="batch_id" class="form-control" onchange="loadStudentsByBatch()">
                            <option value="">-- Select Target Batch --</option>
                            <?php foreach ($batches as $b) { ?>
                            <option value="<?= $b['batch_id'] ?>"><?= htmlspecialchars($b['batch_name'].' ('.$b['session'].')') ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <!-- BOX FOR STUDENTS LIST [Point 5] -->
                    <div id="studentContainer" style="max-height: 180px; overflow-y: auto; display:none; border:1px solid var(--border); background:white; padding:8px; border-radius:var(--radius-sm);">
                        <!-- Populated dynamically via JS with Add Buttons -->
                    </div>
                </div>
            </div>

            <!-- Column 2: Add Teachers and Group Name -->
            <div>
                <!-- Add Teacher Block -->
                <div style="border:1px solid var(--border); padding:16px; border-radius:var(--radius); background:#fafbfc; margin-bottom:15px;">
                    <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--primary-dark);">👨‍🏫 2. Add Teachers (Optional)</h4>
                    <div style="max-height: 180px; overflow-y: auto; border:1px solid var(--border); background:white; padding:8px; border-radius:var(--radius-sm);">
                        <?php foreach ($teachers as $t) { ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">
                            <span style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($t['name']) ?></span>
                            <button type="button" class="btn btn-sm btn-outline" id="t_btn_<?= $t['user_id'] ?>" onclick="toggleTeacherMember(<?= $t['user_id'] ?>)">+ Add</button>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Group Name Input Block -->
                <div style="border:1px solid var(--border); padding:16px; border-radius:var(--radius); background:#fafbfc;">
                    <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--primary-dark);">✍️ 3. Group Name (Optional)</h4>
                    <input type="text" name="title" class="form-control" placeholder="Enter group name (e.g. Science Lab Group A)...">
                </div>
            </div>
        </div>

        <!-- Hidden input fields populated dynamically by JS -->
        <div id="selectedStudentInputs"></div>
        <div id="selectedTeacherInputs"></div>

        <!-- Selected Pill Lists visual display -->
        <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 15px;">
            <div style="font-size:0.8rem; font-weight:700; color:var(--primary-dark); margin-bottom:5px;">Currently Selected Members Pills:</div>
            <div id="selectionVisualPills" style="display:flex; flex-wrap:wrap; gap:6px;">
                <span class="text-muted" style="font-size:0.78rem;">No custom members selected yet.</span>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px; margin-top:20px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('newGroupModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">🚀 Create Custom Group</button>
        </div>
    </form>
    </div>
</div>
</div>
<?php } ?>

</div>
<?php include '../includes/footer.php'; ?>
</div>
</div>

<script>
// Load Students & Teachers list as static JSON into JS memory [Point 5, 6]
const studentsDatabaseList = <?= json_encode($students) ?>;
const teachersDatabaseList = <?= json_encode($teachers) ?>;

// State management arrays to hold selected user IDs & names
let selectedStudentIds = [];
let selectedStudentNames = [];
let selectedTeacherIds = [];
let selectedTeacherNames = [];

function loadStudentsByBatch() {
    const batchId = document.getElementById('groupBatchSelect').value;
    const container = document.getElementById('studentContainer');
    container.innerHTML = '';

    if (!batchId) {
        container.style.display = 'none';
        return;
    }

    const matches = studentsDatabaseList.filter(s => String(s.batch_id) === String(batchId));

    if (matches.length === 0) {
        container.innerHTML = '<span class="text-muted" style="font-size:0.78rem; padding:5px; display:block;">No students mapped in this batch.</span>';
        container.style.display = 'block';
        return;
    }

    let listHtml = '';
    matches.forEach(s => {
        const isAdded = selectedStudentIds.includes(parseInt(s.user_id));
        const btnText = isAdded ? 'Added' : '+ Add';
        const btnClass = isAdded ? 'btn-success' : 'btn-outline';
        
        listHtml += `
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">
            <span style="font-size:0.8rem; font-weight:600;">${s.name} (Roll: ${s.roll})</span>
            <button type="button" class="btn btn-sm ${btnClass}" id="s_btn_${s.user_id}" onclick="toggleStudentMember(${s.user_id})">${btnText}</button>
        </div>`;
    });

    container.innerHTML = listHtml;
    container.style.display = 'block';
}

function toggleStudentMember(userId) {
    const student = studentsDatabaseList.find(s => String(s.user_id) === String(userId));
    if (!student) return;
    
    const index = selectedStudentIds.indexOf(userId);
    const btn = document.getElementById(`s_btn_${userId}`);

    if (index === -1) {
        selectedStudentIds.push(userId);
        selectedStudentNames.push(student.name);
        if (btn) {
            btn.textContent = 'Added';
            btn.className = 'btn btn-sm btn-success';
        }
    } else {
        selectedStudentIds.splice(index, 1);
        selectedStudentNames.splice(index, 1);
        if (btn) {
            btn.textContent = '+ Add';
            btn.className = 'btn btn-sm btn-outline';
        }
    }
    updateVisualSelectionsAndInputs();
}

function toggleTeacherMember(userId) {
    const teacher = teachersDatabaseList.find(t => String(t.user_id) === String(userId));
    if (!teacher) return;

    const index = selectedTeacherIds.indexOf(userId);
    const btn = document.getElementById(`t_btn_${userId}`);

    if (index === -1) {
        selectedTeacherIds.push(userId);
        selectedTeacherNames.push(teacher.name);
        if (btn) {
            btn.textContent = 'Added';
            btn.className = 'btn btn-sm btn-success';
        }
    } else {
        selectedTeacherIds.splice(index, 1);
        selectedTeacherNames.splice(index, 1);
        if (btn) {
            btn.textContent = '+ Add';
            btn.className = 'btn btn-sm btn-outline';
        }
    }
    updateVisualSelectionsAndInputs();
}

function updateVisualSelectionsAndInputs() {
    const studentInputsContainer = document.getElementById('selectedStudentInputs');
    const teacherInputsContainer = document.getElementById('selectedTeacherInputs');
    
    studentInputsContainer.innerHTML = '';
    teacherInputsContainer.innerHTML = '';
    
    // Generate hidden inputs dynamically for form POST [Point 5]
    let sInputs = '';
    selectedStudentIds.forEach(id => {
        sInputs += `<input type="hidden" name="student_users[]" value="${id}">`;
    });
    
    let tInputs = '';
    selectedTeacherIds.forEach(id => {
        tInputs += `<input type="hidden" name="teacher_users[]" value="${id}">`;
    });
    
    studentInputsContainer.innerHTML = sInputs;
    teacherInputsContainer.innerHTML = tInputs;

    // Render Pill Boxes
    const pillContainer = document.getElementById('selectionVisualPills');
    pillContainer.innerHTML = '';

    let pillCount = 0;
    
    selectedStudentIds.forEach((id) => {
        const s = studentsDatabaseList.find(item => String(item.user_id) === String(id));
        if (s) {
            pillContainer.innerHTML += `<span class="badge badge-success" style="font-size:0.75rem; margin-right:4px;">🎓 ${s.name}</span>`;
            pillCount++;
        }
    });

    selectedTeacherIds.forEach((id) => {
        const t = teachersDatabaseList.find(item => String(item.user_id) === String(id));
        if (t) {
            pillContainer.innerHTML += `<span class="badge badge-warning" style="font-size:0.75rem; margin-right:4px;">👨‍🏫 ${t.name}</span>`;
            pillCount++;
        }
    });

    if (pillCount === 0) {
        pillContainer.innerHTML = '<span class="text-muted" style="font-size:0.78rem;">No custom members selected yet.</span>';
    }
}

// Validation Logic: Check if total added members is at least 2 [Point 5]
function validateGroupSubmission() {
    const totalMembers = selectedStudentIds.length + selectedTeacherIds.length;
    if (totalMembers < 2) {
        alert('Validation Error: A discussion group must have at least 2 members (Students or Teachers) to be created.');
        return false;
    }
    return true;
}

function filterGroupsList() {
    const input = document.getElementById('groupSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('#groupsContainer .group-card');
    items.forEach(item => {
        const name = item.querySelector('.group-name').textContent.toLowerCase();
        if (name.includes(input)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
</script>