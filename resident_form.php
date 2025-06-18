<?php
// --- Enhanced Resident Form Page ---
// This page generates a comprehensive form for adding new residents or editing existing resident records.

$page_title = 'Resident Form'; 
require_once 'includes/header.php';

// Initialize all form variables
$resident_id = null;
$first_name = '';
$middle_name = '';
$last_name = '';
$suffix = '';
$gender = '';
$gender_other = '';
$birthdate = '';
$age = '';
$educational_attainment = '';
$maintenance_medicine = '';
$other_medicine = '';
$family_planning = 'Not Applicable';
$no_maintenance = 'No';
$water_source = '';
$toilet_facility = '';
$pantawid_4ps = 'No';
$backyard_gardening = 'No';
$contact_number = '';
$status = 'Active';
$purok_id = '';
$date_status_changed = '';
$status_remarks = '';
$form_action = 'add';
$household_id = '';
$civil_status = '';

// Fetch all puroks for dropdown
$puroks_query = "SELECT id, purok_name FROM puroks ORDER BY purok_name ASC";
$puroks_result = mysqli_query($link, $puroks_query);
$puroks = [];
if ($puroks_result) {
    while ($row = mysqli_fetch_assoc($puroks_result)) {
        $puroks[] = $row;
    }
}

// Fetch all households for dropdown
$households_query = "SELECT id, household_name FROM households ORDER BY household_name ASC";
$households_result = mysqli_query($link, $households_query);
$households = [];
if ($households_result) {
    while ($row = mysqli_fetch_assoc($households_result)) {
        $households[] = $row;
    }
}

