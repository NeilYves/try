<?php
require_once 'config.php';

/**
 * Renders a table of residents for a specific purok and age group.
 *
 * @param mysqli $link The database connection object.
 * @param int $purok_id The ID of the purok for which to render residents.
 * @param string $age_condition_sql The specific SQL condition for filtering by age.
 */
function render_resident_table_for_age_group($link, $purok_id, $age_condition_sql) {
    echo '<div class="table-responsive mb-4">';
    echo '<table class="table table-bordered table-striped">';
    echo '<thead class="table-light"><tr><th>ID</th><th>Fullname</th><th>Address</th><th>Gender</th><th>Age</th></tr></thead>';
    echo '<tbody>';

    $residents_query = "SELECT id, fullname, address, gender, birthdate FROM residents WHERE purok_id = ? AND " . $age_condition_sql . " ORDER BY fullname ASC";
    
    $stmt = mysqli_prepare($link, $residents_query);
    mysqli_stmt_bind_param($stmt, "i", $purok_id);
    mysqli_stmt_execute($stmt);
    $residents_result = mysqli_stmt_get_result($stmt);

    if ($residents_result && mysqli_num_rows($residents_result) > 0) {
        while ($resident = mysqli_fetch_assoc($residents_result)) {
            $age = 'N/A';
            if (!empty($resident['birthdate']) && $resident['birthdate'] != '0000-00-00') {
                $birthDate = new DateTime($resident['birthdate']);
                $today = new DateTime('today');
                $age = $birthDate->diff($today)->y;
            }
            echo '<tr>';
            echo '<td>' . html_escape($resident['id']) . '</td>';
            echo '<td>' . html_escape($resident['fullname']) . '</td>';
            echo '<td>' . html_escape($resident['address']) . '</td>';
            echo '<td>' . html_escape($resident['gender']) . '</td>';
            echo '<td>' . $age . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center text-muted">No residents found in this age group.</td></tr>';
    }
    echo '</tbody></table></div>';
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purok Resident Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { font-size: 10pt; }
            .no-print { display: none !important; }
            .print-container { width: 100% !important; box-shadow: none !important; border: none !important; }
            .purok-group { page-break-after: always; }
            .purok-group:last-child { page-break-after: auto; }
            .age-category-header { break-after: avoid; }
            .table-responsive { break-inside: avoid; }
        }
        body { background-color: #f0f2f5; }
        .print-container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .purok-main-header {
            background-color: #007bff;
            color: white;
            padding: 1rem;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 5px;
        }
        .age-category-header {
            font-size: 1.25rem;
            font-weight: 500;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            color: #495057;
        }
    </style>
</head>
<body>

<div class="print-container">
    <div class="text-center mb-5 no-print">
        <h2>Purok Resident Reports</h2>
        <p class="text-muted">As of <?php echo date('F j, Y'); ?></p>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>

    <?php
    // Fetch all puroks
    $puroks_query = "SELECT id, purok_name FROM puroks ORDER BY purok_name ASC";
    $puroks_result = mysqli_query($link, $puroks_query);

    if ($puroks_result && mysqli_num_rows($puroks_result) > 0) {
        $age_categories = [
            'Children (0-12)' => "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 0 AND 12",
            'Youth (13-24)' => "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 24",
            'Adults (25-59)' => "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 25 AND 59",
            'Seniors (60+)' => "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60",
        ];

        while ($purok = mysqli_fetch_assoc($puroks_result)) {
            echo '<div class="purok-group">';
            echo '<h2 class="purok-main-header">Purok: ' . html_escape($purok['purok_name']) . '</h2>';

            foreach ($age_categories as $title => $condition) {
                echo '<h5 class="age-category-header">' . $title . '</h5>';
                render_resident_table_for_age_group($link, $purok['id'], $condition);
            }
            echo '</div>'; // end .purok-group
        }
    } else {
        echo '<p class="text-center">No puroks found in the system.</p>';
    }
    ?>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html> 