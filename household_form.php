<?php
// --- Household Form Page ---
// This page generates a form for adding new households or editing existing household records.

$page_title = 'Household Form';
require_once 'includes/header.php';

// Initialize all form variables
$household_id = null;
$household_name = '';
$address = '';
$head_of_household_id = '';
$contact_number = '';
$form_action = 'add';

// Fetch all residents for head of household dropdown
$residents_query = "SELECT id, fullname FROM residents ORDER BY fullname ASC";
$residents_result = mysqli_query($link, $residents_query);
$residents = [];
if ($residents_result) {
    while ($row = mysqli_fetch_assoc($residents_result)) {
        $residents[] = $row;
    }
}

// Determine Form Mode (Add or Edit)
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $page_title = 'Edit Household';
        $form_action = 'edit';
        
        $household_id = mysqli_real_escape_string($link, $_GET['id']);
        
        $sql = "SELECT * FROM households WHERE id = '$household_id'";
        $result = mysqli_query($link, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $household = mysqli_fetch_assoc($result);
            // Populate all form variables
            $household_name = $household['household_name'];
            $address = $household['address'];
            $head_of_household_id = $household['head_of_household_id'] ?? '';
            $contact_number = $household['contact_number'] ?? '';
        } else {
            header("Location: manage_households.php?status=error");
            exit;
        }
    } elseif ($_GET['action'] == 'add') {
        $page_title = 'Add New Household';
        $form_action = 'add';
    }
} else {
    header("Location: manage_households.php");
    exit;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><?php echo html_escape($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_households.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Households List
        </a>
    </div>
</div>

<!-- Household Form Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-home me-2"></i>
            <?php echo ($form_action == 'add') ? 'Add New Household' : 'Edit Household Information'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="household_handler.php" method="POST" id="householdForm">
            <input type="hidden" name="action" value="<?php echo html_escape($form_action); ?>">
            <?php if ($form_action == 'edit'): ?>
                <input type="hidden" name="household_id" value="<?php echo html_escape($household_id); ?>">
            <?php endif; ?>

            <!-- Household Information Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>Household Details
                    </h6>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="household_name" class="form-label">Household Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="household_name" name="household_name" 
                           value="<?php echo html_escape($household_name); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo html_escape($address); ?></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="head_of_household_id" class="form-label">Head of Household</label>
                    <select class="form-select" id="head_of_household_id" name="head_of_household_id">
                        <option value="">Select Resident (Optional)</option>
                        <?php foreach ($residents as $resident): ?>
                            <option value="<?php echo html_escape($resident['id']); ?>" 
                                    <?php echo ($head_of_household_id == $resident['id']) ? 'selected' : ''; ?>>
                                <?php echo html_escape($resident['fullname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                           value="<?php echo html_escape($contact_number); ?>" placeholder="09XX-XXX-XXXX">
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="fas fa-save me-2"></i>Save Household
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i>Reset Form
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 