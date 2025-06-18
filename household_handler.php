<?php
// --- Household Handler ---
// This script processes requests to add, edit, or delete household records.

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // Sanitize and validate common fields
    $household_name = sanitize_input($_POST['household_name'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $head_of_household_id = $_POST['head_of_household_id'] ?? null;
    $contact_number = sanitize_input($_POST['contact_number'] ?? '');

    if (empty($household_name)) {
        header("Location: household_form.php?action=add&status=error&message=Household name is required.");
        exit;
    }

    if ($action == 'add') {
        // Add new household
        $stmt = mysqli_prepare($link, "INSERT INTO households (household_name, address, head_of_household_id, contact_number) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssis", $household_name, $address, $head_of_household_id, $contact_number);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_households.php?status=success");
        } else {
            header("Location: household_form.php?action=add&status=error&message=" . urlencode(mysqli_error($link)));
        }
        mysqli_stmt_close($stmt);

    } elseif ($action == 'edit') {
        // Edit existing household
        $household_id = $_POST['household_id'] ?? '';
        if (empty($household_id)) {
            header("Location: manage_households.php?status=error&message=Household ID is missing.");
            exit;
        }

        $stmt = mysqli_prepare($link, "UPDATE households SET household_name = ?, address = ?, head_of_household_id = ?, contact_number = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssisi", $household_name, $address, $head_of_household_id, $contact_number, $household_id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: manage_households.php?status=success");
        } else {
            header("Location: household_form.php?action=edit&id={$household_id}&status=error&message=" . urlencode(mysqli_error($link)));
        }
        mysqli_stmt_close($stmt);

    } else {
        header("Location: manage_households.php?status=error&message=Invalid action.");
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete') {
    // Delete household
    $household_id = $_GET['id'] ?? '';
    if (empty($household_id)) {
        header("Location: manage_households.php?status=error&message=Household ID is missing.");
        exit;
    }

    // Optional: Set residents' household_id to NULL before deleting household
    $update_residents_stmt = mysqli_prepare($link, "UPDATE residents SET household_id = NULL WHERE household_id = ?");
    mysqli_stmt_bind_param($update_residents_stmt, "i", $household_id);
    mysqli_stmt_execute($update_residents_stmt);
    mysqli_stmt_close($update_residents_stmt);

    $stmt = mysqli_prepare($link, "DELETE FROM households WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $household_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_households.php?status=success");
    } else {
        header("Location: manage_households.php?status=error&message=" . urlencode(mysqli_error($link)));
    }
    mysqli_stmt_close($stmt);

} else {
    header("Location: manage_households.php?status=error&message=Invalid request method.");
}

mysqli_close($link);
?> 