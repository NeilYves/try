<?php
// --- Purok Details Page ---
// This page displays residents organized by purok with filtering and detailed information
// Shows status distribution and provides management links

$page_title = 'Purok Details'; 
require_once 'includes/header.php';

// Get selected purok filter
$selected_purok = isset($_GET['purok']) ? intval($_GET['purok']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch all puroks for the filter dropdown
$puroks_query = "SELECT id, purok_name, purok_leader FROM puroks ORDER BY purok_name ASC";
$puroks_result = mysqli_query($link, $puroks_query);

// Build the main query based on filters
$residents_query = "
    SELECT r.*, p.purok_name 
    FROM residents r 
    LEFT JOIN puroks p ON r.purok_id = p.id 
    WHERE 1=1
";

$conditions = [];
$params = [];
$types = '';

if (!empty($selected_purok)) {
    $conditions[] = "r.purok_id = ?";
    $params[] = $selected_purok;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($conditions)) {
    $residents_query .= " AND " . implode(" AND ", $conditions);
}

$residents_query .= " ORDER BY p.purok_name ASC, r.fullname ASC";

// Execute the query
if (!empty($params)) {
    $stmt = mysqli_prepare($link, $residents_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $residents_result = mysqli_stmt_get_result($stmt);
    }
} else {
    $residents_result = mysqli_query($link, $residents_query);
}

// Get statistics by purok
$stats_query = "
    SELECT 
        p.id,
        p.purok_name,
        p.purok_leader,
        COUNT(r.id) as total_residents,
        SUM(CASE WHEN r.status = 'Active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN r.status = 'Deceased' THEN 1 ELSE 0 END) as deceased_count,
        SUM(CASE WHEN r.status = 'Moved Out' THEN 1 ELSE 0 END) as moved_out_count,
        SUM(CASE WHEN r.gender = 'Male' AND r.status = 'Active' THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN r.gender = 'Female' AND r.status = 'Active' THEN 1 ELSE 0 END) as female_count
    FROM puroks p 
    LEFT JOIN residents r ON p.id = r.purok_id 
    GROUP BY p.id, p.purok_name, p.purok_leader 
    ORDER BY p.purok_name ASC
";

$stats_result = mysqli_query($link, $stats_query);

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">
        <i class="fas fa-map-marked-alt me-2"></i>Purok Details & Residents
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="manage_residents.php" class="btn btn-outline-primary">
                <i class="fas fa-users me-1"></i>Manage Residents
            </a>
            <a href="resident_form.php?action=add" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>Add Resident
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="purok_details.php" class="row g-3">
            <div class="col-md-4">
                <label for="purok" class="form-label">Filter by Purok</label>
                <select class="form-select" id="purok" name="purok">
                    <option value="">All Puroks</option>
                    <?php 
                    mysqli_data_seek($puroks_result, 0); // Reset pointer
                    while($purok = mysqli_fetch_assoc($puroks_result)): 
                    ?>
                        <option value="<?php echo $purok['id']; ?>" <?php echo ($selected_purok == $purok['id']) ? 'selected' : ''; ?>>
                            <?php echo html_escape($purok['purok_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Deceased" <?php echo ($status_filter == 'Deceased') ? 'selected' : ''; ?>>Deceased</option>
                    <option value="Moved Out" <?php echo ($status_filter == 'Moved Out') ? 'selected' : ''; ?>>Moved Out</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="purok_details.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Purok Statistics Overview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Purok Statistics Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while($stat = mysqli_fetch_assoc($stats_result)): ?>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card purok-stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo html_escape($stat['purok_name']); ?>
                                </h6>
                                <div class="purok-stats">
                                    <div class="stat-item">
                                        <span class="stat-number text-primary"><?php echo $stat['total_residents']; ?></span>
                                        <span class="stat-label">Total</span>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i> <?php echo $stat['active_count']; ?> Active
                                            </small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="fas fa-user-friends"></i> <?php echo $stat['male_count']; ?>M / <?php echo $stat['female_count']; ?>F
                                            </small>
                                        </div>
                                    </div>
                                    <?php if ($stat['deceased_count'] > 0 || $stat['moved_out_count'] > 0): ?>
                                    <div class="row mt-1">
                                        <?php if ($stat['deceased_count'] > 0): ?>
                                        <div class="col-6">
                                            <small class="text-danger">
                                                <i class="fas fa-cross"></i> <?php echo $stat['deceased_count']; ?> Deceased
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($stat['moved_out_count'] > 0): ?>
                                        <div class="col-6">
                                            <small class="text-warning">
                                                <i class="fas fa-arrow-right"></i> <?php echo $stat['moved_out_count']; ?> Moved
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($stat['purok_leader'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie"></i> Leader: <?php echo html_escape($stat['purok_leader']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <a href="purok_details.php?purok=<?php echo $stat['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Residents
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Residents List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Residents List
            <?php if (!empty($selected_purok)): ?>
                <?php
                // Get purok name for display
                mysqli_data_seek($puroks_result, 0);
                while($purok = mysqli_fetch_assoc($puroks_result)) {
                    if ($purok['id'] == $selected_purok) {
                        echo " - " . html_escape($purok['purok_name']);
                        break;
                    }
                }
                ?>
            <?php endif; ?>
            <?php if (!empty($status_filter)): ?>
                (Status: <?php echo html_escape($status_filter); ?>)
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Purok</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($residents_result && mysqli_num_rows($residents_result) > 0): ?>
                        <?php while($resident = mysqli_fetch_assoc($residents_result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo html_escape($resident['fullname']); ?></strong>
                                    <?php if (!empty($resident['email'])): ?>
                                        <br><small class="text-muted"><?php echo html_escape($resident['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas <?php echo ($resident['gender'] == 'Male') ? 'fa-male text-primary' : (($resident['gender'] == 'Female') ? 'fa-female text-danger' : 'fa-user text-muted'); ?>"></i>
                                    <?php echo html_escape($resident['gender']); ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($resident['birthdate'])) {
                                        $birthdate = new DateTime($resident['birthdate']);
                                        $today = new DateTime();
                                        $age = $today->diff($birthdate)->y;
                                        echo $age . " years";
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo html_escape($resident['purok_name'] ?? 'Unassigned'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo match($resident['status']) {
                                            'Active' => 'bg-success',
                                            'Deceased' => 'bg-dark',
                                            'Moved Out' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo html_escape($resident['status']); ?>
                                    </span>
                                    <?php if (!empty($resident['date_status_changed']) && $resident['status'] != 'Active'): ?>
                                        <br><small class="text-muted">
                                            Changed: <?php echo date("M j, Y", strtotime($resident['date_status_changed'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($resident['contact_number'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo html_escape($resident['contact_number']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="resident_form.php?action=edit&id=<?php echo $resident['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="resident_handler.php?action=delete&id=<?php echo $resident['id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this resident?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                No residents found matching the current filters.
                                <?php if (!empty($selected_purok) || !empty($status_filter)): ?>
                                    <br><a href="purok_details.php" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-times me-1"></i>Clear Filters
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.purok-stat-card {
    border-left: 4px solid #007bff;
    transition: transform 0.2s;
}

.purok-stat-card:hover {
    transform: translateY(-2px);
}

.stat-item {
    text-align: center;
    margin-bottom: 10px;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75em;
}
</style>

<?php 
require_once 'includes/footer.php'; 
?> 