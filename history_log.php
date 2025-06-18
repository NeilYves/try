<?php
$page_title = 'System Activity Log';
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

// Filters
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$filter_type = isset($_GET['type']) ? mysqli_real_escape_string($link, $_GET['type']) : '';
$filter_date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($link, $_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($link, $_GET['date_to']) : '';

$sql = "SELECT id, activity_description, activity_type, timestamp FROM activities";
$conditions = [];

if (!empty($search_query)) {
    $conditions[] = "activity_description LIKE '%$search_query%'";
}
if (!empty($filter_type)) {
    $conditions[] = "activity_type = '$filter_type'";
}
if (!empty($filter_date_from)) {
    $conditions[] = "DATE(timestamp) >= '$filter_date_from'";
}
if (!empty($filter_date_to)) {
    $conditions[] = "DATE(timestamp) <= '$filter_date_to'";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY timestamp DESC";

$activities_result = mysqli_query($link, $sql);

// Get distinct activity types for filter dropdown
$activity_types_sql = "SELECT DISTINCT activity_type FROM activities ORDER BY activity_type ASC";
$activity_types_result = mysqli_query($link, $activity_types_sql);

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-history me-2"></i><?php echo html_escape($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Activities</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="history_log.php" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Description</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Enter keyword..." value="<?php echo html_escape($search_query); ?>">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Activity Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All Types</option>
                    <?php 
                    if ($activity_types_result && mysqli_num_rows($activity_types_result) > 0) {
                        while($type_row = mysqli_fetch_assoc($activity_types_result)) {
                            $selected = ($filter_type == $type_row['activity_type']) ? 'selected' : '';
                            echo '<option value="'.html_escape($type_row['activity_type']).'" '.$selected.'>'.html_escape($type_row['activity_type']).'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo html_escape($filter_date_from); ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo html_escape($filter_date_to); ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="history_log.php" class="btn btn-outline-secondary w-100 mt-2 <?php echo (empty($search_query) && empty($filter_type) && empty($filter_date_from) && empty($filter_date_to)) ? 'disabled' : ''; ?>"><i class="fas fa-times me-1"></i> Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Activity Records</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activities_result && mysqli_num_rows($activities_result) > 0): ?>
                        <?php while($activity = mysqli_fetch_assoc($activities_result)): ?>
                            <tr>
                                <td><?php echo html_escape($activity['id']); ?></td>
                                <td><?php echo html_escape(date('M d, Y h:i:s A', strtotime($activity['timestamp']))); ?></td>
                                <td><span class="badge bg-secondary"><?php echo html_escape($activity['activity_type']); ?></span></td>
                                <td><?php echo html_escape($activity['activity_description']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No activities found<?php echo (!empty($search_query) || !empty($filter_type) || !empty($filter_date_from) || !empty($filter_date_to)) ? ' matching your criteria' : ''; ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Basic Pagination (Placeholder - implement if needed for large datasets) -->
        <?php 
        // Add simple pagination logic here if you anticipate many records.
        // For now, we'll assume all records are manageable on one page.
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
