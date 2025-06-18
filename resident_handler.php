<?php
// --- Enhanced Resident Data Handler ---
// This script processes requests for adding, editing, and deleting resident records.
// It interacts with the database and redirects the user with status messages.

// Include the database configuration file, which establishes $link connection.
require_once 'config.php';

// Start session to use session messages
session_start();

// Function to send JSON response for AJAX requests (e.g., validation feedback)
function sendJsonResponse($status, $message, $errors = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'errors' => $errors]);
    exit;
}

// Initialize the action variable.
$action = '';

// --- Determine Action ---
// The script determines the requested action (add, edit, delete) from POST or GET parameters.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Action from a POST request (typically from a form submission for add/edit).
    $action = $_POST['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // Action from a GET request (typically from a link for delete).
    $action = $_GET['action'];
}

// --- Process Actions ---
// A conditional block to handle different actions based on the $action variable.

if ($action == 'add' || $action == 'edit') {
    $errors = [];

    // Sanitize and validate inputs
    $resident_id = ($action == 'edit' && isset($_POST['resident_id'])) ? mysqli_real_escape_string($link, $_POST['resident_id']) : null;
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $gender_other = trim($_POST['gender_other'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');
    $maintenance_medicine = trim($_POST['maintenance_medicine'] ?? '');
    $other_medicine = trim($_POST['other_medicine'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $educational_attainment = trim($_POST['educational_attainment'] ?? '');
    $family_planning = trim($_POST['family_planning'] ?? 'Not Applicable');
    $no_maintenance = trim($_POST['no_maintenance'] ?? 'No');
    $water_source = trim($_POST['water_source'] ?? '');
    $toilet_facility = trim($_POST['toilet_facility'] ?? '');
    $pantawid_4ps = trim($_POST['pantawid_4ps'] ?? 'No');
    $backyard_gardening = trim($_POST['backyard_gardening'] ?? 'No');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $purok_id = !empty($_POST['purok_id']) ? intval($_POST['purok_id']) : null;
    $date_status_changed = trim($_POST['date_status_changed'] ?? '');
    $status_remarks = trim($_POST['status_remarks'] ?? '');
    $household_id = !empty($_POST['household_id']) ? intval($_POST['household_id']) : null;

    // Server-side Validation
    if (empty($first_name)) {
        $errors['first_name'] = 'First Name is required.';
    }
    if (empty($last_name)) {
        $errors['last_name'] = 'Last Name is required.';
    }
    if (empty($gender)) {
        $errors['gender'] = 'Gender is required.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = 'Invalid Gender selected.';
    }
    if (empty($civil_status)) {
        $errors['civil_status'] = 'Civil Status is required.';
    } elseif (!in_array($civil_status, ['Single', 'Married', 'Widow/er', 'Separated'])) {
        $errors['civil_status'] = 'Invalid Civil Status selected.';
    }
    if (empty($water_source)) {
        $errors['water_source'] = 'Water Source is required.';
    } elseif (!in_array($water_source, ['Level 0 - Deepwell', 'Level 1 - Point Source', 'Level 2 - Communal Faucet', 'Level 3 - Individual Connection', 'Others'])) {
        $errors['water_source'] = 'Invalid Water Source selected.';
    }
    if (empty($purok_id)) {
        $errors['purok_id'] = 'Purok is required.';
    }

    // Simple contact number validation (digits only, adjustable length)
    if (!empty($contact_number) && !preg_match('/^\d{10,15}$/', $contact_number)) {
        $errors['contact_number'] = 'Please enter a valid contact number (10-15 digits).';
    }

    // Validate status and educational attainment against allowed values (if applicable)
    $allowed_statuses = ['Active', 'Deceased', 'Moved Out'];
    if (!in_array($status, $allowed_statuses)) {
        $errors['status'] = 'Invalid status selected.';
    }
    $allowed_education = ['No Formal Education', 'Elementary', 'Elementary Graduate', 'High School', 'High School Graduate', 'Vocational', 'College', 'College Graduate', 'Post Graduate'];
    if (!empty($educational_attainment) && !in_array($educational_attainment, $allowed_education)) {
        $errors['educational_attainment'] = 'Invalid educational attainment selected.';
    }

    // If there are validation errors, redirect back or send JSON response
    if (!empty($errors)) {
        $_SESSION['error'] = 'Please correct the errors in the form.';
        $_SESSION['form_data'] = $_POST; // Persist form data
        $_SESSION['validation_errors'] = $errors; // Store errors
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // AJAX request
            sendJsonResponse('error', 'Validation failed.', $errors);
        } else {
            // Regular form submission
            $redirect_url = ($action == 'edit') ? "resident_form.php?action=edit&id=$resident_id" : "resident_form.php?action=add";
            header("Location: " . $redirect_url);
            exit;
        }
    }

    // All inputs are valid, proceed with database operations
    if ($action == 'add') {
        // SQL query to insert a new resident with all enhanced fields
        $sql = "INSERT INTO residents (
            first_name, middle_name, last_name, suffix, gender, gender_other, civil_status, maintenance_medicine, other_medicine, birthdate, age,
            educational_attainment, family_planning, no_maintenance, water_source, toilet_facility,
            pantawid_4ps, backyard_gardening, contact_number, status, purok_id,
            date_status_changed, status_remarks, household_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Prepare the SQL statement
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters (24 parameters total)
            mysqli_stmt_bind_param($stmt, "ssssssssssississssssissi", 
                $first_name, $middle_name, $last_name, $suffix, $gender, $gender_other, $civil_status, $maintenance_medicine, $other_medicine, $birthdate, $age,
                $educational_attainment, $family_planning, $no_maintenance, $water_source, $toilet_facility,
                $pantawid_4ps, $backyard_gardening, $contact_number, $status, $purok_id,
                $date_status_changed, $status_remarks, $household_id
            );
            
            // Execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Record activity
                $activity_desc = "Added new resident: " . html_escape($first_name . ' ' . $last_name);
                $activity_type = "New Resident";
                $activity_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, ?)";
                if ($activity_stmt = mysqli_prepare($link, $activity_sql)) {
                    mysqli_stmt_bind_param($activity_stmt, "ss", $activity_desc, $activity_type);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }
                $_SESSION['success'] = 'Resident added successfully!';
                header("Location: manage_residents.php");
            } else {
                $_SESSION['error'] = 'Database error: Could not add resident.';
                error_log("Resident Add DB Error: " . mysqli_error($link));
                header("Location: resident_form.php?action=add");
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = 'Database error: Could not prepare statement for add.';
            error_log("Resident Add Prepare Error: " . mysqli_error($link));
            header("Location: resident_form.php?action=add");
        }
    } elseif ($action == 'edit') {
        // --- EDIT EXISTING RESIDENT --- //
        // Check if resident_id is provided for edit action
        if (is_null($resident_id)) {
            $_SESSION['error'] = 'Resident ID is missing for edit.';
            header("Location: manage_residents.php");
            exit;
        }

        // SQL query to update existing resident with all enhanced fields
        $sql = "UPDATE residents SET 
            first_name = ?, middle_name = ?, last_name = ?, suffix = ?, gender = ?, gender_other = ?, civil_status = ?, maintenance_medicine = ?, other_medicine = ?, 
            birthdate = ?, age = ?, educational_attainment = ?, family_planning = ?, no_maintenance = ?, 
            water_source = ?, toilet_facility = ?, pantawid_4ps = ?, backyard_gardening = ?, 
            contact_number = ?, status = ?, purok_id = ?, 
            date_status_changed = ?, status_remarks = ?, household_id = ? 
            WHERE id = ?";

        // Prepare the SQL statement
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind parameters (25 parameters total - 24 fields + 1 for WHERE clause)
            mysqli_stmt_bind_param($stmt, "ssssssssssississssssissii", 
                $first_name, $middle_name, $last_name, $suffix, $gender, $gender_other, $civil_status, $maintenance_medicine, $other_medicine, $birthdate, $age,
                $educational_attainment, $family_planning, $no_maintenance, $water_source, $toilet_facility,
                $pantawid_4ps, $backyard_gardening, $contact_number, $status, $purok_id,
                $date_status_changed, $status_remarks, $household_id, $resident_id
            );
            
            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Record activity
                $activity_desc = "Updated resident details for ID: " . html_escape($resident_id) . " (" . html_escape($first_name . ' ' . $last_name) . ")";
                $activity_type = "Update Resident";
                $activity_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, ?)";
                if ($activity_stmt = mysqli_prepare($link, $activity_sql)) {
                    mysqli_stmt_bind_param($activity_stmt, "ss", $activity_desc, $activity_type);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }
                $_SESSION['success'] = 'Resident updated successfully!';
                header("Location: manage_residents.php");
                exit; // Add explicit exit after redirect
            } else {
                $_SESSION['error'] = 'Database error: Could not update resident.';
                error_log("Resident Update DB Error: " . mysqli_error($link));
                header("Location: resident_form.php?action=edit&id=$resident_id");
                exit; // Add explicit exit after redirect
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = 'Database error: Could not prepare statement for edit.';
            error_log("Resident Update Prepare Error: " . mysqli_error($link));
            header("Location: resident_form.php?action=edit&id=$resident_id");
            exit; // Add explicit exit after redirect
        }
    }

} elseif ($action == 'delete') {
    // --- DELETE RESIDENT --- //
    // Check if the 'id' of the resident to be deleted is provided via GET request.
    if (isset($_GET['id'])) {
        $resident_id = mysqli_real_escape_string($link, $_GET['id']);

        // Optional: Fetch resident's name before deleting for a more descriptive activity log.
        // Using prepared statement for improved security
        $resident_name_for_log = "ID: " . html_escape($resident_id); // Default log message part
        $name_sql = "SELECT first_name, last_name FROM residents WHERE id = ?";
        if ($name_stmt = mysqli_prepare($link, $name_sql)) {
            mysqli_stmt_bind_param($name_stmt, "i", $resident_id);
            mysqli_stmt_execute($name_stmt);
            mysqli_stmt_bind_result($name_stmt, $first_name_del, $last_name_del);
            if (mysqli_stmt_fetch($name_stmt)) {
                $resident_name_for_log = html_escape($first_name_del . ' ' . $last_name_del); // Use actual name if found
            }
            mysqli_stmt_close($name_stmt);
        }

        // SQL query to delete a resident by ID.
        $sql = "DELETE FROM residents WHERE id = ?";

        // Prepare the SQL statement.
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind the resident ID parameter. 'i' denotes an integer.
            mysqli_stmt_bind_param($stmt, "i", $resident_id);
            
            // Execute the statement.
            if (mysqli_stmt_execute($stmt)) {
                // Record this activity using prepared statement for improved security.
                $activity_desc = "Deleted resident: " . $resident_name_for_log;
                $activity_type = "Delete Resident";
                $activity_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, ?)";
                if ($activity_stmt = mysqli_prepare($link, $activity_sql)) {
                    mysqli_stmt_bind_param($activity_stmt, "ss", $activity_desc, $activity_type);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }
                // Redirect with a success message.
                $_SESSION['success'] = 'Resident deleted successfully!';
                header("Location: manage_residents.php");
            } else {
                $_SESSION['error'] = 'Database error: Could not delete resident.';
                error_log("Resident Delete DB Error: " . mysqli_error($link));
                header("Location: manage_residents.php");
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = 'Database error: Could not prepare statement for delete.';
            error_log("Resident Delete Prepare Error: " . mysqli_error($link));
            header("Location: manage_residents.php");
        }
    } else {
        // Redirect if resident ID is missing.
        $_SESSION['error'] = 'Resident ID is missing for delete.';
        header("Location: manage_residents.php");
    }

} else {
    // If no valid action is specified, redirect to a default page or show an error.
    $_SESSION['error'] = 'Invalid action specified.';
    header("Location: manage_residents.php");
}

// Close database connection
mysqli_close($link);
exit;

?>
