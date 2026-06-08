<?php
include '../db/conn.php';

// Check if user is logged in (optional)
$is_logged_in = isset($_SESSION['admin_id']); // Adjust based on your auth system

// Handle AJAX request for animal ID validation
if (isset($_POST['check_animal_id'])) {
    $animal_id = $_POST['animal_id'];
    
    $stmt = $conn->prepare("SELECT animal_id FROM animals WHERE animal_id = ?");
    $stmt->bind_param("s", $animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Animal Tag Scanner | City Vet System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/dist/html5-qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

    
    <style>
        :root {
            --primary-gradient:linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --secondary-gradient: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --success-gradient: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --accent-gradient: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            --dark-bg: #0f172a;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            --shadow-light: 0 4px 20px rgba(16, 185, 129, 0.1);
            --shadow-medium: 0 8px 40px rgba(16, 185, 129, 0.15);
            --shadow-heavy: 0 20px 80px rgba(16, 185, 129, 0.2);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
           
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .scanner-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            margin-top: 100px;
            margin-left: 460px;
        }
        
        .scanner-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            border: 1px solid rgba(16, 185, 129, 0.2);
            backdrop-filter: blur(10px);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .scanner-header {
            background: var(--primary-gradient);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .scanner-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(180deg); }
        }
       @media (min-width: 1200px) {
    .scanner-container {
        max-width: 1200px; /* or 100% for full width */
    }
}


        .scanner-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0 0 8px 0;
            position: relative;
            z-index: 2;
        }
        
        .scanner-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.95);
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .scanner-icon {
            font-size: 48px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
       
        
        .qr-section {
            padding: 30px;
            text-align: center;
        }
        
        #qr-reader {
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: var(--shadow-light);
            min-height: 300px;
            background: var(--green-50);
            border: 2px dashed var(--green-500);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .camera-placeholder {
            color: var(--green-600);
            font-size: 48px;
            opacity: 0.7;
        }
        
        .scan-controls {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn-modern {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-primary-modern {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
            color: white;
        }
        
        .btn-secondary-modern {
            background: var(--green-100);
            color: var(--green-700);
            border: 2px solid var(--green-500);
        }
        
        .btn-secondary-modern:hover {
            background: var(--green-500);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-danger-modern {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-danger-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.6);
            color: white;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--green-600);
            font-size: 14px;
            font-weight: 600;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--green-500), transparent);
        }
        
        .divider span {
            padding: 0 20px;
            background: var(--card-bg);
            border: 2px solid var(--green-500);
            border-radius: 20px;
            color: var(--green-700);
        }
        
        .manual-section {
            padding: 30px;
            background: linear-gradient(135deg, var(--green-50) 0%, rgba(220, 252, 231, 0.5) 100%);
            border-top: 1px solid var(--green-500);
        }
        
        .manual-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--green-700);
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-control-modern {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--green-500);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            background: white;
            transition: all 0.3s ease;
            color: var(--text-primary);
        }
        
        .form-control-modern:focus {
            outline: none;
            border-color: var(--green-600);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
            transform: translateY(-1px);
        }
        
        .form-control-modern::placeholder {
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .btn-submit-modern {
            width: 100%;
            background: var(--success-gradient);
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
           
        }
        
        .btn-submit-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.6);
        }
        
        .help-section {
            padding: 30px;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-top: 1px solid var(--green-500);
        }
        
        .help-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--green-700);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .help-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .help-list li {
            padding: 8px 0;
            color: var(--green-600);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .help-list li::before {
            content: '✓';
            color: white;
            font-weight: bold;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--green-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }
        
        .loading-spinner {
            display: none;
            padding: 40px;
            text-align: center;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--green-100);
            border-top: 4px solid var(--green-500);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .status-message {
            margin-top: 20px;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            display: none;
            border: 2px solid;
        }
        
        .status-success {
            background: var(--green-50);
            color: var(--green-700);
            border-color: var(--green-500);
        }
        
        .status-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .camera-permission-alert {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid #f59e0b;
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .scanner-container {
                padding: 0 15px;
                margin-left: 0;
            }
            
            .scanner-card {
                border-radius: 20px;
            }
            
            .scanner-header {
                padding: 30px 20px;
            }
            
            .scanner-title {
                font-size: 24px;
            }
            
            .qr-section,
            .manual-section,
            .help-section {
                padding: 20px;
            }
            
            .scan-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-modern {
                min-width: 200px;
                justify-content: center;
            }
        }
        
        /* QR Reader Styles Override */
        #qr-reader video {
            border-radius: 12px !important;
        }
        
        #qr-reader__dashboard {
            background: var(--green-50) !important;
            border-radius: 12px !important;
        }
        
        #qr-reader__dashboard_section {
            background: white !important;
            border: 2px solid var(--green-500) !important;
            border-radius: 8px !important;
        }
    </style>