// Determine Form Mode (Add or Edit)
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $page_title = 'Edit Resident';
        $form_action = 'edit';
        
        $resident_id = mysqli_real_escape_string($link, $_GET['id']);
        
        $sql = "SELECT * FROM residents WHERE id = '$resident_id'";
        $result = mysqli_query($link, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $resident = mysqli_fetch_assoc($result);
            // Populate all form variables
            $first_name = $resident['first_name'] ?? '';
            $middle_name = $resident['middle_name'] ?? '';
            $last_name = $resident['last_name'] ?? '';
            $suffix = $resident['suffix'] ?? '';
            $gender = $resident['gender'];
            $birthdate = $resident['birthdate'];
            $age = $resident['age'] ?? '';
            $educational_attainment = $resident['educational_attainment'] ?? '';
            $maintenance_medicine = $resident['maintenance_medicine'] ?? '';
            $other_medicine = $resident['other_medicine'] ?? '';
            $family_planning = $resident['family_planning'] ?? 'Not Applicable';
            $no_maintenance = $resident['no_maintenance'] ?? 'No';
            $water_source = $resident['water_source'] ?? '';
            $toilet_facility = $resident['toilet_facility'] ?? '';
            $pantawid_4ps = $resident['pantawid_4ps'] ?? 'No';
            $backyard_gardening = $resident['backyard_gardening'] ?? 'No';
            $contact_number = $resident['contact_number'];
            $status = $resident['status'];
            $purok_id = $resident['purok_id'];
            $date_status_changed = $resident['date_status_changed'];
            $status_remarks = $resident['status_remarks'];
            $household_id = $resident['household_id'] ?? '';
            $civil_status = $resident['civil_status'] ?? '';
        } else {
            header("Location: manage_residents.php?status=error");
            exit;
        }
    } elseif ($_GET['action'] == 'add') {
        $page_title = 'Add New Resident';
        $form_action = 'add';
    }
} else {
    header("Location: manage_residents.php");
    exit;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><?php echo html_escape($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_residents.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Residents List
        </a>
    </div>
</div>

<!-- Enhanced Resident Form Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-user-plus me-2"></i>
            <?php echo ($form_action == 'add') ? 'Add New Resident' : 'Edit Resident Information'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="resident_handler.php" method="POST" id="residentForm">
            <input type="hidden" name="action" value="<?php echo html_escape($form_action); ?>">
            <?php if ($form_action == 'edit'): ?>
                <input type="hidden" name="resident_id" value="<?php echo html_escape($resident_id); ?>">
            <?php endif; ?>

            <!-- Personal Information Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h6>
                </div>
            </div>

            <!-- Name Fields Row -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo html_escape($first_name); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" 
                           value="<?php echo html_escape($middle_name); ?>">
                </div>
                <div class="col-md-3">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo html_escape($last_name); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="suffix" class="form-label">Suffix</label>
                    <select class="form-select" id="suffix" name="suffix">
                        <option value="">No Suffix</option>
                        <option value="Jr." <?php echo ($suffix == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                        <option value="Sr." <?php echo ($suffix == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                        <option value="II" <?php echo ($suffix == 'II') ? 'selected' : ''; ?>>II</option>
                        <option value="III" <?php echo ($suffix == 'III') ? 'selected' : ''; ?>>III</option>
                        <option value="IV" <?php echo ($suffix == 'IV') ? 'selected' : ''; ?>>IV</option>
                        <option value="V" <?php echo ($suffix == 'V') ? 'selected' : ''; ?>>V</option>
                    </select>
                </div>
            </div>

            <!-- Gender, Civil Status, Birthday, Age Row -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other: Specify</option>
                    </select>
                    <div id="otherGenderDiv" class="mt-2" style="display: <?php echo ($gender == 'Other') ? 'block' : 'none'; ?>;">
                        <input type="text" class="form-control" id="gender_other" name="gender_other" 
                               value="<?php echo html_escape($gender_other); ?>" placeholder="Please specify gender">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="civil_status" class="form-label">Civil Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="civil_status" name="civil_status" required>
                        <option value="">Select Status</option>
                        <option value="Single" <?php echo ($civil_status == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo ($civil_status == 'Married') ? 'selected' : ''; ?>>Married</option>
                        <option value="Widow/er" <?php echo ($civil_status == 'Widow/er') ? 'selected' : ''; ?>>Widow/er</option>
                        <option value="Separated" <?php echo ($civil_status == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="birthdate" class="form-label">Birthday</label>
                    <input type="date" class="form-control" id="birthdate" name="birthdate" 
                           value="<?php echo html_escape($birthdate); ?>" onchange="calculateAge()">
                </div>
                <div class="col-md-3">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" class="form-control bg-light" id="age" name="age" 
                           value="<?php echo html_escape($age); ?>" readonly>
                    <small class="form-text text-muted">Automatically calculated from birthday.</small>
                </div>
            </div>

            <!-- Educational Attainment and Maintenance Medicine -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="educational_attainment" class="form-label">Educational Attainment</label>
                    <select class="form-select" id="educational_attainment" name="educational_attainment">
                        <option value="">Select Educational Level</option>
                        <option value="No Formal Education" <?php echo ($educational_attainment == 'No Formal Education') ? 'selected' : ''; ?>>No Formal Education</option>
                        <option value="Elementary" <?php echo ($educational_attainment == 'Elementary') ? 'selected' : ''; ?>>Elementary (Ongoing)</option>
                        <option value="Elementary Graduate" <?php echo ($educational_attainment == 'Elementary Graduate') ? 'selected' : ''; ?>>Elementary Graduate</option>
                        <option value="High School" <?php echo ($educational_attainment == 'High School') ? 'selected' : ''; ?>>High School (Ongoing)</option>
                        <option value="High School Graduate" <?php echo ($educational_attainment == 'High School Graduate') ? 'selected' : ''; ?>>High School Graduate</option>
                        <option value="Vocational" <?php echo ($educational_attainment == 'Vocational') ? 'selected' : ''; ?>>Vocational/Technical</option>
                        <option value="College" <?php echo ($educational_attainment == 'College') ? 'selected' : ''; ?>>College (Ongoing)</option>
                        <option value="College Graduate" <?php echo ($educational_attainment == 'College Graduate') ? 'selected' : ''; ?>>College Graduate</option>
                        <option value="Post Graduate" <?php echo ($educational_attainment == 'Post Graduate') ? 'selected' : ''; ?>>Post Graduate</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="maintenance_medicine" class="form-label">Maintenance Medicine</label>
                    <select class="form-select" id="maintenance_medicine" name="maintenance_medicine">
                        <option value="">Select Condition</option>
                        <option value="Hypertension" <?php echo ($maintenance_medicine == 'Hypertension') ? 'selected' : ''; ?>>Hypertension</option>
                        <option value="Diabetes" <?php echo ($maintenance_medicine == 'Diabetes') ? 'selected' : ''; ?>>Diabetes</option>
                        <option value="Arthritis" <?php echo ($maintenance_medicine == 'Arthritis') ? 'selected' : ''; ?>>Arthritis</option>
                        <option value="Other" <?php echo ($maintenance_medicine == 'Other') ? 'selected' : ''; ?>>Other: Specify</option>
                    </select>
                    <div id="otherMedicineDiv" class="mt-2" style="display: none;">
                        <input type="text" class="form-control" id="other_medicine" name="other_medicine" 
                               value="<?php echo html_escape($other_medicine); ?>" placeholder="Please specify other condition">
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="row mb-4 mt-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-address-card me-2"></i>Contact & Address Information
                    </h6>
                </div>
            </div>

            <!-- Address and Contact -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="purok_id" class="form-label">Purok <span class="text-danger">*</span></label>
                    <select class="form-select" id="purok_id" name="purok_id" required>
                        <option value="">Select Purok</option>
                        <?php foreach ($puroks as $purok): ?>
                            <option value="<?php echo html_escape($purok['id']); ?>" 
                                    <?php echo ($purok_id == $purok['id']) ? 'selected' : ''; ?>>
                                <?php echo html_escape($purok['purok_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Household Selection -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="household_id" class="form-label">Household</label>
                    <select class="form-select" id="household_id" name="household_id">
                        <option value="">Select Household (Optional)</option>
                        <?php foreach ($households as $household): ?>
                            <option value="<?php echo html_escape($household['id']); ?>" 
                                    <?php echo ($household_id == $household['id']) ? 'selected' : ''; ?>>
                                <?php echo html_escape($household['household_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Contact Details -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                           value="<?php echo html_escape($contact_number); ?>" placeholder="09XX-XXX-XXXX">
                </div>
            </div>

            <!-- Household Information Section -->
            <div class="row mb-4 mt-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-home me-2"></i>Household & Social Information
                    </h6>
                </div>
            </div>

            <!-- Family Planning and Health -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="family_planning" class="form-label">Family Planning</label>
                    <select class="form-select" id="family_planning" name="family_planning">
                        <option value="Not Applicable" <?php echo ($family_planning == 'Not Applicable') ? 'selected' : ''; ?>>Not Applicable</option>
                        <option value="Yes" <?php echo ($family_planning == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo ($family_planning == 'No') ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="no_maintenance" class="form-label">No Maintenance Medicine</label>
                    <select class="form-select" id="no_maintenance" name="no_maintenance">
                        <option value="No" <?php echo ($no_maintenance == 'No') ? 'selected' : ''; ?>>No (Has Maintenance)</option>
                        <option value="Yes" <?php echo ($no_maintenance == 'Yes') ? 'selected' : ''; ?>>Yes (No Maintenance)</option>
                    </select>
                </div>
            </div>

            <!-- Utilities Information -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="water_source" class="form-label">Type of Water Source</label>
                    <select class="form-select" id="water_source" name="water_source">
                        <option value="">Select Water Source</option>
                        <option value="Level 0 - Deepwell" <?php echo ($water_source == 'Level 0 - Deepwell') ? 'selected' : ''; ?>>Level 0 - Deepwell</option>
                        <option value="Level 1 - Point Source" <?php echo ($water_source == 'Level 1 - Point Source') ? 'selected' : ''; ?>>Level 1 - Point Source</option>
                        <option value="Level 2 - Communal Faucet" <?php echo ($water_source == 'Level 2 - Communal Faucet') ? 'selected' : ''; ?>>Level 2 - Communal Faucet</option>
                        <option value="Level 3 - Individual Connection" <?php echo ($water_source == 'Level 3 - Individual Connection') ? 'selected' : ''; ?>>Level 3 - Individual Connection</option>
                        <option value="Others" <?php echo ($water_source == 'Others') ? 'selected' : ''; ?>>Others</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="toilet_facility" class="form-label">Toilet Facility (CR)</label>
                    <select class="form-select" id="toilet_facility" name="toilet_facility">
                        <option value="">Select Toilet Type</option>
                        <option value="Water Sealed" <?php echo ($toilet_facility == 'Water Sealed') ? 'selected' : ''; ?>>Water Sealed</option>
                        <option value="Closed Pit" <?php echo ($toilet_facility == 'Closed Pit') ? 'selected' : ''; ?>>Closed Pit</option>
                        <option value="Open Pit" <?php echo ($toilet_facility == 'Open Pit') ? 'selected' : ''; ?>>Open Pit</option>
                        <option value="None/No Toilet" <?php echo ($toilet_facility == 'None/No Toilet') ? 'selected' : ''; ?>>None/No Toilet</option>
                        <option value="Other" <?php echo ($toilet_facility == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <!-- Government Programs -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="pantawid_4ps" class="form-label">4Ps Beneficiary</label>
                    <select class="form-select" id="pantawid_4ps" name="pantawid_4ps">
                        <option value="No" <?php echo ($pantawid_4ps == 'No') ? 'selected' : ''; ?>>No</option>
                        <option value="Yes" <?php echo ($pantawid_4ps == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                    </select>
                    <small class="form-text text-muted">Pantawid Pamilyang Pilipino Program</small>
                </div>
                <div class="col-md-4">
                    <label for="backyard_gardening" class="form-label">Backyard Gardening</label>
                    <select class="form-select" id="backyard_gardening" name="backyard_gardening">
                        <option value="No" <?php echo ($backyard_gardening == 'No') ? 'selected' : ''; ?>>No</option>
                        <option value="Yes" <?php echo ($backyard_gardening == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                    </select>
                </div>
            </div>

            <!-- Status Information Section -->
            <div class="row mb-4 mt-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2"></i>Status Information
                    </h6>
                </div>
            </div>

            <!-- Status Fields -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required onchange="toggleStatusFields()">
                        <option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Deceased" <?php echo ($status == 'Deceased') ? 'selected' : ''; ?>>Deceased</option>
                        <option value="Moved Out" <?php echo ($status == 'Moved Out') ? 'selected' : ''; ?>>Moved Out</option>
                    </select>
                </div>
                <div class="col-md-4" id="status-date-col" style="<?php echo ($status == 'Active') ? 'display: none;' : ''; ?>">
                    <label for="date_status_changed" class="form-label">Date Status Changed</label>
                    <input type="date" class="form-control" id="date_status_changed" name="date_status_changed" 
                           value="<?php echo html_escape($date_status_changed); ?>">
                </div>
            </div>

            <div class="row mb-3" id="status-remarks-row" style="<?php echo ($status == 'Active') ? 'display: none;' : ''; ?>">
                <div class="col-md-12">
                    <label for="status_remarks" class="form-label">Status Remarks</label>
                    <textarea class="form-control" id="status_remarks" name="status_remarks" rows="2" 
                              placeholder="Additional information about the status change..."><?php echo html_escape($status_remarks); ?></textarea>
                </div>
            </div>

            <!-- Form Submission Buttons -->
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary btn-lg me-2"><i class="fas fa-save me-2"></i>Save Resident</button>
                    <button type="reset" class="btn btn-secondary btn-lg"><i class="fas fa-redo me-2"></i>Reset Form</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
require_once 'includes/footer.php'; // Include the footer
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const residentForm = document.getElementById('residentForm');

        // Calculate Age from Birthdate
        const birthdateInput = document.getElementById('birthdate');
        const ageInput = document.getElementById('age');

        window.calculateAge = function() {
            const birthdate = new Date(birthdateInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthdate.getFullYear();
            const m = today.getMonth() - birthdate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            ageInput.value = isNaN(age) ? '' : age;
        };
        calculateAge(); // Initial calculation on load

        // Handle Other Medicine Toggle
        const maintenanceMedicineSelect = document.getElementById('maintenance_medicine');
        const otherMedicineDiv = document.getElementById('otherMedicineDiv');
        const otherMedicineInput = document.getElementById('other_medicine');

        function toggleMaintenanceMedicine() {
            if (maintenanceMedicineSelect.value === 'Other') {
                otherMedicineDiv.style.display = 'block';
                otherMedicineInput.required = true;
            } else {
                otherMedicineDiv.style.display = 'none';
                otherMedicineInput.required = false;
                otherMedicineInput.value = ''; // Clear the input when hidden
            }
        }

        maintenanceMedicineSelect.addEventListener('change', toggleMaintenanceMedicine);
        toggleMaintenanceMedicine(); // Initial toggle on page load

        // Handle Other Gender Toggle
        const genderSelect = document.getElementById('gender');
        const otherGenderDiv = document.getElementById('otherGenderDiv');
        const otherGenderInput = document.getElementById('gender_other');

        function toggleGenderOther() {
            if (genderSelect.value === 'Other') {
                otherGenderDiv.style.display = 'block';
                otherGenderInput.required = true;
            } else {
                otherGenderDiv.style.display = 'none';
                otherGenderInput.required = false;
                otherGenderInput.value = ''; // Clear the input when hidden
            }
        }

        genderSelect.addEventListener('change', toggleGenderOther);
        toggleGenderOther(); // Initial toggle on page load

        // Handle Status Fields Toggle
        window.toggleStatusFields = function() {
            const statusSelect = document.getElementById('status');
            const statusDateCol = document.getElementById('status-date-col');
            const statusRemarksRow = document.getElementById('status-remarks-row');
            
            if (statusSelect.value === 'Active') {
                statusDateCol.style.display = 'none';
                statusRemarksRow.style.display = 'none';
            } else {
                statusDateCol.style.display = 'block';
                statusRemarksRow.style.display = 'block';
            }
        };

        // Client-side Form Validation
        residentForm.addEventListener('submit', function(event) {
            let isValid = true;

            // Clear previous error messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Validate required fields
            const requiredFields = [
                { id: 'first_name', name: 'First Name' },
                { id: 'last_name', name: 'Last Name' },
                { id: 'gender', name: 'Gender' },
                { id: 'purok_id', name: 'Purok' }
            ];

            requiredFields.forEach(field => {
                const input = document.getElementById(field.id);
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    const feedback = `<div class="invalid-feedback">${field.name} is required.</div>`;
                    input.insertAdjacentHTML('afterend', feedback);
                }
            });

            // Validate Contact Number format (simple check for digits and length)
            const contactNumberInput = document.getElementById('contact_number');
            if (contactNumberInput && contactNumberInput.value.trim() !== '' && !/^\d{10,15}$/.test(contactNumberInput.value)) {
                isValid = false;
                contactNumberInput.classList.add('is-invalid');
                const feedback = `<div class="invalid-feedback">Please enter a valid contact number (10-15 digits).</div>`;
                contactNumberInput.insertAdjacentHTML('afterend', feedback);
            }

            if (!isValid) {
                event.preventDefault(); // Prevent form submission
                alert('Please correct the errors in the form.');
            }
        });
    });
</script>
