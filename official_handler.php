<?php
// --- Barangay Official Management Handler ---
// This script processes requests for adding, editing, and deleting barangay officials.
// It includes database connection, input validation, SQL operations with prepared statements,
// activity logging, image upload handling, and redirects the user with status messages.

// Include the database configuration file which establishes $link (database connection).
require_once 'config.php';

// Initialize action variable.
$action = '';

// --- Determine Action --- 
// Check if the action is specified in POST data (typically from forms) or GET data (typically from links).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action']; // Action from POST request.
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action']; // Action from GET request.
}

// --- Helper Function for Image Upload ---
function handleImageUpload($file, $old_image_path = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $old_image_path; // Return old path if no new file uploaded
    }
    
    $upload_dir = 'assets/images/uploads/officials/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_type = $file['type'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];
    
    // Validate file type
    if (!in_array($file_type, $allowed_types)) {
        header("Location: manage_officials.php?status=error_invalid_file_type");
        exit;
    }
    
    // Validate file size
    if ($file_size > $max_size) {
        header("Location: manage_officials.php?status=error_file_too_large");
        exit;
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = 'official_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        // Delete old image if exists and new upload successful
        if ($old_image_path && file_exists($old_image_path) && $old_image_path !== $upload_path) {
            unlink($old_image_path);
        }
        return $upload_path;
    } else {
        header("Location: manage_officials.php?status=error_upload_failed");
        exit;
    }
}

// --- Process Action: ADD OFFICIAL --- 
if ($action == 'add') {
    // Check if required fields (fullname, position) are submitted.
    if (isset($_POST['fullname'], $_POST['position'])) {
        // Sanitize and retrieve form data.
        $fullname = mysqli_real_escape_string($link, $_POST['fullname']);
        $position = mysqli_real_escape_string($link, $_POST['position']);
        $gender = isset($_POST['gender']) ? mysqli_real_escape_string($link, $_POST['gender']) : '';
        $term_start_date = !empty($_POST['term_start_date']) ? mysqli_real_escape_string($link, $_POST['term_start_date']) : NULL;
        $term_end_date = !empty($_POST['term_end_date']) ? mysqli_real_escape_string($link, $_POST['term_end_date']) : NULL;
        $contact_number = !empty($_POST['contact_number']) ? mysqli_real_escape_string($link, $_POST['contact_number']) : NULL;
        $display_order = isset($_POST['display_order']) && is_numeric($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

        // Handle image upload
        $image_path = null;
        if (isset($_FILES['official_image'])) {
            $image_path = handleImageUpload($_FILES['official_image']);
        }

        // Validate required fields and gender value.
        $allowed_genders = ['Male', 'Female', 'Other'];
        if (empty($fullname) || empty($position) || empty($gender) || !in_array($gender, $allowed_genders)) {
            header("Location: manage_officials.php?status=error_validation");
            exit;
        }

        // SQL query to insert a new official with image_path using a prepared statement.
        $sql = "INSERT INTO officials (fullname, gender, position, term_start_date, term_end_date, contact_number, display_order, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Prepare the SQL statement.
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters to the prepared statement.
            mysqli_stmt_bind_param($stmt, "sssssssi", $fullname, $gender, $position, $term_start_date, $term_end_date, $contact_number, $display_order, $image_path);
            
            // Execute the prepared statement.
            if (mysqli_stmt_execute($stmt)) {
                // Log the activity using a prepared statement.
                $activity_desc = "Added new official: " . html_escape($fullname) . " (" . html_escape($position) . ")";
                $log_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'New Official')";
                if($stmt_log = mysqli_prepare($link, $log_sql)){
                    mysqli_stmt_bind_param($stmt_log, "s", $activity_desc);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }
                // Redirect to manage_officials.php with a success message.
                header("Location: manage_officials.php?status=success_add");
            } else {
                // If execution fails, redirect with a database error status.
                error_log("DB Error (Add Official): " . mysqli_stmt_error($stmt));
                header("Location: manage_officials.php?status=error_db");
            }
            // Close the statement.
            mysqli_stmt_close($stmt);
        } else {
            // If statement preparation fails, redirect with a preparation error status.
            error_log("DB Prepare Error (Add Official): " . mysqli_error($link));
            header("Location: manage_officials.php?status=error_prepare");
        }
    } else {
        // If required fields are missing, redirect with an error status.
        header("Location: manage_officials.php?status=error_missing_fields");
    }

