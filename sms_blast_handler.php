<?php
// --- SMS Blast Handler ---
// This script processes form submissions from sms_blast.php.
// It validates the message and recipient selections, sends SMS messages using Infobip API,
// logs the activity, and redirects back to the form with appropriate status messages.

// Include the database configuration file which establishes $link (database connection).
require_once 'config.php';

// Infobip API configuration
define('INFOBIP_API_KEY', 'efc46c3b6ed1038907877562426d52e8-b5bbda88-649d-4f9f-a088-0407e446306e');
define('INFOBIP_API_BASE_URL', 'https://9k9dzy.api.infobip.com');

// Twilio API configuration
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token');
define('TWILIO_PHONE_NUMBER', '+1234567890');

// TextMagic API configuration
define('TEXTMAGIC_USERNAME', 'your_textmagic_username');
define('TEXTMAGIC_API_KEY', 'your_textmagic_api_key');

// --- Check Form Submission and Validate Inputs ---
// Only process if the form was properly submitted with all required fields.
if (isset($_POST['send_blast']) && isset($_POST['sms_message']) && isset($_POST['recipients'])) {
    // Sanitize and validate the SMS message text.
    $sms_message = trim($_POST['sms_message']); // Remove leading/trailing whitespace.
    $selected_recipients = $_POST['recipients']; // Array of recipient IDs or 'all'.

    // Validate that the message is not empty.
    if (empty($sms_message)) {
        header("Location: sms_blast.php?status=error_message_empty");
        exit; // Terminate script execution.
    }

    // Validate that at least one recipient is selected.
    if (empty($selected_recipients)) {
        header("Location: sms_blast.php?status=error_no_recipients");
        exit; // Terminate script execution.
    }

    // --- Determine Recipients Based on Selection ---
    // Check if the 'all' option is selected (send to all residents with contact numbers).
    $send_to_all = in_array('all', $selected_recipients);

    // Initialize variables for recipients query.
    $recipients_result = null;

    // Prepare and execute SQL based on recipient selection.
    if ($send_to_all) {
        // Case 1: Send to all residents with valid contact numbers.
        $sql_recipients = "SELECT id, fullname, contact_number FROM residents WHERE contact_number IS NOT NULL AND contact_number != '' ORDER BY fullname ASC";
        // Execute the query. No parameters needed as we're not using user input in the query.
        $recipients_result = mysqli_query($link, $sql_recipients);
    } else {
        // Case 2: Send to specific residents only (based on selected IDs).
        // Sanitize IDs by casting each to integer to prevent SQL injection.
        $recipient_ids = array_map('intval', $selected_recipients);
        
        // Use a prepared statement instead of string concatenation for better security.
        // We'll build a parameterized query with the right number of placeholders.
        if (!empty($recipient_ids)) {
            // Create placeholders for the IN clause: ?,?,?...
            $placeholders = implode(',', array_fill(0, count($recipient_ids), '?'));
            $sql_recipients = "SELECT id, fullname, contact_number FROM residents WHERE id IN ($placeholders) AND contact_number IS NOT NULL AND contact_number != '' ORDER BY fullname ASC";
            
            // Prepare the statement.
            if ($stmt = mysqli_prepare($link, $sql_recipients)) {
                // Create the types string for bind_param (all integers)
                $types = str_repeat('i', count($recipient_ids));
                
                // Create an array of references for bind_param.
                $params = array($types);
                foreach ($recipient_ids as $key => $id) {
                    $params[] = &$recipient_ids[$key];
                }
                
                // Call bind_param with dynamic parameters using call_user_func_array.
                call_user_func_array(array($stmt, 'bind_param'), $params);
                
                // Execute the statement and get the result.
                mysqli_stmt_execute($stmt);
                $recipients_result = mysqli_stmt_get_result($stmt);
                mysqli_stmt_close($stmt);
            } else {
                // If prepare fails, log error and redirect with error status.
                error_log("DB Prepare Error (SMS Recipients): " . mysqli_error($link));
                header("Location: sms_blast.php?status=error_db_prepare");
                exit;
            }
        } else {
            // If recipient_ids is empty after filtering, redirect with error.
            header("Location: sms_blast.php?status=error_no_recipients");
            exit;
        }
    }

    // --- Process Recipients and Send SMS via Infobip API ---
    $sent_count = 0; // Counter for successfully sent messages
    $recipient_details = []; // Array to store recipient details for detailed logging if needed

    // Check if we have valid recipients with contact numbers
    if ($recipients_result && mysqli_num_rows($recipients_result) > 0) {
        // Process each recipient
        while ($recipient = mysqli_fetch_assoc($recipients_result)) {
            // Send SMS through Infobip API
            $result = send_sms_via_infobip($recipient['contact_number'], $sms_message);
            
            if ($result['success']) { 
                $sent_count++; 
            }
            
            // Store recipient details for potential detailed logging.
            $recipient_details[] = $recipient['fullname'] . ' (' . $recipient['contact_number'] . ')';
        }

        // --- Log the SMS Blast Activity ---
        $activity_type = 'SMS Blast Sent';
        
        // Create a descriptive log entry with a truncated message preview.
        // Truncate the message to 100 characters and add ellipsis if longer.
        $message_preview = substr($sms_message, 0, 100) . (strlen($sms_message) > 100 ? '...' : '');
        
        // Build the activity description
        $activity_description = "SMS blast sent to " . $sent_count . " resident(s). Message: '" . $message_preview . "'.";
        
        // For more detailed logging, you could add recipient details:
        // $activity_description .= " Recipients: " . implode(", ", array_slice($recipient_details, 0, 5));
        // if (count($recipient_details) > 5) { $activity_description .= " and " . (count($recipient_details) - 5) . " more"; }
        
        // Insert the activity log using a prepared statement for security.
        $log_sql = "INSERT INTO activities (activity_description, activity_type) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $log_sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $activity_description, $activity_type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // Log error but continue execution - logging failure shouldn't stop the process
            error_log("DB Prepare Error (SMS Activity Log): " . mysqli_error($link));
        }

        // --- Redirect with Success Status ---
        // Include the count of recipients in the success status for feedback display.
        header("Location: sms_blast.php?status=success&count=" . $sent_count);
        exit; // Terminate script execution.

    } else {
        // --- No Valid Recipients Found ---
        // This could happen if no residents have contact numbers or if selected IDs don't have valid numbers.
        header("Location: sms_blast.php?status=error_no_recipients");
        exit; // Terminate script execution.
    }

} else {
    // --- Invalid Request Handling ---
    // If the script is accessed directly or without proper form submission,
    // redirect back to the SMS blast form without any status message.
    header("Location: sms_blast.php");
    exit; // Terminate script execution.
}

