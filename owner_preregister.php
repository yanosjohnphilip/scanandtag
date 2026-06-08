<?php
session_start();
include 'db/conn.php';

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"] ?? "");
    $middle_name = trim($_POST["middle_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $barangay = trim($_POST["barangay"] ?? "");
    $birthdate = trim($_POST["birthdate"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $zip_code = trim($_POST["zip_code"] ?? "");
    $latitude = trim($_POST["latitude"] ?? "");
    $longitude = trim($_POST["longitude"] ?? "");

    // Calculate Age from Birthdate
    $today = new DateTime();
    $dob = new DateTime($birthdate);
    $age = $dob->diff($today)->y;

    if ($age < 18) {
        $error_message = "You must be 18 years old or above to register.";
    }

    // Enhanced Password Validation
    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (preg_match('/\s/', $password)) {
        $error_message = "Password must not contain spaces.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error_message = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error_message = "Password must contain at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error_message = "Password must contain at least one special character.";
    }

    // Check if Latitude and Longitude are selected first
    if (empty($latitude) || empty($longitude)) {
        $error_message = "Please select a location on the map.";
    }

    // Check if email already exists
    if (empty($error_message)) {
        $check_email = $conn->prepare("SELECT owner_id FROM owners WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email address already exists. Please use a different email.";
        }
        $check_email->close();
    }

    // Ensure all required fields are filled
    if (empty($error_message) && (empty($first_name) || empty($last_name) || empty($phone) || empty($address) || empty($birthdate))) {
        $error_message = "Please fill in all required fields.";
    }
    if (empty($barangay)) {
        $error_message = "Please select a barangay.";
    }

    if (empty($error_message)) {
        if (!preg_match('/^09\d{9}$/', $phone)) {
            $error_message = "Phone number must be 11 digits and start with 09.";
        }
    }

    if (empty($error_message)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $status = 'pending'; // Pre-registration status
        $owner_id = 'OWN' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        // Check if owner_id already exists
        $check_id = $conn->prepare("SELECT owner_id FROM owners WHERE owner_id = ?");
        $check_id->bind_param("s", $owner_id);
        $check_id->execute();
        $result = $check_id->get_result();
        
        while ($result->num_rows > 0) {
            $owner_id = 'OWN' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $check_id->bind_param("s", $owner_id);
            $check_id->execute();
            $result = $check_id->get_result();
        }
        $check_id->close();

        $stmt = $conn->prepare("INSERT INTO owners (owner_id, first_name, middle_name, last_name, email, password, phone, address, birthdate, city, barangay, province, zip_code, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssss", $owner_id, $first_name, $middle_name, $last_name, $email, $hashed_password, $phone, $address, $birthdate, $city, $barangay, $province, $zip_code, $latitude, $longitude, $status);
        
        if ($stmt->execute()) {
            $success_message = "Pre-registration submitted successfully! ";
            $_SESSION['preregistration_success'] = true;
            $_SESSION['owner_id'] = $owner_id;
        } else {
            error_log($stmt->error);
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/scanAndTagLogo.png" />
    <title>Pre-Register as Owner | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #16a34a;
            --secondary-color: #22c55e;
            --accent-color: #22c55e;
            --light-bg: #f4f6f8;
            --text-color: #2c3e50;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
        }
        
        body {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
        }
        
        .preregister-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 2rem;
            border-bottom: none;
            text-align: center;
        }
        
        .card-header h2 {
            font-weight: 700;
            margin: 0;
            color: white;
            font-size: 2rem;
        }
        
        .card-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.5rem 0 0 0;
        }
        
        .card-body {
            padding: 3rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.875rem 1rem;
            border-radius: 12px;
            border: 2px solid #d1d8e0;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.15);
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
            font-weight: bold;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            font-weight: 500;
        }
        
        #map { 
            height: 350px; 
            border-radius: 12px; 
            margin-top: 10px;
            border: 2px solid #d1d8e0;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #d1d8e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        
        .step.active {
            background: var(--primary-color);
        }
        
        .step.completed {
            background: var(--secondary-color);
        }
        
        .back-to-login {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .back-to-login:hover {
            color: white;
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-box h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: var(--text-color);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .password-strength {
            height: 6px;
            margin-top: 8px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .requirement {
            color: #6c757d;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        .requirement i {
            margin-right: 8px;
            width: 16px;
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 2rem 1.5rem;
            }
            
            .card-header h2 {
                font-size: 1.5rem;
            }
            
            .back-to-login {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 1rem;
                display: inline-block;
            }
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-to-login">
        <i class="fas fa-arrow-left me-2"></i>Back to Login
    </a>

    <div class="preregister-container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus me-3"></i>Pre-Register as Owner</h2>
                <p>Submit your information for verification at the City Vet Office</p>
            </div>
            <div class="card-body">
                <!-- Info Box -->
                <div class="info-box">
                    <h6><i class="fas fa-info-circle me-2"></i>Pre-Registration Process</h6>
                    <p>1. Fill out the form below → 2. Visit City Vet Office with valid ID → 3. Complete registration → 4. Register your animals</p>
                </div>

                
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Pre-Registration Form -->
                <form method="POST" id="preregistrationForm">
                    <h5 class="mb-4 text-center text-success">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">First Name</label>
                                <input type="text" name="first_name" class="form-control" required pattern="[A-Za-z\s'-]+" title="Letters, spaces, apostrophes, and hyphens only" oninput="this.value = this.value.replace(/[^A-Za-z\s'-]/g, '')">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" pattern="[A-Za-z\s'-]+" oninput="this.value = this.value.replace(/[^A-Za-z\s'-]/g, '')">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required pattern="[A-Za-z\s'-]+" title="Letters, spaces, apostrophes, and hyphens only" oninput="this.value = this.value.replace(/[^A-Za-z\s'-]/g, '')">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Phone Number</label>
                                <input type="tel" name="phone" id="phone" class="form-control" pattern="09[0-9]{9}" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,11);" required>
                                <small id="phoneError" class="text-danger small d-none mt-1">Phone number must be 11 digits and start with 09.</small>
                            </div>
                        </div>
                        
                            <div class="mb-3">
                                <label class="form-label required-field">Birthdate</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" required>
                                <small id="ageError" class="text-danger d-none">You must be 18 years old or above.</small>
                            </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Password</label>
                                <div class="position-relative">
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <i class="fa fa-eye-slash position-absolute" id="togglePassword" style="top: 50%; right: 18px; transform: translateY(-50%); cursor: pointer;"></i>
                                </div>
                                <div class="password-strength"></div>
                                <div class="password-requirements mt-2 d-none" id="password-requirements">
                                    <div class="requirement d-none" id="length-req"><i class="fa fa-times-circle"></i> At least 8 characters</div>
                                    <div class="requirement d-none" id="uppercase-req"><i class="fa fa-times-circle"></i> At least 1 uppercase letter</div>
                                    <div class="requirement d-none" id="lowercase-req"><i class="fa fa-times-circle"></i> At least 1 lowercase letter</div>
                                    <div class="requirement d-none" id="number-req"><i class="fa fa-times-circle"></i> At least 1 number</div>
                                    <div class="requirement d-none" id="symbol-req"><i class="fa fa-times-circle"></i> At least 1 special character</div>
                                    <div class="requirement d-none" id="space-req"><i class="fa fa-times-circle"></i> No spaces allowed</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-4 text-center text-success mt-4">
                        <i class="fas fa-map-marker-alt me-2"></i>Location Information
                    </h5>
                    
                    <div class="mb-3">
                        <label class="form-label required-field">Street Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label required-field">Barangay</label>
                            <select name="barangay" class="form-control" required>
                                <option value="">-- Select Barangay --</option>
                                <option value="Ara-al">Ara-al</option>
                                <option value="Ayungon">Ayungon</option>
                                <option value="Balabag">Balabag</option>
                                <option value="Batuan">Batuan</option>
                                <option value="Cubay">Cubay</option>
                                <option value="Haguimit">Haguimit</option>
                                <option value="La Granja">La Granja</option>
                                <option value="Nagasi">Nagasi</option>
                                <option value="Barangay I (Poblacion)">Barangay I (Poblacion)</option>
                                <option value="Barangay II (Poblacion)">Barangay II (Poblacion)</option>
                                <option value="Barangay III (Poblacion)">Barangay III (Poblacion)</option>
                                <option value="Barangay RSB (Consuelo)">Barangay RSB (Consuelo)</option>
                                <option value="San Miguel">San Miguel</option>
                                <option value="Yubo">Yubo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city" class="form-control">
                                <option selected>La Carlota City</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Province</label>
                            <select name="province" class="form-control">
                                <option selected>Negros Occidental</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required-field">Select Your Location on Map</label>
                        <div id="map"></div>
                    </div>

                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="zip_code" value="6130">

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-register btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i>Submit Pre-Registration
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted">
                            Already have an account? 
                            <a href="login.php" class="text-success fw-bold">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password validation
            const passwordInput = document.getElementById('password');
            const lengthReq = document.getElementById('length-req');
            const uppercaseReq = document.getElementById('uppercase-req');
            const lowercaseReq = document.getElementById('lowercase-req');
            const numberReq = document.getElementById('number-req');
            const symbolReq = document.getElementById('symbol-req');
            const spaceReq = document.getElementById('space-req');
            const strengthBar = document.querySelector('.password-strength');

            const passwordRequirementsDiv = document.getElementById('password-requirements');
            const phoneInput = document.getElementById('phone');
            const phoneError = document.getElementById('phoneError');
            
            // Show password requirements when user focuses on password field
            passwordInput.addEventListener('focus', function() {
                const password = this.value;
                if (password.length > 0) {
                    passwordRequirementsDiv.classList.remove('d-none');
                }
            });

            phoneInput.addEventListener('input', function() {
                const phonePattern = /^09\d{9}$/;
                const value = this.value.trim();
                if (value.length === 0 || phonePattern.test(value)) {
                    phoneError.classList.add('d-none');
                } else {
                    phoneError.classList.remove('d-none');
                }
            });
            
            // Show only failed requirements when user finishes typing (on blur)
            passwordInput.addEventListener('blur', function() {
                const password = this.value;
                if (password.length > 0) {
                    const hasLength = password.length >= 8;
                    const hasUppercase = /[A-Z]/.test(password);
                    const hasLowercase = /[a-z]/.test(password);
                    const hasNumber = /[0-9]/.test(password);
                    const hasSymbol = /[^A-Za-z0-9]/.test(password);
                    const hasNoSpaces = !/\s/.test(password);
                    
                    // Show only the requirements that are not met
                    toggleRequirementVisibility(lengthReq, !hasLength);
                    toggleRequirementVisibility(uppercaseReq, !hasUppercase);
                    toggleRequirementVisibility(lowercaseReq, !hasLowercase);
                    toggleRequirementVisibility(numberReq, !hasNumber);
                    toggleRequirementVisibility(symbolReq, !hasSymbol);
                    toggleRequirementVisibility(spaceReq, !hasNoSpaces);
                    
                    // Show requirements container if any requirement is not met
                    if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSymbol || !hasNoSpaces) {
                        passwordRequirementsDiv.classList.remove('d-none');
                    } else {
                        passwordRequirementsDiv.classList.add('d-none');
                    }
                }
            });
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let score = 0;
                
                // Show requirements container if password is being entered
                if (password.length > 0) {
                    passwordRequirementsDiv.classList.remove('d-none');
                } else {
                    passwordRequirementsDiv.classList.add('d-none');
                }
                
                // Validate minimum length
                const hasLength = password.length >= 8;
                updateRequirement(lengthReq, hasLength);
                if (hasLength) score += 20;
                
                // Validate uppercase
                const hasUppercase = /[A-Z]/.test(password);
                updateRequirement(uppercaseReq, hasUppercase);
                if (hasUppercase) score += 20;
                
                // Validate lowercase
                const hasLowercase = /[a-z]/.test(password);
                updateRequirement(lowercaseReq, hasLowercase);
                if (hasLowercase) score += 20;
                
                // Validate numbers
                const hasNumber = /[0-9]/.test(password);
                updateRequirement(numberReq, hasNumber);
                if (hasNumber) score += 20;
                
                // Validate special characters
                const hasSymbol = /[^A-Za-z0-9]/.test(password);
                updateRequirement(symbolReq, hasSymbol);
                if (hasSymbol) score += 20;
                
                // Validate no spaces
                const hasNoSpaces = !/\s/.test(password);
                updateRequirement(spaceReq, hasNoSpaces);
                if (!hasNoSpaces) score = 0;
                
                // Update strength bar
                updateStrengthBar(strengthBar, score);
            });

            function updateRequirement(element, valid) {
                if (valid) {
                    element.classList.add('met');
                    element.querySelector('i').className = 'fa fa-check-circle';
                    // Hide met requirements
                    element.classList.add('d-none');
                } else {
                    element.classList.remove('met');
                    element.querySelector('i').className = 'fa fa-times-circle';
                    // Show unmet requirements
                    element.classList.remove('d-none');
                }
            }
            
            function toggleRequirementVisibility(element, shouldShow) {
                if (shouldShow) {
                    element.classList.remove('d-none');
                } else {
                    element.classList.add('d-none');
                }
            }

            function updateStrengthBar(bar, score) {
                bar.style.width = score + '%';
                
                if (score < 40) {
                    bar.style.backgroundColor = '#dc3545';
                } else if (score < 80) {
                    bar.style.backgroundColor = '#ffc107';
                } else {
                    bar.style.backgroundColor = '#28a745';
                }
            }

            // Toggle password visibility
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordField = document.getElementById('password');
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                if (type === 'text') {
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                } else {
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                }
            });

            // Age validation
            document.getElementById('birthdate').addEventListener('change', function() {
                validateAge();
            });

            function validateAge() {
                let birthdate = document.getElementById('birthdate').value;
                let today = new Date();
                let dob = new Date(birthdate);
                let age = today.getFullYear() - dob.getFullYear();
                
                let m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (isNaN(age) || age < 18) {
                    document.getElementById('ageError').classList.remove('d-none');
                    return false;
                } else {
                    document.getElementById('ageError').classList.add('d-none');
                    return true;
                }
            }

            // Form validation
            document.getElementById('preregistrationForm').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                let valid = true;
                
                // Check password requirements
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSymbol = /[^A-Za-z0-9]/.test(password);
                const hasNoSpaces = !/\s/.test(password);
                
                if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSymbol || !hasNoSpaces) {
                    valid = false;
                    // Show only the requirements that are not met
                    toggleRequirementVisibility(lengthReq, !hasLength);
                    toggleRequirementVisibility(uppercaseReq, !hasUppercase);
                    toggleRequirementVisibility(lowercaseReq, !hasLowercase);
                    toggleRequirementVisibility(numberReq, !hasNumber);
                    toggleRequirementVisibility(symbolReq, !hasSymbol);
                    toggleRequirementVisibility(spaceReq, !hasNoSpaces);
                    passwordRequirementsDiv.classList.remove('d-none');
                    alert("Please make sure your password meets all requirements");
                }
                
                // Check age
                if (!validateAge()) {
                    valid = false;
                }
                
                // Check location selected
                var latitude = document.getElementById('latitude').value;
                var longitude = document.getElementById('longitude').value;
                if (!latitude || !longitude) {
                    valid = false;
                    alert("⚠️ Please select a location on the map before submitting.");
                }
                
                const phonePattern = /^09\d{9}$/;
                if (!phonePattern.test(phoneInput.value.trim())) {
                    valid = false;
                    phoneError.classList.remove('d-none');
                }

                if (!valid) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            // Map functionality
            var map = L.map('map').setView([10.4242, 122.9216], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            var redIcon = L.icon({
                iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            }); 
            
            var marker = L.marker([10.4242, 122.9216], { icon: redIcon, draggable: true }).addTo(map);

            function updateLatLng(lat, lng) {
                document.getElementById('latitude').value = lat.toFixed(6);
                document.getElementById('longitude').value = lng.toFixed(6);
            }

            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateLatLng(e.latlng.lat, e.latlng.lng);
            });

            marker.on('dragend', function(e) {
                var position = marker.getLatLng();
                updateLatLng(position.lat, position.lng);
            });

            // Barangay coordinates mapping
            const barangayCoords = {
                "Ara-al": [10.419781, 123.042601],
                "Ayungon": [10.459981, 122.918824],
                "Balabag": [10.433793, 122.978029],
                "Batuan": [10.445484, 122.907122],
                "Cubay": [10.418430, 122.948627],
                "Haguimit": [10.399014, 123.018581],
                "La Granja": [10.405767, 122.996593],
                "Nagasi": [10.400111, 122.973172],
                "Barangay I (Poblacion)": [10.424677, 122.923209],
                "Barangay II (Poblacion)": [10.431134, 122.921794],
                "Barangay III (Poblacion)": [10.419401, 122.917033],
                "Barangay RSB (Consuelo)": [10.392429, 122.927988],
                "San Miguel": [10.415813, 123.021383],
                "Yubo": [10.390361, 123.046768]
            };

            document.querySelector('select[name="barangay"]').addEventListener('change', function() {
                const barangay = this.value;
                if (barangayCoords[barangay]) {
                    const coords = barangayCoords[barangay];
                    map.setView(coords, 16);
                    marker.setLatLng(coords);
                    updateLatLng(coords[0], coords[1]);
                }
            });
        });
    </script>
</body>
</html>
