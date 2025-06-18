<?php
// --- Barangay Official Form Page ---
// This script generates an HTML form for adding a new barangay official or editing an existing one.
// It dynamically sets the page title and pre-populates form fields if editing.

// Set a default page title. This will be overridden if editing or adding.
$page_title = 'Official Form'; 
// Include the common header, which also establishes the database connection ($link).
require_once 'includes/header.php';

// --- Initialize Variables for Form Fields ---
$official_id = null;       // Will hold the ID when editing.
$fullname = '';
$position = '';
$gender = '';
$term_start_date = '';
$term_end_date = '';
$contact_number = '';
$image_path = '';          // Reserved for future image upload functionality.
$display_order = 0;        // Default display order.
$form_action = 'add';      // Default form action is 'add'.

// --- Determine Form Action (Add or Edit) and Load Data if Editing ---
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        // --- EDIT MODE ---
        $page_title = 'Edit Official'; // Set page title for editing.
        $form_action = 'edit';         // Set form action to 'edit'.
        $official_id = mysqli_real_escape_string($link, $_GET['id']); // Sanitize the ID from GET parameter.
        
        // SQL to fetch the official's details for pre-populating the form.
        // NOTE: This query uses mysqli_real_escape_string. For enhanced security, 
        // consider using a prepared statement with a bound parameter for the ID.
        $sql = "SELECT fullname, gender, position, term_start_date, term_end_date, contact_number, image_path, display_order FROM officials WHERE id = ?"; // Changed to use ? for prepared statement
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $official_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $official = mysqli_fetch_assoc($result);
                // Populate variables with data from the database.
                $fullname = $official['fullname'];
                $gender = $official['gender'];
                $position = $official['position'];
                $term_start_date = $official['term_start_date'];
                $term_end_date = $official['term_end_date'];
                $contact_number = $official['contact_number'];
                $image_path = $official['image_path']; // For future image display.
                $display_order = $official['display_order'];
            } else {
                // If no official is found with the given ID, redirect with an error status.
                header("Location: manage_officials.php?status=error_notfound");
                exit;
            }
            mysqli_stmt_close($stmt);
        } else {
            // SQL preparation failed
            error_log("DB Prepare Error (Fetch Official for Edit): " . mysqli_error($link));
            header("Location: manage_officials.php?status=error_prepare");
            exit;
        }

    } elseif ($_GET['action'] == 'add') {
        // --- ADD MODE ---
        $page_title = 'Add New Official'; // Set page title for adding.
        $form_action = 'add';           // Confirm form action is 'add'.
    }
} else {
    // If no action ('edit' or 'add') is specified in GET parameters, redirect to the management page.
    header("Location: manage_officials.php");
    exit;
}

?>

<!-- Page Header and Back Button -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><?php echo html_escape($page_title); // Display the dynamic page title, escaped for security ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_officials.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Officials List
        </a>
    </div>
</div>

