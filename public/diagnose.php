<?php
/**
 * AMIS Enrollment Path Diagnostic Script
 */
header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>AMIS Diagnostic</title><style>body { font-family: monospace; padding: 20px; background: #0f172a; color: #38bdf8; line-height: 1.5; } h2 { color: #f43f5e; } pre { background: #1e293b; padding: 15px; border-radius: 8px; color: #e2e8f0; overflow-x: auto; border: 1px solid #334155; }</style></head><body>";

echo "<h2>🚀 AMIS Enrollment Diagnostic Tool</h2>";
echo "====================================================<br>";
echo "<b>Server Host:</b> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
echo "<b>Diagnostic File Path:</b> " . htmlspecialchars(__FILE__) . "<br>";
echo "<b>Document Root:</b> " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "<b>Current Working Directory:</b> " . htmlspecialchars(getcwd()) . "<br>";
echo "====================================================<br><br>";

// Helper to look for brand-header
$pathsToTry = [
    __DIR__ . '/../resources/views/components/enrollment/brand-header.blade.php',
    __DIR__ . '/enrollment/resources/views/components/enrollment/brand-header.blade.php',
    __DIR__ . '/../enrollment/resources/views/components/enrollment/brand-header.blade.php',
    dirname(__DIR__) . '/resources/views/components/enrollment/brand-header.blade.php'
];

$found = false;
foreach ($pathsToTry as $path) {
    $real = realpath($path);
    if ($real && file_exists($real)) {
        echo "<b>✅ Found brand-header.blade.php at:</b> " . htmlspecialchars($real) . "<br>";
        echo "<b>File Contents:</b><br>";
        echo "<pre>" . htmlspecialchars(file_get_contents($real)) . "</pre>";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "<b>❌ brand-header.blade.php not found in standard paths. Searching parent directories...</b><br>";
    // Recursive search up to 3 levels
    $dir = __DIR__;
    for ($i = 0; $i < 4; $i++) {
        $searchPattern = $dir . '/**/components/enrollment/brand-header.blade.php';
        $matches = glob($searchPattern);
        if ($matches) {
            foreach ($matches as $match) {
                echo "<b>Found via glob:</b> " . htmlspecialchars($match) . "<br>";
                echo "<pre>" . htmlspecialchars(file_get_contents($match)) . "</pre>";
                $found = true;
            }
            break;
        }
        $dir = dirname($dir);
    }
}

echo "<br><b>Git Status of this folder (if available):</b><br>";
if (function_exists('shell_exec')) {
    $gitStatus = shell_exec('git status 2>&1');
    echo "<pre>" . htmlspecialchars($gitStatus) . "</pre>";
    $gitLog = shell_exec('git log -n 1 --oneline 2>&1');
    echo "<b>Last Commit:</b> <pre>" . htmlspecialchars($gitLog) . "</pre>";
} else {
    echo "<i>shell_exec() is disabled on this server.</i><br>";
}

echo "</body></html>";