/**
 * Send SMS via Infobip API
 * 
 * @param string $phone_number The recipient's phone number
 * @param string $message The SMS message content
 * @return array Array with success status and any error message
 */
function send_sms_via_infobip($phone_number, $message) {
    // Format phone number (remove any non-numeric characters and ensure it has country code)
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    if (substr($phone_number, 0, 1) !== '+') {
        // Add Philippines country code if not present
        if (substr($phone_number, 0, 2) !== '63') {
            // If number starts with 0, replace it with 63
            if (substr($phone_number, 0, 1) === '0') {
                $phone_number = '63' . substr($phone_number, 1);
            } else {
                $phone_number = '63' . $phone_number;
            }
        }
        $phone_number = '+' . $phone_number;
    }
    
    // Prepare the request payload
    $payload = [
        'messages' => [
            [
                'from' => 'InfoSMS',
                'destinations' => [
                    ['to' => $phone_number]
                ],
                'text' => $message
            ]
        ]
    ];
    
    // Initialize cURL session
    $curl = curl_init();
    
    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => INFOBIP_API_BASE_URL . '/sms/2/text/advanced',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: App ' . INFOBIP_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    // Execute the request
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    // Close cURL session
    curl_close($curl);
    
    // Process the response
    if ($error || $httpCode >= 400) {
        error_log("Infobip API Error: " . ($error ?: $response));
        // Use fallback mechanism
        return send_sms_with_fallback($phone_number, $message);
    }
    
    // Parse the response
    $response_data = json_decode($response, true);
    
    // Check if the message was sent successfully
    if (isset($response_data['messages'][0]['status']['groupId']) && 
        $response_data['messages'][0]['status']['groupId'] == 1) {
        return ['success' => true];
    } else {
        $error_msg = isset($response_data['messages'][0]['status']['description']) 
            ? $response_data['messages'][0]['status']['description'] 
            : 'Unknown error';
        error_log("Infobip SMS Delivery Error: " . $error_msg);
        // Use fallback mechanism
        return send_sms_with_fallback($phone_number, $message);
    }
}

/**
 * Send SMS via Twilio API
 * 
 * @param string $phone_number The recipient's phone number
 * @param string $message The SMS message content
 * @return array Array with success status and any error message
 */
