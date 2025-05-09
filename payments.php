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


if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    die("No booking to pay for.");
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];


$sql = "SELECT b.service_id, b.total_hours, s.rate_per_hour, s.title, u.name AS provider_name
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON s.provider_id = u.user_id
        WHERE b.booking_id = ? AND b.customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found or you do not have permission.");
}

$data = $result->fetch_assoc();
$hours = $data['total_hours'];
$rate = $data['rate_per_hour'];
$total_amount = $rate * $hours;
$service_title = $data['title'];
$provider_name = $data['provider_name'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sql = "UPDATE payments SET status = 'paid', paid_at = NOW(), amount = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $total_amount, $booking_id);
    $stmt->execute();


    $sql = "UPDATE bookings SET status = 'accepted' WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    
    header("Location: my_bookings.php?payment=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - Hire Per Hour</title>
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
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        .payment-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .payment-header i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .payment-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .detail-item {
            background: rgba(245, 245, 245, 0.5);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }
        
        .detail-item strong {
            display: block;
            font-size: 0.9rem;
            color: var(--dark);
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .detail-item span {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .total-amount {
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.1), rgba(37, 117, 252, 0.1));
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin: 2rem 0;
        }
        
        .total-amount strong {
            font-size: 1rem;
            color: var(--dark);
        }
        
        .total-amount span {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
            margin-top: 0.5rem;
        }
        
        .payment-form {
            margin-top: 2rem;
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
            margin-top: 1.5rem;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
            
            .container {
                padding: 0 1rem;
                margin: 2rem auto;
            }
            
            .payment-card {
                padding: 1.5rem;
            }
            
            .payment-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>Complete Your Payment</h1>
    <p>Secure and easy payment process</p>
</header>

<nav>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> My Bookings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>

<div class="container">
    <div class="payment-card">
        <div class="payment-header">
            <i class="fas fa-credit-card"></i>
            <h2>Payment Summary</h2>
            <p>Review your booking details before payment</p>
        </div>
        
        <div class="payment-details">
            <div class="detail-item">
                <strong>Service</strong>
                <span><?= htmlspecialchars($service_title) ?></span>
            </div>
            
            <div class="detail-item">
                <strong>Provider</strong>
                <span><?= htmlspecialchars($provider_name) ?></span>
            </div>
            
            <div class="detail-item">
                <strong>Hours Booked</strong>
                <span><?= $hours ?> hours</span>
            </div>
            
            <div class="detail-item">
                <strong>Rate Per Hour</strong>
                <span>$<?= number_format($rate, 2) ?></span>
            </div>
        </div>
        
        <div class="total-amount">
            <strong>Total Amount Due</strong>
            <span>$<?= number_format($total_amount, 2) ?></span>
        </div>
        
        <form method="POST" class="payment-form">
            <div class="form-group">
                <label for="card-number"><i class="fas fa-credit-card"></i> Card Number</label>
                <input type="text" id="card-number" placeholder="1234 5678 9012 3456" required>
            </div>
            
            <div class="form-group">
                <label for="card-name"><i class="fas fa-user"></i> Name on Card</label>
                <input type="text" id="card-name" placeholder="John Doe" required>
            </div>
            
            <div class="payment-details">
                <div class="form-group">
                    <label for="expiry"><i class="fas fa-calendar-alt"></i> Expiry Date</label>
                    <input type="text" id="expiry" placeholder="MM/YY" required>
                </div>
                
                <div class="form-group">
                    <label for="cvv"><i class="fas fa-lock"></i> CVV</label>
                    <input type="text" id="cvv" placeholder="123" required>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-lock"></i> Pay $<?= number_format($total_amount, 2) ?> Now
            </button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Hire Per Hour. All rights reserved.</p>
</footer>

</body>
</html>