<?php
require __DIR__ . '/../../amis_admin/vendor/autoload.php';
$app = require_once __DIR__ . '/../../amis_admin/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$searchTerms = ['93', '0093', 'Eijazh'];

$tablesRaw = DB::select('SHOW TABLES');
$tables = [];
foreach ($tablesRaw as $tblObj) {
    $tables[] = current((array)$tblObj);
}

echo "Searching database for: " . implode(', ', $searchTerms) . "\n";

foreach ($tables as $table) {
    $columns = Schema::getColumnListing($table);
    if (empty($columns)) continue;
    
    $query = DB::table($table);
    
    $query->where(function($q) use ($columns, $searchTerms) {
        foreach ($columns as $column) {
            foreach ($searchTerms as $term) {
                $q->orWhere($column, '=', $term);
            }
        }
    });
    
    $count = $query->count();
    if ($count > 0) {
        echo "Table: {$table} | Found {$count} matching rows\n";
        $rows = $query->get();
        foreach ($rows as $row) {
            echo "  Row: " . json_encode($row) . "\n";
        }
    }
}