function send_sms_via_twilio($phone_number, $message) {
    // Format phone number (remove any non-numeric characters and ensure it has country code)
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    if (substr($phone_number, 0, 1) !== '+') {
        // Add Philippines country code if not present
        if (substr($phone_number, 0, 2) !== '63') {
            // If number starts with 0, replace it with 63
            if (substr($phone_number, 0, 1) === '0') {
                $phone_number = '63' . substr($phone_number, 1);
            } else {
                $phone_number = '63' . $phone_number;
            }
        }
        $phone_number = '+' . $phone_number;
    }
    
    try {
        // Initialize cURL session
        $curl = curl_init();
        
        // Twilio API endpoint
        $twilio_url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";
        
        // Prepare the request payload
        $payload = http_build_query([
            'From' => TWILIO_PHONE_NUMBER,
            'To' => $phone_number,
            'Body' => $message
        ]);
        
        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $twilio_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload
        ]);
        
        // Execute the request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        // Close cURL session
        curl_close($curl);
        
        // Process the response
        if ($error || $httpCode >= 400) {
            error_log("Twilio API Error: " . ($error ?: $response));
            return ['success' => false, 'error' => 'Failed to send SMS via Twilio: ' . ($error ?: $response)];
        }
        
        // Parse the response
        $response_data = json_decode($response, true);
        
        // Check if the message was sent successfully
        if (isset($response_data['sid'])) {
            return ['success' => true];
        } else {
            $error_msg = isset($response_data['message']) ? $response_data['message'] : 'Unknown Twilio error';
            error_log("Twilio SMS Delivery Error: " . $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
    } catch (Exception $e) {
        error_log("Twilio SMS Exception: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send SMS via TextMagic API
 * 
 * @param string $phone_number The recipient's phone number
 * @param string $message The SMS message content
 * @return array Array with success status and any error message
 */
function send_sms_via_textmagic($phone_number, $message) {
    // Format phone number (remove any non-numeric characters and ensure it has country code)
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    if (substr($phone_number, 0, 1) !== '+') {
        // Add Philippines country code if not present
        if (substr($phone_number, 0, 2) !== '63') {
            // If number starts with 0, replace it with 63
            if (substr($phone_number, 0, 1) === '0') {
                $phone_number = '63' . substr($phone_number, 1);
            } else {
                $phone_number = '63' . $phone_number;
            }
        }
        $phone_number = '+' . $phone_number;
    }
    
    try {
        // Initialize cURL session
        $curl = curl_init();
        
        // TextMagic API endpoint
        $textmagic_url = "https://rest.textmagic.com/api/v2/messages";
        
        // Prepare the request payload
        $payload = json_encode([
            'phones' => [$phone_number],
            'text' => $message,
            'from' => 'TextMagic'
        ]);
        
        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $textmagic_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-TM-Username: ' . TEXTMAGIC_USERNAME,
                'X-TM-Key: ' . TEXTMAGIC_API_KEY
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload
        ]);
        
        // Execute the request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        // Close cURL session
        curl_close($curl);
        
        // Process the response
        if ($error || $httpCode >= 400) {
            error_log("TextMagic API Error: " . ($error ?: $response));
            return ['success' => false, 'error' => 'Failed to send SMS via TextMagic: ' . ($error ?: $response)];
        }
        
        // Parse the response
        $response_data = json_decode($response, true);
        
        // Check if the message was sent successfully
        if (isset($response_data['id'])) {
            return ['success' => true];
        } else {
            $error_msg = isset($response_data['errors'][0]['message']) ? $response_data['errors'][0]['message'] : 'Unknown TextMagic error';
            error_log("TextMagic SMS Delivery Error: " . $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
    } catch (Exception $e) {
        error_log("TextMagic SMS Exception: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send SMS with Fallback Mechanism
 * 
 * @param string $phone_number The recipient's phone number
 * @param string $message The SMS message content
 * @return array Array with success status and any error message
 */
function send_sms_with_fallback($phone_number, $message) {
    // Try Infobip first
    $infobip_result = send_sms_via_infobip($phone_number, $message);
    if ($infobip_result['success']) {
        return $infobip_result;
    }
    
    // If Infobip fails, try Twilio
    $twilio_result = send_sms_via_twilio($phone_number, $message);
    if ($twilio_result['success']) {
        return $twilio_result;
    }
    
    // If Twilio fails, try TextMagic
    $textmagic_result = send_sms_via_textmagic($phone_number, $message);
    if ($textmagic_result['success']) {
        return $textmagic_result;
    }
    
    // If all APIs fail, return the last error
    return $textmagic_result;
}

// --- Close Database Connection ---
// Because this is a handler script with no HTML output, we need to explicitly close the connection.
// Check if the $link variable exists, is a resource, and is a MySQL link type before closing.
if (isset($link) && is_resource($link) && get_resource_type($link) === 'mysql link') {
    mysqli_close($link);
}
?>
