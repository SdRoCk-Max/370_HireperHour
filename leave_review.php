<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    die("Invalid booking.");
}

// Check if the booking is valid, completed, belongs to this user, and not already reviewed
$booking_check = $conn->prepare("
    SELECT b.booking_id, b.provider_id, b.service_id, s.title
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    LEFT JOIN provider_reviews r ON b.booking_id = r.booking_id
    WHERE b.booking_id = ?
      AND b.user_id = ?
      AND b.status = 'completed'
      AND r.booking_id IS NULL
    LIMIT 1
");
$booking_check->bind_param("ii", $booking_id, $user_id);
$booking_check->execute();
$result = $booking_check->get_result();

if ($result->num_rows === 0) {
    die("Unauthorized or already reviewed booking.");
}

$booking = $result->fetch_assoc();
$provider_id = $booking['provider_id'];
$service_title = $booking['title'];

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO provider_reviews (booking_id, provider_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiis", $booking_id, $provider_id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            header("Location: provider_reviews.php?provider_id=$provider_id&reviewed=1");
            exit;
        } else {
            $error = "Failed to submit review. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - Hire Per Hour</title>
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
        
        .review-container {
            max-width: 700px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .review-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .review-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .review-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .review-header i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .review-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .service-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
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
        
        .rating-select {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .rating-select select {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            background-color: rgba(245, 245, 245, 0.5);
            font-size: 1rem;
        }
        
        .stars-preview {
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(245, 245, 245, 0.5);
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group textarea:focus {
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
        
        .error {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 0.8rem;
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
            
            .review-container {
                padding: 0 1rem;
                margin: 2rem auto;
            }
            
            .review-card {
                padding: 1.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Share Your Experience</h1>
    <p>Your feedback helps us improve our services</p>
</header>

<nav>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> My Bookings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="review-container">
    <div class="review-card">
        <div class="review-header">
            <i class="fas fa-star"></i>
            <h2>Leave a Review</h2>
        </div>
        
        <div class="service-title">
            For: <?= htmlspecialchars($service_title) ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="rating"><i class="fas fa-star"></i> Rating</label>
                <div class="rating-select">
                    <select name="rating" id="rating" required onchange="updateStars(this)">
                        <option value="">Select your rating</option>
                        <option value="1">1 star - Poor</option>
                        <option value="2">2 stars - Fair</option>
                        <option value="3">3 stars - Good</option>
                        <option value="4">4 stars - Very Good</option>
                        <option value="5">5 stars - Excellent</option>
                    </select>
                    <div class="stars-preview" id="stars-preview"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="comment"><i class="fas fa-comment"></i> Your Review</label>
                <textarea id="comment" name="comment" placeholder="Share details about your experience with this service..." required></textarea>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Submit Review
            </button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

<script>
    function updateStars(selectElement) {
        const rating = selectElement.value;
        const starsContainer = document.getElementById('stars-preview');
        starsContainer.innerHTML = '';
        
        if (rating) {
            for (let i = 0; i < 5; i++) {
                const star = document.createElement('span');
                star.textContent = i < rating ? '★' : '☆';
                starsContainer.appendChild(star);
            }
        }
    }
</script>

</body>
</html>