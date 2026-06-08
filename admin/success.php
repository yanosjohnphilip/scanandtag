<?php
if (!isset($_GET['animal_id'])) {
    header("Location: animal_Registration_Form.php");
    exit();
}

$animal_id = $_GET['animal_id'];
// Fetch the animal details including the QR code from the database
include '../db/conn.php';
$stmt = $conn->prepare("SELECT animal_name, qr_code FROM animals WHERE animal_id = ?");
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $animal = $result->fetch_assoc();
    $animal_name = $animal['animal_name'];
    $qr_code = $animal['qr_code'];
} else {
    echo "Animal not found.";
    exit();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Success</title>
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background-color: #f5f5f5;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
           
            z-index: -1;
        }

        .card {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.4);
        
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            z-index: 1;
            margin: 20px;
            backdrop-filter: blur(5px);
            
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            border: none;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 30px;
            text-align: center;
            
        }
        
        .card-body p{
            
            color: #000;
            font-size: 1rem;
        }

        .btn {
            margin: 10px 5px;
            min-width: 200px;
            border-radius: 25px;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }

        .btn-outline-secondary {
            color:#3a3a3a;
            border-color:#3a3a3a;
        }

        .btn-outline-secondary:hover {
            background-color: #3a3a3a;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .qr-code {
            max-width: 220px;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: transform 0.3s ease;
        }

        .qr-code:hover {
            transform: scale(1.02);
        }

        .home-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .home-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-50%) scale(1.1);
            color: white;
        }

        .animal-name {
            color: #2E7D32;
            font-weight: 700;
            margin: 10px 0;
            font-size: 2rem;
        }

        .success-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .card {
                margin: 10px;
                max-width: 100%;
            }
            
            .btn {
                width: 100%;
                margin: 8px 0;
            }
            
            .card-body {
                padding: 20px 15px;
            }

            .card-header h4 {
                font-size: 1.3rem;
            }

            .home-btn {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i>
            </a>
            <h4>Registration Successful!</h4>
        </div>
        <div class="card-body">
           
            <div class="animal-name">
                <i class="fas fa-paw me-2"></i>
                <?php echo htmlspecialchars($animal_name); ?>
            </div>
            <p class="text-muted mb-4">Scan this QR code to view pet details</p>
            <div class="qr-code">
                <img src="<?php echo htmlspecialchars($qr_code); ?>" alt="QR Code" class="img-fluid" />
            </div>
            <div class="mt-4 d-flex flex-column flex-md-row justify-content-center gap-2">
                <a href="<?php echo htmlspecialchars($qr_code); ?>" download class="btn btn-success">
                    <i class="fas fa-download"></i> Download QR Code
                </a>
                <a href="animal_Registration_Form.php" class="btn btn-outline-secondary">
                    <i class="fas fa-plus-circle"></i> Register Another
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scroll to top when page loads
        window.onload = function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
    </script>
</body>
</html>
