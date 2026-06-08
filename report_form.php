<?php
include 'db/conn.php';

// Set timezone to Philippine time for accurate timestamps
date_default_timezone_set('Asia/Manila');


$animal_id = intval($_GET['animal_id'] ?? 0);
$owner_id = intval($_GET['owner_id'] ?? 0);
$success_message = '';
$error = '';

// Get animal info for the page title
$animalName = "";
if ($animal_id > 0) {
    $animalStmt = $conn->prepare("SELECT animal_name FROM animals WHERE animal_id = ?");
    $animalStmt->bind_param("i", $animal_id);
    $animalStmt->execute();
    $animalResult = $animalStmt->get_result();
    if ($animalData = $animalResult->fetch_assoc()) {
        $animalName = $animalData['animal_name'];
    }
    $animalStmt->close();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $reporter_name = trim($_POST['reporter_name']);
    $reporter_phone = trim($_POST['reporter_phone']);
    $reporter_address = trim($_POST['reporter_address']);
    $message = trim($_POST['message'] ?? '');
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
    $image_proof = '';

    // Handle image upload
    if (isset($_FILES['image_proof']) && $_FILES['image_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image_proof']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['image_proof']['name'], PATHINFO_EXTENSION);
            $image_proof = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_path = 'report_proof/' . $image_proof;
            
            if (!move_uploaded_file($_FILES['image_proof']['tmp_name'], $target_path)) {
                $image_proof = '';
                $error = "Failed to upload image";
            }
        } else {
            $error = "Only JPG, PNG, and GIF images are allowed";
        }
    }

    if (empty($error)) {
        // Insert report into database
        try {
            $current_time = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO found_reports 
                                (animal_id, owner_id, reporter_name, reporter_phone, reporter_address,
                                latitude, longitude, message, image_proof, report_time)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iisssddsss", $animal_id, $owner_id, $reporter_name, $reporter_phone,
                             $reporter_address, $latitude, $longitude, $message, $image_proof, $current_time);
            
            if ($stmt->execute()) {
                
                $report_id = $conn->insert_id;
                // Assuming you have an admin_id to use            
                // Fetch all admin IDs
                $adminQuery = "SELECT admin_id FROM admins";
                $adminResult = $conn->query($adminQuery);
            
                if ($adminResult->num_rows > 0) {
                    while ($adminRow = $adminResult->fetch_assoc()) {
                        $admin_id = $adminRow['admin_id'];
                        // Insert notification for admins
                        $notif_message = "$reporter_name submitted a found report of animal named $animalName.";
                        $notif_stmt = $conn->prepare("INSERT INTO notification (report_id, admin_id, message, date_notify, is_read) VALUES (?, ?, ?, ?, 0)");
                        $notif_stmt->bind_param("iiss", $report_id, $admin_id, $notif_message,$current_time);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
                
                // Insert notification for the owner (without admin_id)
                $owner_notif_message = "Good news! Someone reported finding your animal named $animalName.";
                $owner_notif_stmt = $conn->prepare("INSERT INTO notification (report_id, owner_id, message, date_notify, is_read) VALUES (?, ?, ?, ?, 0)");
                $owner_notif_stmt->bind_param("iiss", $report_id, $owner_id, $owner_notif_message,$current_time);
                $owner_notif_stmt->execute();
                $owner_notif_stmt->close();
                
                $success_message = "Report submitted successfully! Thank you for reporting this pet as found.";
            } else {
                $error = "Database error: " . $stmt->error;
                if (!empty($image_proof)) {
                    @unlink('report_proof/' . $image_proof);
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            if (!empty($image_proof)) {
                @unlink('report_proof/' . $image_proof);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/scanAndTagLogo.png" />
    <title>Report <?php echo htmlspecialchars($animalName); ?> as Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 30px;
        }
        .card-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-bottom: none;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
        }
        .location-status {
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1ca98c);
        }
        .map-container {
            height: 250px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        #locationMap {
            width: 100%;
            height: 100%;
        }
        .leaflet-container {
            border-radius: 8px;
        }
        .map-instructions {
            font-size: 0.85rem;
            margin-top: 5px;
            color: #6c757d;
        }
        /* Modal styles */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php if (empty($success_message)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-paw me-2"></i>Report <?php echo htmlspecialchars($animalName); ?> as Found
                </h3>
                <p class="mb-0 mt-2 text-white-50">
                    <i class="fas fa-info-circle me-1"></i> 
                    Fill out this form to notify the animal owner that you've found their animal
                </p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="reportForm">
                    <input type="hidden" name="animal_id" value="<?php echo $animal_id; ?>">
                    <input type="hidden" name="owner_id" value="<?php echo $owner_id; ?>">
                    <input type="hidden" id="latitude" name="latitude" value="0">
                    <input type="hidden" id="longitude" name="longitude" value="0">
                    
                    <div class="alert alert-info" id="locationStatus">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span id="locationMessage">Detecting your location...</span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Animal Found Location</label>
                        <div class="map-container">
                            <div id="locationMap"></div>
                        </div>
                        <p class="map-instructions">
                            <i class="fas fa-info-circle"></i> 
                            Drag the marker to adjust the location where you found the pet.
                        </p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Your Full Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="reporter_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="reporter_phone" required
                                       placeholder="e.g. +63 912 345 6789">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Your Complete Address *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                            <textarea class="form-control" name="reporter_address" rows="2" required
                                     placeholder="Street, Barangay, City, Province"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Where and How You Found the Pet</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-comment"></i></span>
                            <textarea class="form-control" name="message" rows="3" 
                                     placeholder="Describe where you found the pet, its condition, and any other important details..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-camera me-1"></i> Photo Proof (Optional)
                        </label>
                        <input type="file" class="form-control" name="image_proof" id="imageInput" accept="image/*">
                        <small class="text-muted">Upload a photo of the pet (JPG, PNG, or GIF - Max 10MB)</small>
                        <div class="text-center mt-2">
                            <img id="imagePreview" class="preview-image" alt="Preview">
                        </div>
                    </div>
                    
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="confirmCheck" required>
                        <label class="form-check-label" for="confirmCheck">
                            I confirm that the Information I provided is accurate, and I allow the system to use it for animal report. 
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Report
                        </button>
                        <a href="animal.php?id=<?php echo $animal_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Pet Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Success Modal -->
    <?php if (!empty($success_message)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success modal when page loads
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        
        // Set up the "Return" button
        document.getElementById('returnButton').addEventListener('click', function() {
            window.location.href = 'animal.php?id=<?php echo $animal_id; ?>';
        });
    });
    </script>
    <?php endif; ?>

    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center p-4">
            <div class="mb-3" style="font-size: 3rem; color: #28a745;">
              <i class="fas fa-check-circle"></i>
            </div>
            <h4 class="mb-3">Success!</h4>
            <p><?php echo htmlspecialchars($success_message ?? ''); ?></p>
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-success" id="returnButton">
                <i class="fas fa-arrow-left me-2"></i> Return to Pet Profile
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Global variables
    let map;
    let marker;
    // Default location (Philippines)
    const defaultLocation = [14.5995, 120.9842];
    
    // Initialize map
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        
        // Image preview
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="reporter_phone"]');
            if (!/^[\d\+\- ]+$/.test(phone.value)) {
                alert('Please enter a valid phone number');
                e.preventDefault();
                return false;
            }
            
            const fileInput = document.getElementById('imageInput');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > 10 * 1024 * 1024) { // 2MB limit
                    alert('Image must be less than 10MB');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    });
    
    function initMap() {
        // Create map with default location
        map = L.map('locationMap').setView(defaultLocation, 13);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add draggable marker
        marker = L.marker(defaultLocation, {
            draggable: true
        }).addTo(map);
        
        // Update coordinates when marker is dragged
        marker.on('dragend', function() {
            const position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });
        
        // Get user's location
        getUserLocation();
    }
    
    // Get user's location
    function getUserLocation() {
        const locationStatus = document.getElementById('locationStatus');
        const locationMessage = document.getElementById('locationMessage');
        
        if (navigator.geolocation) {
            locationMessage.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Detecting your location...';
            
            navigator.geolocation.getCurrentPosition(
                // Success callback
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Update map and form
                    updateCoordinates(lat, lng);
                    updateMapPosition(lat, lng);
                    
                    // Show success message
                    locationStatus.classList.remove('alert-info', 'alert-danger');
                    locationStatus.classList.add('alert-success');
                    locationMessage.innerHTML = '<i class="fas fa-check-circle me-2"></i> Location detected successfully! You can adjust the marker if needed.';
                },
                // Error callback
                function(error) {
                    console.error("Geolocation error:", error);
                    locationStatus.classList.remove('alert-info', 'alert-success');
                    locationStatus.classList.add('alert-danger');
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            locationMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Location access denied. Please enable location services or position the marker manually.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            locationMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Location information unavailable. Please position the marker manually.';
                            break;
                        case error.TIMEOUT:
                            locationMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Location request timed out. Please position the marker manually.';
                            break;
                        default:
                            locationMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Unknown error occurred getting location. Please position the marker manually.';
                    }
                },
                // Options
                { 
                    enableHighAccuracy: true, 
                    timeout: 10000, 
                    maximumAge: 0 
                }
            );
        } else {
            locationStatus.classList.remove('alert-info', 'alert-success');
            locationStatus.classList.add('alert-danger');
            locationMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Geolocation is not supported by this browser. Please position the marker manually.';
        }
    }
    
    // Update coordinates in form
    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
    }
    
    // Update map with new coordinates
    function updateMapPosition(lat, lng) {
        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);
    }
    </script>
</body>
</html>