<?php
// Get animal ID from URL parameter
$animal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

include '../db/conn.php';
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s');
// Initialize message variables
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $owner_id = $conn->real_escape_string($_POST['owner_id']);
    $animal_name = $conn->real_escape_string($_POST['animal_name']);
    $species = $conn->real_escape_string($_POST['species']);
    $breed = $conn->real_escape_string($_POST['breed']);
    $birthdate = $conn->real_escape_string($_POST['birthdate']);
    $sex = $conn->real_escape_string($_POST['sex']);
    $color = $conn->real_escape_string($_POST['color']);
    $behavior = $conn->real_escape_string($_POST['behavior']);
    $animal_class = $conn->real_escape_string($_POST['animal_class']);
    $date_registered = $conn->real_escape_string($_POST['date_registered']);
    $status = $conn->real_escape_string($_POST['status']);
    $vaccination_status = $conn->real_escape_string($_POST['vaccination_status']);
    
    // Get current status before update to check for changes
    $current_status_sql = "SELECT status FROM animals WHERE animal_id = ?";
    $current_status_stmt = $conn->prepare($current_status_sql);
    $current_status_stmt->bind_param("i", $animal_id);
    $current_status_stmt->execute();
    $current_status_result = $current_status_stmt->get_result();
    $current_status_row = $current_status_result->fetch_assoc();
    $current_status = $current_status_row['status'];
    $current_status_stmt->close();

    if (strtolower($current_status) === 'dead') {
        $status = 'dead';
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['animal_image']) && $_FILES['animal_image']['error'] == 0) {
        $upload_dir = 'images/animals/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['animal_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['animal_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }
    
    // Update query
    $sql = "UPDATE animals SET 
            owner_id = ?, 
            animal_name = ?, 
            species = ?, 
            breed = ?, 
            birthdate = ?, 
            sex = ?, 
            color = ?, 
            behavior = ?, 
            animal_class = ?, 
            date_registered = ?, 
            status = ?, 
            vaccination_status = ?";
    
    // Add image to update only if a new one was uploaded
    if (!empty($image_path)) {
        $sql .= ", animal_image = ?";
    }
    
    $sql .= " WHERE animal_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($image_path)) {
        $stmt->bind_param("issssssssssssi", 
                        $owner_id, $animal_name, $species, $breed, 
                        $birthdate, $sex, $color, 
                        $behavior, $animal_class, $date_registered, 
                        $status, $vaccination_status, $image_path, $animal_id);
    } else {
        $stmt->bind_param("isssssssssssi", 
                        $owner_id, $animal_name, $species, $breed, 
                        $birthdate, $sex, $color, 
                        $behavior, $animal_class, $date_registered, 
                        $status, $vaccination_status, $animal_id);
    }
    
    if ($stmt->execute()) {
        $success_message = "Animal information updated successfully!";
        
        // Check if status has changed and insert into lost_found_history
        if ($current_status !== $status) {
            // Get admin name for updated_by field
            $admin_name = "Unknown";
            if (isset($_SESSION['admin_id'])) {
                $admin_query = "SELECT CONCAT(first_name, ' ', last_name) as admin_name FROM admins WHERE admin_id = ?";
                $admin_stmt = $conn->prepare($admin_query);
                $admin_stmt->bind_param("i", $_SESSION['admin_id']);
                $admin_stmt->execute();
                $admin_result = $admin_stmt->get_result();
                if ($admin_row = $admin_result->fetch_assoc()) {
                    $admin_name = $admin_row['admin_name'];
                }
                $admin_stmt->close();
            }
            
            $history_sql = "INSERT INTO lost_found_history (animal_id, status, updated_by, update_date) VALUES (?, ?, ?, NOW())";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("iss", $animal_id, $status, $admin_name);
            
            if ($history_stmt->execute()) {
                $success_message .= " Status change has been recorded.";
                
                // Get the lf_id of the newly inserted record
                $lf_id = $conn->insert_id;
                
                // Send notification to the owner about the status change
                $notification_message = "Your pet $animal_name's status has been updated to $status by an $admin_name.";
                $owner_notification_stmt = $conn->prepare("INSERT INTO notification (owner_id, lf_id, admin_id, message, date_notify) VALUES (?, ?, ?, ?, ?)");
                $owner_notification_stmt->bind_param("iiiss", $owner_id, $lf_id, $_SESSION['admin_id'], $notification_message,$current_time);
                
                if (!$owner_notification_stmt->execute()) {
                    error_log("Owner notification insert failed: " . $owner_notification_stmt->error);
                }
                $owner_notification_stmt->close();
            } else {
                $error_message = "Animal updated but failed to record status change: " . $conn->error;
            }
            
            $history_stmt->close();
        }
    } else {
        $error_message = "Error updating animal: " . $conn->error;
    }
    
    $stmt->close();
}

