<?php
require_once 'config.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Determine action
$action = $_POST['action'] ?? 'add';

if ($action === 'add') {
    // Add new announcement
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $event_date = $_POST['event_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    
    // Validation
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = 'Title and content are required.';
        header('Location: index.php');
        exit();
    }
    
    // Combine location with content if location is provided
    if (!empty($location)) {
        $content = $content . "\n\nLocation: " . $location;
    }
    
    // Convert empty event_date to null
    if (empty($event_date)) {
        $event_date = null;
    }
    
    // Insert into database
    $stmt = mysqli_prepare($link, "INSERT INTO announcements (title, content, event_date, is_active) VALUES (?, ?, ?, 1)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $title, $content, $event_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Announcement added successfully!';
            
            // Log activity
            $activity_desc = "New announcement added: " . $title;
            $activity_stmt = mysqli_prepare($link, "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'Announcement')");
            if ($activity_stmt) {
                mysqli_stmt_bind_param($activity_stmt, "s", $activity_desc);
                mysqli_stmt_execute($activity_stmt);
                mysqli_stmt_close($activity_stmt);
            }
        } else {
            $_SESSION['error'] = 'Error adding announcement: ' . mysqli_error($link);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($link);
    }
    
} elseif ($action === 'delete') {
    // Delete announcement
    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    
    if ($announcement_id <= 0) {
        $_SESSION['error'] = 'Invalid announcement ID.';
        header('Location: index.php');
        exit();
    }
    
    // Get announcement title for logging
    $title_stmt = mysqli_prepare($link, "SELECT title FROM announcements WHERE id = ?");
    $announcement_title = '';
    if ($title_stmt) {
        mysqli_stmt_bind_param($title_stmt, "i", $announcement_id);
        mysqli_stmt_execute($title_stmt);
        $result = mysqli_stmt_get_result($title_stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $announcement_title = $row['title'];
        }
        mysqli_stmt_close($title_stmt);
    }
    
    // Delete announcement
    $stmt = mysqli_prepare($link, "DELETE FROM announcements WHERE id = ?");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $announcement_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Announcement deleted successfully!';
            
            // Log activity
            if (!empty($announcement_title)) {
                $activity_desc = "Announcement deleted: " . $announcement_title;
                $activity_stmt = mysqli_prepare($link, "INSERT INTO activities (activity_description, activity_type) VALUES (?, 'Delete Announcement')");
                if ($activity_stmt) {
                    mysqli_stmt_bind_param($activity_stmt, "s", $activity_desc);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }
            }
        } else {
            $_SESSION['error'] = 'Error deleting announcement: ' . mysqli_error($link);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($link);
    }
}

// Redirect back to dashboard
header('Location: index.php');
exit();
?> 