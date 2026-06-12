<?php
// Simple security check
define('SECRET_PASSCODE', 'amis123'); // Security passcode to access the script

session_start();
if (isset($_POST['passcode']) && $_POST['passcode'] === SECRET_PASSCODE) {
    $_SESSION['auth_cleaner'] = true;
}

if (!empty($_GET['logout'])) {
    unset($_SESSION['auth_cleaner']);
    header('Location: ?');
    exit;
}

if (empty($_SESSION['auth_cleaner'])):
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Cleanup Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { margin-top: 0; color: #0f172a; font-size: 24px; font-weight: 700; }
        input[type="password"] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 16px; box-sizing: border-box; margin-bottom: 16px; margin-top: 16px; }
        button { background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s; }
        button:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="card">
        <h2>AMIS Roster Cleanup</h2>
        <form method="POST">
            <p style="color: #64748b; font-size: 14px; margin: 0;">Enter passcode to safely manage duplicates.</p>
            <input type="password" name="passcode" required placeholder="Passcode">
            <button type="submit">Unlock Tool</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
endif;

// Boot Laravel environment relative to public folder
$bootstrapPath = __DIR__.'/../bootstrap/app.php';
if (!file_exists($bootstrapPath)) {
    // Try subdirectory structure (if uploaded directly inside public_html and enrollment/ is next to it)
    $bootstrapPath = __DIR__.'/enrollment/bootstrap/app.php';
}
if (!file_exists($bootstrapPath)) {
    die("❌ Error: Could not locate Laravel bootstrap path at " . __DIR__);
}

require_once dirname($bootstrapPath).'/vendor/autoload.php';
$app = require_once $bootstrapPath;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;
use App\Models\Payment;

?>
<!DOCTYPE html>
<html>
<head>
    <title>AMIS Enrollment - Duplicate Cleanup Tool</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 40px; background: #f8fafc; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        h1 { margin-top: 0; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; color: #475569; font-weight: 600; }
        .btn-delete { background: #ef4444; color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; }
        .btn-delete:hover { background: #dc2626; }
        .alert-success { background: #dcfce7; color: #166534; padding: 16px; border-radius: 8px; margin-top: 20px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Roster Duplicate Cleanup Tool</h1>
            <a href="?logout=1" style="color:#64748b; text-decoration:none; font-weight:600;">Lock Tool 🔒</a>
        </div>
        <hr style="border:0; border-top:1px solid #e2e8f0; margin: 20px 0;">

        <?php
        // Find duplicate applicants
        $applicants = EnrollmentApplicant::where(function($q) {
            $q->where('first_name', 'like', '%Abdul-Aziz%')
              ->orWhere('last_name', 'like', '%Ladjahali%');
        })->get();

        if ($applicants->isEmpty()) {
            echo "<p style='color:#64748b; font-size:16px;'>No matching applicant records found for 'Abdul-Aziz Ladjahali'.</p>";
        } else {
            echo "<table>";
            echo "<thead><tr><th>ID</th><th>Family ID</th><th>First Name</th><th>Last Name</th><th>Middle Name</th><th>Email</th><th>Status</th><th>Created At</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            foreach ($applicants as $app) {
                echo "<tr>";
                echo "<td><strong>#{$app->id}</strong></td>";
                echo "<td>{$app->family_application_id}</td>";
                echo "<td>{$app->first_name}</td>";
                echo "<td>{$app->last_name}</td>";
                echo "<td>{$app->middle_name}</td>";
                echo "<td>{$app->email}</td>";
                echo "<td><span style='background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; text-transform:uppercase;'>{$app->status}</span></td>";
                echo "<td>{$app->created_at}</td>";
                echo "<td><a class='btn-delete' href='?delete_id={$app->id}' onclick='return confirm(\"Are you sure you want to delete applicant record ID {$app->id}? This will also delete any associated payment proof.\");'>Delete</a></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        }

        if (!empty($_GET['delete_id'])) {
            $deleteId = (int)$_GET['delete_id'];
            $toDelete = EnrollmentApplicant::find($deleteId);
            if ($toDelete) {
                // Delete associated payments
                Payment::where('enrollment_applicant_id', $deleteId)->delete();
                $toDelete->delete();
                echo "<div class='alert-success'>Successfully deleted applicant record ID #{$deleteId} and their associated payment proof (if any).</div>";
                echo "<script>setTimeout(function() { window.location.href = '?'; }, 2000);</script>";
            }
        }
        ?>
    </div>
</body>
</html>
