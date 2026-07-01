<?php
/**
 * FileUpload — Secure, reusable file upload handler
 * CSE Department Portal
 *
 * Usage:
 *   $uploader = new FileUpload('notices');
 *   $result = $uploader->upload($_FILES['attachment']);
 *   if ($result['success']) { $path = $result['path']; }
 */
class FileUpload
{
    private string $subdir;
    private array $allowedExt;
    private int $maxSize;

    public function __construct(string $subdir = '', array $allowedExt = null, int $maxSize = null)
    {
        $this->subdir     = trim($subdir, '/');
        $this->allowedExt = $allowedExt ?? ALLOWED_EXTENSIONS;
        $this->maxSize    = $maxSize ?? MAX_UPLOAD_SIZE;
    }

    /**
     * @param array $file  A single entry from $_FILES
     * @return array ['success'=>bool, 'path'=>string|null (relative, for DB), 'message'=>string]
     */
    public function upload(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'path' => null, 'message' => 'Invalid file upload parameters.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'path' => null, 'message' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'path' => null, 'message' => 'File exceeds the maximum allowed size.'];
            default:
                return ['success' => false, 'path' => null, 'message' => 'File upload failed (error code ' . $file['error'] . ').'];
        }

        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'path' => null, 'message' => 'File is too large. Maximum allowed size is 20 MB.'];
        }

        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $this->allowedExt, true)) {
            return ['success' => false, 'path' => null, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $this->allowedExt)];
        }

        // Validate actual MIME type for extra safety (basic check)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf', 'image/jpeg', 'image/png', 'image/jpg',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-zip-compressed',
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'path' => null, 'message' => 'File content does not match an allowed type.'];
        }

        // Build safe destination
        $targetDir = rtrim(UPLOAD_PATH, '/') . '/' . $this->subdir;
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return ['success' => false, 'path' => null, 'message' => 'Could not create upload directory: ' . $targetDir . '. Run: chmod -R 775 uploads/'];
            }
        }
        // Ensure the directory is actually writable even if it already existed with stale permissions
        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0775);
        }
        if (!is_writable($targetDir)) {
            return ['success' => false, 'path' => null, 'message' => 'Upload directory is not writable: ' . $targetDir . '. Run: chmod -R 775 uploads/ on the project folder.'];
        }

        $safeName = $this->generateSafeName($ext);
        $destPath = $targetDir . '/' . $safeName;

        $moved = @move_uploaded_file($file['tmp_name'], $destPath);

        // Fallback: some XAMPP/PHP configs (open_basedir, certain FPM setups) reject
        // move_uploaded_file even though the file is a legitimate upload. copy()+unlink()
        // works in those cases since it doesn't go through the same upload-validation path.
        if (!$moved && is_uploaded_file($file['tmp_name'])) {
            $moved = @copy($file['tmp_name'], $destPath);
            if ($moved) { @unlink($file['tmp_name']); }
        }

        if (!$moved) {
            $err = error_get_last();
            $detail = $err ? (' (' . $err['message'] . ')') : '';
            return ['success' => false, 'path' => null, 'message' => 'Failed to save uploaded file' . $detail . '. Check that "uploads/' . $this->subdir . '" is writable by the web server.'];
        }

        @chmod($destPath, 0644);

        // Relative path stored in DB, e.g. "notices/abc123.pdf"
        $relativePath = $this->subdir . '/' . $safeName;

        return ['success' => true, 'path' => $relativePath, 'message' => 'File uploaded successfully.'];
    }

    private function generateSafeName(string $ext): string
    {
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    }

    public function delete(string $relativePath): bool
    {
        $full = rtrim(UPLOAD_PATH, '/') . '/' . $relativePath;
        if (file_exists($full)) {
            return unlink($full);
        }
        return false;
    }
}