<!-- Official Form Card -->
<div class="card">
    <div class="card-body">
        <!-- The form submits data to 'official_handler.php' using the POST method. -->
        <!-- 'enctype="multipart/form-data"' is included for future file (image) uploads. -->
        <form action="official_handler.php" method="POST" enctype="multipart/form-data"> 
            <!-- Hidden field to send the action type ('add' or 'edit') to the handler. -->
            <input type="hidden" name="action" value="<?php echo html_escape($form_action); ?>">
            <?php if ($form_action == 'edit'): // If editing, include a hidden field for the official's ID. ?>
                <input type="hidden" name="official_id" value="<?php echo html_escape($official_id); ?>">
            <?php endif; ?>

            <!-- Full Name Field (Required) -->
            <div class="mb-3 row">
                <label for="fullname" class="col-sm-3 col-form-label">Full Name <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo html_escape($fullname); // Pre-fill if editing, escape output ?>" required>
                </div>
            </div>

            <!-- Gender Field (Required) -->
            <div class="mb-3 row">
                <label for="gender" class="col-sm-3 col-form-label">Gender <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="" <?php if (empty($gender)) echo 'selected'; ?> disabled>Select Gender</option>
                        <option value="Male" <?php if ($gender == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if ($gender == 'Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
            </div>

            <!-- Position Field (Required) -->
            <div class="mb-3 row">
                <label for="position" class="col-sm-3 col-form-label">Position <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <select class="form-select" id="position" name="position" required>
                        <option value="" <?php if (empty($position)) echo 'selected'; ?> disabled>Select Position</option>
                        
                        <!-- Executive Positions -->
                        <optgroup label="Executive Positions">
                            <option value="Barangay Captain" <?php if ($position == 'Barangay Captain') echo 'selected'; ?>>Barangay Captain</option>
                            <option value="Punong Barangay" <?php if ($position == 'Punong Barangay') echo 'selected'; ?>>Punong Barangay</option>
                        </optgroup>
                        
                        <!-- Administrative Positions -->
                        <optgroup label="Administrative Positions">
                            <option value="Barangay Secretary" <?php if ($position == 'Barangay Secretary') echo 'selected'; ?>>Barangay Secretary</option>
                            <option value="Barangay Treasurer" <?php if ($position == 'Barangay Treasurer') echo 'selected'; ?>>Barangay Treasurer</option>
                        </optgroup>
                        
                        <!-- Council Members -->
                        <optgroup label="Council Members">
                            <option value="Barangay Kagawad" <?php if ($position == 'Barangay Kagawad') echo 'selected'; ?>>Barangay Kagawad</option>
                            <option value="Kagawad - Committee on Peace and Order" <?php if ($position == 'Kagawad - Committee on Peace and Order') echo 'selected'; ?>>Kagawad - Committee on Peace and Order</option>
                            <option value="Kagawad - Committee on Health and Sanitation" <?php if ($position == 'Kagawad - Committee on Health and Sanitation') echo 'selected'; ?>>Kagawad - Committee on Health and Sanitation</option>
                            <option value="Kagawad - Committee on Education" <?php if ($position == 'Kagawad - Committee on Education') echo 'selected'; ?>>Kagawad - Committee on Education</option>
                            <option value="Kagawad - Committee on Agriculture" <?php if ($position == 'Kagawad - Committee on Agriculture') echo 'selected'; ?>>Kagawad - Committee on Agriculture</option>
                            <option value="Kagawad - Committee on Infrastructure" <?php if ($position == 'Kagawad - Committee on Infrastructure') echo 'selected'; ?>>Kagawad - Committee on Infrastructure</option>
                            <option value="Kagawad - Committee on Finance" <?php if ($position == 'Kagawad - Committee on Finance') echo 'selected'; ?>>Kagawad - Committee on Finance</option>
                            <option value="Kagawad - Committee on Environment" <?php if ($position == 'Kagawad - Committee on Environment') echo 'selected'; ?>>Kagawad - Committee on Environment</option>
                        </optgroup>
                        
                        <!-- Youth and Special Positions -->
                        <optgroup label="Youth & Special Positions">
                            <option value="SK Chairperson" <?php if ($position == 'SK Chairperson') echo 'selected'; ?>>SK Chairperson</option>
                            <option value="SK Kagawad" <?php if ($position == 'SK Kagawad') echo 'selected'; ?>>SK Kagawad</option>
                            <option value="Barangay Tanod" <?php if ($position == 'Barangay Tanod') echo 'selected'; ?>>Barangay Tanod</option>
                            <option value="Barangay Health Worker" <?php if ($position == 'Barangay Health Worker') echo 'selected'; ?>>Barangay Health Worker</option>
                            <option value="Barangay Nutrition Scholar" <?php if ($position == 'Barangay Nutrition Scholar') echo 'selected'; ?>>Barangay Nutrition Scholar</option>
                        </optgroup>
                    </select>
                    <small class="form-text text-muted">Select the official position. This determines how they appear in the organizational chart.</small>
                </div>
            </div>

            <!-- Term Start Date Field -->
            <div class="mb-3 row">
                <label for="term_start_date" class="col-sm-3 col-form-label">Term Start Date</label>
                <div class="col-sm-9">
                    <input type="date" class="form-control" id="term_start_date" name="term_start_date" value="<?php echo html_escape($term_start_date); // Pre-fill if editing, escape output ?>">
                </div>
            </div>

            <!-- Term End Date Field -->
            <div class="mb-3 row">
                <label for="term_end_date" class="col-sm-3 col-form-label">Term End Date</label>
                <div class="col-sm-9">
                    <input type="date" class="form-control" id="term_end_date" name="term_end_date" value="<?php echo html_escape($term_end_date); // Pre-fill if editing, escape output ?>">
                </div>
            </div>

            <!-- Contact Number Field -->
            <div class="mb-3 row">
                <label for="contact_number" class="col-sm-3 col-form-label">Contact Number</label>
                <div class="col-sm-9">
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo html_escape($contact_number); // Pre-fill if editing, escape output ?>">
                </div>
            </div>
            
            <!-- Display Order Field -->
            <div class="mb-3 row">
                <label for="display_order" class="col-sm-3 col-form-label">Display Order</label>
                <div class="col-sm-9">
                    <input type="number" class="form-control" id="display_order" name="display_order" value="<?php echo html_escape($display_order); // Pre-fill if editing, escape output ?>" min="0">
                    <small class="form-text text-muted">Controls the order in which officials appear (lower numbers appear first).</small>
                </div>
            </div>

            <!-- Placeholder for Future Image Upload Field -->
            <!-- 
            <div class="mb-3 row">
                <label for="official_image" class="col-sm-3 col-form-label">Official Photo</label>
                <div class="col-sm-9">
                    <input type="file" class="form-control" id="official_image" name="official_image">
                    <?php if ($form_action == 'edit' && !empty($image_path)): // Display current image if editing and image exists ?>
                        <small class="form-text text-muted">Current image: <a href="<?php echo html_escape($image_path); ?>" target="_blank">View Image</a>. Upload a new image to replace it.</small>
                    <?php endif; ?>
                </div>
            </div>
            -->

            <!-- Official Photo Upload Field -->
            <div class="mb-3 row">
                <label for="official_image" class="col-sm-3 col-form-label">Official Photo</label>
                <div class="col-sm-9">
                    <?php if ($form_action == 'edit' && !empty($image_path)): ?>
                        <div class="mb-3">
                            <img src="<?php echo html_escape($image_path); ?>" alt="Current Official Photo" 
                                 class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <p class="small text-muted mt-1">Current photo</p>
                        </div>
                    <?php endif; ?>
                    
                    <input type="file" class="form-control" id="official_image" name="official_image" 
                           accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                    
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <img id="preview" src="" alt="Preview" class="img-thumbnail" 
                             style="max-width: 150px; max-height: 150px;">
                        <p class="small text-muted mt-1">New photo preview</p>
                    </div>
                    
                    <small class="form-text text-muted">
                        Upload a photo of the official (JPEG, PNG, or GIF format). Maximum file size: 5MB. 
                        Recommended: Square images work best for the organizational chart.
                        <?php if ($form_action == 'edit' && !empty($image_path)): ?>
                            <br>Leave empty to keep current photo.
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <script>
            function previewImage(input) {
                const preview = document.getElementById('preview');
                const previewDiv = document.getElementById('imagePreview');
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        previewDiv.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                } else {
                    previewDiv.style.display = 'none';
                }
            }
            </script>

            <!-- Submit and Cancel Buttons -->
            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo ($form_action == 'edit') ? 'Update Official' : 'Add Official'; // Dynamically set button text ?>
                    </button>
                    <a href="manage_officials.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
// Include the common footer for the page.
require_once 'includes/footer.php'; 
?>
