<?php
// --- Manage Households Page ---
// This page lists all registered households and allows for adding, editing, and deleting household records.

$page_title = 'Manage Households';
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

require_once 'config.php'; // Ensure config.php is included for database connection and utilities

// Session-based message handling
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
$limit = 10; // Number of households per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search settings
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($search_query)) {
    $where_clauses[] = "(household_name LIKE ? OR address LIKE ?)";
    $params[] = "%" . $search_query . "%";
    $params[] = "%" . $search_query . "%";
    $param_types .= 'ss';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total number of households for pagination
$total_households_query = "SELECT COUNT(id) AS total FROM households {$where_sql}";
$total_stmt = mysqli_prepare($link, $total_households_query);
if ($total_stmt) {
    if (!empty($params)) {
        // Need to create a new array for binding as ...$params would modify the original
        $temp_params = $params;
        mysqli_stmt_bind_param($total_stmt, $param_types, ...$temp_params);
    }
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_households = $total_row['total'];
    mysqli_stmt_close($total_stmt);
} else {
    $total_households = 0;
    error_log("Failed to prepare total households query: " . mysqli_error($link));
}

$total_pages = ceil($total_households / $limit);

// Fetch households with search and pagination
$households_query = "SELECT h.id, h.household_name, h.address, h.contact_number,\n                     (SELECT COUNT(r.id) FROM residents r WHERE r.household_id = h.id) AS num_residents\n                     FROM households h {$where_sql} ORDER BY h.household_name ASC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($link, $households_query);

if ($stmt) {
    $current_params = $params; // Copy parameters for the main query
    $current_param_types = $param_types; // Copy param types

    // Add limit and offset parameters
    $current_params[] = $limit;
    $current_params[] = $offset;
    $current_param_types .= 'ii';

    mysqli_stmt_bind_param($stmt, $current_param_types, ...$current_params);
    mysqli_stmt_execute($stmt);
    $households_result = mysqli_stmt_get_result($stmt);
} else {
    $households_result = false;
    error_log("Failed to prepare households query: " . mysqli_error($link));
}

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><?php echo html_escape($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="household_form.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Household
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Households</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="manage_households.php" class="row g-3 align-items-end">
            <div class="col-md-10">
                <label for="search" class="form-label">Search Household Name or Address</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo html_escape($search_query); ?>" placeholder="e.g., Household Name, Address">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filter</button>
            </div>
            <?php if (!empty($search_query)): ?>
            <div class="col-md-12 text-end mt-2">
                <a href="manage_households.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Clear Filter</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Households List Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-home me-2"></i>Household Records
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="householdsTable">
                <thead>
                    <tr>
                        <th>Household Name</th>
                        <th>Address</th>
                        <th>Contact Number</th>
                        <th>Number of Residents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($households_result && mysqli_num_rows($households_result) > 0): ?>
                        <?php while ($household = mysqli_fetch_assoc($households_result)): ?>
                            <tr>
                                <td><?php echo html_escape($household['household_name']); ?></td>
                                <td><?php echo html_escape($household['address']); ?></td>
                                <td><?php echo html_escape($household['contact_number']); ?></td>
                                <td><?php echo html_escape($household['num_residents']); ?></td>
                                <td>
                                    <a href="view_household.php?id=<?php echo html_escape($household['id']); ?>" 
                                       class="btn btn-primary btn-sm" title="View Household">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="household_form.php?action=edit&id=<?php echo html_escape($household['id']); ?>" 
                                       class="btn btn-info btn-sm" title="Edit Household">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm delete-household-btn" 
                                            data-id="<?php echo html_escape($household['id']); ?>" 
                                            data-name="<?php echo html_escape($household['household_name']); ?>"
                                            title="Delete Household">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No households found<?php echo !empty($search_query) ? ' matching your search' : ''; ?>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo html_escape($search_query); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo html_escape($search_query); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo html_escape($search_query); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="card-footer text-muted text-center mt-3">
            Displaying <?php echo ($households_result ? mysqli_num_rows($households_result) : 0); ?> of <?php echo $total_households; ?> households.
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteHouseholdModal" tabindex="-1" aria-labelledby="deleteHouseholdModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHouseholdModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete household "<strong id="householdToDeleteName"></strong>"? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteHouseholdBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt); // Close the prepared statement for households_result
mysqli_close($link); // Close the database connection
include 'includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // DataTable initialization removed as custom pagination is implemented

        // Delete button click handler
        $('.delete-household-btn').on('click', function() {
            var householdId = $(this).data('id');
            var householdName = $(this).data('name');
            
            $('#householdToDeleteName').text(householdName);
            $('#confirmDeleteHouseholdBtn').attr('href', 'household_handler.php?action=delete&id=' + householdId);
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteHouseholdModal'));
            deleteModal.show();
        });
    });
</script>
