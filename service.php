<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Identify logged in user
$user_id = $_SESSION['user_id'] ?? null;
$user_type = null;
if ($user_id) {
    $result = $conn->query("SELECT user_type FROM users WHERE user_id = $user_id");
    if ($result && $result->num_rows > 0) {
        $user_type = $result->fetch_assoc()['user_type'];
    }
}

// Fetch all services with availability status
$services = [];
$sql = "
    SELECT 
        s.service_id, 
        s.title, 
        s.description, 
        s.rate_per_hour, 
        sp.profession,
        sp.provider_id,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM bookings b 
                WHERE b.service_id = s.service_id 
                AND b.status = 'accepted'
                AND NOW() BETWEEN b.booking_time AND DATE_ADD(b.booking_time, INTERVAL b.total_hours HOUR)
            )
            THEN 'Not Available'
            ELSE 'Available'
        END AS availability
    FROM services s
    JOIN service_providers sp ON s.provider_id = sp.provider_id 
    ORDER BY s.title ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Services - Hire Per Hour</title>
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
        }
        
        nav a:hover {
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .service-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .service-id {
            font-size: 0.8rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .service-title {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            color: var(--dark);
        }
        
        .service-profession {
            display: inline-block;
            background: rgba(106, 17, 203, 0.1);
            color: var(--primary);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .service-description {
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        
        .service-rate {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .service-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 1.5rem;
        }
        
        .action-btn {
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .book-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .book-btn.disabled {
            background: #e0e0e0;
            color: #9e9e9e;
            pointer-events: none;
            cursor: not-allowed;
        }
        
        .review-btn {
            background: #28a745;
            color: white;
        }
        
        .leave-review-btn {
            background: #ff9800;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .not-available {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .no-services {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
        }
        
        .no-services i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary);
            opacity: 0.5;
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
            
            .container {
                padding: 0 1rem;
                margin: 2rem auto;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .service-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Available Services</h1>
    <p>Find professionals for your needs</p>
</header>

<nav>
    <?php if ($user_id): ?>
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
        <a href="auth.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="auth.php"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
</nav>

<div class="container">
    <h2 class="page-title"><i class="fas fa-list"></i> Service Listings</h2>
    
    <?php if (!empty($services)): ?>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-id">Service #<?= htmlspecialchars($service['service_id']) ?></div>
                    <h3 class="service-title"><?= htmlspecialchars($service['title']) ?></h3>
                    <span class="service-profession"><?= htmlspecialchars($service['profession']) ?></span>
                    
                    <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                    
                    <div class="service-rate">$<?= $service['rate_per_hour'] ?>/hr</div>
                    
                    <span class="status-badge <?= $service['availability'] === 'Available' ? 'available' : 'not-available' ?>">
                        <?= $service['availability'] ?>
                    </span>
                    
                    <div class="service-actions">
                        <a 
                            class="action-btn book-btn <?= $service['availability'] === 'Available' ? '' : 'disabled' ?>" 
                            href="<?= $service['availability'] === 'Available' ? 'book_service.php?id=' . $service['service_id'] : '#' ?>">
                            <i class="fas fa-calendar-check"></i> Book Now
                        </a>
                        
                        <a class="action-btn review-btn" href="provider_reviews.php?provider_id=<?= $service['provider_id'] ?>">
                            <i class="fas fa-star"></i> Reviews
                        </a>

                        <?php
                        // Show leave review button only if user is customer and has completed booking
                        if ($user_id && $user_type === 'customer') {
                            // Get any completed bookings by this user for this service
                            $booking_sql = "
                                SELECT b.booking_id FROM bookings b
                                LEFT JOIN reviews r ON b.booking_id = r.booking_id
                                WHERE b.customer_id = $user_id
                                AND b.service_id = {$service['service_id']}
                                AND b.status = 'completed'
                                AND r.booking_id IS NULL
                                LIMIT 1
                            ";
                            $booking_res = $conn->query($booking_sql);
                            if ($booking_res && $booking_res->num_rows > 0) {
                                $booking_row = $booking_res->fetch_assoc();
                                $booking_id = $booking_row['booking_id'];
                                echo '<a class="action-btn leave-review-btn" href="reviews.php?booking_id=' . $booking_id . '">
                                    <i class="fas fa-edit"></i> Leave Review
                                </a>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-services">
            <i class="fas fa-exclamation-circle"></i>
            <h3>No Services Available</h3>
            <p>There are currently no services listed. Please check back later.</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body>
</html>