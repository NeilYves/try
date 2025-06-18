<?php
// --- Manage Barangay Officials Page ---
// This page displays a list of barangay officials, allows searching for officials,
// and provides options to add, edit, or delete official records.
// It interacts with 'official_handler.php' for processing actions and 'official_form.php' for add/edit forms.

// Set the page title, which is used in the header include.
$page_title = 'Manage Barangay Officials';
// Include the common header for the page layout and database connection ($link).
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

require_once 'config.php'; // Ensure config.php is included for database connection and utilities

// --- Handle Status Messages from Handler ---
// Check for 'status' GET parameter, typically set by 'official_handler.php' after an action.
// Display messages using session variables for better practice
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-check-circle me-2"></i>';
    echo html_escape($_SESSION['success']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-circle me-2"></i>';
    echo html_escape($_SESSION['error']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error']);
}

// Pagination settings
$limit = 10; // Number of officials per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter settings
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$position_filter = isset($_GET['position']) ? trim($_GET['position']) : '';
$term_status_filter = isset($_GET['term_status']) ? trim($_GET['term_status']) : '';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($search_query)) {
    $where_clauses[] = "(fullname LIKE ? OR position LIKE ?)";
    $params[] = "%" . $search_query . "%";
    $params[] = "%" . $search_query . "%";
    $param_types .= 'ss';
}

if (!empty($position_filter)) {
    $where_clauses[] = "position = ?";
    $params[] = $position_filter;
    $param_types .= 's';
}

if (!empty($term_status_filter)) {
    if ($term_status_filter == 'Active') {
        $where_clauses[] = "(term_start_date <= CURDATE() AND term_end_date >= CURDATE())";
    } elseif ($term_status_filter == 'Expired') {
        $where_clauses[] = "term_end_date < CURDATE()";
    } elseif ($term_status_filter == 'Future') {
        $where_clauses[] = "term_start_date > CURDATE()";
    } elseif ($term_status_filter == 'No Term Set') {
        $where_clauses[] = "(term_start_date IS NULL OR term_start_date = '0000-00-00' OR term_end_date IS NULL OR term_end_date = '0000-00-00')";
    }
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total number of officials for pagination
$total_officials_query = "SELECT COUNT(id) AS total FROM officials {$where_sql}";
$total_stmt = mysqli_prepare($link, $total_officials_query);
if ($total_stmt) {
    if (!empty($params)) {
        // Need to create a new array for binding as ...$params would modify the original
        $temp_params = $params;
        mysqli_stmt_bind_param($total_stmt, $param_types, ...$temp_params);
    }
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_officials = $total_row['total'];
    mysqli_stmt_close($total_stmt);
} else {
    $total_officials = 0;
    error_log("Failed to prepare total officials query: " . mysqli_error($link));
}

$total_pages = ceil($total_officials / $limit);

// Fetch officials with search, filter, and pagination
$sql = "SELECT id, fullname, position, term_start_date, term_end_date, contact_number, display_order, image_path FROM officials {$where_sql} ORDER BY display_order ASC, fullname ASC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    $current_params = $params; // Copy parameters for the main query
    $current_param_types = $param_types; // Copy param types

    // Add limit and offset parameters
    $current_params[] = $limit;
    $current_params[] = $offset;
    $current_param_types .= 'ii';

    mysqli_stmt_bind_param($stmt, $current_param_types, ...$current_params);
    mysqli_stmt_execute($stmt);
    $officials_result = mysqli_stmt_get_result($stmt);
} else {
    $officials_result = false;
    error_log("Failed to prepare officials query: " . mysqli_error($link));
}

// Fetch distinct positions for filter dropdown
$distinct_positions_query = "SELECT DISTINCT position FROM officials ORDER BY position ASC";
$distinct_positions_result = mysqli_query($link, $distinct_positions_query);
$official_positions = [];
if ($distinct_positions_result) {
    while ($row = mysqli_fetch_assoc($distinct_positions_result)) {
        $official_positions[] = $row['position'];
    }
}

// Function to get position category and styling
function getPositionStyling($position) {
    $position_lower = strtolower($position);
    
    if (strpos($position_lower, 'captain') !== false || strpos($position_lower, 'punong') !== false) {
        return ['icon' => 'fa-crown', 'color' => 'text-warning', 'badge' => 'bg-warning'];
    } elseif (strpos($position_lower, 'secretary') !== false) {
        return ['icon' => 'fa-file-alt', 'color' => 'text-info', 'badge' => 'bg-info'];
    } elseif (strpos($position_lower, 'treasurer') !== false) {
        return ['icon' => 'fa-coins', 'color' => 'text-success', 'badge' => 'bg-success'];
    } elseif (strpos($position_lower, 'kagawad') !== false) {
        return ['icon' => 'fa-users', 'color' => 'text-primary', 'badge' => 'bg-primary'];
    } elseif (strpos($position_lower, 'sk') !== false) {
        return ['icon' => 'fa-graduation-cap', 'color' => 'text-warning', 'badge' => 'bg-warning'];
    } elseif (strpos($position_lower, 'tanod') !== false) {
        return ['icon' => 'fa-shield-alt', 'color' => 'text-danger', 'badge' => 'bg-danger'];
    } else {
        return ['icon' => 'fa-user-tie', 'color' => 'text-secondary', 'badge' => 'bg-secondary'];
    }
}

// Function to determine term status
function getTermStatus($start_date, $end_date) {
    if (empty($start_date) || $start_date === '0000-00-00' || empty($end_date) || $end_date === '0000-00-00') {
        return ['status' => 'No Term Set', 'class' => 'bg-secondary'];
    }
    
    $today = new DateTime();
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    if ($today < $start) {
        return ['status' => 'Future', 'class' => 'bg-info'];
    } elseif ($today > $end) {
        return ['status' => 'Expired', 'class' => 'bg-danger'];
    } else {
        return ['status' => 'Active', 'class' => 'bg-success'];
    }
}

