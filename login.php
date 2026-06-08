<?php

include 'db/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Check if username or password contains spaces
    if (strpos($username, ' ') !== false || strpos($password, ' ') !== false) {
        $_SESSION['error'] = "Username and password cannot contain spaces!";
        header("Location: login.php");
        exit();
    }
    
    // Check in Admins Table - using binary comparison for case sensitivity
    $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE BINARY username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            // Terminate any previous session
            session_unset();
            session_destroy();
            session_start(); // Start a new session
            
            $_SESSION['admin_id'] = $admin['admin_id'];
            header("Location: admin/index.php");
            exit();
        }
    }

    // Check in Owners Table - using binary comparison for case sensitivity
    $stmt = $conn->prepare("SELECT owner_id, password, status FROM owners WHERE BINARY email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $owner = $result->fetch_assoc();

        if (password_verify($password, $owner['password'])) {
            // Check if account is verified
            if ($owner['status'] === 'pending') {
                $_SESSION['error'] = "Your account is pending approval. Please visit the City Vet Office to complete verification.";
                header("Location: login.php");
                exit();
            } elseif ($owner['status'] === 'suspended') {
                $_SESSION['error'] = "Your account has been suspended. Please contact the City Vet Office for assistance.";
                header("Location: login.php");
                exit();
            }
            
            // Terminate any previous session
            session_unset();
            session_destroy();
            session_start(); // Start a new session
            
            $_SESSION['owner_id'] = $owner['owner_id'];
            header("Location: owner/index_owner.php");
            exit();
        }
    }

    // Invalid login
    $_SESSION['error'] = "Invalid username or password!";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/scanAndTagLogo.png" />
    <title>Login | Pet Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

    <style>
   :root {
    --primary-color: #16a34a;
    --secondary-color: #22c55e;
    --accent-color:#22c55e;
    --light-bg: #f4f6f8;
    --text-color: #2c3e50;
    --danger-color: #e74c3c;
    --success-color: #2ecc71;
}

body {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    color: var(--text-color);
    overflow: hidden;
}

.login-container {
    z-index: 2;
    max-width: 460px;
    width: 100%;
    margin: auto;
    animation: fadeIn 1s ease;
}

.login-card {
    background: white;
    border-radius: 16px;
    border:1px solid #2c3e50;
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    overflow: hidden;
    transform: scale(1);
    transition: transform 0.3s ease;
}

.login-card:hover {
    transform: scale(1.01);
}

.login-header {
    padding: 30px;
    text-align: center;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.login-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.login-sub-title {
    font-size: 18px;
    opacity: 0.9;
}

.login-body {
    padding: 35px 30px;
}

.form-label {
    font-weight: 600;
    color: var(--text-color);
}

.input-group-text,
.password-toggle {
    background-color: var(--light-bg);
    border: 1.5px solid #d1d8e0;
    color: var(--secondary-color);
}

.form-control {
    border-radius: 6px;
    padding: 12px 14px;
    font-size: 14px;
    border: 1.5px solid #d1d8e0;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 4px rgba(52,152,219,0.15);
}

.login-btn {
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 8px;
    font-weight: 600;
    background-color: var(--primary-color);
    color: white;
    border: none;
    transition: background 0.3s, transform 0.2s;
}

.login-btn:hover {
    background-color: var(--secondary-color);
    color: white;
    
}

.qr-btn {
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    background-color: transparent;
    border: 2px solid var(--accent-color);
    color: var(--accent-color);
    transition: all 0.3s ease;
}

.qr-btn:hover {
    background-color: rgba(52,152,219,0.1);
   
}


.alert {
    border-radius: 6px;
    border-left: 4px solid var(--danger-color);
    font-size: 14px;
}

.modal-content {
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
    animation: zoomIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}


#qr-reader {
    border: 2px dashed var(--accent-color);
    border-radius: 12px;
    padding: 10px;
}

.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 100vh;
}

.container::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    transform: translate(-50%, -50%);
    background: url('images/cityVetLogo.png') no-repeat center center;
    background-size: contain;
    opacity: 0.10; /* Subtle background */
    z-index: 0;
    pointer-events: none;
}