// Fetch animal data
$sql = "SELECT a.*, o.first_name, o.last_name 
        FROM animals a 
        JOIN owners o ON a.owner_id = o.owner_id 
        WHERE a.animal_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Animal not found, redirect to animals page
    header("Location: animals.php");
    exit;
}

$animal = $result->fetch_assoc();
$stmt->close();

$status_locked = strtolower($animal['status'] ?? '') === 'dead';

// Fetch all owners for dropdown
$owners_result = $conn->query("SELECT owner_id, first_name, last_name FROM owners ORDER BY last_name, first_name");
$owners = [];
while ($row = $owners_result->fetch_assoc()) {
    $owners[] = $row;
}

$med_stmt = $conn->prepare("SELECT am.*, m.name, m.type, m.description 
FROM animal_medication am 
JOIN medication m ON am.med_id = m.med_id 
WHERE am.animal_id = ? 
ORDER BY am.date_given DESC");
$med_stmt->bind_param("i", $animal_id);
$med_stmt->execute();
$med_result = $med_stmt->get_result();
while ($med_row = $med_result->fetch_assoc()) {
$medications[] = $med_row;
}
$med_stmt->close();



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Edit Animal | CityVet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #059669;
            --info: #0284c7;
            --warning: #d97706;
            --danger: #dc2626;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .main-content {
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .card-header {
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1.2rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-outline-secondary {
            color: var(--secondary);
            border-color: #cbd5e1;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f1f5f9;
            color: var(--dark);
            border-color: #cbd5e1;
        }
        
        .file-upload {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            border: 2px dashed #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
        }
        
        .file-upload-icon {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .form-text {
            color: var(--secondary);
            font-size: 0.875rem;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--dark);
            font-weight: 500;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .animal-preview {
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .animal-preview img {
            width: 100%;
            object-fit: cover;
            height: 250px;
        }
        
        .animal-preview-placeholder {
            background-color: #e2e8f0;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 3rem;
        }
        
        .edit-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: 12px;
        }
        
        .animal-preview:hover .edit-overlay {
            opacity: 1;
        }
        
        .age-display {
            font-weight: normal;
            color: var(--secondary);
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .nav-tabs {
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--secondary);
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-right: 1rem;
            border-bottom: 2px solid transparent;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            border-bottom: 2px solid #cbd5e1;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom: 2px solid var(--primary);
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .required-field::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }
        
        @media (max-width: 768px) {
            .breadcrumb {
                margin-bottom: 1rem;
            }
            
            .btn {
                width: 80%;
                margin-bottom: 0.5rem;
                margin-left: 0.5rem;
            }
            .main-content{
                margin-left: 0;
                padding: 5px;
            }
            .animal-preview img,
            .animal-preview-placeholder {
                height: 200px;
            }
        }
    </style>
</head>
<body>


<div class="container">
    <div >
        <?php include 'templates/sidebar.php'; ?>
    </div>
        <!-- Dashboard Header -->
    <div class="main-content ">
         <div class="header">
            <button id="sidebar-toggle" style="display: none;">            
            </button>
            
            <div class="header-title">
                <h1 class="h3 mt-4 fw-bold">Edit Animal Details</h1>
                <p class="mb-5">Edit Animal Basic Information and Medical Record!</p>
            </div>
            <div class="admin-header">
                <a href="scanner.php" class="action-link">
                    <i class="fas fa-qrcode" style="font-size: large;"></i>
                </a>
                <?php include'templates/admin_header.php'; ?>
            </div>
        </div>
     
        
        
        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left Column: Animal Image -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Animal Profile</h5>
                        
                        <div class="animal-preview" id="imagePreviewContainer">
                            <?php if (!empty($animal['animal_image'])): ?>
                                <img src="<?= htmlspecialchars($animal['animal_image']) ?>" alt="<?= htmlspecialchars($animal['animal_name']) ?>" id="imagePreview">
                            <?php else: ?>
                                <div class="animal-preview-placeholder" id="imagePlaceholder">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>
                            <div class="edit-overlay">
                                <button type="button" class="btn btn-light" id="triggerFileUpload">
                                    <i class="fas fa-camera me-2"></i> Change Image
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <h4 class="mb-1"><?= htmlspecialchars($animal['animal_name']) ?></h4>
                            <p class="text-muted mb-3"><?= htmlspecialchars($animal['species']) ?> • <?= htmlspecialchars($animal['breed']) ?></p>
                            
                            <div class="d-flex justify-content-center mb-3">
                                <?php 
                                $statusClass = [
                                    'safe' => 'bg-success',
                                    'lost' => 'bg-danger',
                                    'found' => 'bg-warning'
                                ];
                                $badgeClass = isset($statusClass[$animal['status']]) ? $statusClass[$animal['status']] : 'bg-secondary';
                                ?>
                                <span class="badge <?= $badgeClass ?> me-2"><?= strtoupper(htmlspecialchars($animal['status'] ?? 'UNKNOWN')) ?></span>
                                
                                <?php if ($animal['vaccination_status'] == 'Vaccinated'): ?>
                                    <span class="badge bg-info">VACCINATED</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">NOT VACCINATED</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="small text-muted">
                                Owner: <?= htmlspecialchars($animal['first_name'] . ' ' . $animal['last_name']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Edit Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-white" style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit <?= htmlspecialchars($animal['animal_name']) ?></h5>
                        <a href="animal_details.php?id=<?= $animal_id ?>" class="btn btn-outline-light">
                            <i class="fas fa-eye me-1"></i> View Details
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Edit Form -->
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- Hidden file input -->
                            <input type="file" id="animal_image" name="animal_image" accept="image/*" class="d-none" onchange="previewImage(this)">
                            
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" id="animalFormTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                        Basic Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                        Additional Details
                                    </button>
                                </li>                           
                            </ul>
                            
                            <!-- Tab content -->
                            <div class="tab-content" id="animalFormContent">
                                <!-- Basic Info Tab -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label for="animal_name" class="form-label required-field">Animal Name</label>
                                            <input type="text" class="form-control" id="animal_name" name="animal_name" value="<?= htmlspecialchars($animal['animal_name']) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="owner_id" class="form-label required-field">Owner</label>
                                            <select class="form-select" id="owner_id" name="owner_id" required>
                                                <?php foreach ($owners as $owner): ?>
                                                    <option value="<?= $owner['owner_id'] ?>" <?= ($owner['owner_id'] == $animal['owner_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                         <div class="col-md-6">
                                            <label for="status" class="form-label required-field">Status</label>
                                            <select class="form-select" id="status" name="status" <?php echo $status_locked ? 'disabled' : ''; ?>>
                                                <option value="Safe" <?= ($animal['status'] == 'safe') ? 'selected' : '' ?>>Safe</option>
                                                <option value="Lost" <?= ($animal['status'] == 'lost') ? 'selected' : '' ?>>Lost</option>
                                                <option value="Found" <?= ($animal['status'] == 'found') ? 'selected' : '' ?>>Found</option>
                                                <option value="Dead" <?= ($animal['status'] == 'dead') ? 'selected' : '' ?>>Dead</option>
                                            </select>
                                            <?php if ($status_locked): ?>
                                                <input type="hidden" name="status" value="dead">
                                                <small class="text-muted d-block mt-1">Status is locked because this animal has already been marked dead.</small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="vaccination_status" class="form-label required-field">Vaccination Status</label>
                                            <select class="form-select" id="vaccination_status" name="vaccination_status">
                                                <option value="Vaccinated" <?= ($animal['vaccination_status'] == 'Vaccinated') ? 'selected' : '' ?>>Vaccinated</option>
                                                <option value="Not Vaccinated" <?= ($animal['vaccination_status'] == 'Not Vaccinated') ? 'selected' : '' ?>>Not Vaccinated</option>
                                            </select>
                                        </div>
                                      
                                        <div class="col-md-6">
                                            <label for="behavior" class="form-label required-field">Behavior</label>
                                            <select class="form-select" id="behavior" name="behavior">
                                                <option value="Aggressive" <?= ($animal['behavior'] == 'Aggressive') ? 'selected' : '' ?>>Aggressive</option>
                                                <option value="Calm" <?= ($animal['behavior'] == 'Calm') ? 'selected' : '' ?>>Calm</option>
                                            </select>
                                        </div>
                                     

                                        <div class="col-md-6">
                                            <label for="birthdate" class="form-label required-field">Birthdate <span class="age-display" id="ageDisplay"></span></label>
                                            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?= htmlspecialchars($animal['birthdate']) ?>" onchange="calculateAge()">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Additional Details Tab -->
                                <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label for="color" class="form-label">Color</label>
                                            <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($animal['color']) ?>"readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="animal_class" class="form-label">Animal Class</label>
                                            <input type="text" class="form-control" id="animal_class" name="animal_class" value="<?= htmlspecialchars($animal['animal_class']) ?>"readonly>
                                        </div>
                                          <div class="col-md-6">
                                            <label for="species" class="form-label">Species</label>
                                            <input type="text" class="form-control" id="species" name="species" value="<?= htmlspecialchars($animal['species']) ?>" readonly>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="breed" class="form-label">Breed</label>
                                            <input type="text" class="form-control" id="breed" name="breed" value="<?= htmlspecialchars($animal['breed']) ?>"readonly>
                                        </div>
                                        <!-- QR Code input removed -->
                                        <div class="col-md-6">
                                            <label for="sex" class="form-label">Sex</label>
                                            <input type="text" class="form-control" id="sex" name="sex" value="<?= htmlspecialchars($animal['sex']) ?>"readonly>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="date_registered" class="form-label">Date Registered</label>
                                            <input 
                                                type="date" 
                                                class="form-control" 
                                                id="date_registered" 
                                                name="date_registered" 
                                                value="<?= !empty($animal['date_registered']) ? date('Y-m-d', strtotime($animal['date_registered'])) : '' ?>" readonly>
                                        </div>
                                    </div>
                                    
                                </div>
                                <!-- Medical History Section -->

                                <!-- End Medical History Section -->
                                
                            
                            <!-- Form Actions -->
                            <div class="pt-4 border-top mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                                    <i class="fas fa-trash-alt me-1"></i> Delete Animal
                                </button>
                                <div>
                                    <a href="animals.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?= htmlspecialchars($animal['animal_name']) ?></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_animal.php" method="POST">
                    <input type="hidden" name="animal_id" value="<?= $animal_id ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate age on page load
    calculateAge();
    
    // Trigger file upload when the button is clicked
    document.getElementById('triggerFileUpload').addEventListener('click', function() {
        document.getElementById('animal_image').click();
    });
});

// Calculate and display age based on birthdate
function calculateAge() {
    const birthdateInput = document.getElementById('birthdate');
    const ageDisplay = document.getElementById('ageDisplay');
    
    if (birthdateInput.value) {
        const birthdate = new Date(birthdateInput.value);
        const today = new Date();
        
        let years = today.getFullYear() - birthdate.getFullYear();
        let months = today.getMonth() - birthdate.getMonth();
        
        // Adjust years and months if needed
        if (months < 0 || (months === 0 && today.getDate() < birthdate.getDate())) {
            years--;
            months += 12;
        }
        
        // Format the age display text
        let ageText = '';
        if (years > 0) {
            ageText += years + (years === 1 ? ' year' : ' years');
            if (months > 0) {
                ageText += ', ' + months + (months === 1 ? ' month' : ' months');
            }
        } else if (months > 0) {
            ageText = months + (months === 1 ? ' month' : ' months');
        } else {
            // Calculate days if less than a month
            const diffTime = Math.abs(today - birthdate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            ageText = diffDays + (diffDays === 1 ? ' day' : ' days');
        }
        
        ageDisplay.textContent = '(' + ageText + ' old)';
    } else {
        ageDisplay.textContent = '';
    }
}

// Preview the image before upload
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const imagePreview = document.getElementById('imagePreview');
        const imagePlaceholder = document.getElementById('imagePlaceholder');
        
        reader.onload = function(e) {
            // If there's no img element yet, create one
            if (!imagePreview) {
                const img = document.createElement('img');
                img.id = 'imagePreview';
                img.alt = "Animal Preview";
                img.src = e.target.result;
                document.getElementById('imagePreviewContainer').prepend(img);
                
                // Hide placeholder if it exists
                if (imagePlaceholder) {
                    imagePlaceholder.style.display = 'none';
                }
            } else {
                // Update existing image
                imagePreview.src = e.target.result;
                
                // Hide placeholder if it exists
                if (imagePlaceholder) {
                    imagePlaceholder.style.display = 'none';
                }
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
// Initialize Bootstrap tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Form validation
const form = document.querySelector('form');
form.addEventListener('submit', function(event) {
    let isValid = true;
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        event.preventDefault();
        event.stopPropagation();
        
        // Show alert if there are validation errors
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i> Please fill in all required fields.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const existingAlert = form.querySelector('.alert-danger');
        if (!existingAlert) {
            form.prepend(alertDiv);
        }
        
        // Scroll to first error
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
});

// Add event listeners to remove invalid class when user starts typing
const inputs = form.querySelectorAll('input, select, textarea');
inputs.forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });
});
</script>

</body>
</html>