<?php
/**
 * Discussion Page Debugger — CSE Department Portal
 * Put this file in your root cse_portal/ folder and run http://localhost/cse_portal/debug_discussion.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<div style="font-family: monospace; padding: 20px; background: #0f172a; color: #f1f5f9; min-height: 100vh; line-height: 1.8;">';
echo '<h2 style="color: #0ea5e9; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px;">🔍 Discussion Page Syntax & Compile-Time Debugger</h2>';

// Function to test-compile a PHP file for syntax/parse errors
function testCompileFile($filePath) {
    if (!file_exists($filePath)) {
        echo "<div style='color: #ef4444;'>❌ Error: File not found at `$filePath`</div>";
        return false;
    }
    
    // Command line syntax check (works if php is in environment variables)
    $output = [];
    $returnVar = -1;
    @exec("php -l " . escapeshellarg($filePath), $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "<div style='color: #10b981;'>✅ Syntax Check Passed: `$filePath` is syntactically correct.</div>";
        return true;
    } else if (!empty($output)) {
        echo "<div style='color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; margin: 10px 0;'>";
        echo "<strong>❌ Syntax Error detected in `$filePath`:</strong><br>";
        echo nl2br(htmlspecialchars(implode("\n", $output)));
        echo "</div>";
        return false;
    }
    
    // Fallback: Try requiring it in a try-catch sandbox
    try {
        ob_start();
        include_once $filePath;
        ob_end_clean();
        echo "<div style='color: #10b981;'>✅ Load Success: `$filePath` successfully included without instant crashes.</div>";
        return true;
    } catch (Throwable $t) {
        ob_end_clean();
        echo "<div style='color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; margin: 10px 0;'>";
        echo "<strong>❌ Fatal Runtime Error in `$filePath`:</strong><br>";
        echo "Message: " . htmlspecialchars($t->getMessage()) . "<br>";
        echo "File: " . htmlspecialchars($t->getFile()) . "<br>";
        echo "Line: " . htmlspecialchars($t->getLine());
        echo "</div>";
        return false;
    }
}

echo '<h3 style="margin-top: 20px; color: #3b82f6;">Step 1: Checking Model Files</h3>';
$modelSuccess = testCompileFile('app/classes/DiscussionModel.php');

echo '<h3 style="margin-top: 20px; color: #3b82f6;">Step 2: Checking Panel Controller Files</h3>';
$adminSuccess = testCompileFile('admin/discussions.php');

echo '<div style="margin-top: 30px; border-top: 1px solid #334155; padding-top: 15px; font-size: 0.9rem; color: #94a3b8;">';
echo 'যদি উপরে কোনো লাল এরর বক্স দেখতে পান, তবে সেই এরর মেসেজটি কপি করে আমাকে পাঠান। আর যদি কোনো এরর না দেখায় কিন্তু পেজটি এখনো সাদা থাকে, তবে দয়া করে আপনার <strong>app/classes/DiscussionModel.php</strong> ফাইলের সম্পূর্ণ কোডটি আমাকে এখানে মেসেজ বক্সে পাঠান, আমি নিজেই সব চেক করে কোড সাজিয়ে দেব।';
echo '</div>';
echo '</div>';