?>

<!-- Page Header and Add Button -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><?php echo html_escape($page_title); // Display the escaped page title ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Link to the form for adding a new official -->
        <a href="official_form.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Official
        </a>
    </div>
</div>

<!-- Status Messages Displayed via Session -->

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Officials</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="manage_officials.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Name/Position</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo html_escape($search_query); ?>" placeholder="e.g., Juan Dela Cruz, Kagawad">
            </div>
            <div class="col-md-3">
                <label for="position" class="form-label">Filter by Position</label>
                <select id="position" name="position" class="form-select">
                    <option value="">All Positions</option>
                    <?php foreach ($official_positions as $position): ?>
                        <option value="<?php echo html_escape($position); ?>" <?php echo ($position_filter == $position) ? 'selected' : ''; ?>><?php echo html_escape($position); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="term_status" class="form-label">Filter by Term Status</label>
                <select id="term_status" name="term_status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Active" <?php echo ($term_status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Expired" <?php echo ($term_status_filter == 'Expired') ? 'selected' : ''; ?>>Expired</option>
                    <option value="Future" <?php echo ($term_status_filter == 'Future') ? 'selected' : ''; ?>>Future</option>
                    <option value="No Term Set" <?php echo ($term_status_filter == 'No Term Set') ? 'selected' : ''; ?>>No Term Set</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filters</button>
            </div>
            <?php if (!empty($search_query) || !empty($position_filter) || !empty($term_status_filter)): ?>
            <div class="col-md-12 text-end mt-2">
                <a href="manage_officials.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Clear Filters</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Officials List Card -->
<div class="card officials-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Officials List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive"> <!-- Ensures table is scrollable on small screens -->
            <table class="table table-hover"> <!-- Hover effect for table rows -->
                <thead class="table-light"> <!-- Light background for table header -->
                    <tr>
                        <th>Photo</th>
                        <th>Official Information</th>
                        <th>Position</th>
                        <th>Term Status</th>
                        <th>Contact</th>
                        <th>Display Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php // Check if there are any officials to display
                    if ($officials_result && mysqli_num_rows($officials_result) > 0): ?>
                        <?php // Loop through each official record and display it in a table row
                        while($official = mysqli_fetch_assoc($officials_result)): 
                            $position_styling = getPositionStyling($official['position']);
                            $term_status = getTermStatus($official['term_start_date'], $official['term_end_date']);
                        ?>
                            <tr>
                                <!-- Official Photo -->
                                <td>
                                    <img src="<?php echo !empty($official['image_path']) && file_exists($official['image_path']) ? html_escape($official['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                                         alt="<?php echo html_escape($official['fullname']); ?>" 
                                         class="rounded-circle" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                
                                <!-- Official Information -->
                                <td>
                                    <strong><?php echo html_escape($official['fullname']); ?></strong>
                                    <br><small class="text-muted">ID: <?php echo html_escape($official['id']); ?></small>
                                </td>
                                
                                <!-- Position with Icon -->
                                <td>
                                    <i class="fas <?php echo $position_styling['icon']; ?> <?php echo $position_styling['color']; ?> me-2"></i>
                                    <?php echo html_escape($official['position']); ?>
                                </td>
                                
                                <!-- Term Status -->
                                <td>
                                    <span class="badge <?php echo $term_status['class']; ?> mb-1">
                                        <?php echo $term_status['status']; ?>
                                    </span>
                                    <?php if (!empty($official['term_start_date']) && !empty($official['term_end_date'])): ?>
                                        <br><small class="text-muted">
                                            <?php echo date('M Y', strtotime($official['term_start_date'])); ?> - 
                                            <?php echo date('M Y', strtotime($official['term_end_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Contact Information -->
                                <td>
                                    <?php if (!empty($official['contact_number'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo html_escape($official['contact_number']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Display Order -->
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo html_escape($official['display_order'] ?? 'Not set'); ?>
                                    </span>
                                </td>
                                
                                <!-- Actions -->
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Info button: links to view_official.php with official ID -->
                                        <a href="view_official.php?id=<?php echo html_escape($official['id']); ?>" class="btn btn-outline-info" title="View Complete Information">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <!-- Edit button: links to official_form.php with action=edit and official ID -->
                                        <a href="official_form.php?action=edit&id=<?php echo html_escape($official['id']); ?>" class="btn btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Delete button: links to official_handler.php with action=delete and official ID -->
                                        <a href="official_handler.php?action=delete&id=<?php echo html_escape($official['id']); ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Delete" 
                                           onclick="return confirm('Are you sure you want to delete this official? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: // If no officials are found (or search yields no results) ?>
                        <tr><td colspan="7" class="text-center">No officials found<?php echo !empty($search_query) ? ' matching your search' : ''; // Tailor message if search was active ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo html_escape($search_query); ?>&position=<?php echo html_escape($position_filter); ?>&term_status=<?php echo html_escape($term_status_filter); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo html_escape($search_query); ?>&position=<?php echo html_escape($position_filter); ?>&term_status=<?php echo html_escape($term_status_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo html_escape($search_query); ?>&position=<?php echo html_escape($position_filter); ?>&term_status=<?php echo html_escape($term_status_filter); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Enhanced Table Footer with Actions -->
        <div class="card-footer text-muted text-center">
            Displaying <?php echo mysqli_num_rows($officials_result); ?> of <?php echo $total_officials; ?> officials.
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt); // Close the prepared statement for officials_result
mysqli_close($link); // Close the database connection
require_once 'includes/footer.php'; // Include the footer
?>
