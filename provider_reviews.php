<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
if ($provider_id <= 0) {
    echo "Invalid provider.";
    exit;
}

// Fetch provider name or profession
$providerInfo = $conn->query("SELECT profession FROM service_providers WHERE provider_id = $provider_id");
$profession = ($providerInfo && $providerInfo->num_rows > 0) 
    ? $providerInfo->fetch_assoc()['profession'] 
    : "Unknown Provider";

// Fetch all reviews for services by this provider
$sql = "
    SELECT 
        r.rating, 
        r.comment, 
        b.booking_time, 
        s.title,
        u.name AS customer_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.customer_id = u.user_id
    WHERE s.provider_id = $provider_id
    ORDER BY b.booking_time DESC
";
$result = $conn->query($sql);
$reviews = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profession) ?> - Reviews | Hire Per Hour</title>
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
        
        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .reviews-header {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .reviews-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .reviews-count {
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .review-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .review-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .review-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .review-customer {
            font-weight: 500;
            color: var(--primary);
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .rating-value {
            font-weight: 600;
            margin-left: 0.3rem;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .review-comment {
            padding: 1rem;
            background: rgba(245, 245, 245, 0.5);
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            line-height: 1.7;
        }
        
        .no-reviews {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        
        .no-reviews i {
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
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Reviews for <?= htmlspecialchars($profession) ?></h1>
    <p>See what customers say about this provider</p>
</header>

<nav>
    <a href="services.php"><i class="fas fa-search"></i> All Services</a>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
</nav>

<div class="container">
    <div class="reviews-header">
        <h2><i class="fas fa-star"></i> Customer Feedback</h2>
        <span class="reviews-count"><?= count($reviews) ?> Review<?= count($reviews) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
                    <div class="review-customer">by <?= htmlspecialchars($review['customer_name']) ?></div>
                </div>
                
                <div class="rating">
                    <div class="rating-stars">
                        <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                    </div>
                    <span class="rating-value"><?= $review['rating'] ?>/5</span>
                </div>
                
                <div class="review-date">
                    <i class="far fa-calendar-alt"></i> <?= date('F j, Y', strtotime($review['booking_time'])) ?>
                </div>
                
                <div class="review-comment">
                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-reviews">
            <i class="far fa-star"></i>
            <h3>No Reviews Yet</h3>
            <p>This provider hasn't received any reviews yet.</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body>
</html>