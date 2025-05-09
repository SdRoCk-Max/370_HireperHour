<?php 
session_start(); 
$conn = new mysqli("localhost", "root", "", "hireperhour");  


if ($conn->connect_error) {     
    die("Connection failed: " . $conn->connect_error); 
}  


if (!isset($_SESSION['user_id'])) {     
    header("Location: auth.php");     
    exit; 
}  


$user_id = $_SESSION['user_id']; 
$user_name = $_SESSION['name'] ?? 'User';  


$user_result = $conn->query("SELECT user_type FROM users WHERE user_id = $user_id"); 
if ($user_result->num_rows > 0) {     
    $user_row = $user_result->fetch_assoc();     
    $user_type = $user_row['user_type'];
} else {     
        
    header("Location: auth.php");     
    exit; 
} 
?>  

<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hire Per Hour</title>
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
            overflow-x: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
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
        
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
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
            position: relative;
        }
        
        nav a:hover {
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
        }
        
        nav a:hover::after {
            width: 70%;
        }
        
        .welcome-box {
            background: white;
            margin: 3rem auto;
            padding: 2.5rem;
            max-width: 900px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .welcome-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .welcome-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .welcome-box h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            color: var(--dark);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        
        .welcome-box p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto 1.5rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            padding: 0 2rem;
            margin: 3rem auto;
            max-width: 1200px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .dashboard-card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--dark);
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
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .welcome-box {
                margin: 2rem 1rem;
                padding: 1.5rem;
            }
            
            .welcome-box h2 {
                font-size: 1.8rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head> 
<body> 

<header>
    <h1>Hire Per Hour</h1>
    <p>Welcome back to your personalized dashboard</p>
</header>

<nav>
    <a href="service.php"><i class="fas fa-search"></i> Explore Services</a>
    <a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> My Bookings</a>
    
    <?php if ($user_type === 'provider'): ?>
        <a href="post_service.php"><i class="fas fa-plus-circle"></i> Post Service</a>
    <?php endif; ?>
    
    <a href="update_profile.php"><i class="fas fa-user-edit"></i> Profile</a>
    <a href="auth.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="welcome-box">
    <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
    <h2>Hello, <?= htmlspecialchars($user_name) ?> 👋</h2>
    <p>Welcome to your dashboard. Find amazing services or manage your offerings in one convenient place.</p>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <i class="fas fa-search"></i>
        <h3>Find Services</h3>
        <p>Discover skilled professionals ready to help with your needs</p>
        <a href="service.php" class="btn">Browse Now</a>
    </div>
    
    <div class="dashboard-card">
        <i class="fas fa-calendar-check"></i>
        <h3>Your Bookings</h3>
        <p>Manage all your upcoming and past appointments</p>
        <a href="my_bookings.php" class="btn">View Bookings</a>
    </div>
    
    <?php if ($user_type === 'provider'): ?>
    <div class="dashboard-card">
        <i class="fas fa-briefcase"></i>
        <h3>Your Services</h3>
        <p>Manage the services you offer to clients</p>
        <a href="post_service.php" class="btn">Manage Services</a>
    </div>
    <?php endif; ?>
    
    <div class="dashboard-card">
        <i class="fas fa-user-cog"></i>
        <h3>Account Settings</h3>
        <p>Update your profile and preferences</p>
        <a href="update_profile.php" class="btn">Edit Profile</a>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body> 
</html>