// --- Process Action: EDIT OFFICIAL --- 
} elseif ($action == 'edit') {
    // Check if required fields (official_id, fullname, position) are submitted.
    if (isset($_POST['official_id'], $_POST['fullname'], $_POST['position'])) {
        // Sanitize and retrieve form data.
        $official_id = mysqli_real_escape_string($link, $_POST['official_id']);
        $fullname = mysqli_real_escape_string($link, $_POST['fullname']);
        $position = mysqli_real_escape_string($link, $_POST['position']);
        $gender = isset($_POST['gender']) ? mysqli_real_escape_string($link, $_POST['gender']) : '';
        $term_start_date = !empty($_POST['term_start_date']) ? mysqli_real_escape_string($link, $_POST['term_start_date']) : NULL;
        $term_end_date = !empty($_POST['term_end_date']) ? mysqli_real_escape_string($link, $_POST['term_end_date']) : NULL;
        $contact_number = !empty($_POST['contact_number']) ? mysqli_real_escape_string($link, $_POST['contact_number']) : NULL;
        $display_order = isset($_POST['display_order']) && is_numeric($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

        // Get current image path
        $current_image_path = null;
        $img_query = "SELECT image_path FROM officials WHERE id = ?";
        if ($img_stmt = mysqli_prepare($link, $img_query)) {
            mysqli_stmt_bind_param($img_stmt, "i", $official_id);
            mysqli_stmt_execute($img_stmt);
            $img_result = mysqli_stmt_get_result($img_stmt);
            if ($img_row = mysqli_fetch_assoc($img_result)) {
                $current_image_path = $img_row['image_path'];
            }
            mysqli_stmt_close($img_stmt);
        }

        // Handle image upload
        $image_path = $current_image_path; // Keep current image by default
        if (isset($_FILES['official_image'])) {
            $image_path = handleImageUpload($_FILES['official_image'], $current_image_path);
        }

        // Validate required fields and gender value.
        $allowed_genders = ['Male', 'Female', 'Other'];
        if (empty($official_id) || empty($fullname) || empty($position) || empty($gender) || !in_array($gender, $allowed_genders)) {
            header("Location: manage_officials.php?status=error_validation");
            exit;
        }

        // SQL query to update an existing official with image_path using a prepared statement.
        $sql = "UPDATE officials SET fullname = ?, gender = ?, position = ?, term_start_date = ?, term_end_date = ?, contact_number = ?, display_order = ?, image_path = ? WHERE id = ?";

        // Prepare the SQL statement.
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters to the prepared statement.
            mysqli_stmt_bind_param($stmt, "ssssssisi", $fullname, $gender, $position, $term_start_date, $term_end_date, $contact_number, $display_order, $image_path, $official_id);
            
            // Execute the prepared statement.
            if (mysqli_stmt_execute($stmt)) {
                // Log the activity using a prepared statement.
                $activity_desc = "Updated official details for ID: " . html_escape($official_id) . " (" . html_escape($fullname) . ")";
                $log_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'Update Official')";
                if($stmt_log = mysqli_prepare($link, $log_sql)){
                    mysqli_stmt_bind_param($stmt_log, "s", $activity_desc);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }
                // Redirect with a success message.
                header("Location: manage_officials.php?status=success_update");
            } else {
                // If execution fails, redirect with a database error status.
                error_log("DB Error (Edit Official): " . mysqli_stmt_error($stmt));
                header("Location: manage_officials.php?status=error_db");
            }
            // Close the statement.
            mysqli_stmt_close($stmt);
        } else {
            // If statement preparation fails, redirect.
            error_log("DB Prepare Error (Edit Official): " . mysqli_error($link));
            header("Location: manage_officials.php?status=error_prepare");
        }
    } else {
        // If required fields are missing, redirect.
        header("Location: manage_officials.php?status=error_missing_fields");
    }

// --- Process Action: DELETE OFFICIAL --- 
} elseif ($action == 'delete') {
    // Check if the official ID is provided in the GET request.
    if (isset($_GET['id'])) {
        $official_id = mysqli_real_escape_string($link, $_GET['id']);

        // Retrieve official's details for logging and image deletion before deletion.
        $name_query_sql = "SELECT fullname, position, image_path FROM officials WHERE id = ?";
        $official_details = "ID: " . html_escape($official_id);
        $image_to_delete = null;
        if ($name_stmt = mysqli_prepare($link, $name_query_sql)) {
            mysqli_stmt_bind_param($name_stmt, "i", $official_id);
            mysqli_stmt_execute($name_stmt);
            $result = mysqli_stmt_get_result($name_stmt);
            if($name_row = mysqli_fetch_assoc($result)) {
                $official_details = html_escape($name_row['fullname']) . " (" . html_escape($name_row['position']) . ")";
                $image_to_delete = $name_row['image_path'];
            }
            mysqli_stmt_close($name_stmt);
        }

        // SQL query to delete an official using a prepared statement.
        $sql = "DELETE FROM officials WHERE id = ?";

        // Prepare the SQL statement.
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind the official ID parameter.
            mysqli_stmt_bind_param($stmt, "i", $official_id);
            
            // Execute the prepared statement.
            if (mysqli_stmt_execute($stmt)) {
                // Delete associated image file if exists
                if ($image_to_delete && file_exists($image_to_delete)) {
                    unlink($image_to_delete);
                }
                
                // Log the activity using a prepared statement.
                $activity_desc = "Deleted official: " . $official_details;
                $log_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'Delete Official')";
                if($stmt_log = mysqli_prepare($link, $log_sql)){
                    mysqli_stmt_bind_param($stmt_log, "s", $activity_desc);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }
                // Redirect with a success message.
                header("Location: manage_officials.php?status=success_delete");
            } else {
                // If execution fails, redirect with a database error status.
                error_log("DB Error (Delete Official): " . mysqli_stmt_error($stmt));
                header("Location: manage_officials.php?status=error_db");
            }
            // Close the statement.
            mysqli_stmt_close($stmt);
        } else {
            // If statement preparation fails, redirect.
            error_log("DB Prepare Error (Delete Official): " . mysqli_error($link));
            header("Location: manage_officials.php?status=error_prepare");
        }
    } else {
        // If official ID is missing, redirect.
        header("Location: manage_officials.php?status=error_missing_id");
    }

// --- Invalid Action --- 
} else {
    // If no valid action (add, edit, delete) is specified, redirect to the main officials page.
    header("Location: manage_officials.php");
    exit; // Terminate script execution.
}

// --- Close Database Connection ---
// Check if the $link variable exists, is a resource, and is a MySQL link type before closing.
// This prevents errors if the connection was not established or already closed.
if (isset($link) && is_resource($link) && get_resource_type($link) === 'mysql link') {
    mysqli_close($link);
}
?>
