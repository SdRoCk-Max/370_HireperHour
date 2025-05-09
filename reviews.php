<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['rating'], $_POST['comment'])) {
    $booking_id = intval($_POST['booking_id']);
    $rating = intval($_POST['rating']);
    $comment = $conn->real_escape_string(trim($_POST['comment']));

    // Check if review already exists
    $check = $conn->query("SELECT 1 FROM reviews WHERE booking_id = $booking_id");
    if ($check && $check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, rating, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $booking_id, $rating, $comment);
        if ($stmt->execute()) {
            $message = "Review submitted successfully!";
        } else {
            $message = "Error submitting review.";
        }
        $stmt->close();
    } else {
        $message = "Review already submitted for this booking.";
    }
}

// Fetch completed bookings that don't have a review yet
$sql = "
    SELECT b.booking_id, s.title, sp.profession, b.booking_time 
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_providers sp ON s.provider_id = sp.provider_id
    WHERE b.user_id = $user_id 
      AND b.status = 'completed'
      AND NOT EXISTS (
          SELECT 1 FROM reviews r WHERE r.booking_id = b.booking_id
      )
    ORDER BY b.booking_time DESC
";
$result = $conn->query($sql);
$pending_reviews = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Reviews - Hire Per Hour</title>
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
        
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .page-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .page-header i {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 2rem;
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
        
        .review-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
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
        
        .review-service {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .review-profession {
            color: var(--primary);
            margin-bottom: 1rem;
            display: inline-block;
            background: rgba(106, 17, 203, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
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
            gap: 0.5rem;
        }
        
        .rating-select select {
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            background-color: rgba(245, 245, 245, 0.5);
            min-width: 80px;
        }
        
        .stars-preview {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(245, 245, 245, 0.5);
            min-height: 120px;
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
    <p>Help us improve by leaving your feedback</p>
</header>

<nav>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="container">
    <div class="page-header">
        <i class="fas fa-star"></i>
        <h2>Submit Reviews</h2>
    </div>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
            <i class="fas <?= strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($pending_reviews)): ?>
        <?php foreach ($pending_reviews as $review): ?>
            <div class="review-card">
                <div class="review-service"><?= htmlspecialchars($review['title']) ?></div>
                <span class="review-profession"><?= htmlspecialchars($review['profession']) ?></span>
                <div class="review-date">
                    <i class="far fa-calendar-alt"></i> <?= htmlspecialchars($review['booking_time']) ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?= $review['booking_id'] ?>">
                    
                    <div class="form-group">
                        <label for="rating_<?= $review['booking_id'] ?>">Rating</label>
                        <div class="rating-select">
                            <select name="rating" id="rating_<?= $review['booking_id'] ?>" required onchange="updateStars(this)">
                                <option value="">Select rating</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> star<?= $i !== 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="stars-preview" id="stars_<?= $review['booking_id'] ?>"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment_<?= $review['booking_id'] ?>">Your Feedback</label>
                        <textarea name="comment" id="comment_<?= $review['booking_id'] ?>" 
                                  placeholder="Share your experience with this service..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-reviews">
            <i class="far fa-check-circle"></i>
            <h3>No Reviews Pending</h3>
            <p>You don't have any completed bookings that need reviewing at this time.</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

<script>
    function updateStars(selectElement) {
        const rating = selectElement.value;
        const starsContainer = document.getElementById('stars_' + selectElement.id.split('_')[1]);
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