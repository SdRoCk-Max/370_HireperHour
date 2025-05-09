<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch existing user info
$query = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Update profile logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email)) {
        $message = "Name and email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Update name/email
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        $stmt->execute();

        // Update password if provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $pass_stmt->bind_param("si", $hashed_password, $user_id);
            $pass_stmt->execute();
        }

        $message = "Profile updated successfully!";
    }

    // Refresh user info
    $query = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Hire Per Hour</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff4d4d;
            --light: #f8f9fa;
            --dark: #212529;
            --text: #495057;
            --shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover;
            opacity: 0.15;
            z-index: 0;
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        nav a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        nav a:hover {
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .profile-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-header i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .profile-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(245, 245, 245, 0.5);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: var(--secondary);
            transform: translateX(-5px);
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        footer {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            margin-top: 4rem;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        footer p {
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            header {
                padding: 2rem 1rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
                padding: 1rem;
            }
            
            .profile-container {
                padding: 0 1rem;
                margin: 2rem auto;
            }
            
            .profile-card {
                padding: 1.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Update Your Profile</h1>
    <p>Keep your information up to date</p>
</header>

<nav>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <i class="fas fa-user-edit"></i>
            <h2>Profile Information</h2>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
                <i class="fas <?= strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> New Password (leave blank to keep current)</label>
                <input type="password" name="password" id="password">
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password">
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>

        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body>
</html>