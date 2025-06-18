<?php
// --- Barangay Management System Configuration File ---
// This file contains the database connection settings and essential utility functions.

// --- Error Reporting Configuration ---
// Ensure errors are not displayed on the live site for security, but are logged.
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/error_log.log'); // Ensure this directory is writable

// Custom Error Handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false;
    }
    $log_message = sprintf("[%s] E_ERROR: %s in %s on line %d
", date('Y-m-d H:i:s'), $errstr, $errfile, $errline);
    error_log($log_message, 3, ini_get('error_log')); // Log to file
    // For critical errors, you might want to show a generic error page
    // if (!headers_sent()) {
    //     header('Location: error.php'); // Redirect to a user-friendly error page
    //     exit();
    // }
    return true;
});

// Custom Exception Handler
set_exception_handler(function($exception) {
    $log_message = sprintf("[%s] EXCEPTION: %s in %s on line %d
", date('Y-m-d H:i:s'), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    error_log($log_message, 3, ini_get('error_log')); // Log to file
    // For exceptions, you might want to show a generic error page
    // if (!headers_sent()) {
    //     header('Location: error.php'); // Redirect to a user-friendly error page
    //     exit();
    // }
    die("An unexpected error occurred. Please try again later.");
});

// --- Database Configuration ---
// Defines constants for database server, username, password, and database name.
// These constants are used to establish a connection to the MySQL database.

define('DB_SERVER', 'localhost');       // Database server hostname (usually 'localhost')
define('DB_USERNAME', 'root');         // Database username (default is 'root' for XAMPP)
define('DB_PASSWORD', '');             // Database password (default is empty for XAMPP)
define('DB_NAME', 'barangay_mingming'); // Name of the database for this application

// --- Database Connection ---
// Attempts to establish a connection to the MySQL database using the defined credentials.
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check if the database connection was successful.
// If the connection fails, the script will terminate and display an error message.
if($link === false){
    // Use error_log for database connection errors as well
    error_log("Database connection error: " . mysqli_connect_error());
    die("ERROR: Could not connect to database. Please check your configuration.");
}

// --- Character Set Configuration ---
// Sets the default character set to utf8mb4 for the database connection.
// This ensures proper handling of a wide range of characters, including emojis.
mysqli_set_charset($link, "utf8mb4");

// --- Utility Functions ---

/**
 * Escapes HTML special characters in a string to prevent Cross-Site Scripting (XSS) attacks.
 * This function should be used whenever outputting user-supplied data to an HTML page.
 *
 * @param string $text The input string to be escaped.
 * @return string The HTML-escaped string.
 */
function html_escape($text) {
    // htmlspecialchars() converts special characters to HTML entities.
    // ENT_QUOTES: Converts both double and single quotes.
    // 'UTF-8': Specifies the character set.
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // Additionally escape backticks as they are not escaped by htmlspecialchars by default
    $text = str_replace('`', '&#96;', $text);
    return $text;
}

/**
 * Sanitizes input data by trimming whitespace and escaping HTML special characters.
 * This function helps prevent XSS attacks and ensures data consistency.
 *
 * @param string $data The input string to be sanitized.
 * @return string The sanitized string.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Formats a given date string into a more readable format.
 *
 * @param string|null $dateString The date string to format. Can be null or empty.
 * @return string The formatted date string, or 'N/A' if invalid.
 */
function format_date($dateString) {
    if (empty($dateString) || $dateString === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    $dateTime = new DateTime($dateString);
    return $dateTime->format('F j, Y, g:i A');
}

// End of configuration file
?>
