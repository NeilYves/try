<?php
// --- SMS Blast Page ---
// This page provides a form for sending bulk SMS messages to residents.
// It allows administrators to send to all residents with contact numbers
// or select specific individuals. Messages are sent using the Infobip API.

// Set the page title for the header.
$page_title = 'SMS Blast';
// Include the common header which also establishes the database connection ($link).
require_once 'includes/header.php';

// Role-based access control
if ($_SESSION['role'] !== 'Barangay Secretary') {
    // Redirect to dashboard if not a secretary
    header("Location: index.php");
    exit;
}

// --- Handle Status Messages from Handler ---
// Process feedback messages passed from sms_blast_handler.php via GET parameters.
$message_feedback = ''; // Initialize empty message string.
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        // Success message with count of recipients included in the status
        $count = isset($_GET['count']) ? (int)$_GET['count'] : 0; // Cast to integer for security
        $message_feedback = '<div class="alert alert-success" role="alert">SMS Blast sent successfully to ' . $count . ' resident(s)!</div>';
    } elseif ($_GET['status'] == 'error_no_recipients') {
        // Warning when no valid recipients are found
        $message_feedback = '<div class="alert alert-warning" role="alert">No residents found with valid contact numbers.</div>';
    } elseif ($_GET['status'] == 'error_message_empty') {
        // Error when SMS message content is empty
        $message_feedback = '<div class="alert alert-danger" role="alert">Message cannot be empty.</div>';
    } elseif (strpos($_GET['status'], 'error') === 0) {
        // Generic error message for any other error conditions
        $message_feedback = '<div class="alert alert-danger" role="alert">An error occurred. Please try again.</div>';
    }
}

// --- Count Eligible Recipients ---
// Query to count how many residents have valid contact numbers.
// This is displayed for informational purposes and used in the "All residents" option.
$count_sql = "SELECT COUNT(id) as total_recipients FROM residents WHERE contact_number IS NOT NULL AND contact_number != ''";
$count_result = mysqli_query($link, $count_sql);
$total_recipients = 0; // Default value if query fails
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_recipients = $row['total_recipients'];
}
// TODO: Consider using prepared statements here for consistency with other queries,
// although this query doesn't use any user input and isn't vulnerable to injection.

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title"><i class="fas fa-bullhorn me-2"></i><?php echo html_escape($page_title); // Display page title, escaped ?></h1>
</div>

<?php echo $message_feedback; // Display success, warning, or error messages from handler ?>

<!-- SMS Recipient Information - Shows the count of eligible recipients -->
<div class="mb-3">
    <p class="mb-0">Currently, there are <strong><?php echo $total_recipients; ?></strong> resident(s) with contact numbers in the database.</p>
</div>

