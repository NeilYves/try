<?php
session_start();
require_once 'config.php'; // Ensure this path is correct based on your file structure

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $role = sanitize_input($_POST['role']); // Added role from form

    // Prepare a statement to prevent SQL injection
    $stmt = mysqli_prepare($link, "SELECT id, username, password, role FROM users WHERE username = ? AND role = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 1) {
        mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $db_role);
        mysqli_stmt_fetch($stmt);

        // Verify password
        if ($password === $hashed_password) {
            // Password is correct, start a new session
            $_SESSION['loggedin'] = TRUE;
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = $db_role; // Store role in session

            // Redirect to dashboard or intended page
            header("location: index.php");
            exit;
        } else {
            // Display an error message if password is not valid
            $error_message = "Invalid username, password, or role.";
        }
    } else {
        // Display an error message if username doesn't exist
        $error_message = "Invalid username, password, or role.";
    }
    mysqli_stmt_close($stmt);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: url('images/background.jfif') no-repeat center center;
            background-size: 150% 150%; /* Scale up to allow movement */
            animation: background-pan 30s linear infinite alternate; /* New animation */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }

        @keyframes background-pan {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), rgba(173, 216, 230, 0.5)); /* Subtle blue/white gradient */
            border-radius: 50%;
            animation: magic-dance infinite;
            pointer-events: none;
            filter: blur(2px); /* Soften edges */
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.7); /* Subtle glow */
        }

        @keyframes magic-dance {
            0% { transform: translate(0, 0) scale(0); opacity: 0; }
            10% { opacity: 0.8; transform: scale(0.8); }
            100% { transform: translate(var(--x), var(--y)) scale(var(--s)); opacity: 0; }
        }

        .login-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 248, 255, 0.98) 100%); /* Subtle gradient */
            padding: 40px;
            border-radius: 20px; /* More rounded corners */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3); /* Stronger shadow */
            width: 100%;
            max-width: 480px; /* Slightly wider */
            text-align: center;
            z-index: 1;
            position: relative;
            overflow: hidden; /* Hide overflowing elements like animated borders */
            backdrop-filter: blur(8px); /* Stronger blur */
            border: 2px solid transparent; /* Prepare for animated border */
            animation: glow-border 2s infinite alternate;
        }

        @keyframes glow-border {
            0% { border-color: rgba(0, 123, 255, 0.3); }
            100% { border-color: rgba(0, 123, 255, 0.7); }
        }

        .login-container h2 {
            margin-bottom: 35px; /* More space */
            color: #1a2a3a; /* Darker, richer text */
            font-weight: 800; /* Bolder */
            font-size: 2.5em; /* Larger font */
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            border-radius: 10px; /* More rounded */
            padding: 14px 18px; /* More padding */
            font-size: 1.1em; /* Slightly larger text */
            transition: all 0.4s ease;
            border: 1px solid #c0d0e0; /* Lighter border */
            background-color: #f8fcfd; /* Off-white input background */
        }
        .form-control:focus {
            border-color: #007bff; /* Focus color */
            box-shadow: 0 0 0 0.3rem rgba(0, 123, 255, 0.3); /* More pronounced shadow */
            background-color: #ffffff;
        }
        .btn-primary {
            background-color: #007bff; /* Primary blue */
            border-color: #007bff;
            padding: 14px 0; /* More padding */
            font-size: 1.3em; /* Larger font */
            font-weight: 700; /* Bolder */
            border-radius: 10px; /* More rounded */
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4); /* Button shadow */
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.6);
        }
        .alert {
            margin-top: 30px; /* More space */
            font-size: 1.05em;
            text-align: center;
            border-radius: 10px; /* More rounded */
            padding: 18px; /* More padding */
            background-color: rgba(255, 0, 0, 0.1); /* Lighter error background */
            color: #cc0000; /* Darker error text */
            border: 1px solid rgba(255, 0, 0, 0.2);
        }
        .form-label {
            font-weight: 700; /* Bolder */
            color: #2c3e50; /* Darker text */
            text-align: left;
            display: block;
            margin-bottom: 10px; /* More space */
        }
        .logo {
            max-width: 140px; /* Larger logo */
            margin-bottom: 30px; /* More space */
            border-radius: 50%;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .logo:hover {
            transform: scale(1.1) rotate(8deg); /* More pronounced hover */
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        /* New styles for inner bubbles/particles on login container */
        .login-container::before,
        .login-container::after {
            content: '';
            position: absolute;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.7), rgba(173, 216, 230, 0.4));
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
            animation: bubble-fade 10s infinite ease-out;
        }

        .login-container::before {
            width: 80px;
            height: 80px;
            top: 10%;
            left: -20px;
            animation-delay: 0s;
        }

        .login-container::after {
            width: 60px;
            height: 60px;
            bottom: 5%;
            right: -15px;
            animation-delay: 5s;
        }

        @keyframes bubble-fade {
            0% { transform: scale(0) translate(0, 0); opacity: 0.3; }
            20% { transform: scale(1) translate(15px, -15px); opacity: 0.7; }
            50% { transform: scale(1.1) translate(-15px, 15px); opacity: 0.6; }
            80% { opacity: 0.5; transform: scale(1.05) translate(5px, -5px); }
            100% { transform: scale(1.2) translate(0, 0); opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="images/logo.jfif" alt="Barangay Logo" class="logo">
        <h2>SentroGlad</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" name="username" id="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label for="role" class="form-label">Role:</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="Barangay Secretary">Barangay Secretary</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.querySelector('body');
            const numParticles = 120; // Increased number of particles for more density

            for (let i = 0; i < numParticles; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 40 + 20; // Larger range: 20px to 60px
                const duration = Math.random() * 20 + 10; // Longer duration: 10s to 30s
                const delay = Math.random() * 10; // Increased delay range
                const x = (Math.random() - 0.5) * window.innerWidth * 3; // Wider movement range
                const y = (Math.random() - 0.5) * window.innerHeight * 3; // Wider movement range
                const scale = Math.random() * 1 + 0.5; // Scale from 0.5 to 1.5

                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.setProperty('--x', `${x}px`);
                particle.style.setProperty('--y', `${y}px`);
                particle.style.setProperty('--s', `${scale}`);
                body.appendChild(particle);
            }
        });
    </script>
</body>
</html> 