</head>
<body>
    <div class="container ">
        <div >
            <?php include 'templates/sidebar.php'; ?>
        </div>
        <div class="main-content ">
        
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                    <h1 class="h3 mt-4 fw-bold" >Scanner</h1>
                    <p class="mb-5">Scan Animal QR tags</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
                    
    
    
            
            <div class="scanner-card">
                <!-- Header -->
                <div class="scanner-header">
                    
                    <div class="scanner-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h1 class="scanner-title">Animal Tag Scanner</h1>
                    <p class="scanner-subtitle">Scan or enter animal ID to view medical records</p>
                </div>
                
                <!-- QR Scanner Section -->
                <div class="qr-section">
                    <div id="qr-reader">
                        <div class="camera-placeholder">
                            <i class="fas fa-camera"></i>
                            <p style="font-size: 16px; margin-top: 10px;">Click "Start Camera" to begin scanning</p>
                        </div>
                    </div>
                    
                    <div class="camera-permission-alert" id="camera-alert">
                        <h6><i class="fas fa-exclamation-triangle"></i> Camera Permission Required</h6>
                        <p class="mb-2">Please allow camera access to scan QR codes. Check your browser settings if the permission was denied.</p>
                        <small>You can also manually enter the animal ID below.</small>
                    </div>
                    
                    <div class="scan-controls">
                        <button id="start-scan" class="btn-modern btn-primary-modern">
                            <i class="fas fa-camera"></i>
                            Start Camera
                        </button>
                        <button id="stop-scan" class="btn-modern btn-danger-modern" style="display: none;">
                            <i class="fas fa-stop"></i>
                            Stop Camera
                        </button>
                        <button id="switch-camera" class="btn-modern btn-secondary-modern" style="display: none;">
                            <i class="fas fa-sync-alt"></i>
                            Switch Camera
                        </button>
                    </div>
                    
                    <div class="loading-spinner" id="loading-spinner">
                        <div class="spinner"></div>
                        <p style="color: var(--green-600); font-weight: 600;">Processing scan result...</p>
                    </div>
                    
                    <div class="status-message" id="status-message"></div>
                </div>
                
                <!-- Divider -->
                <div class="divider">
                    <span><i class="fas fa-paw"></i> OR <i class="fas fa-paw"></i></span>
                </div>
                
                <!-- Manual Input Section -->
                <div class="manual-section">
                    <h3 class="manual-title">
                        <i class="fas fa-keyboard"></i>
                        Enter Animal ID Manually
                    </h3>
                    
                    <form id="manual-form">
                        <div class="form-group">
                            <input 
                                type="text" 
                                class="form-control-modern" 
                                id="animal-id" 
                                name="animal_id" 
                                placeholder="Enter Animal ID (e.g., 1 , 2, 3)" 
                                required
                            >
                        </div>
                        <button type="submit" class="btn-submit-modern">
                            <i class="fas fa-search"></i>
                            Look Up Animal Records
                        </button>
                    </form>
                </div>
                
                <!-- Help Section -->
                <div class="help-section">
                    <div class="help-title">
                        <i class="fas fa-info-circle"></i>
                        Scanning Instructions
                    </div>
                    <ul class="help-list">
                        <li>Allow camera permissions when prompted</li>
                        <li>Ensure adequate lighting for clear scanning</li>
                        <li>Hold device 6-8 inches from the QR code</li>
                        <li>Keep QR code centered and fully visible</li>
                        <li>Use manual entry if camera is unavailable</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header" style="background: var(--danger-gradient); color: white; border: none;">
                    <h5 class="modal-title fw-bold" id="errorModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Animal Not Found
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-search" style="font-size: 48px; color: #ef4444; opacity: 0.7;"></i>
                    </div>
                    <h6 class="fw-bold mb-3" style="color: var(--text-primary);">Animal ID Not Found in Database</h6>
                    <p class="text-muted mb-3">The animal ID you entered does not exist in the City Vet System database. Please verify the ID and try again.</p>
                    <div class="alert alert-warning">
                        <strong>Entered ID:</strong> <span id="entered-id" class="fw-bold"></span>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn-modern btn-primary-modern" data-bs-dismiss="modal">
                        <i class="fas fa-redo"></i>
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header" style="background: var(--success-gradient); color: white; border: none;">
                    <h5 class="modal-title fw-bold" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>
                        Scan Successful
                    </h5>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-paw" style="font-size: 48px; color: var(--green-500); opacity: 0.8;"></i>
                    </div>
                    <h6 class="fw-bold mb-3" style="color: var(--green-700);">Animal Found in Database!</h6>
                    <p class="text-muted mb-3">Loading animal medical records...</p>
                    <div class="spinner" style="width: 30px; height: 30px; border-width: 3px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // QR Code Scanner Variables
        let html5QrCode;
        let isScanning = false;
        let cameras = [];
        let currentCameraIndex = 0;
        
        const startScanBtn = document.getElementById('start-scan');
        const stopScanBtn = document.getElementById('stop-scan');
        const switchCameraBtn = document.getElementById('switch-camera');
        const qrReader = document.getElementById('qr-reader');
        const loadingSpinner = document.getElementById('loading-spinner');
        const statusMessage = document.getElementById('status-message');
        const cameraAlert = document.getElementById('camera-alert');
        
        // Initialize cameras on page load
        window.addEventListener('DOMContentLoaded', function() {
            initializeCameras();
        });
        
        // Initialize available cameras
        function initializeCameras() {
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    cameras = devices;
                    console.log('Available cameras:', cameras);
                    
                    if (cameras.length > 1) {
                        switchCameraBtn.style.display = 'inline-flex';
                    }
                    
                    showStatus(`${cameras.length} camera(s) detected. Ready to scan!`, 'success');
                } else {
                    console.warn('No cameras found');
                    showStatus('No cameras detected. Please use manual entry.', 'error');
                    cameraAlert.style.display = 'block';
                }
            }).catch(err => {
                console.error('Failed to get cameras:', err);
                showStatus('Failed to access camera devices.', 'error');
                cameraAlert.style.display = 'block';
            });
        }
        
        // QR Code Success Callback
        function onScanSuccess(decodedText, decodedResult) {
            console.log('QR Code scanned:', decodedText);
            
            // Stop scanning immediately
            stopScanner();
            
            // Show loading
            showLoading(true);
            
            // Extract animal ID
            let animalId = decodedText;
            if (decodedText.includes('id=')) {
                const urlParams = new URLSearchParams(decodedText.split('?')[1]);
                animalId = urlParams.get('id') || decodedText;
            }
            
            // Clean up the animal ID
            animalId = animalId.trim();
            
            // Validate and redirect
            validateAndRedirect(animalId);
        }
        
        function onScanError(errorMessage) {
            // Only log errors, don't show to user to avoid spam
            console.log('Scan error:', errorMessage);
        }
        
        // Start QR Scanner
        function startScanner() {
            if (isScanning || cameras.length === 0) return;
            
            const cameraId = cameras[currentCameraIndex].id;
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 280, height: 280 },
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true,
                showZoomSliderIfSupported: true,
                rememberLastUsedCamera: true
            };
            
            html5QrCode.start(cameraId, config, onScanSuccess, onScanError)
                .then(() => {
                    isScanning = true;
                    startScanBtn.style.display = 'none';
                    stopScanBtn.style.display = 'inline-flex';
                    if (cameras.length > 1) {
                        switchCameraBtn.style.display = 'inline-flex';
                    }
                    
                    showStatus(`Camera started (${cameras[currentCameraIndex].label || 'Camera ' + (currentCameraIndex + 1)})`, 'success');
                    cameraAlert.style.display = 'none';
                })
                .catch(err => {
                    console.error('Failed to start scanner:', err);
                    isScanning = false;
                    
                    if (err.toString().includes('Permission')) {
                        showStatus('Camera permission denied. Please allow camera access and try again.', 'error');
                        cameraAlert.style.display = 'block';
                    } else {
                        showStatus('Failed to start camera. Please try again or use manual entry.', 'error');
                    }
                });
        }
        
        // Stop QR Scanner
        function stopScanner() {
            if (!isScanning || !html5QrCode) return;
            
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                isScanning = false;
                startScanBtn.style.display = 'inline-flex';
                stopScanBtn.style.display = 'none';
                switchCameraBtn.style.display = cameras.length > 1 ? 'inline-flex' : 'none';
                
                // Reset QR reader content
                qrReader.innerHTML = `
                    <div class="camera-placeholder">
                        <i class="fas fa-camera"></i>
                        <p style="font-size: 16px; margin-top: 10px;">Click "Start Camera" to begin scanning</p>
                    </div>
                `;
                
                showStatus('Camera stopped', 'success');
            }).catch(err => {
                console.error('Failed to stop scanner:', err);
            });
        }
        
        // Switch Camera
        function switchCamera() {
            if (!isScanning || cameras.length <= 1) return;
            
            stopScanner();
            
            setTimeout(() => {
                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                startScanner();
            }, 500);
        }
        
        // Validate Animal ID and Redirect
        function validateAndRedirect(animalId) {
            if (!animalId || animalId === '') {
                showLoading(false);
                showStatus('Invalid animal ID detected', 'error');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `check_animal_id=1&animal_id=${encodeURIComponent(animalId)}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.exists) {
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Redirect after 2.5 seconds
                    setTimeout(() => {
                        window.location.href = `../animal.php?id=${encodeURIComponent(animalId)}`;
                    }, 2500);
                } else {
                    // Show error modal
                    document.getElementById('entered-id').textContent = animalId;
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showLoading(false);
                showStatus('Error checking animal ID. Please try again.', 'error');
            });
        }
        
        // Show/Hide Loading Spinner
        function showLoading(show) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }
        
        // Show Status Message
        function showStatus(message, type) {
            statusMessage.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
            statusMessage.className = `status-message status-${type}`;
            statusMessage.style.display = 'block';
            
            setTimeout(() => {
                statusMessage.style.display = 'none';
            }, 4000);
        }
        
        // Event Listeners
        startScanBtn.addEventListener('click', startScanner);
        stopScanBtn.addEventListener('click', stopScanner);
        switchCameraBtn.addEventListener('click', switchCamera);
        
        // Manual Form Handler
        document.getElementById('manual-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const animalId = document.getElementById('animal-id').value.trim();
            
            if (!animalId) {
                showStatus('Please enter an Animal ID', 'error');
                return;
            }
            
            // Basic validation for animal ID format
            if (animalId.length < 1) {
                showStatus('Animal ID must be at least 1 characters long', 'error');
                return;
            }
            
            showLoading(true);
            validateAndRedirect(animalId);
        });
        
        // Add input formatting for animal ID
        document.getElementById('animal-id').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            e.target.value = value;
        });
        
        // Request location permission for tracking
        function requestLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        console.log('Location captured:', position.coords);
                        // You can send this to your server for tracking purposes
                    },
                    error => {
                        console.log('Location error:', error.message);
                    },
                    { 
                        enableHighAccuracy: true, 
                        timeout: 10000,
                        maximumAge: 300000 
                    }
                );
            }
        }
        
        // Request location on page load
        requestLocation();
        
        // Handle page visibility changes (pause/resume scanner)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isScanning) {
                // Page is hidden, stop scanner to save resources
                console.log('Page hidden, pausing scanner');
            } else if (!document.hidden && !isScanning && cameras.length > 0) {
                // Page is visible again, user can manually restart if needed
                console.log('Page visible again');
            }
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        if (!isScanning) {
                            startScanner();
                        } else {
                            stopScanner();
                        }
                        break;
                    case 'f':
                        e.preventDefault();
                        document.getElementById('animal-id').focus();
                        break;
                }
            }
        });
        
       
    </script>
</body>
</html>