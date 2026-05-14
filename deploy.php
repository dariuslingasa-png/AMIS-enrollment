<?php
/**
 * AMIS Enrollment - Production Deployment Script
 * Run this after uploading files to Bluehost
 */

echo "🚀 AMIS Enrollment Deployment Script\n";
echo "=====================================\n\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    die("❌ Error: Please run this script from the Laravel root directory\n");
}

// Check PHP version
$phpVersion = phpversion();
echo "📋 PHP Version: {$phpVersion}\n";

if (version_compare($phpVersion, '8.1.0', '<')) {
    echo "⚠️  Warning: PHP 8.1+ recommended for Laravel 11\n";
}

// Check if .env exists
if (!file_exists('.env')) {
    echo "❌ Error: .env file not found. Please create it from .env.production\n";
    exit(1);
}

echo "✅ Environment file found\n";

// Load environment
if (function_exists('exec')) {
    echo "\n🔧 Running deployment commands...\n";
    
    $commands = [
        'php artisan config:clear' => 'Clearing config cache',
        'php artisan cache:clear' => 'Clearing application cache',
        'php artisan view:clear' => 'Clearing view cache',
        'php artisan route:clear' => 'Clearing route cache',
        'php artisan config:cache' => 'Caching config',
        'php artisan route:cache' => 'Caching routes',
        'php artisan view:cache' => 'Caching views',
    ];
    
    foreach ($commands as $command => $description) {
        echo "   {$description}... ";
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅\n";
        } else {
            echo "❌\n";
            echo "   Error: " . implode("\n   ", $output) . "\n";
        }
        $output = [];
    }
    
    // Check if database connection works
    echo "\n🗄️  Testing database connection... ";
    exec('php artisan migrate:status 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅\n";
        
        // Run migrations
        echo "   Running migrations... ";
        exec('php artisan migrate --force 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅\n";
        } else {
            echo "❌\n";
            echo "   Migration errors: " . implode("\n   ", $output) . "\n";
        }
    } else {
        echo "❌\n";
        echo "   Database connection failed. Check your .env settings.\n";
        echo "   Error: " . implode("\n   ", $output) . "\n";
    }
    
} else {
    echo "⚠️  exec() function disabled. Please run commands manually:\n";
    echo "   php artisan config:cache\n";
    echo "   php artisan route:cache\n";
    echo "   php artisan view:cache\n";
    echo "   php artisan migrate --force\n";
}

// Check storage permissions
echo "\n📁 Checking storage permissions... ";
$storageWritable = is_writable('storage');
$bootstrapWritable = is_writable('bootstrap/cache');

if ($storageWritable && $bootstrapWritable) {
    echo "✅\n";
} else {
    echo "❌\n";
    echo "   Please set permissions:\n";
    echo "   chmod -R 775 storage/\n";
    echo "   chmod -R 775 bootstrap/cache/\n";
}

// Check if storage link exists
echo "🔗 Checking storage link... ";
if (file_exists('public/storage')) {
    echo "✅\n";
} else {
    echo "❌\n";
    echo "   Run: php artisan storage:link\n";
}

echo "\n🎉 Deployment script completed!\n";
echo "📝 Next steps:\n";
echo "   1. Test your application in browser\n";
echo "   2. Check error logs if issues occur\n";
echo "   3. Enable SSL certificate\n";
echo "   4. Update APP_URL to https:// in .env\n\n";