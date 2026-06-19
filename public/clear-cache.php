<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../repositories/AMIS-enrollment/vendor/autoload.php';
$app = require_once __DIR__.'/../repositories/AMIS-enrollment/bootstrap/app.php';

// Bootstrap console kernel to register Facades
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

header('Content-Type: text/plain');

try {
    echo "Clearing route cache...\n";
    Artisan::call('route:clear');
    echo "Route cache cleared!\n\n";
    
    echo "Clearing config cache...\n";
    Artisan::call('config:clear');
    echo "Config cache cleared!\n\n";

    echo "Clearing view cache...\n";
    Artisan::call('view:clear');
    echo "View cache cleared!\n\n";
    
    echo "Re-caching routes...\n";
    Artisan::call('route:cache');
    echo "Routes cached successfully!\n\n";
    
    echo "All cache updated successfully!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