<!-- SMS Composition Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Compose SMS Message</h5>
    </div>
    <div class="card-body">
        <!-- Form submits to sms_blast_handler.php for processing -->
        <form action="sms_blast_handler.php" method="POST">
            <!-- SMS Message Content Field (Required) -->
            <div class="mb-3">
                <label for="sms_message" class="form-label">Message Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="sms_message" name="sms_message" rows="5" required 
                          placeholder="Enter your SMS message here..."></textarea>
                <small class="form-text text-muted">Standard SMS messages are typically limited to 160 characters. Longer messages may be split or incur additional costs depending on the SMS provider.</small>
            </div>
            
            <!-- Recipients Selection Section -->
            <div class="mb-3">
                <label for="recipients" class="form-label"><strong>Recipients:</strong></label>
                <!-- Search box for filtering recipients by name -->
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="recipient-search" 
                           placeholder="Search residents by name..." aria-label="Search residents">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <!-- Multiple selection dropdown for recipients -->
                <select class="form-select" id="recipients" name="recipients[]" multiple size="5" required>
                    <!-- Default option to send to all residents -->
                    <option value="all" selected>All residents with registered contact numbers (<?php echo $total_recipients; ?> recipient(s))</option>
                    <?php
                    // Fetch all residents with valid contact numbers
                    // TODO: Consider using prepared statements here for consistency with other queries
                    $residents_sql = "SELECT id, fullname, contact_number FROM residents WHERE contact_number IS NOT NULL AND contact_number != '' ORDER BY fullname ASC";
                    $residents_result = mysqli_query($link, $residents_sql);
                    
                    // Populate dropdown with residents and their contact numbers
                    if ($residents_result && mysqli_num_rows($residents_result) > 0) {
                        while ($resident = mysqli_fetch_assoc($residents_result)) {
                            // Escape output to prevent XSS
                            echo '<option value="' . $resident['id'] . '">' . html_escape($resident['fullname']) . ' - ' . html_escape($resident['contact_number']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <!-- Help text for recipient selection -->
                <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple specific residents, or choose "All residents" option. Use the search box to filter by name.</small>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="send_blast" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>Send SMS Blast
            </button>
        </form>
    </div>
</div>

<!-- Custom JavaScript for the SMS recipient search functionality -->
<script>
// Initialize event handlers when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get references to relevant DOM elements
    const searchInput = document.getElementById('recipient-search');
    const clearButton = document.getElementById('clear-search');
    const recipientSelect = document.getElementById('recipients');
    const allOptions = Array.from(recipientSelect.options); // Convert options collection to array for easier manipulation
    
    /**
     * Function to filter recipient dropdown options based on search text input
     * - Shows/hides options based on whether they match the search text
     * - Always keeps the "All residents" option visible
     * - Displays a "No results" message when appropriate
     */
    function filterRecipients() {
        const searchText = searchInput.value.toLowerCase();
        
        // Step 1: Hide all options first
        allOptions.forEach(option => {
            option.style.display = 'none';
        });
        
        // Step 2: Show options that match the search criteria
        const filteredOptions = allOptions.filter(option => {
            // Always keep the 'All residents' option visible
            if (option.value === 'all') {
                option.style.display = '';
                return true;
            }
            
            // Filter by name (case-insensitive)
            if (option.text.toLowerCase().includes(searchText)) {
                option.style.display = '';
                return true;
            }
            
            return false;
        });
        
        // Step 3: Handle the case when no individual recipients match the search
        if (filteredOptions.length === 1 && filteredOptions[0].value === 'all') {
            // Only the 'All' option is visible - no individual matches
            if (searchText.length > 0) {
                // Create or show a disabled option with "No results" message
                let noResultsOption = recipientSelect.querySelector('.no-results');
                if (!noResultsOption) {
                    // Create the option if it doesn't exist
                    noResultsOption = document.createElement('option');
                    noResultsOption.disabled = true;
                    noResultsOption.className = 'no-results';
                    noResultsOption.text = 'No residents match your search';
                    recipientSelect.appendChild(noResultsOption);
                }
                noResultsOption.style.display = ''; // Show the message
            }
        } else {
            // Hide the "No results" message if it exists
            const noResultsOption = recipientSelect.querySelector('.no-results');
            if (noResultsOption) {
                noResultsOption.style.display = 'none';
            }
        }
    }
    
    // Set up event listener for the search input
    searchInput.addEventListener('input', filterRecipients);
    
    // Set up event listener for the clear search button
    clearButton.addEventListener('click', function() {
        searchInput.value = ''; // Clear the search text
        filterRecipients();     // Update the filtered results
        searchInput.focus();    // Return focus to the search box
    });
    
    /**
     * Handle the special behavior of the 'All residents' option:
     * - If 'All residents' is selected, deselect all individual residents
     * - If any individual resident is selected, deselect 'All residents'
     * - If nothing is selected, select 'All residents' again
     */
    recipientSelect.addEventListener('change', function() {
        const allOption = recipientSelect.querySelector('option[value="all"]');
        const otherOptions = Array.from(recipientSelect.options).filter(option => option.value !== 'all');
        
        if (allOption.selected) {
            // If 'All residents' is selected, deselect individual residents
            otherOptions.forEach(option => option.selected = false);
        } else {
            // If any individual resident is selected, deselect 'All residents'
            const anyIndividualSelected = otherOptions.some(option => option.selected);
            if (anyIndividualSelected) {
                allOption.selected = false;
            } else {
                // If nothing is selected, select 'All residents' again
                allOption.selected = true;
            }
        }
    });
});
</script>

<?php 
// Include the common footer for the page.
require_once 'includes/footer.php'; 
?>
