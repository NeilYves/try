<?php
// --- Remove Duplicate Residents Script ---
// This script identifies and removes duplicate resident entries based on fullname

// Include the database configuration file, which establishes $link connection.
require_once 'config.php';

// Check if this is a POST request to actually remove duplicates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_duplicates'])) {
    // Find and remove duplicates, keeping the earliest registration
    $remove_sql = "DELETE r1 FROM residents r1
                   INNER JOIN residents r2 
                   WHERE r1.id > r2.id 
                   AND r1.fullname = r2.fullname 
                   AND r1.birthdate = r2.birthdate";
    
    // Execute the SQL statement
    $result = mysqli_query($link, $remove_sql);
    
    // Check if the query was successful
    if ($result) {
        // Count the number of affected rows (i.e. the number of duplicates removed)
        $removed_count = mysqli_affected_rows($link);
        
        // Display a success message
        $message = "<div class='alert alert-success'>Successfully removed $removed_count duplicate resident(s)!</div>";
    } else {
        // Display an error message
        $message = "<div class='alert alert-danger'>Error removing duplicates: " . mysqli_error($link) . "</div>";
    }
}

// Find duplicates
$duplicate_sql = "SELECT fullname, birthdate, COUNT(*) as count, 
                         GROUP_CONCAT(id ORDER BY id) as ids,
                         GROUP_CONCAT(registration_date ORDER BY id) as reg_dates
                  FROM residents 
                  GROUP BY fullname, birthdate 
                  HAVING COUNT(*) > 1
                  ORDER BY fullname";

// Execute the SQL statement
$duplicate_result = mysqli_query($link, $duplicate_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Duplicate Residents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-users-slash me-2"></i>Remove Duplicate Residents
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)) echo $message; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will permanently remove duplicate resident entries. 
                            The oldest registration will be kept, and newer duplicates will be deleted.
                        </div>

                        <?php if ($duplicate_result && mysqli_num_rows($duplicate_result) > 0): ?>
                            <h5 class="text-danger mb-3">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Found <?php echo mysqli_num_rows($duplicate_result); ?> duplicate resident group(s):
                            </h5>
                            
                            <div class="table-responsive mb-4">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Birthdate</th>
                                            <th>Count</th>
                                            <th>IDs (oldest first)</th>
                                            <th>Registration Dates</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($duplicate = mysqli_fetch_assoc($duplicate_result)): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($duplicate['fullname']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($duplicate['birthdate']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?php echo $duplicate['count']; ?> entries
                                                    </span>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($duplicate['ids']); ?></code>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($duplicate['reg_dates']); ?></small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove all duplicate residents? This action cannot be undone. The oldest registration for each person will be kept.');">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="manage_residents.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Residents
                                    </a>
                                    <button type="submit" name="remove_duplicates" class="btn btn-danger">
                                        <i class="fas fa-trash me-2"></i>Remove All Duplicates
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Great!</strong> No duplicate residents found in the database.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_residents.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Residents
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($link); ?>