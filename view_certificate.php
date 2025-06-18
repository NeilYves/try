<?php
// --- View Certificate Page ---
// Fetches certificate data and includes the correct template to display it.
// Adds controls for printing and inline editing of the certificate content.

require_once 'config.php'; 

// Validate Input: Certificate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: Issued Certificate ID not provided.');
}
$issued_certificate_id = (int)$_GET['id'];

// --- Fetch All Necessary Data ---

// Fetch system settings for logos
$system_settings = [];
$settings_query = "SELECT setting_key, setting_value FROM system_settings";
$settings_result = mysqli_query($link, $settings_query);
if ($settings_result) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch Issued Certificate, Resident, and Type Data
$sql = "SELECT 
            ic.*,
            r.first_name, r.middle_name, r.last_name, r.suffix, r.civil_status, r.gender,
            p.purok_name,
            ct.name as certificate_type_name, ct.template_file
        FROM issued_certificates ic
        JOIN residents r ON ic.resident_id = r.id
        JOIN certificate_types ct ON ic.certificate_type_id = ct.id
        LEFT JOIN puroks p ON r.purok_id = p.id
        WHERE ic.id = ?";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $issued_certificate_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$certificate = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$certificate) {
    die('Error: Certificate not found.');
}

// Fetch Punong Barangay from Officials
$punong_barangay_sql = "SELECT fullname FROM officials WHERE position = 'Punong Barangay' LIMIT 1";
$punong_barangay_result = mysqli_query($link, $punong_barangay_sql);
$punong_barangay_row = mysqli_fetch_assoc($punong_barangay_result);
$punong_barangay = $punong_barangay_row ? $punong_barangay_row['fullname'] : 'HON. [Punong Barangay Name Not Set]';

// --- Prepare Data for the Template ---
$resident_name_parts = array_filter([$certificate['first_name'], $certificate['middle_name'], $certificate['last_name'], $certificate['suffix']]);
$resident_fullname = strtoupper(implode(' ', $resident_name_parts));

$full_address = 'Purok ' . ($certificate['purok_name'] ?? '[Purok Not Set]') . ', Barangay Central Glad';

$issue_date = new DateTime($certificate['issue_date']);

// This array bundles all the data the template will need.
$certificate_data = [
    'resident_name'         => $resident_fullname,
    'resident_civil_status' => $certificate['civil_status'] ?? 'N/A',
    'gender'                => $certificate['gender'] ?? 'N/A',
    'resident_address'      => $full_address,
    'day'                   => $issue_date->format('jS'),
    'month'                 => $issue_date->format('F'),
    'year'                  => $issue_date->format('Y'),
    'punong_barangay'       => $punong_barangay,
    'barangay_logo_path'    => $system_settings['barangay_logo_path'] ?? null,
    'municipality_logo_path'=> $system_settings['municipality_logo_path'] ?? null,
    'system_settings'       => $system_settings, // Pass all settings for template flexibility

    // For template-specific fields that might not be in the DB yet
    'purpose' => $certificate['purpose'] ?? 'any legal purpose',
    'or_number' => $certificate['or_number'] ?? null,
    'fee_paid' => $certificate['amount_paid'] ?? null,
    'control_number' => $certificate['control_no'] ?? 'N/A',
    'resident_birthdate' => $certificate['birthdate'] ?? null,
    'issuing_official_fullname' => $punong_barangay,
    'issuing_official_position' => 'Punong Barangay'
];

// Determine Template File
$template_file_path = 'templates/' . $certificate['template_file'];
if (!file_exists($template_file_path)) {
    die('Error: Certificate template file not found: ' . html_escape($template_file_path));
}

// --- Render Page ---
// The following is the HTML shell that will contain the certificate template.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Certificate - <?php echo html_escape($certificate['certificate_type_name']); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background-color: #e9ecef; }
        .control-panel {
            padding: 15px;
            background-color: #343a40;
            color: white;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 8.5in;
        }
        .editable {
            background-color: #fff8e1;
            padding: 2px 5px;
            border-radius: 3px;
            border: 1px dashed #ffd54f;
            cursor: pointer;
        }
        .editable:focus {
            background-color: #ffffff;
            outline: 2px solid #007bff;
            border-color: transparent;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; }
            .certificate-container { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="control-panel no-print">
        <h5 class="mb-3">Certificate Controls</h5>
        <button onclick="window.print();" class="btn btn-primary">Print Certificate</button>
        <button id="saveChangesBtn" class="btn btn-success">Save Temporary Changes</button>
        <small class="d-block mt-2">To edit, simply click on the highlighted yellow fields in the certificate below. Changes are temporary and for printing purposes only.</small>
    </div>
</div>

<?php
// Include the actual certificate template
include $template_file_path;
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Make specific spans editable
    const editableSpans = document.querySelectorAll('.certificate-container .underline');
    editableSpans.forEach(span => {
        span.setAttribute('contenteditable', 'true');
        span.classList.add('editable');
    });

    // Save changes button logic
    const saveBtn = document.getElementById('saveChangesBtn');
    if(saveBtn) {
        saveBtn.addEventListener('click', function() {
            // This is a placeholder. For now, it just confirms that the command is registered.
            // A full implementation would save this data via AJAX to the server.
            alert('Changes have been noted for this session. They will be reflected on the printout. Note: These changes are not permanently saved to the database.');
            
            // To make the "saved" state visually clear, remove the highlight
            editableSpans.forEach(span => {
                span.classList.remove('editable');
                span.setAttribute('contenteditable', 'false'); // Lock after saving
            });
        });
    }
});
</script>

</body>
</html>
<?php
mysqli_close($link);
?>

