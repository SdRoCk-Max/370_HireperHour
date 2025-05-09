<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hireperhour");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';

// Ensure service ID is passed via GET
if (!isset($_GET['id'])) {
    die("Service ID not provided.");
}

$service_id = intval($_GET['id']);

// Fetch service details with provider name
$sql = "SELECT s.title, s.description, s.rate_per_hour, sp.profession, u.name AS provider_name
        FROM services s
        JOIN service_providers sp ON s.provider_id = sp.provider_id
        JOIN users u ON sp.provider_id = u.user_id
        WHERE s.service_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $service = $result->fetch_assoc();
} else {
    die("Service not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hours = $_POST['hours'];
    if (!is_numeric($hours) || $hours <= 0) {
        $error = "Please enter a valid number of hours.";
    } else {
        $total_amount = $service['rate_per_hour'] * $hours;

        // Insert booking
        $sql = "INSERT INTO bookings (service_id, customer_id, total_hours, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $service_id, $user_id, $hours);
        $stmt->execute();
        $booking_id = $stmt->insert_id;

        // Insert payment
        $sql = "INSERT INTO payments (booking_id, amount, status, paid_at) VALUES (?, ?, 'unpaid', NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $booking_id, $total_amount);
        $stmt->execute();

        // Redirect to payment
        header("Location: payments.php?booking_id=$booking_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Hire Per Hour</title>
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
        
        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .service-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
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
        
        .service-card h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .service-details {
            margin-bottom: 1.5rem;
        }
        
        .service-details p {
            margin-bottom: 0.8rem;
        }
        
        .service-details strong {
            color: var(--dark);
        }
        
        .booking-form {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }
        
        .booking-form h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
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
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .error {
            color: var(--accent);
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: rgba(255, 77, 77, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--accent);
        }
        
        .rate-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 768px) {
            header {
                padding: 2rem 1rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .container {
                padding: 0 1rem;
                margin: 2rem auto;
            }
            
            .service-card, .booking-form {
                padding: 1.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Book Service: <?= htmlspecialchars($service['title']) ?></h1>
    <p>Provider: <?= htmlspecialchars($service['provider_name']) ?> - <?= htmlspecialchars($service['profession']) ?></p>
</header>

<div class="container">
    <div class="service-card">
        <h2>Service Details</h2>
        <div class="service-details">
            <p><strong>Description:</strong> <?= htmlspecialchars($service['description']) ?></p>
            <p><strong>Rate:</strong> $<?= htmlspecialchars($service['rate_per_hour']) ?><span class="rate-badge">per hour</span></p>
        </div>
    </div>

    <div class="booking-form">
        <h3><i class="fas fa-calendar-check"></i> Book This Service</h3>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="hours"><i class="fas fa-clock"></i> How many hours?</label>
                <input type="number" id="hours" name="hours" required min="1" step="1" placeholder="Enter hours needed">
            </div>
            <div class="form-group">
                <button type="submit"><i class="fas fa-credit-card"></i> Confirm and Pay</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>