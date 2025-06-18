<?php
// --- View Resident Page ---
// Displays all detailed information for a single resident.

$page_title = 'View Resident Details';
require_once 'includes/header.php';

// Check if a resident ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect if no ID is provided
    header("Location: manage_residents.php?status=error_no_id");
    exit;
}

$resident_id = (int)$_GET['id'];

// Fetch all resident data from the database, including joining with puroks and households
$sql = "SELECT r.*, p.purok_name, h.household_name 
        FROM residents r 
        LEFT JOIN puroks p ON r.purok_id = p.id
        LEFT JOIN households h ON r.household_id = h.id
        WHERE r.id = ?";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $resident_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$resident = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If no resident is found with the given ID, redirect back
if (!$resident) {
    header("Location: manage_residents.php?status=error_not_found");
    exit;
}

// Helper function to display data or a default placeholder
function display_data($data, $default = 'N/A') {
    echo html_escape(!empty($data) ? $data : $default);
}

// Assemble full name
$fullname_parts = [$resident['first_name'], $resident['middle_name'], $resident['last_name'], $resident['suffix']];
$full_name = implode(' ', array_filter($fullname_parts));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-user-circle me-2"></i>Resident Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_residents.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Residents
        </a>
        <a href="resident_form.php?action=edit&id=<?php echo $resident_id; ?>" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>Edit Resident
        </a>
    </div>
</div>

<div class="card profile-card">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">
            <?php display_data($full_name); ?>
        </h4>
        <small>Resident ID: <?php echo $resident['id']; ?></small>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Personal Information -->
            <div class="col-md-6">
                <h5 class="section-title">Personal Information</h5>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 40%;">First Name</th>
                            <td><?php display_data($resident['first_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Middle Name</th>
                            <td><?php display_data($resident['middle_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Last Name</th>
                            <td><?php display_data($resident['last_name']); ?></td>
                        </tr>
                         <tr>
                            <th scope="row">Suffix</th>
                            <td><?php display_data($resident['suffix']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Gender</th>
                            <td><?php display_data($resident['gender']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Civil Status</th>
                            <td><strong><?php display_data($resident['civil_status']); ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Birthdate</th>
                            <td><?php display_data(date('F j, Y', strtotime($resident['birthdate']))); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Age</th>
                            <td><?php echo date_diff(date_create($resident['birthdate']), date_create('today'))->y; ?> years</td>
                        </tr>
                        <tr>
                            <th scope="row">Educational Attainment</th>
                            <td><?php display_data($resident['educational_attainment']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Contact & Address Information -->
            <div class="col-md-6">
                <h5 class="section-title">Contact & Address</h5>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 40%;">Contact Number</th>
                            <td><?php display_data($resident['contact_number']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Purok</th>
                            <td><span class="badge bg-info"><?php display_data($resident['purok_name']); ?></span></td>
                        </tr>
                         <tr>
                            <th scope="row">Household</th>
                            <td><?php display_data($resident['household_name'], 'Not assigned'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h5 class="section-title mt-4">System Information</h5>
                 <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 40%;">Registration Date</th>
                            <td><?php display_data(date('F j, Y, g:i a', strtotime($resident['registration_date']))); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td>
                                <span class="badge <?php echo ($resident['status'] == 'Active') ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php display_data($resident['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if($resident['status'] !== 'Active'): ?>
                        <tr>
                            <th scope="row">Date Status Changed</th>
                            <td><?php display_data(date('F j, Y', strtotime($resident['date_status_changed']))); ?></td>
                        </tr>
                         <tr>
                            <th scope="row">Status Remarks</th>
                            <td><?php display_data($resident['status_remarks']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted">
        Last updated: <?php echo date("F j, Y, g:i a"); // Placeholder for a real last_updated field if it exists ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 