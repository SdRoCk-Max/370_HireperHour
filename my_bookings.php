<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';

// Fetch bookings with payment info
$bookings = [];
$sql = "SELECT b.booking_id, b.status AS booking_status, b.booking_time,
               s.title, s.service_id, s.rate_per_hour, sp.profession,
               p.status AS payment_status, p.amount, p.paid_at
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.customer_id = ?
        ORDER BY b.booking_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Hire Per Hour</title>
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
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--dark);
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .booking-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .booking-card h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .booking-card h3 i {
            color: var(--primary);
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-item strong {
            font-size: 0.9rem;
            color: var(--dark);
            opacity: 0.8;
            margin-bottom: 0.3rem;
        }
        
        .detail-item span {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-done {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            margin-right: 1rem;
            margin-top: 0.5rem;
        }
        
        .review-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .pay-btn {
            background-color: #ffc107;
            color: #000;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-bookings {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        
        .no-bookings i {
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
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-card {
                padding: 1.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>My Bookings</h1>
    <p>Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
</header>

<nav>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="service.php"><i class="fas fa-search"></i> Services</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="container">
    <h2 class="page-title"><i class="fas fa-calendar-alt"></i> Your Bookings</h2>

    <?php if (!empty($bookings)): ?>
        <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <h3><i class="fas fa-calendar-check"></i> <?= htmlspecialchars($booking['title']) ?> (<?= htmlspecialchars($booking['profession']) ?>)</h3>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <strong>Status</strong>
                        <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>">
                            <?= htmlspecialchars($booking['booking_status']) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Booking Time</strong>
                        <span><?= htmlspecialchars($booking['booking_time']) ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Rate</strong>
                        <span>$<?= $booking['rate_per_hour'] ?>/hr</span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Payment Status</strong>
                        <?php if ($booking['payment_status']): ?>
                            <span class="status-badge status-<?= htmlspecialchars($booking['payment_status']) ?>">
                                <?= htmlspecialchars($booking['payment_status']) ?>
                                <?php if ($booking['payment_status'] === 'paid'): ?>
                                    on <?= htmlspecialchars($booking['paid_at']) ?>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-unpaid">Not Paid</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($booking['booking_status'] === 'done'): ?>
                    <a class="action-btn review-btn" href="reviews.php?booking_id=<?= $booking['booking_id'] ?>&service_id=<?= $booking['service_id'] ?>">
                        <i class="fas fa-star"></i> Leave a Review
                    </a>
                <?php endif; ?>

                <?php if ($booking['payment_status'] !== 'paid' && $booking['booking_status'] === 'accepted'): ?>
                    <a class="action-btn pay-btn" href="payments.php?booking_id=<?= $booking['booking_id'] ?>">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-calendar-times"></i>
            <h3>No bookings found</h3>
            <p>You haven't made any bookings yet. Explore our services to get started!</p>
            <a href="service.php" class="action-btn review-btn" style="margin-top: 1.5rem;">
                <i class="fas fa-search"></i> Browse Services
            </a>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body>
</html>