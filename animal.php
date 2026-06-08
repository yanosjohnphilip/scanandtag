<?php
session_start();
include 'db/conn.php';

// Set timezone to Philippine time for accurate timestamps
date_default_timezone_set('Asia/Manila');

function calculateAge($birthdate) {
    if (!$birthdate) return "Unknown";
    $dob = new DateTime($birthdate);
    $now = new DateTime();
    return $dob->diff($now)->y;
}

// Get current animal id from URL if present
$animal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentScript = basename($_SERVER['PHP_SELF']);

// 1. Unset all scanned_animal_* sessions except the current animal if a new animal is scanned
if ($animal_id > 0) {
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'scanned_animal_') === 0 && $key !== 'scanned_animal_' . $animal_id) {
            unset($_SESSION[$key]);
        }
    }
}

// 2. Unset the scan session if not on animal.php or report_form.php
if (
    isset($_SESSION['scanned_animal_' . $animal_id]) &&
    $currentScript !== 'animal.php' &&
    $currentScript !== 'report_form.php'
) {
    unset($_SESSION['scanned_animal_' . $animal_id]);
}

if (isset($_GET['id'])) {
    $animal_id = $_GET['id'];
    
    // Check if user is admin
    $is_admin = isset($_SESSION['admin_id']) ? true : false;
    
    // Prepare and execute query to get animal details, including barangay, city, and province
    $stmt = $conn->prepare("SELECT a.*, o.first_name, o.last_name, o.phone, o.address, o.barangay, o.city, o.province 
                           FROM animals a 
                           LEFT JOIN owners o ON a.owner_id = o.owner_id 
                           WHERE a.animal_id = ?");
    $stmt->bind_param("i", $animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $animal = $result->fetch_assoc();
 // Get animal ID from URL parameter
$animal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch animal info from database
$animal_info = [];
if ($animal_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM animals WHERE animal_id = ?");
    $stmt->bind_param("i", $animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal_info = $result->fetch_assoc();
    $stmt->close();
}

// Process location data if available
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['latitude']) && isset($_POST['longitude'])) {
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    // Check session to prevent duplicate scan insertions
    if (!isset($_SESSION['scanned_animal_' . $animal_id])) {
        $status = strtolower(trim($animal['status']));

        if ($status === 'lost') {
        // Save scan to history
        $current_time = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO scan_history (animal_id, latitude, longitude, scan_time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idds", $animal_id, $latitude, $longitude, $current_time);
        $stmt->execute();

        // Get the last inserted scan_id
        $scan_id = $conn->insert_id;
        $stmt->close();

        // Set session variable to prevent duplicate scan
        $_SESSION['scanned_animal_' . $animal_id] = true;

        // Check if the animal status is 'lost'
        
            // Prepare notification variables
            $owner_id = $animal['owner_id']; // From earlier query
            $animal_name = $animal['animal_name'];
            $message = "Lost animal named $animal_name was scanned.";

            // Fetch all admin IDs
            $adminQuery = "SELECT admin_id FROM admins";
            $adminResult = $conn->query($adminQuery);

            if ($adminResult->num_rows > 0) {
                while ($adminRow = $adminResult->fetch_assoc()) {
                    $admin_id = $adminRow['admin_id'];

                    // Insert notification for each admin
                    $notifStmt = $conn->prepare("INSERT INTO notification (admin_id, scan_id, message, date_notify) VALUES (?, ?, ?, ?)");
                    $notifStmt->bind_param("iiss", $admin_id, $scan_id, $message, $current_time);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            
            // Insert notification for the owner (without admin_id)
            $owner_message = "Your lost animal named $animal_name was scanned at a location.";
            $ownerNotifStmt = $conn->prepare("INSERT INTO notification (owner_id, scan_id, message, date_notify) VALUES (?, ?, ?, ?)");
            $ownerNotifStmt->bind_param("iiss", $owner_id, $scan_id, $owner_message, $current_time);
            $ownerNotifStmt->execute();
            $ownerNotifStmt->close();
        }
        $scanSuccess = true;
    } else {
        // Already scanned in this session
        $scanSuccess = false;
    }

    // Send JSON response for AJAX
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $scanSuccess]);
        exit;
    }
}


if (isset($_SESSION['report_success'])) {
    echo '<div class="alert alert-success">Thank you! Your report has been submitted.</div>';
    unset($_SESSION['report_success']);
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title><?php echo htmlspecialchars($animal['animal_name']); ?> | Animal Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/animal.css">
</head>
<body>

     <div class="page-container">
        
        <div class="header-section">
        
            <?php if (isset($_SESSION['admin_id'])): ?>
            <div class="d-flex align-items-center gap-2">
               
                <a href="admin/index.php" class="btn btn-secondary d-flex align-items-center">
                    <i class="fas fa-arrow-left me-1"></i> Back Home
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($animal['status'] === 'lost'): ?>
        <div class="lost-alert">
            <div class="lost-alert-title">
                <i class="fas fa-exclamation-triangle lost-alert-icon"></i>
                URGENT: This animal is reported as LOST!
            </div>
            <div class="lost-alert-message">
                Please contact the owner immediately if you have any information about this pet.
            </div>
            <div class="lost-alert-buttons">
                <a href="tel:<?php echo htmlspecialchars($animal['phone']); ?>" class="contact-button urgent-button">
                    <i class="fas fa-phone"></i> Contact Owner Now
                </a>
                <a href="report_form.php?animal_id=<?php echo $animal['animal_id']; ?>&owner_id=<?php echo $animal['owner_id']; ?>" class="contact-button found-button">
                    <i class="fas fa-check-circle"></i> Report as Found
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="main-card">
            <!-- Animal Image Section -->
            <?php if (!empty($animal['animal_image'])): ?>
            <div class="animal-image-container">
                <img src="../admin/<?php echo htmlspecialchars($animal['animal_image']); ?>" alt="<?php echo htmlspecialchars($animal['animal_name']); ?>" class="animal-image" onerror="this.onerror=null; this.style.display='none'; this.parentElement.style.backgroundColor='#e9ecef'; this.parentElement.style.display='flex'; this.parentElement.style.alignItems='center'; this.parentElement.style.justifyContent='center'; this.parentElement.innerHTML='<i class=\'fas fa-paw\' style=\'font-size: 3rem; color: #adb5bd;\'></i>';">
            </div>
            <?php else: ?>
            <div class="animal-image-container" style="background-color: #e9ecef; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-paw" style="font-size: 3rem; color: #adb5bd;"></i>
            </div>
            <?php endif; ?>
            
            <div class="card-header">
                <h2>
                    <?php echo htmlspecialchars($animal['animal_name']); ?>
                    <?php 
                    $vaccination_status = strtolower($animal['vaccination_status']);
                    if (strpos($vaccination_status, 'vaccinated') !== false || strpos($vaccination_status, 'up to date') !== false): 
                    ?>
                    <span class="pet-badge badge-vaccinated">
                        <i class="fas fa-check-circle me-1"></i> Vaccinated
                    </span>
                    <?php else: ?>
                    <span class="pet-badge badge-not-vaccinated">
                        <i class="fas fa-exclamation-circle me-1"></i> Not Vaccinated
                    </span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-paw me-2"></i>Animal Information
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Species:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['species']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Breed:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['breed']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo calculateAge($animal['birthdate']); ?> year(s)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sex:</span>
                        <span class="info-value">
                            <?php 
                            $sex = strtolower($animal['sex']);
                            $icon = ($sex === 'male') ? '<i class="fas fa-mars text-primary me-1"></i>' : 
                                   (($sex === 'female') ? '<i class="fas fa-venus text-danger me-1"></i>' : '');
                            echo $icon . ucfirst(htmlspecialchars($sex)); 
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Color:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['color']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value"><?php echo htmlspecialchars(ucfirst($animal['status'])); ?></span>
                    </div>
                </div>
                
                <h3 class="section-title">
                    <i class="fas fa-user me-2"></i>Owner Information
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['first_name'] . ' ' . $animal['last_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['phone']); ?></span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['address']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Barangay:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['barangay']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">City:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['city']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Province:</span>
                        <span class="info-value"><?php echo htmlspecialchars($animal['province']); ?></span>
                    </div>
                </div>
                
                <div class="contact-container">
                    <h4 class="contact-title">
                        <i class="fas fa-phone-alt me-2"></i>Contact Owner
                    </h4>
                    <div class="contact-methods">
                        <a href="tel:<?php echo htmlspecialchars($animal['phone']); ?>" class="contact-method">
                            <i class="fas fa-phone"></i> Call
                        </a>
                        <a href="sms:<?php echo htmlspecialchars($animal['phone']); ?>" class="contact-method">
                            <i class="fas fa-comment"></i> Text
                        </a>
                        <?php if (!empty($animal['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($animal['email']); ?>" class="contact-method">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($is_admin): ?>
                <a href="admin/animal_details.php?id=<?php echo $animal_id; ?>" class="admin-button">
                    <i class="fas fa-eye me-1"></i> View More Details (Admin)
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Location Success -->
    <div class="modal fade" id="locationSuccessModal" tabindex="-1" aria-labelledby="locationSuccessModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="locationSuccessModalLabel">
              <i class="fas fa-check-circle me-2"></i> Location Success
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <p>Your location was fetched successfully.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- GET LOCATION SCRIPT -->
    <script>
        function requestLocation() {
            if (!navigator.geolocation) {
                console.log("Geolocation is not supported by this browser.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `latitude=${position.coords.latitude}&longitude=${position.coords.longitude}&ajax=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Show Bootstrap modal for location success
                        var modal = new bootstrap.Modal(document.getElementById('locationSuccessModal'));
                        modal.show();
                        setTimeout(() => {
                            modal.hide();
                        }, 2000);
                    });
                },

                function(error) {
                    let message = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = 'Location access was denied. Please enable location permissions in your browser settings to help locate pets.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            message = 'Location request timed out.';
                            break;
                        default:
                            message = 'Error getting location.';
                    }
                    
                    const statusContainer = document.createElement('div');
                    statusContainer.className = 'location-message-container top-center';
                    statusContainer.innerHTML = ``;
                    
                    document.body.appendChild(statusContainer);
                    setTimeout(() => {
                        statusContainer.style.opacity = '0';
                        setTimeout(() => statusContainer.remove(), 300);
                    }, 5000);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        window.addEventListener('DOMContentLoaded', requestLocation);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

<?php
    } else {
        // Animal not found - show modal
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Animal Not Found</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body>
            <!-- Modal for Animal Not Found -->
            <div class="modal fade show" id="animalNotFoundModal" tabindex="-1" aria-labelledby="animalNotFoundModalLabel" aria-hidden="false" style="display: block;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="animalNotFoundModalLabel">
                                <i class="fas fa-exclamation-triangle me-2"></i> Animal Not Found
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="mb-2">The scanned QR code doesn't match any animal in our database.</p>
                                <p class="text-muted">Animal ID: #<?php echo str_pad($animal_id, 5, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                         
                            <button type="button" class="btn btn-primary" id="redirectButton">
                                <i class="fas fa-home me-1"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('animalNotFoundModal'));
                modal.show();
                
                // Redirect when modal is closed
                var modalElement = document.getElementById('animalNotFoundModal');
                modalElement.addEventListener('hidden.bs.modal', function () {
                    window.location.href = 'index.php';
                });
                
                // Redirect immediately when button is clicked
                document.getElementById('redirectButton').addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
                
                // Auto-redirect after 5 seconds
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 5000);
            });
            </script>
        </body>
        </html>
        <?php
    }
    // Close statement if it exists and hasn't been closed
 
} else {
    // No ID provided - redirect to home
    header("Location: index.php");
    exit();
}
$conn->close();
?>