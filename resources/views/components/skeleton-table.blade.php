@props(['rows' => 5])

<div class="skeleton-table">
    <!-- Table Header -->
    <div class="skeleton-table-header">
        <div class="skeleton skeleton-table-header-cell"></div>
        <div class="skeleton skeleton-table-header-cell"></div>
        <div class="skeleton skeleton-table-header-cell"></div>
        <div class="skeleton skeleton-table-header-cell"></div>
        <div class="skeleton skeleton-table-header-cell"></div>
    </div>
    
    <!-- Table Rows -->
    @for($i = 0; $i < $rows; $i++)
    <div class="skeleton-table-row">
        <div class="skeleton skeleton-table-cell"></div>
        <div class="skeleton skeleton-table-cell"></div>
        <div class="skeleton skeleton-table-cell"></div>
        <div class="skeleton skeleton-table-cell"></div>
        <div class="skeleton skeleton-table-cell"></div>
    </div>
    @endfor
</div>