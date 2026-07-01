<?php
require_once 'config/database.php';

if (Auth::isLoggedIn()) {
    Auth::redirectByRole();
}

$settingsModel = new SettingsModel();
$settings      = $settingsModel->get();
$error         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = trim($_POST['role']     ?? 'student');
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Student-only fields
    $roll    = trim($_POST['roll']    ?? '');
    $regNo   = trim($_POST['reg_no']  ?? '');
    $batchId = intval($_POST['batch_id'] ?? 0);
    $session = trim($_POST['session'] ?? '');

    // Teacher-only field
    $designation = trim($_POST['designation'] ?? '');

    $userModel = new User();

    // Validation
    if (!in_array($role, ['student', 'teacher'], true)) {
        $error = 'Please select a valid role.';
    } elseif (empty($name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required personal information fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($role === 'student' && (empty($roll) || empty($regNo) || !$batchId || empty($session))) {
        $error = 'Please fill in all required academic information fields.';
    } elseif ($role === 'teacher' && empty($designation)) {
        $error = 'Please enter your designation.';
    } elseif (empty($password) || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Profile photo is required.';
    } elseif ($userModel->emailExists($email)) {
        $error = 'This email is already registered. Please use a different email or log in.';
    } else {
        // Upload photo
        $uploader = new FileUpload('photos', ALLOWED_PHOTO_EXTENSIONS, 5 * 1024 * 1024);
        $uploadResult = $uploader->upload($_FILES['photo']);

        if (!$uploadResult['success']) {
            $error = $uploadResult['message'];
        } else {
            // Create user (status = pending)
            $userId = $userModel->create($name, $email, $password, $role, 'pending', $uploadResult['path']);

            if ($role === 'student') {
                $studentModel = new Student();
                $studentModel->create($userId, $roll, $regNo, $batchId, $session, $phone);
            } else {
                $teacherModel = new Teacher();
                $teacherModel->create($userId, $designation, $phone);
            }

            header('Location: ' . BASE_URL . 'login.php?registered=1');
            exit();
        }
    }
}

$batchModel = new BatchModel();
$batches    = $batchModel->all();

// Preserve form values on error
$old = $_POST ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= htmlspecialchars($settings['portal_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-logo">🎓</div>
        <div class="auth-brand-name">Join the Portal</div>
        <div class="auth-univ"><?= htmlspecialchars($settings['university_name']) ?></div>
        <div class="auth-desc"><?= htmlspecialchars($settings['department_name']) ?></div>
        <ul class="auth-features">
            <li><span class="feature-dot"></span>Register as Student or Teacher</li>
            <li><span class="feature-dot"></span>Admin reviews & approves new accounts</li>
            <li><span class="feature-dot"></span>You'll be notified once approved</li>
            <li><span class="feature-dot"></span>Profile photo required for ID verification</li>
        </ul>
    </div>

    <div class="auth-right">
        <div class="auth-form-title">Create Account</div>
        <div class="auth-form-subtitle">Please enter your information to register</div>

        <?php if ($error): ?><div class="auth-alert auth-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form class="auth-form" method="POST" enctype="multipart/form-data" id="registerForm">
            <input type="hidden" name="role" id="roleInput" value="student">

            <!-- Role Selector: Selected state is active as student by default on load [Point 1] -->
            <div class="form-group">
                <label class="form-label">I am registering as *</label>
                <div class="role-selector">
                    <label class="role-option selected" id="btnStudent">
                        <div class="role-icon">🎓</div><div>Student</div>
                    </label>
                    <label class="role-option" id="btnTeacher">
                        <div class="role-icon">👨‍🏫</div><div>Teacher</div>
                    </label>
                </div>
            </div>

            <!-- Profile Photo Upload -->
            <div class="form-group">
                <label class="form-label">Profile Photo *</label>
                <label class="photo-upload-box-unified" id="photoBox">
                    <div id="photoPreview">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">Click to upload your photo (JPG/PNG, required)</div>
                    </div>
                    <input type="file" name="photo" class="photo-input" id="photoFileInput" accept=".jpg,.jpeg,.png" style="display:none" required>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($old['name'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- Student Fields (Displayed by default) -->
            <div id="studentFields">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Roll *</label>
                        <input type="text" name="roll" id="inputRoll" class="form-control" value="<?= htmlspecialchars($old['roll'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration No *</label>
                        <input type="text" name="reg_no" id="inputReg" class="form-control" value="<?= htmlspecialchars($old['reg_no'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Batch *</label>
                        <select name="batch_id" id="selectBatch" class="form-control">
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['batch_id'] ?>" <?= ($old['batch_id'] ?? '')==$b['batch_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['batch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Session *</label>
                        <input type="text" name="session" id="inputSession" class="form-control" placeholder="e.g. 2020-2024" value="<?= htmlspecialchars($old['session'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Teacher Fields (Hidden by default) -->
            <div id="teacherFields" style="display:none">
                <div class="form-group">
                    <label class="form-label">Designation *</label>
                    <input type="text" name="designation" id="inputDesignation" class="form-control" placeholder="e.g. Lecturer" value="<?= htmlspecialchars($old['designation'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn-auth" style="margin-top:20px;">Submit Registration</button>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="<?= BASE_URL ?>login.php">Sign in here</a>
        </div>
    </div>
</div>

<script>
const studentFields = document.getElementById('studentFields');
const teacherFields = document.getElementById('teacherFields');
const btnStudent = document.getElementById('btnStudent');
const btnTeacher = document.getElementById('btnTeacher');
const roleInput = document.getElementById('roleInput');

const inputRoll = document.getElementById('inputRoll');
const inputReg = document.getElementById('inputReg');
const selectBatch = document.getElementById('selectBatch');
const inputSession = document.getElementById('inputSession');
const inputDesignation = document.getElementById('inputDesignation');

function setRole(role) {
    roleInput.value = role;
    if (role === 'student') {
        studentFields.style.display = 'block';
        teacherFields.style.display = 'none';
        
        inputRoll.required = true;
        inputReg.required = true;
        selectBatch.required = true;
        inputSession.required = true;
        inputDesignation.required = false;

        btnStudent.classList.add('selected');
        btnTeacher.classList.remove('selected');
    } else {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'block';

        inputRoll.required = false;
        inputReg.required = false;
        selectBatch.required = false;
        inputSession.required = false;
        inputDesignation.required = true;

        btnStudent.classList.remove('selected');
        btnTeacher.classList.add('selected');
    }
}

// Student is active visually by default on load [Point 1]
setRole('student');

btnStudent.addEventListener('click', () => setRole('student'));
btnTeacher.addEventListener('click', () => setRole('teacher'));

document.getElementById('photoFileInput')?.addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('photoPreview');
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        preview.innerHTML = `<img src="${e.target.result}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;" alt="Preview">`;
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>