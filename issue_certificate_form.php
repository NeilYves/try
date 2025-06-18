<?php
// --- Issue New Certificate Form Page ---
// This script generates a form for issuing new barangay certificates.
// It fetches necessary data like residents, certificate types, and the Punong Barangay.

$page_title = 'Issue New Certificate';
require_once 'includes/header.php';

// --- Data Fetching for Form Dropdowns and Defaults ---

// Fetch all active residents to populate the 'Select Resident' dropdown.
// Concatenates name parts for display and orders by last name.
$residents_sql = "SELECT id, CONCAT(last_name, ', ', first_name, ' ', COALESCE(suffix, '')) as display_name 
                  FROM residents 
                  WHERE status = 'Active' 
                  ORDER BY last_name ASC, first_name ASC";
$residents_result = mysqli_query($link, $residents_sql);
if (!$residents_result) {
    // A more graceful error handling could be implemented here
    die('Error fetching residents: ' . mysqli_error($link));
}
$residents = [];
if ($residents_result) {
    while($row = mysqli_fetch_assoc($residents_result)) {
        $residents[] = $row;
    }
}

// Fetch all active certificate types.
$cert_types_sql = "SELECT id, name, default_purpose FROM certificate_types WHERE is_active = 1 ORDER BY name ASC";
$cert_types_result = mysqli_query($link, $cert_types_sql);
$certificate_types = [];
if ($cert_types_result) {
    while($row = mysqli_fetch_assoc($cert_types_result)) {
        $certificate_types[] = $row;
    }
}

// Fetch the current Punong Barangay who will be the issuing official.
$punong_barangay_id = null;
$punong_barangay_name = 'N/A (Please set in Barangay Officials)'; // Default display if not set.
$official_sql = "SELECT id, fullname FROM officials WHERE position = 'Punong Barangay' LIMIT 1";
$official_result = mysqli_query($link, $official_sql);
if ($official_result && $official_row = mysqli_fetch_assoc($official_result)) {
    $punong_barangay_id = $official_row['id'];
    $punong_barangay_name = $official_row['fullname'];
}

// --- Handle Potential Error Messages ---
$message = '';
if (isset($_GET['status']) && strpos($_GET['status'], 'error_') === 0) {
    $message = '<div class="alert alert-danger">Error: ' . html_escape(str_replace('error_', '', $_GET['status'])) . '</div>';
}

// If there are no certificate types defined, we cannot proceed.
if (empty($certificate_types)) {
    echo '<div class="alert alert-danger"><strong>Setup Error:</strong> No active certificate types found in the database. Please add a certificate type in the `certificate_types` table.</div>';
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- Page Header and Back Button -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-file-signature me-2"></i><?php echo html_escape($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_certificates.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Certificates List
        </a>
    </div>
</div>

<?php echo $message; ?>

<!-- Certificate Issuance Form Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0" id="certificate-title">Issue New Certificate</h5>
    </div>
    <div class="card-body">
        <form action="certificate_handler.php" method="POST" id="issueCertificateForm">
            <input type="hidden" name="action" value="issue">
            
            <!-- Certificate Type Selection -->
            <div class="mb-3 row">
                <label for="certificate_type_id" class="col-sm-3 col-form-label">Certificate Type <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <select class="form-select" id="certificate_type_id" name="certificate_type_id" required>
                        <option value="">-- Select Certificate Type --</option>
                        <?php foreach ($certificate_types as $type): ?>
                            <option value="<?php echo html_escape($type['id']); ?>" data-purpose="<?php echo html_escape($type['default_purpose']); ?>">
                                <?php echo html_escape($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Resident Selection Dropdown (Required) -->
            <div class="mb-3 row">
                <label for="resident_id" class="col-sm-3 col-form-label">Select Resident <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <select class="form-select" id="resident_id" name="resident_id" required>
                        <option value="">-- Select Resident --</option>
                        <?php 
                        foreach ($residents as $resident) {
                            echo '<option value="' . html_escape($resident['id']) . '">' . html_escape($resident['display_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Purpose Textarea (Required) -->
            <div class="mb-3 row">
                <label for="purpose" class="col-sm-3 col-form-label">Purpose <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                    <small class="form-text text-muted">Default purpose is set. You can modify it if needed.</small>
                </div>
            </div>

            <!-- Date of Issue Input (Required) -->
            <div class="mb-3 row">
                <label for="issue_date" class="col-sm-3 col-form-label">Date of Issue <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <!-- Remarks Textarea -->
            <div class="mb-3 row">
                <label for="remarks" class="col-sm-3 col-form-label">Remarks</label>
                <div class="col-sm-9">
                    <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Enter any remarks (optional)"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-2"></i>Issue Certificate & Proceed to Print
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
// For searchable dropdowns, you would include JS libraries like Select2 here.
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const certTypeDropdown = document.getElementById('certificate_type_id');
    const purposeTextarea = document.getElementById('purpose');
    const titleElement = document.getElementById('certificate-title');

    certTypeDropdown.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const purpose = selectedOption.getAttribute('data-purpose');
            const typeName = selectedOption.text;
            purposeTextarea.value = purpose;
            titleElement.textContent = 'Issue ' + typeName;
        } else {
            purposeTextarea.value = '';
            titleElement.textContent = 'Issue New Certificate';
        }
    });
});
</script>
<?php
require_once 'includes/footer.php'; 
?>
