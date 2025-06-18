<?php
// --- Manage Puroks Page ---
// This page displays all puroks with their statistics and management options

$page_title = 'Manage Puroks'; 
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

// Handle success/error messages
$status_message = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_add':
            $status_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Purok added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
        case 'success_edit':
            $status_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Purok updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
        case 'success_delete':
            $status_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Purok deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
        case 'error':
            $status_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>An error occurred. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
        case 'error_delete':
            $status_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Cannot delete purok that has residents assigned to it.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
    }
}

// Get puroks with resident statistics
$puroks_query = "
    SELECT 
        p.id,
        p.purok_name,
        p.purok_leader,
        p.description,
        p.created_at,
        COUNT(r.id) as total_residents,
        SUM(CASE WHEN r.status = 'Active' THEN 1 ELSE 0 END) as active_residents,
        SUM(CASE WHEN r.status = 'Deceased' THEN 1 ELSE 0 END) as deceased_residents,
        SUM(CASE WHEN r.status = 'Moved Out' THEN 1 ELSE 0 END) as moved_out_residents,
        SUM(CASE WHEN r.gender = 'Male' AND r.status = 'Active' THEN 1 ELSE 0 END) as male_residents,
        SUM(CASE WHEN r.gender = 'Female' AND r.status = 'Active' THEN 1 ELSE 0 END) as female_residents
    FROM puroks p 
    LEFT JOIN residents r ON p.id = r.purok_id 
    GROUP BY p.id, p.purok_name, p.purok_leader, p.description, p.created_at 
    ORDER BY p.purok_name ASC
";

$puroks_result = mysqli_query($link, $puroks_query);

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">
        <i class="fas fa-map-marked-alt me-2"></i>Manage Puroks
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="print_puroks.php" target="_blank" class="btn btn-outline-secondary">
                <i class="fas fa-print me-1"></i>Print All Puroks
            </a>
            <a href="purok_details.php" class="btn btn-outline-info">
                <i class="fas fa-chart-bar me-1"></i>View Statistics
            </a>
            <a href="purok_form.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New Purok
            </a>
        </div>
    </div>
</div>

<!-- Status Messages -->
<?php echo $status_message; ?>

<!-- Puroks Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>All Puroks
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Purok Name</th>
                        <th>Leader</th>
                        <th>Description</th>
                        <th>Total Residents</th>
                        <th>Active</th>
                        <th>Male/Female</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($puroks_result && mysqli_num_rows($puroks_result) > 0): ?>
                        <?php while ($purok = mysqli_fetch_assoc($puroks_result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo html_escape($purok['purok_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo html_escape($purok['purok_leader'] ?? 'Not assigned'); ?>
                                </td>
                                <td>
                                    <small><?php echo html_escape($purok['description'] ?? 'No description'); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary fs-6">
                                        <?php echo $purok['total_residents']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo $purok['active_residents']; ?> Active
                                    </span>
                                    <?php if ($purok['deceased_residents'] > 0): ?>
                                        <br><span class="badge bg-dark mt-1">
                                            <?php echo $purok['deceased_residents']; ?> Deceased
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($purok['moved_out_residents'] > 0): ?>
                                        <br><span class="badge bg-warning mt-1">
                                            <?php echo $purok['moved_out_residents']; ?> Moved Out
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($purok['active_residents'] > 0): ?>
                                        <small class="text-info">
                                            <i class="fas fa-male"></i> <?php echo $purok['male_residents']; ?> |
                                            <i class="fas fa-female"></i> <?php echo $purok['female_residents']; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">No active residents</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date("M j, Y", strtotime($purok['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Residents Button -->
                                        <a href="purok_details.php?purok=<?php echo $purok['id']; ?>" 
                                           class="btn btn-outline-info" title="View Residents">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <!-- Edit Button -->
                                        <a href="purok_form.php?action=edit&id=<?php echo $purok['id']; ?>" 
                                           class="btn btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Delete Button -->
                                        <a href="purok_handler.php?action=delete&id=<?php echo $purok['id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this purok? This action cannot be undone.\n\nNote: You cannot delete a purok that has residents assigned to it.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-map-marked-alt fa-2x mb-3"></i>
                                    <p>No puroks found. <a href="purok_form.php?action=add" class="btn btn-primary btn-sm">Add the first purok</a></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Statistics Card -->
<?php
// Get overall statistics
$total_puroks = mysqli_num_rows($puroks_result);
mysqli_data_seek($puroks_result, 0); // Reset pointer

$total_residents = 0;
$total_active = 0;
while ($purok = mysqli_fetch_assoc($puroks_result)) {
    $total_residents += $purok['total_residents'];
    $total_active += $purok['active_residents'];
}
?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4 class="text-primary"><?php echo $total_puroks; ?></h4>
                        <small class="text-muted">Total Puroks</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success"><?php echo $total_residents; ?></h4>
                        <small class="text-muted">Total Residents</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-info"><?php echo $total_active; ?></h4>
                        <small class="text-muted">Active Residents</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning"><?php echo $total_puroks > 0 ? round($total_residents / $total_puroks, 1) : 0; ?></h4>
                        <small class="text-muted">Avg. per Purok</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 