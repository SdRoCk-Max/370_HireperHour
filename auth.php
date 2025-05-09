<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = trim($_POST['phone']);
    $user_type = $_POST['user_type'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $password, $phone, $user_type);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Please log in.');</script>";
    } else {
        echo "<script>alert('Email already exists or error occurred.');</script>";
    }
}


if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - Hire Per Hour</title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .auth-header p {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        .auth-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            max-width: 1000px;
            width: 100%;
        }
        
        .form-container {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .form-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.8rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            opacity: 0.7;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(245, 245, 245, 0.5);
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            margin-top: 1rem;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .toggle-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .toggle-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .auth-header h1 {
                font-size: 2rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="auth-header">
    <h1>Hire Per Hour</h1>
    <p>Welcome to our service platform</p>
</div>

<div class="auth-container">
    <div class="form-container">
        <h2>Login</h2>
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <button name="login" type="submit">Login <i class="fas fa-arrow-right"></i></button>
            <p class="toggle-text">Don't have an account? <a href="#register">Register here</a></p>
        </form>
    </div>

    <div class="form-container" id="register">
        <h2>Register</h2>
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required />
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required />
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" placeholder="Phone" required />
            </div>
            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select name="user_type" required>
                    <option value="">Select User Type</option>
                    <option value="customer">Customer</option>
                    <option value="provider">Service Provider</option>
                </select>
            </div>
            <button name="register" type="submit">Register <i class="fas fa-user-plus"></i></button>
            <p class="toggle-text">Already have an account? <a href="#">Login here</a></p>
        </form>
    </div>
</div>

</body>
</html>