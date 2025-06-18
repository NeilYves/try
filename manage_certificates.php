<?php
// --- Manage Certificates Page ---
// This script displays a list of all issued certificates, allows searching/filtering,
// and provides links to issue new certificates or view/print existing ones.

// Set the page title for the header.
$page_title = 'Manage Certificates';
// Include the common header, which also establishes the database connection ($link).
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

// --- Handle Status Messages from Other Actions (e.g., after issuing a certificate) ---
$message = ''; // Initialize an empty message string.
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_issued') {
        // If a certificate was successfully issued, display a success message with its control number.
        $control_no = isset($_GET['control_no']) ? html_escape($_GET['control_no']) : ''; // Get and escape control number for display.
        $message = '<div class="alert alert-success" role="alert">Certificate ('. $control_no .') issued successfully!</div>';
    } elseif (strpos($_GET['status'], 'error') === 0) {
        // If an error occurred, display a generic error message with the specific error code (escaped).
        $message = '<div class="alert alert-danger" role="alert">An error occurred: '. html_escape(substr($_GET['status'], 6)) .'. Please try again.</div>';
    }
}

// --- Fetch Issued Certificates from Database ---
// Get the search query from GET parameter, if any, and sanitize it.
$search_query_display = isset($_GET['search']) ? $_GET['search'] : '';
$search_query_sql = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';

// Base SQL query to select certificate details along with related resident, certificate type, and official names.
$sql = "SELECT ic.id, ic.control_number, ic.issue_date, ic.purpose, 
               r.fullname as resident_name, ct.name as certificate_name, 
               o.fullname as issuing_official_name
        FROM issued_certificates ic
        JOIN residents r ON ic.resident_id = r.id
        JOIN certificate_types ct ON ic.certificate_type_id = ct.id
        LEFT JOIN officials o ON ic.issuing_official_id = o.id";

// --- Append Search Conditions if a Search Query Exists ---
// NOTE: The current search implementation uses string concatenation with LIKE '%...%'.
// This is vulnerable to SQL injection. For enhanced security, this should be refactored
// to use prepared statements with bound parameters for the search term.
$conditions = [];
$params = [];
$types = '';

if (!empty($search_query_sql)) {
    $search_term_like = '%' . $search_query_sql . '%';
    $conditions[] = "ic.control_number LIKE ?";
    $conditions[] = "r.fullname LIKE ?";
    $conditions[] = "ct.name LIKE ?";
    $conditions[] = "ic.purpose LIKE ?";
    for ($i = 0; $i < 4; $i++) {
        $params[] = $search_term_like;
        $types .= 's';
    }
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' OR ', $conditions);
}

// Order the results by issue date (descending) and then by ID (descending for tie-breaking).
$sql .= " ORDER BY ic.issue_date DESC, ic.id DESC";

// Execute the query.
if (!empty($conditions) && count($params) > 0) {
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $certificates_result = mysqli_stmt_get_result($stmt);
} else {
    $certificates_result = mysqli_query($link, $sql);
}

?>

<!-- Page Header and "Issue New Certificate" Button -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-file-alt me-2"></i><?php echo html_escape($page_title); // Display page title, escaped ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="issue_certificate_form.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Issue New Certificate
        </a>
    </div>
</div>

<?php echo $message; // Display any status messages (success/error) here ?>

<!-- Search Form -->
<form method="GET" action="manage_certificates.php" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by Control No, Resident, Type, Purpose..." value="<?php echo html_escape($search_query_display); // Display search query, escaped ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search_query_display)): // Show clear button if a search is active ?>
            <a href="manage_certificates.php" class="btn btn-outline-danger"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Issued Certificates List Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Issued Certificates List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive"> <!-- Ensures table is scrollable on small screens -->
            <table class="table table-hover"> <!-- Hover effect for table rows -->
                <thead class="table-light"> <!-- Light background for table header -->
                    <tr>
                        <th>Control No.</th>
                        <th>Date Issued</th>
                        <th>Resident Name</th>
                        <th>Certificate Type</th>
                        <th>Purpose</th>
                        <th>Issued By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($certificates_result && mysqli_num_rows($certificates_result) > 0): // Check if there are any certificates to display ?>
                        <?php while($cert = mysqli_fetch_assoc($certificates_result)): // Loop through each certificate record ?>
                            <tr>
                                <!-- Display certificate details, all escaped for security -->
                                <td><?php echo html_escape($cert['control_number']); ?></td>
                                <td><?php echo html_escape(date('M d, Y', strtotime($cert['issue_date']))); // Format date for readability ?></td>
                                <td><?php echo html_escape($cert['resident_name']); ?></td>
                                <td><?php echo html_escape($cert['certificate_name']); ?></td>
                                <td><?php echo nl2br(html_escape($cert['purpose'])); // Use nl2br to preserve line breaks in purpose ?></td>
                                <td><?php echo html_escape($cert['issuing_official_name'] ?? 'N/A'); // Display 'N/A' if issuing official is not set ?></td>
                                <td>
                                    <!-- Link to view/print the certificate (opens in a new tab) -->
                                    <a href="view_certificate.php?id=<?php echo html_escape($cert['id']); ?>" class="btn btn-sm btn-info me-1" title="View/Print" target="_blank">
                                        <i class="fas fa-print"></i> View
                                    </a>
                                    <!-- Placeholder for future Edit/Delete actions. -->
                                    <!-- Note: Deleting issued certificates might have legal/auditing implications. -->
                                    <!-- Editing might also be complex depending on how control numbers are managed. -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: // If no certificates are found (or none match search) ?>
                        <tr><td colspan="7" class="text-center">No certificates found<?php echo !empty($search_query_display) ? ' matching your search' : ''; ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Include the common footer for the page.
require_once 'includes/footer.php'; 
?>
