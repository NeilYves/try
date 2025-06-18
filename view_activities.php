<?php
$page_title = 'Activity Log';
require_once 'includes/header.php';
require_once 'config.php';

// Pagination settings
$limit = 15; // Number of activities per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter settings
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($link, $_GET['type']) : '';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($search_query)) {
    $where_clauses[] = "activity_description LIKE ?";
    $params[] = "%$search_query%";
    $param_types .= 's';
}

if (!empty($type_filter)) {
    $where_clauses[] = "activity_type = ?";
    $params[] = $type_filter;
    $param_types .= 's';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total number of activities for pagination
$total_activities_query = "SELECT COUNT(id) AS total FROM activities {$where_sql}";
$total_stmt = mysqli_prepare($link, $total_activities_query);
if ($total_stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($total_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_activities = $total_row['total'];
    mysqli_stmt_close($total_stmt);
} else {
    $total_activities = 0;
    error_log("Failed to prepare total activities query: " . mysqli_error($link));
}

$total_pages = ceil($total_activities / $limit);

// Fetch activities
$activities_query = "SELECT * FROM activities {$where_sql} ORDER BY timestamp DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($link, $activities_query);

if ($stmt) {
    $current_params = $params; // Copy for this specific query
    $current_param_types = $param_types; // Copy for this specific query

    $current_params[] = $limit;
    $current_params[] = $offset;
    $current_param_types .= 'ii';

    mysqli_stmt_bind_param($stmt, $current_param_types, ...$current_params);
    mysqli_stmt_execute($stmt);
    $activities_result = mysqli_stmt_get_result($stmt);
} else {
    $activities_result = false;
    error_log("Failed to prepare activities query: " . mysqli_error($link));
}

// Fetch distinct activity types for filter dropdown
$distinct_types_query = "SELECT DISTINCT activity_type FROM activities ORDER BY activity_type ASC";
$distinct_types_result = mysqli_query($link, $distinct_types_query);
$activity_types = [];
if ($distinct_types_result) {
    while ($row = mysqli_fetch_assoc($distinct_types_result)) {
        $activity_types[] = $row['activity_type'];
    }
}

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">Activity Log</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Activities</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="view_activities.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Search Description</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo html_escape($search_query); ?>" placeholder="e.g., New Resident, Certificate">
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label">Filter by Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($activity_types as $type): ?>
                        <option value="<?php echo html_escape($type); ?>" <?php echo ($type_filter == $type) ? 'selected' : ''; ?>><?php echo html_escape($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
    </div>
    <div class="card-body">
        <?php if ($activities_result && mysqli_num_rows($activities_result) > 0): ?>
            <ul class="list-group list-group-flush activity-list">
                <?php while ($activity = mysqli_fetch_assoc($activities_result)): ?>
                    <li class="list-group-item">
                        <div class="d-flex">
                            <div class="activity-icon 
                                <?php 
                                    if (stripos($activity['activity_type'], 'New') !== false) echo 'bg-primary';
                                    elseif (stripos($activity['activity_type'], 'Update') !== false) echo 'bg-warning';
                                    elseif (stripos($activity['activity_type'], 'Delete') !== false) echo 'bg-danger';
                                    elseif (stripos($activity['activity_type'], 'Issue') !== false) echo 'bg-success';
                                    else echo 'bg-info'; 
                                ?>">
                                <i class="fas 
                                <?php 
                                    if (stripos($activity['activity_type'], 'Resident') !== false) echo 'fa-user-plus';
                                    elseif (stripos($activity['activity_type'], 'Certificate') !== false) echo 'fa-file-alt';
                                    elseif (stripos($activity['activity_type'], 'SMS') !== false) echo 'fa-sms';
                                    elseif (stripos($activity['activity_type'], 'Login') !== false) echo 'fa-sign-in-alt';
                                    elseif (stripos($activity['activity_type'], 'Logout') !== false) echo 'fa-sign-out-alt';
                                    else echo 'fa-bell'; // Default icon
                                ?>"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?php echo html_escape($activity['activity_description']); ?></h6>
                                <small class="text-muted">Type: <?php echo html_escape($activity['activity_type']); ?> | Timestamp: <?php echo date("F j, Y, g:i a", strtotime(html_escape($activity['timestamp']))); ?></small>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>

            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo html_escape($search_query); ?>&type=<?php echo html_escape($type_filter); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo html_escape($search_query); ?>&type=<?php echo html_escape($type_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo html_escape($search_query); ?>&type=<?php echo html_escape($type_filter); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No activities found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
mysqli_close($link);
require_once 'includes/footer.php';
?> 