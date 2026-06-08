<?php
include '../db/conn.php';

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $role_name = trim($_POST["role_name"]);

    // Check if any field is empty
    if (empty($username) || empty($password) || empty($first_name)|| empty($last_name) || empty($role_name)) {
        $error_message = "All fields are required.";
    } elseif (strlen($password) < 8 || strpos($password, " ") !== false) {
        $error_message = "Password must be at least 8 characters long and contain no spaces.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "Username is already taken. Please choose another.";
        } else {
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin (add role_name)
            $stmt = $conn->prepare("INSERT INTO admins (username, password, first_name,middle_name, last_name, role_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashed_password, $first_name,$middle_name, $last_name, $role_name);

            if ($stmt->execute()) {
                $success_message = "Admin registered successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Admin Management | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
         :root {
            --primary-color: #388E3C;
            --primary-light: #4CAF50;
            --primary-dark: #2E7D32;
            --secondary-color: #FFC107;
            --accent-color: #FF5722;
            --dark: #263238;
            --light: #f5f5f5;
            --gray: #757575;
            --light-gray: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--dark);
            min-height: 100vh;
        }
        
        .card {
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .card-header h4 {
            font-weight: 600;
            margin: 0;
            color: white;
        }
        
        .card-body {
            padding: 2rem;
        }
        .main-content{
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
        }
        .section-title {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s;
        }
        
        .btn-success:active {
            transform: scale(0.98);
        }
        .main-content {
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 73%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .position-relative {
            position: relative;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
            .card{
                margin-top: 10px;
            }
            .card-header h4{   
                margin-top: 10px;
            }

            .mobile-toggle {
                display: block;
            }

            .header-search input {
                width: 180px;
            }
        }
    </style>
</head>
<body class="bg-light">

<div class="container ">
        <div >
            <?php include 'templates/sidebar.php'; ?>
        </div>
        <div class="main-content ">
        
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                   <h1 class="h3 mt-4 fw-bold">Admin Management</h1>
                   <p class="mb-5">Register New Admin</p>
                    
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
        
        <div class="row ms-12 justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4> <i class="fas fa-user-shield"></i> Register Admin</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)) { echo "<div class='alert alert-success'>$success_message</div>"; } ?>
                        <?php if (!empty($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-2"></i> Username
                                </label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3 position-relative">
                                <label class="form-label">
                                    <i class="fas fa-lock me-2"></i> Password
                                </label>
                                <input type="password" name="password" class="form-control" id="password" required>
                                <i class="fas fa-eye-slash password-toggle" id="togglePassword"></i>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tag me-2"></i> First Name
                                </label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tag me-2"></i> Middle Name
                                </label>
                                <input type="text" name="middle_name" class="form-control" >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-tag me-2"></i> Last Name
                                </label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-shield me-2"></i> Role Name
                                </label>
                                <select name="role_name" class="form-control" required>
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="Veterinarian">Veterinarian</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-user-shield me-2"></i>Register Admin
                            </button>
                        </form>
                    </div> <!-- end of .card-body -->
                </div> <!-- end of .card.shadow -->
            </div> <!-- end of .col-lg-10 -->
        </div> <!-- end of .row.ms-12.justify-content-center -->
    </div> <!-- end of .main-content -->
</div> <!-- end of .container.mt-5 -->

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the eye and eye-slash icon
            if (type === 'text') {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    </script>
</body>
</html>
