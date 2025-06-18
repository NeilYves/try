<?php
// --- Barangay Management System Diagnostics Page ---
// This script performs various checks on the database and system components
// to help diagnose potential issues. It's intended for administrative or development use.
// It outputs results directly to the browser as an HTML page.

require_once 'config.php'; // Includes database connection ($link) and html_escape() function.


echo "<h1><i class='fas fa-stethoscope'></i> Database & System Diagnostics</h1>"; // Page Title

// --- 1. Database Connection Test ---
// Checks if the application can successfully connect to the configured database.
echo "<h2><i class='fas fa-database'></i> 1. Database Connection</h2>";
if ($link) {
    echo "<p style='color:green'>✓ Successfully connected to database: <strong>" . html_escape(DB_NAME) . "</strong> on server <strong>" . html_escape(DB_SERVER) . "</strong></p>";
} else {
    echo "<p style='color:red'>✗ Failed to connect to database. Please check your <strong>config.php</strong> settings and ensure the database server is running.</p>";
    exit; // Stop further diagnostics if connection fails.
}

// --- 2. Database Table Listing ---
// Lists all tables found in the connected database.
echo "<h2><i class='fas fa-table'></i> 2. Database Tables</h2>";
$tables_result = mysqli_query($link, "SHOW TABLES");
if ($tables_result) {
    echo "<ul>";
    $table_count = 0;
    while ($row = mysqli_fetch_row($tables_result)) {
        echo "<li>" . html_escape($row[0]) . "</li>"; // Escape table name
        $table_count++;
    }
    echo "</ul>";
    echo "<p>Total tables found: <strong>" . $table_count . "</strong></p>";
} else {
    echo "<p style='color:red'>✗ Error listing tables: " . html_escape(mysqli_error($link)) . "</p>";
}

// --- 3. Residents Table Diagnostics ---
// Checks the 'residents' table for record count, structure, and sample data.
echo "<h2><i class='fas fa-users'></i> 3. Residents Table (<code>residents</code>)</h2>";
$residents_result = mysqli_query($link, "SELECT COUNT(*) as count FROM residents");
if ($residents_result) {
    $residents_count = mysqli_fetch_assoc($residents_result);
    echo "<p>Total records in <code>residents</code> table: <strong>" . html_escape($residents_count['count']) . "</strong></p>";
    
    // Check structure
    echo "<h3>Table Structure</h3>";
    $structure = mysqli_query($link, "DESCRIBE residents");
    if ($structure) {
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($field = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            echo "<td>" . html_escape($field['Field']) . "</td>";
            echo "<td>" . html_escape($field['Type']) . "</td>";
            echo "<td>" . html_escape($field['Null']) . "</td>";
            echo "<td>" . html_escape($field['Key']) . "</td>";
            echo "<td>" . html_escape((string)$field['Default']) . "</td>";
            echo "<td>" . html_escape($field['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Sample data
    echo "<h3>Sample Data (5 rows)</h3>";
    $sample = mysqli_query($link, "SELECT * FROM residents LIMIT 5");
    if ($sample && mysqli_num_rows($sample) > 0) {
        $fields = mysqli_fetch_fields($sample);
        echo "<table border='1' cellpadding='5'><tr>";
        foreach ($fields as $field) {
            echo "<th>" . html_escape($field->name) . "</th>";
        }
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($sample)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . html_escape((string)$value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No sample data found in <code>residents</code> table or an error occurred.</p>";
    }
} else {
    echo "<p style='color:red'>✗ Error checking <code>residents</code> table: " . html_escape(mysqli_error($link)) . "</p>";
}

// --- 4. Officials Table Diagnostics ---
// Checks the 'officials' table for record count, structure, and sample data.
echo "<h2><i class='fas fa-user-tie'></i> 4. Officials Table (<code>officials</code>)</h2>";
$officials_result = mysqli_query($link, "SELECT COUNT(*) as count FROM officials");
if ($officials_result) {
    $officials_count = mysqli_fetch_assoc($officials_result);
    echo "<p>Total records in <code>officials</code> table: <strong>" . html_escape($officials_count['count']) . "</strong></p>";
    
    // Check structure
    echo "<h3>Table Structure (<code>DESCRIBE officials</code>)</h3>";
    $structure = mysqli_query($link, "DESCRIBE officials");
    if ($structure) {
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($field = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            echo "<td>" . html_escape($field['Field']) . "</td>";
            echo "<td>" . html_escape($field['Type']) . "</td>";
            echo "<td>" . html_escape($field['Null']) . "</td>";
            echo "<td>" . html_escape($field['Key']) . "</td>";
            echo "<td>" . html_escape((string)$field['Default']) . "</td>";
            echo "<td>" . html_escape($field['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Sample data
    echo "<h3>Sample Data (<code>SELECT * FROM officials LIMIT 5</code>)</h3>";
    $sample = mysqli_query($link, "SELECT * FROM officials LIMIT 5");
    if ($sample && mysqli_num_rows($sample) > 0) {
        $fields = mysqli_fetch_fields($sample);
        echo "<table border='1' cellpadding='5'><tr>";
        foreach ($fields as $field) {
            echo "<th>" . html_escape($field->name) . "</th>";
        }
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($sample)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . html_escape((string)$value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No sample data found in <code>officials</code> table or an error occurred.</p>";
    }
} else {
    echo "<p style='color:red'>✗ Error checking <code>officials</code> table: " . html_escape(mysqli_error($link)) . "</p>";
}

// --- 5. JavaScript Counter Animation Test ---
// This section includes a simple JavaScript snippet to test the functionality
// of a number counting animation, which might be used on the dashboard.
echo "<h2><i class='fas fa-tachometer-alt'></i> 5. Counter JavaScript Test</h2>";
echo "<p>Testing counter animation with different values:</p>";
echo '<div id="test-counter" class="count-number" data-target="42" style="font-size: 2em; font-weight: bold;">0</div>'; // Added some style for visibility
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Self-invoking function to animate the counter for the test element.
    // This demonstrates the counter animation used elsewhere (e.g., dashboard stats).
    function animateTestCounter() {
        const counter = document.getElementById("test-counter");
        const target = parseInt(counter.getAttribute("data-target") || "0");
        const speed = 200;
        let count = 0;
        
        const updateCounter = () => {
            const increment = Math.trunc(target / speed);
            
            if (count < target) {
                count += increment;
                counter.innerText = count;
                setTimeout(updateCounter, 1); // Call recursively with a small delay for animation effect
            } else {
                counter.innerText = target; // Ensure final target value is set
            }
        };
        
        updateCounter(); // Initial call to start the animation
    }
    
    // Only run if the test counter element exists
    if (document.getElementById("test-counter")) {
        animateTestCounter();
    }
});
</script>';

// --- End of Diagnostics Script ---
echo "<p style='margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center;'>Diagnostics script finished.</p>";

