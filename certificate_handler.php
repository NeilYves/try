<?php
// --- Certificate Issuance Handler ---
// This script processes the submission from the certificate issuance form.
// It validates input, generates a unique control number, saves the certificate details to the database,
// logs the activity, and redirects the user to view/print the newly issued certificate.

// Include the database configuration file which establishes $link (database connection).
require_once 'config.php';

// --- Check Request Method and Action ---
// Ensure the script is accessed via a POST request and the 'action' is 'issue'.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'issue') {

    // --- Validate Required Fields ---
    // Define an array of fields that must be filled in the form.
    $required_fields = ['resident_id', 'certificate_type_id', 'purpose', 'issue_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            // If a required field is empty, redirect back to the form with an error status.
            // The specific missing field is indicated in the status for better user feedback.
            header("Location: issue_certificate_form.php?status=error_missing_" . html_escape($field));
            exit; // Terminate script execution.
        }
    }

    // --- Sanitize and Retrieve Form Data ---
    // Cast IDs to integers for security and type consistency.
    $resident_id = (int)$_POST['resident_id'];
    $certificate_type_id = (int)$_POST['certificate_type_id'];
    // Sanitize string inputs to prevent XSS if they were ever directly outputted without html_escape, and for general safety with SQL.
    $purpose = mysqli_real_escape_string($link, $_POST['purpose']);
    $issue_date = mysqli_real_escape_string($link, $_POST['issue_date']); // Date format should be validated on client/server side if strict format needed.
    
    // Optional fields: remarks. O.R. Number and Issuing official are handled on the template.
    $remarks = !empty($_POST['remarks']) ? mysqli_real_escape_string($link, $_POST['remarks']) : NULL;       // Allow NULL if empty.

    // --- Generate Control Number ---
    // The control number format is: CERTCODE-YYYY-MM-NNNN (e.g., COR-2023-12-0001).
    
    // 1. Get certificate type code (e.g., 'COR' for Certificate of Residency).
    // Prepare statement to fetch certificate type name to prevent SQL injection.
    $cert_type_sql = "SELECT name FROM certificate_types WHERE id = ?";
    $cert_type_code = 'CERT'; // Default code if type not found or name is unusual.
    if($stmt_cert_type = mysqli_prepare($link, $cert_type_sql)){
        mysqli_stmt_bind_param($stmt_cert_type, "i", $certificate_type_id);
        mysqli_stmt_execute($stmt_cert_type);
        $cert_type_result = mysqli_stmt_get_result($stmt_cert_type);
        if ($cert_type_result && $row = mysqli_fetch_assoc($cert_type_result)) {
            // Create a simple code from the first letter of each word in the certificate name.
            $words = explode(' ', $row['name']);
            $code_from_name = '';
            foreach ($words as $word) {
                if (!empty($word)) $code_from_name .= strtoupper(substr($word, 0, 1));
            }
            $cert_type_code = !empty($code_from_name) ? $code_from_name : 'CERT';
        }
        mysqli_stmt_close($stmt_cert_type);
    }

    // 2. Get year and month from the issue date.
    $year_month = date('Y-m', strtotime($issue_date)); // Converts issue_date to YYYY-MM format.
    $control_number_prefix = $cert_type_code . '-' . $year_month . '-';

    // 3. Find the next sequence number for this certificate type and month.
    // This query finds the highest sequence number used for the given prefix in the current month.
    $seq_sql = "SELECT MAX(CAST(SUBSTRING_INDEX(control_number, '-', -1) AS UNSIGNED)) as last_seq 
                FROM issued_certificates 
                WHERE control_number LIKE ?"; // Use prepared statement for LIKE
    $next_seq = 1; // Default sequence number starts at 1.
    if($stmt_seq = mysqli_prepare($link, $seq_sql)){
        $like_prefix = $control_number_prefix . '%';
        mysqli_stmt_bind_param($stmt_seq, "s", $like_prefix);
        mysqli_stmt_execute($stmt_seq);
        $seq_result = mysqli_stmt_get_result($stmt_seq);
        if ($seq_result && $seq_row = mysqli_fetch_assoc($seq_result)) {
            $next_seq = (int)$seq_row['last_seq'] + 1; // Increment the last sequence number.
        }
        mysqli_stmt_close($stmt_seq);
    }
    // Format the sequence number with leading zeros (e.g., 0001, 0012, 0123).
    $control_number = $control_number_prefix . str_pad($next_seq, 4, '0', STR_PAD_LEFT);

    // --- Save Certificate Details to Database ---
    // SQL query to insert the new certificate record using a prepared statement.
    $sql = "INSERT INTO issued_certificates (resident_id, certificate_type_id, control_number, issue_date, purpose, remarks) 
            VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare the SQL statement.
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind parameters to the prepared statement.
        mysqli_stmt_bind_param($stmt, "iissss", 
            $resident_id, 
            $certificate_type_id, 
            $control_number, 
            $issue_date, 
            $purpose, 
            $remarks
        );

        // Execute the prepared statement.
        if (mysqli_stmt_execute($stmt)) {
            $issued_certificate_id = mysqli_insert_id($link); // Get the ID of the newly inserted certificate record.

            // Log the activity.
            // Fetch resident's name for a more descriptive log.
            $resident_name = 'ID:' . $resident_id; // Default if name not found.
            $res_name_sql = "SELECT first_name, last_name FROM residents WHERE id = ?";
            if($stmt_res_name = mysqli_prepare($link, $res_name_sql)){
                mysqli_stmt_bind_param($stmt_res_name, "i", $resident_id);
                mysqli_stmt_execute($stmt_res_name);
                $res_name_result = mysqli_stmt_get_result($stmt_res_name);
                if ($res_name_result && $r_row = mysqli_fetch_assoc($res_name_result)) {
                    $resident_name = html_escape($r_row['first_name'] . ' ' . $r_row['last_name']);
                }
                mysqli_stmt_close($stmt_res_name);
            }
            
            $activity_desc = "Issued certificate ($control_number) to $resident_name. Purpose: " . html_escape(substr($purpose, 0, 50)) . "...";
            // Use prepared statement for inserting activity log.
            $log_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'Certificate Issued')";
            if($stmt_log = mysqli_prepare($link, $log_sql)){
                mysqli_stmt_bind_param($stmt_log, "s", $activity_desc);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);
            }

            // Redirect to the view_certificate.php page to display/print the certificate.
            header("Location: view_certificate.php?id=" . $issued_certificate_id);
            exit; // Terminate script execution.
        } else {
            // If database execution fails, redirect with an error message.
            error_log("DB Execute Error (Issue Cert): " . mysqli_stmt_error($stmt));
            header("Location: issue_certificate_form.php?status=error_db_execute&msg=" . urlencode(mysqli_stmt_error($stmt)));
            exit;
        }
        mysqli_stmt_close($stmt); // Close the main statement.
    } else {
        // If SQL statement preparation fails, redirect with an error message.
        error_log("DB Prepare Error (Issue Cert): " . mysqli_error($link));
        header("Location: issue_certificate_form.php?status=error_db_prepare&msg=" . urlencode(mysqli_error($link)));
        exit;
    }

} else {
    // If the script is accessed directly or with an invalid action, redirect to the certificate management page.
    header("Location: manage_certificates.php");
    exit; // Terminate script execution.
}

// --- Close Database Connection ---
// Check if the $link variable exists, is a resource, and is a MySQL link type before closing.
if (isset($link) && is_resource($link) && get_resource_type($link) === 'mysql link') {
    mysqli_close($link);
}
?>
