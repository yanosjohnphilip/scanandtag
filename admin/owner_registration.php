<?php
include '../db/conn.php'; // Include database connection

$success_message = "";
$error_message = "";

date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"] ?? "");
    $middle_name = trim($_POST["middle_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? ""); // New: Password Field
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $barangay = trim($_POST["barangay"] ?? "");
    $birthdate = trim($_POST["birthdate"] ?? ""); // New: Birthdate in MM/DD/YYYY format
    $city = trim($_POST["city"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $zip_code = trim($_POST["zip_code"] ?? "");
    $latitude = trim($_POST["latitude"] ?? "");
    $longitude = trim($_POST["longitude"] ?? "");

    // **Calculate Age from Birthdate**
    $today = new DateTime();
    $dob = new DateTime($birthdate);
    $age = $dob->diff($today)->y;

    if ($age < 18) {
        $error_message = "You must be 18 years old or above to register.";
    }

    // **Enhanced Password Validation**
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

    // **Check if Latitude and Longitude are selected first**
    if (empty($latitude) || empty($longitude)) {
        $error_message = "Please select a location on the map.";
    }

    // **Ensure all required fields are filled (if no previous error)**
    if (empty($error_message) && (empty($first_name) || empty($last_name) || empty($phone) || empty($address) || empty($birthdate))) {
        $error_message = "Please fill in all required fields.";
    }
    if (empty($barangay)) {
        $error_message = "Please select a barangay.";
    }

    if (empty($error_message)) { // Only insert if there's no error
        // Check for duplicate email
        $check_email = $conn->prepare("SELECT email FROM owners WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $error_message = "This email is already registered. Please use a different email address.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Secure Password Hashing
            $status = "verified"; // Set status as verified since admin is inputting
            $verified_by = $_SESSION['admin_id'];
            $verifiedAt = new DateTime('now', new DateTimeZone('UTC'));
            $verifiedAt->modify('+8 hours');
            $verified_at = $verifiedAt->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO owners (first_name, middle_name, last_name, email, password, phone, address, birthdate, city, barangay, province, zip_code, latitude, longitude, status, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssssss", $first_name, $middle_name, $last_name, $email, $hashed_password, $phone, $address, $birthdate, $city, $barangay, $province, $zip_code, $latitude, $longitude, $status, $verified_by, $verified_at);
            
            if ($stmt->execute()) {
                $success_message = "Owner registered successfully!";
                // Clear form data on successful submission
                $_POST = array();
            } else {
                error_log($stmt->error); // Log error for debugging
                if ($stmt->errno == 1062) { // MySQL duplicate entry error code
                    $error_message = "This email is already registered. Please use a different email address.";
                } else {
                    $error_message = "An error occurred while saving the data. Please try again.";
                }
            }
            $stmt->close();
        }
        $check_email->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Owner Registration | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <style>
        :root {
            --primary-color: #388E3C;
            --primary-light: #4CAF50;
            --primary-dark: #2E7D32;
            --secondary-color: #FFC107;
            --accent-color: #FF5722;
            --dark: #263238;
            --light:#c6c2c2;
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
        .main-content {
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
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .input-group-text {
            background-color: var(--light);
            border-radius: 10px 0 0 10px;
        }
        
        .medication-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .medication-type {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        
        .image-upload-container {
            border: 2px dashed var(--light-gray);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .image-upload-container:hover {
            border-color: var(--primary-color);
        }
        
        .image-upload-container i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .image-upload-container p {
            margin-bottom: 0;
            color: var(--gray);
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            margin-top: 1rem;
        }
        
        .animal-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(56, 142, 60, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        #map { height: 350px; border-radius: 8px; margin-top: 10px; }
       
        
        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            height: calc(3.5rem + 2px);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
        }
        
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: 10px;
            border: 1px solid var(--primary-color);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown .select2-search__field {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown .select2-search__field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
        }
        
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-color);
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .password-requirements {
            margin-top: 10px;
            font-size: 0.8rem;
        }
        .requirement {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirement i {
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement.valid i {
            color: #28a745;
        }
        
        .requirement.invalid {
            color: #6c757d;
        }
        
        .requirement.invalid i {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            .main-content{
           
            margin-left: 0  ;
            
            
        }
        }
    
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .card{
                margin-top: 10px;
                
            }
            .card-header h4{
                
                margin-top: 10px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
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
<body>

<!-- Main Content -->
<div class="container ">
    <div >
        <?php include 'templates/sidebar.php'; ?>
    </div>
    <div class="main-content ">
        
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                    <h1 class="h3 mt-4 fw-bold">Owner Registration</h1>
                    <p class="mb-5">Only Administrator can Input!</p>
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
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <h4><i class="fas fa-user me-2"></i>Owner Registration</h4>
                    </div>
                    <div class="card-body">
                        
                       

                        <!-- Registration Form -->
                        <form method="POST" id="registrationForm">
                             <!-- Success/Error Messages -->
                            <?php if ($success_message): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            <h5 class="section-title">
                                <span class="animal-icon"><i class="fas fa-user"></i></span>
                                Owner Information
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
                                    <label class="form-label required-field">
                                        <i class="fas fa-calendar-alt me-2"></i>Birthdate
                                    </label>
                                    <input type="date" id="birthdate" name="birthdate" class="form-control" required value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">
                                    <small id="ageError" class="text-danger d-none">You must be 18 years old or above.</small>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required-field">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label required-field">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
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
                                
                                <h5 class="mb-4 text-center text-success mt-4">
                                    <i class="fas fa-map-marker-alt me-2"></i>Location Information
                                </h5>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label required-field">
                                            <i class="fas fa-home me-2"></i>Street
                                        </label>
                                        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required-field">
                                        <i class="fas fa-map-marker-alt me-2"></i>Barangay
                                    </label>
                                    <select name="barangay" class="form-control" required>
                                        <option value="">-- Select Barangay --</option>
                                        <?php $selectedBarangay = $_POST['barangay'] ?? ''; ?>
                                        <?php
                                        
                                        $barangays = [
                                            'Ara-al', 'Ayungon', 'Balabag', 'Batuan', 'Cubay', 'Haguimit',
                                            'La Granja', 'Nagasi', 'Barangay I (Poblacion)', 'Barangay II (Poblacion)',
                                            'Barangay III (Poblacion)', 'Barangay RSB (Consuelo)', 'San Miguel', 'Yubo'
                                        ];
                                        foreach ($barangays as $barangay) {
                                            $selected = ($selectedBarangay === $barangay) ? 'selected' : '';
                                            echo "<option value='$barangay' $selected>$barangay</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        <i class="fas fa-city me-2"></i>City
                                    </label>
                                    <select name="city" class="form-control">
                                        <option selected>La Carlota City</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        <i class="fas fa-map me-2"></i>Province
                                    </label>
                                    <select name="province" class="form-control">
                                        <option selected>Negros Occidental</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        <i class="fas fa-mail-bulk me-2"></i>Zip Code
                                    </label>
                                    <select name="zip_code" class="form-control">
                                        <option selected>6130</option>
                                    </select>
                                </div>

                                <!-- Leaflet Map -->
                                <div class="col-md-12">
                                    <label class="form-label">
                                        <i class="fas fa-map-marked-alt me-2"></i>Select Location
                                    </label>
                                    <div id="map" style="height: 400px;"></div>
                                </div>

                                <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-location-arrow me-2"></i>Latitude: <div class="required-label">*</div>
                                    </label>
                                    <input type="text" id="lat-display" class="form-control" readonly value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-location-arrow me-2"></i>Longitude:<div class="required-label">*</div>
                                    </label>
                                    <input type="text" id="lng-display" class="form-control" readonly value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>Register Owner
                                </button>
                            </div>
                        </form>
                    </div>
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
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
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
                document.getElementById('lat-display').value = lat.toFixed(6);
                document.getElementById('lng-display').value = lng.toFixed(6);
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
