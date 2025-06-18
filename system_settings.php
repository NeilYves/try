<?php
$page_title = 'System Settings';
require_once 'includes/header.php';

// Function to handle logo uploads
function handle_logo_upload($file_input_name, $setting_key, $link, &$message) {
    $upload_dir = 'images/';
    $allowed_logo_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_logo_size = 2 * 1024 * 1024; // 2MB

    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_input_name];

        if (!in_array($file['type'], $allowed_logo_types)) {
            $message = '<div class="alert alert-danger">Invalid file type for ' . html_escape($file_input_name) . '. Only JPG, PNG, and GIF are allowed.</div>';
            return;
        }

        if ($file['size'] > $max_logo_size) {
            $message = '<div class="alert alert-danger">File size for ' . html_escape($file_input_name) . ' exceeds the 2MB limit.</div>';
            return;
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid($setting_key . '_', true) . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Remove old logo if it exists
            $old_logo_query = mysqli_prepare($link, "SELECT setting_value FROM system_settings WHERE setting_key = ?");
            mysqli_stmt_bind_param($old_logo_query, "s", $setting_key);
            mysqli_stmt_execute($old_logo_query);
            mysqli_stmt_bind_result($old_logo_query, $old_logo_path);
            if (mysqli_stmt_fetch($old_logo_query) && !empty($old_logo_path) && file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
            mysqli_stmt_close($old_logo_query);

            // Update database with new logo path
            $path_value = mysqli_real_escape_string($link, $destination);
            $upsert_sql = "REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
            $upsert_stmt = mysqli_prepare($link, $upsert_sql);
            mysqli_stmt_bind_param($upsert_stmt, "ss", $setting_key, $path_value);
            
            if (mysqli_stmt_execute($upsert_stmt)) {
                $message = '<div class="alert alert-success">Logo uploaded successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Database error on logo update: ' . mysqli_error($link) . '</div>';
            }
            mysqli_stmt_close($upsert_stmt);
        } else {
            $message = '<div class="alert alert-danger">Failed to move uploaded file. Check permissions for ' . $upload_dir . ' directory.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Please choose a file to upload.</div>';
    }
}

// The page is now accessible to staff for password changes.
// We'll use the role to conditionally display content.
$user_id = $_SESSION['id'] ?? 0;
$user_role = $_SESSION['role'] ?? 'staff';

$password_message = '';
$message = ''; // For existing settings messages

// Handle Password Change
if (isset($_POST['change_password'])) {
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $password_message = '<div class="alert alert-warning">Please fill in all password fields.</div>';
    } else {
        $stmt = mysqli_prepare($link, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_db_password);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($current_db_password && $old_password === $current_db_password) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) < 8) {
                    $password_message = '<div class="alert alert-danger">Password must be at least 8 characters long.</div>';
                } else {
                    $update_stmt = mysqli_prepare($link, "UPDATE users SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($update_stmt, "si", $new_password, $user_id);
                    if (mysqli_stmt_execute($update_stmt)) {
                        $password_message = '<div class="alert alert-success">Password updated successfully.</div>';
                    } else {
                        $password_message = '<div class="alert alert-danger">Error updating password. Please try again.</div>';
                    }
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                $password_message = '<div class="alert alert-danger">New password and confirmation password do not match.</div>';
            }
        } else {
            $password_message = '<div class="alert alert-danger">Incorrect old password.</div>';
        }
    }
}

// The rest of the file's POST handling is for Barangay Secretary only
if ($user_role === 'Barangay Secretary' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_barangay_logo'])) {
        handle_logo_upload('barangay_logo', 'barangay_logo_path', $link, $message);
    } elseif (isset($_POST['upload_municipality_logo'])) {
        handle_logo_upload('municipality_logo', 'municipality_logo_path', $link, $message);
    } elseif (isset($_POST['save_settings'])) {
        $allowed_keys = ['barangay_name', 'barangay_address_line1', 'barangay_address_line2', 'current_punong_barangay_id', 'default_certificate_fee', 'barangay_seal_text', 'municipality_seal_text'];
        $errors = [];
        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $value = mysqli_real_escape_string($link, $_POST[$key]);
                $upsert_sql = "REPLACE INTO system_settings (setting_key, setting_value) VALUES ('$key', '$value')";
                if (!mysqli_query($link, $upsert_sql)) {
                    $errors[] = "Error updating $key: " . mysqli_error($link);
                }
            }
        }
        if (empty($errors)) {
            $message = '<div class="alert alert-success" role="alert">System settings updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger" role="alert">Error updating settings: <br>' . implode('<br>', $errors) . '</div>';
        }
    }
}

