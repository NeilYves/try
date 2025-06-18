<?php
// --- View Household Page ---
// This page displays detailed information about a specific household.

require_once 'config.php'; // Ensure config.php is loaded first for utility functions

$page_title = 'View Household';
require_once 'includes/header.php';

// Check if household ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_households.php?status=error&message=Household ID not provided.");
    exit;
}

$household_id = mysqli_real_escape_string($link, $_GET['id']);

// Fetch household details
$household_query = "SELECT h.id, h.household_name, h.address, h.date_created, h.last_updated, 
                      h.head_of_household_id, h.contact_number, 
                      (SELECT fullname FROM residents WHERE id = h.head_of_household_id) AS head_of_household_name
                      FROM households h
                      WHERE h.id = '$household_id'";
$household_result = mysqli_query($link, $household_query);

if ($household_result && mysqli_num_rows($household_result) > 0) {
    $household = mysqli_fetch_assoc($household_result);
} else {
    header("Location: manage_households.php?status=error&message=Household not found.");
    exit;
}

// Fetch residents belonging to this household
$residents_query = "SELECT id, fullname, gender, age FROM residents WHERE household_id = '$household_id' ORDER BY fullname ASC";
$residents_result = mysqli_query($link, $residents_query);
$residents_in_household = [];
if ($residents_result) {
    while ($row = mysqli_fetch_assoc($residents_result)) {
        $residents_in_household[] = $row;
    }
}

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">View Household: <?php echo html_escape($household['household_name']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_households.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Households List
        </a>
        <a href="household_form.php?action=edit&id=<?php echo html_escape($household['id']); ?>" class="btn btn-info">
            <i class="fas fa-edit me-2"></i>Edit Household
        </a>
    </div>
</div>

<!-- Household Details Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Household Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>Household Name:</strong> <?php echo html_escape($household['household_name']); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Head of Household:</strong> 
                <?php if (!empty($household['head_of_household_name'])): ?>
                    <a href="view_resident.php?id=<?php echo html_escape($household['head_of_household_id']); ?>">
                        <?php echo html_escape($household['head_of_household_name']); ?>
                    </a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Address:</strong> <?php echo html_escape($household['address']); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Contact Number:</strong> <?php echo html_escape($household['contact_number']); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Date Created:</strong> <?php echo html_escape(format_date($household['date_created'])); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Last Updated:</strong> <?php echo html_escape(format_date($household['last_updated'])); ?>
            </div>
        </div>
    </div>
</div>

<!-- Residents in Household Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Residents in this Household (<?php echo count($residents_in_household); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($residents_in_household)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($residents_in_household as $resident): ?>
                            <tr>
                                <td><?php echo html_escape($resident['fullname']); ?></td>
                                <td><?php echo html_escape($resident['gender']); ?></td>
                                <td><?php echo html_escape($resident['age']); ?></td>
                                <td>
                                    <a href="view_resident.php?id=<?php echo html_escape($resident['id']); ?>" class="btn btn-primary btn-sm" title="View Resident">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center">No residents found for this household.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