.invalid-feedback {
    display: none;
    color: var(--danger-color);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

</style>
</head>
<body>
  <div class="container">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h4 class="login-title">La Carlota City Veterinary Office</h4>
                <h4 class="login-sub-title">Animal Registration System</h4>
            </div>
            <div class="login-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" id="loginForm" novalidate>
                    <div class="mb-4">
                        <label class="form-label">Username / Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username or email" required onkeypress="return event.charCode !== 32">
                        </div>
                        <div class="invalid-feedback" id="usernameError">Username cannot contain spaces</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group password-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required onkeypress="return event.charCode !== 32">
                            <button type="button" class="btn password-toggle" id="togglePassword" tabindex="-1">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="passwordError">Password cannot contain spaces</div>
                    </div>
                  
                    <div class="action-buttons">
                        <button type="submit" class="login-btn w-100">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                        
                        <button type="button" class="btn qr-btn w-100" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                            <i class="fas fa-qrcode me-2"></i> Scan QR Code
                        </button>
                        
                        <div class="text-center mt-3 ">
                            <h6>Don't have an account yet?</h6>

                            <a href="owner_preregister.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-user-plus me-2"></i> Pre-Register as Owner
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
            <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="successModalLabel">Success</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                          QR Code Scanned Successfully!
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
           <!-- QR Scanner Modal -->
        <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrScannerModalLabel">Scan QR Code</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="qr-reader" style="width: 100%;"></div>
                        <p class="mt-3"><strong>Result:</strong> <span id="qr-result">No QR Code Scanned</span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                // Toggle the password field type
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Form validation to prevent spaces
            const loginForm = document.getElementById('loginForm');
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const usernameError = document.getElementById('usernameError');
            const passwordError = document.getElementById('passwordError');
            
            // Disable paste with spaces
            username.addEventListener('paste', preventSpacesOnPaste);
            password.addEventListener('paste', preventSpacesOnPaste);
            
            function preventSpacesOnPaste(e) {
                // Get pasted data
                let pastedText;
                if (window.clipboardData && window.clipboardData.getData) {
                    pastedText = window.clipboardData.getData('Text');
                } else if (e.clipboardData && e.clipboardData.getData) {
                    pastedText = e.clipboardData.getData('text/plain');
                }
                
                // If pasted text contains spaces, prevent default and show error
                if (pastedText && pastedText.includes(' ')) {
                    e.preventDefault();
                    if (this.id === 'username') {
                        usernameError.style.display = 'block';
                        username.classList.add('is-invalid');
                    } else {
                        passwordError.style.display = 'block';
                        password.classList.add('is-invalid');
                    }
                    
                    // Optional: Insert filtered text without spaces
                    const filteredText = pastedText.replace(/\s/g, '');
                    if (filteredText) {
                        document.execCommand('insertText', false, filteredText);
                    }
                }
            }
            
            loginForm.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Reset error states
                usernameError.style.display = 'none';
                passwordError.style.display = 'none';
                
                // Double-check for spaces in username (in case JavaScript was bypassed)
                if (username.value.includes(' ')) {
                    usernameError.style.display = 'block';
                    username.classList.add('is-invalid');
                    isValid = false;
                } else {
                    username.classList.remove('is-invalid');
                }
                
                // Double-check for spaces in password (in case JavaScript was bypassed)
                if (password.value.includes(' ')) {
                    passwordError.style.display = 'block';
                    password.classList.add('is-invalid');
                    isValid = false;
                } else {
                    password.classList.remove('is-invalid');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
            
            // Add input event listeners to hide error messages when user starts typing
            username.addEventListener('input', function() {
                // Also filter out any spaces that might have gotten in
                if (this.value.includes(' ')) {
                    this.value = this.value.replace(/\s/g, '');
                }
                usernameError.style.display = 'none';
                this.classList.remove('is-invalid');
            });
            
            password.addEventListener('input', function() {
                // Also filter out any spaces that might have gotten in
                if (this.value.includes(' ')) {
                    this.value = this.value.replace(/\s/g, '');
                }
                passwordError.style.display = 'none';
                this.classList.remove('is-invalid');
            });
        });
        
         // QR Scanner
         let qrScanner;
        function onScanSuccess(decodedText) {
            console.log("Scanned QR Code:", decodedText);
            document.getElementById('qr-result').innerText = decodedText;

            const qrModal = bootstrap.Modal.getInstance(document.getElementById('qrScannerModal'));
            if (qrModal) qrModal.hide();

            setTimeout(() => {
                new bootstrap.Modal(document.getElementById('successModal')).show();
            }, 500);

            setTimeout(() => {
                window.location.href = decodedText.startsWith("http") ? decodedText 
                    : "animal_details.php?id=" + encodeURIComponent(decodedText);
            }, 2500);
        }

        function onScanError(errorMessage) {
            console.error("QR Scan Error:", errorMessage);
            document.getElementById('qr-result').innerText = "Error: " + errorMessage;
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("qrScannerModal").addEventListener("shown.bs.modal", function() {
                if (!qrScanner) {
                    qrScanner = new Html5QrcodeScanner("qr-reader", { 
                        fps: 10, 
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0,
                        showTorchButtonIfSupported: true,
                        showZoomSliderIfSupported: true
                    });
                    qrScanner.render(onScanSuccess, onScanError);
                }
            });

            document.getElementById("qrScannerModal").addEventListener("hidden.bs.modal", function() {
                if (qrScanner) {
                    qrScanner.clear();
                    qrScanner = null;
                }
            });
        });
    </script>
</body>
</html>