// Fetch settings data (needed for both roles for layout, but only editable by secretary)
$settings_data = [];
$settings_sql = "SELECT setting_key, setting_value FROM system_settings";
$settings_result = mysqli_query($link, $settings_sql);
if ($settings_result) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $settings_data[$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch officials for Punong Barangay dropdown
$officials_sql = "SELECT id, fullname, position FROM officials WHERE position LIKE '%Punong Barangay%' OR position LIKE '%Captain%' ORDER BY fullname ASC";
$officials_result = mysqli_query($link, $officials_sql);

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-cogs me-2"></i><?php echo html_escape($page_title); ?></h1>
</div>

<?php 
if ($user_role === 'Barangay Secretary') {
    echo $message; // Display settings update messages
}
echo $password_message; // Display password change messages 
?>

<!-- Change Password Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Your Password</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="system_settings.php">
            <div class="mb-3 row">
                <label for="old_password" class="col-sm-3 col-form-label">Old Password</label>
                <div class="col-sm-9">
                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                <div class="col-sm-9">
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <small class="form-text text-muted">Must be at least 8 characters long.</small>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="confirm_password" class="col-sm-3 col-form-label">Confirm New Password</label>
                <div class="col-sm-9">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($user_role === 'Barangay Secretary'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Barangay Information</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="system_settings.php" enctype="multipart/form-data">
                <div class="mb-3 row">
                    <label for="barangay_name" class="col-sm-3 col-form-label">Barangay Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="barangay_name" name="barangay_name" value="<?php echo html_escape($settings_data['barangay_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="barangay_address_line1" class="col-sm-3 col-form-label">Address Line 1</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="barangay_address_line1" name="barangay_address_line1" value="<?php echo html_escape($settings_data['barangay_address_line1'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="barangay_address_line2" class="col-sm-3 col-form-label">Address Line 2</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="barangay_address_line2" name="barangay_address_line2" value="<?php echo html_escape($settings_data['barangay_address_line2'] ?? ''); ?>" placeholder="e.g., City, Province">
                    </div>
                </div>
                
                <div class="mb-3 row">
                    <label for="barangay_logo" class="col-sm-3 col-form-label">Barangay Logo</label>
                    <div class="col-sm-9">
                        <input type="file" class="form-control" id="barangay_logo" name="barangay_logo">
                        <?php if (!empty($settings_data['barangay_logo_path'])): ?>
                            <small class="form-text text-muted mt-2 d-block">
                                Current: <img src="<?php echo html_escape($settings_data['barangay_logo_path']); ?>?t=<?php echo time(); ?>" alt="Current Logo" style="max-height: 50px; margin-left: 10px; vertical-align: middle;">
                            </small>
                        <?php endif; ?>
                        <button type="submit" name="upload_barangay_logo" class="btn btn-sm btn-outline-primary mt-2">Upload Logo</button>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="municipality_logo" class="col-sm-3 col-form-label">Municipality Logo</label>
                    <div class="col-sm-9">
                        <input type="file" class="form-control" id="municipality_logo" name="municipality_logo">
                        <?php if (!empty($settings_data['municipality_logo_path'])): ?>
                            <small class="form-text text-muted mt-2 d-block">
                                Current: <img src="<?php echo html_escape($settings_data['municipality_logo_path']); ?>?t=<?php echo time(); ?>" alt="Current Municipality Logo" style="max-height: 50px; margin-left: 10px; vertical-align: middle;">
                            </small>
                        <?php endif; ?>
                        <button type="submit" name="upload_municipality_logo" class="btn btn-sm btn-outline-primary mt-2">Upload Municipality Logo</button>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="barangay_seal_text" class="col-sm-3 col-form-label">Barangay Seal Text</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="barangay_seal_text" name="barangay_seal_text" value="<?php echo html_escape($settings_data['barangay_seal_text'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="municipality_seal_text" class="col-sm-3 col-form-label">Municipality Seal Text</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="municipality_seal_text" name="municipality_seal_text" value="<?php echo html_escape($settings_data['municipality_seal_text'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3 row">
                    <label for="current_punong_barangay_id" class="col-sm-3 col-form-label">Current Punong Barangay</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="current_punong_barangay_id" name="current_punong_barangay_id">
                            <option value="">Select Punong Barangay</option>
                            <?php 
                            if ($officials_result && mysqli_num_rows($officials_result) > 0) {
                                while ($official = mysqli_fetch_assoc($officials_result)) {
                                    $selected = (isset($settings_data['current_punong_barangay_id']) && $settings_data['current_punong_barangay_id'] == $official['id']) ? 'selected' : '';
                                    echo '<option value="' . html_escape($official['id']) . '" ' . $selected . '>' . html_escape($official['fullname']) . ' (' . html_escape($official['position']) . ')</option>';
                                }
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">Select the currently active Punong Barangay. This will be used as the signatory on certificates.</small>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="default_certificate_fee" class="col-sm-3 col-form-label">Default Certificate Fee</label>
                    <div class="col-sm-9">
                        <input type="number" step="0.01" class="form-control" id="default_certificate_fee" name="default_certificate_fee" value="<?php echo html_escape($settings_data['default_certificate_fee'] ?? '0.00'); ?>">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
