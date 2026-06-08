
<?php
include '../db/conn.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
// Get the animal ID from the URL
$animal_id = $_GET['id'] ?? 0;
$animal = [];
$medications = [];
$vaccination_history = [];

if (isset($_POST['delete_animal_medication'])) {
    $animed_id = $_POST['animed_id'];

    $delete_sql = "DELETE FROM animal_medication WHERE animed_id = ?";
    $delete_stmt = $conn->prepare($delete_sql); // FIX: use SQL, not $animed_id

    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $animed_id);

        if ($delete_stmt->execute()) {
            $success_message = "Medicine deleted successfully!";
        } else {
            $error_message = "Error deleting medicine: " . $delete_stmt->error;
        }

        $delete_stmt->close();
    } else {
        $error_message = "Error preparing delete statement: " . $conn->error;
    }
}


if (isset($_POST['update_animal_medication'])) {
    $animed_id = $_POST['animed_id']; // <-- Add this line
    $name = $_POST['name'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $date_given = $_POST['date_given'] ?? '';

    $admin_name = '';
    if ($admin_id !== null) {
        $admin_name_stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE admin_id = ?");
        $admin_name_stmt->bind_param('i', $admin_id);
        $admin_name_stmt->execute();
        $admin_name_stmt->bind_result($first_name, $last_name);
        if ($admin_name_stmt->fetch()) {
            $admin_name = $first_name . ' ' . $last_name;
        }
        $admin_name_stmt->close();
    } else {
        $_SESSION['error_message'] = " Admin ID is not set.";
        header("Location: edit_animal_medication.php");
        exit();
    }

    // Get med_id from medication table based on selected name
    $stmt = $conn->prepare("SELECT med_id FROM medication WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $medication_row = $result->fetch_assoc();
    $stmt->close();

    $selected_med_id = $medication_row['med_id'] ?? null;

    if ($selected_med_id) {
        $stmt = $conn->prepare("UPDATE animal_medication SET med_id = ?, dosage = ?, date_given = ?, updated_by = ? WHERE animed_id = ?");
        $stmt->bind_param("isssi", $selected_med_id, $dosage, $date_given, $admin_name, $animed_id);
        if ($stmt->execute()) {
            $_SESSION['succes_message'] = "Medication updated successfully.";
            header("Location: animal_details.php?id=$animal_id&success=" . urlencode($success_message));
            exit;
        } else {
            $_SESSION['error_message'] = "Failed to update medication.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Selected medicine not found.";
    }
}

// Handle add medication
if (isset($_POST['add_animal_medication'])) {
    $med_name = $_POST['med_name'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $date_given = $_POST['date_given'] ?? '';
    $admin_id = $_SESSION['admin_id'] ?? null;
    $admin_name = '';

    if ($admin_id !== null) {
        $admin_name_stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE admin_id = ?");
        $admin_name_stmt->bind_param('i', $admin_id);
        $admin_name_stmt->execute();
        $admin_name_stmt->bind_result($first_name, $last_name);
        if ($admin_name_stmt->fetch()) {
            $admin_name = $first_name . ' ' . $last_name;
        }
        $admin_name_stmt->close();
    } else {
        $_SESSION['error_message'] = "Admin ID is not set.";
        header("Location: animal_details.php?id=$animal_id");
        exit();
    }

    // Get med_id from medication table based on selected name
    $stmt = $conn->prepare("SELECT med_id FROM medication WHERE name = ?");
    $stmt->bind_param("s", $med_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $medication_row = $result->fetch_assoc();
    $stmt->close();

    $selected_med_id = $medication_row['med_id'] ?? null;

    if ($selected_med_id) {
        $stmt = $conn->prepare("INSERT INTO animal_medication (animal_id, med_id, dosage, date_given, added_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $animal_id, $selected_med_id, $dosage, $date_given, $admin_name);
        if ($stmt->execute()) {
            // Update vaccination status to 'Vaccinated'
            $update_vax_stmt = $conn->prepare("UPDATE animals SET vaccination_status = 'Vaccinated' WHERE animal_id = ?");
            $update_vax_stmt->bind_param("i", $animal_id);
            $update_vax_stmt->execute();
            $update_vax_stmt->close();

            $_SESSION['success_message'] = "Medication added successfully.";
            header("Location: animal_details.php?id=$animal_id&success=" . urlencode("Medication added successfully."));
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to add medication.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Selected medicine not found.";
    }
}

if ($animal_id) {
    // Fetch animal details with owner information
    $stmt = $conn->prepare("SELECT a.*, o.first_name, o.last_name, o.phone, o.email, o.address, o.barangay 
                           FROM animals a 
                           JOIN owners o ON a.owner_id = o.owner_id 
                           WHERE a.animal_id = ?");
    $stmt->bind_param("i", $animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal = $result->fetch_assoc();
    $stmt->close();

    // Fetch medication history
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

    // Update vaccination status to 'Not Vaccinated' if no medications
    if (empty($medications)) {
        $update_vax_stmt = $conn->prepare("UPDATE animals SET vaccination_status = 'Not Vaccinated' WHERE animal_id = ?");
        $update_vax_stmt->bind_param("i", $animal_id);
        $update_vax_stmt->execute();
        $update_vax_stmt->close();
        // Also update the $animal array for immediate UI feedback
        $animal['vaccination_status'] = 'Not Vaccinated';
    }

    $conn->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Veterinary Record - <?= htmlspecialchars($animal['animal_name'] ?? 'Animal') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c7a3c;
            --secondary-color: #4CAF50;
            --accent-color: #81C784;
            --background-light: #f0f4f0;
            --text-color: #2c3e50;
        }
        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }
        .vet-record-card {
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
            margin: auto;
            width: 90%;
            transition: all 0.3s ease;
        }
        .record-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .animal-profile-img {
            width: 280px;
            height: 280px;
            object-fit: cover;
            border-radius: 15px;
            border: 6px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        .animal-profile-img:hover {
            transform: scale(1.02);
        }
        .profile-section {
            background-color: #f8fbf8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(44, 122, 60, 0.1);
        }
        .medical-tag {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .vaccination-status-vaccinated {
            background-color: var(--primary-color);
            color: white;
        }
        .vaccination-status-not-vaccinated {
            background-color: #dc3545;
            color: white;
        }
        .status-badge-safe {
            background-color: #28a745;
            color: white;
        }
        .status-badge-lost {
            background-color: #dc3545;
            color: white;
        }
        .status-badge-found {
            background-color: #17a2b8;
            color: white;
        }
        .status-badge-dead {
            background-color: #212529;
            color: white;
            border: 1px solid #0b0b0d;
        }
     
    
        .bg-success {
            background-color: var(--primary-color);
            color: white;
        }
        .bg-danger {
            background-color:rgb(241, 56, 10);
            color: white;
        }
        .medication-history {
            max-height: 300px;
            overflow-y: auto;
        }
        .qr-code-section {
            background-color: #e8f5e9;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .section-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .main-content {
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
        }
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-btn-edit {
            background-color: var(--secondary-color);
            color: var(--dark);
        }
        
        .action-btn-edit:hover {
            background-color: #e6ae06;
        }
        
        .action-btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .action-btn-delete:hover {
            background-color: #bb2d3b;
        }
        @media (max-width: 768px) {
            .animal-profile-img {
                width: 250px;
                height: 250px;
            }
            .vet-record-card {
                width: 100%;
            }
           
            .main-content {
                margin-left: 0;
                margin-top: 0;
            }
            .card-footer{
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    
<div class="container">      
    <div class="sidebar-container">
        <?php include'templates/sidebar.php'; ?>
    </div>
    <div class="main-content">
         <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                   <h1 class="h3 mt-4 fw-bold">Animal details</h1>
                    <p class="mb-5">Animal Basic Information and Medical Record!</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
        <div class="vet-record-card">
            <div class="record-header">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-paw me-2"></i>
                        <?= htmlspecialchars($animal['animal_name'] ?? 'Animal Record') ?>
                    </h2>
                    <small>Veterinary Medical Record</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="edit_animal.php?id=<?= $animal['animal_id'] ?>" class="btn btn-light">
                        <i class="fas fa-edit me-1"></i><span class="d-none d-sm-inline">Edit Record</span>
                    </a>
                  <button class="btn btn-outline-light" onclick="window.history.back()">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Back</span>
                </button>

                </div>
            </div>
            
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <?php if (!empty($animal['animal_image'])): ?>
                            <div class="d-flex flex-column align-items-center">
                                <img src="<?= htmlspecialchars($animal['animal_image']) ?>" 
                                    alt="<?= htmlspecialchars($animal['animal_name']) ?>" 
                                    class="animal-profile-img mb-3">
                                <h4 class="text-center fs-7" style="color: var(--primary-color); font-weight: bold">
                                    <?= htmlspecialchars($animal['animal_name']) ?>
                                </h4>
                            </div>
                        <?php else: ?>
                            <div class="bg-light p-5 text-muted rounded">
                                <i class="fas fa-image fa-3x" style="color: var(--primary-color);"></i>
                                <p class="mt-2">No image available</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="qr-code-section mt-3">
                        <?php if (!empty($animal['qr_code'])): ?>
                            <div class="d-flex flex-column align-items-center">
                                <img src="<?= htmlspecialchars($animal['qr_code']) ?>" 
                                    alt="Animal QR Code" 
                                    class="img-fluid" 
                                    style="max-width: 150px;"
                                    id="qrCodeImage">
                            
                                <p class="small mt-3 text-muted">Animal Identification QR</p> 
                                <button onclick="downloadQRCode()" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-download me-1"></i> Download QR
                                </button>
                            
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-qrcode fa-3x" style="color: var(--primary-color);"></i>
                                <p class="small mt-2 text-muted">No QR Code Available</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <script>
                    function downloadQRCode() {
                        // Get the QR code image element
                        const qrCode = document.getElementById('qrCodeImage');
                        // Create a temporary anchor element
                        const link = document.createElement('a');
                        // Set the href to the QR code image source
                        link.href = qrCode.src;
                        // Set the download attribute with the desired filename
                        link.download = 'qrID<?= $animal['animal_id'] ?? '' ?>.png';
                        // Append to body, click and then remove
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                    </script>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="profile-section">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle me-2 text-secondary"></i>
                                        Basic Information
                                    </h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <strong>Class:</strong> 
                                            <?= htmlspecialchars($animal['animal_class'] ?? 'N/A') ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Species:</strong> 
                                            <?= htmlspecialchars($animal['species'] ?? 'N/A') ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Breed:</strong> 
                                            <?= htmlspecialchars($animal['breed'] ?? 'N/A') ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Sex:</strong> 
                                            <?= htmlspecialchars($animal['sex'] ?? 'N/A') ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Color:</strong> 
                                            <?= htmlspecialchars(ucfirst($animal['color'] ?? 'N/A')) ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Date of Birth:</strong> 
                                            <?= !empty($animal['birthdate']) ? date('M d, Y', strtotime($animal['birthdate'])) : 'N/A' ?>
                                        </li>
                                      
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="profile-section">
                                    <h5 class="mb-3">
                                        <i class="fas fa-shield-alt me-2 text-secondary"></i>
                                        Medical Status
                                    </h5>
                                    <ul class="list-unstyled">
                                       
                                        <li class="mb-2">
                                            <strong>Vaccination Status:</strong> 
                                            <span class="medical-tag 
                                                <?= (strtolower($animal['vaccination_status'] ?? '') === 'vaccinated') 
                                                    ? 'vaccination-status-vaccinated' 
                                                    : 'vaccination-status-not-vaccinated' ?>">
                                                <?= strtoupper(htmlspecialchars($animal['vaccination_status'] ?? 'UNKNOWN')) ?>
                                            </span>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Status:</strong> 
                                            <span class="medical-tag 
                                                <?php 
                                                $status = strtolower($animal['status'] ?? '');
                                                switch($status) {
                                                    case 'safe':
                                                        echo 'status-badge-safe';
                                                        break;
                                                    case 'lost':
                                                        echo 'status-badge-lost';
                                                        break;
                                                    case 'found':
                                                        echo 'status-badge-found';
                                                        break;
                                                    case 'dead':
                                                        echo 'status-badge-dead';
                                                        break;
                                                    default:
                                                        echo 'status-badge-default';
                                                }
                                                ?>">
                                                <?= strtoupper(htmlspecialchars($animal['status'] ?? 'UNKNOWN')) ?>
                                            </span>
                                        </li>
                                        <li class="mb-2">
                                            <strong>Behavior:</strong> 
                                            <?= htmlspecialchars($animal['behavior'] ?? 'N/A') ?>
                                        </li>
                                          <li class="mb-2">
                                            <strong>Registration Date:</strong> 
                                            <?= !empty($animal['date_registered']) ? date('M d, Y', strtotime($animal['date_registered'])) : 'N/A' ?>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-section mt-3">
                            <h5 class="mb-3 section-header">
                                <i class="fas fa-user me-2" style="color: var(--primary-color);"></i>
                                Owner Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>Name:</strong> 
                                    <?= htmlspecialchars(($animal['first_name'] ?? '') . ' ' . ($animal['last_name'] ?? '')) ?>
                                    <br><strong>Birthdate:</strong> 
                                    <?= !empty($animal['birthdate']) ? date('M d, Y', strtotime($animal['birthdate'])) : 'N/A' ?>
                                
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Contact:</strong> 
                                    <?= htmlspecialchars($animal['phone'] ?? 'N/A') ?>
                                    <?php if (!empty($animal['email'])): ?>
                                        <br><strong>Email:</strong> 
                                        <?= htmlspecialchars($animal['email']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 mt-2 border-top pt-2">
                                    <strong>Address:</strong> 
                                    <?php 
                                    $full_address = [];
                                    if (!empty($animal['address'])) {
                                        $full_address[] = htmlspecialchars($animal['address']);
                                    }
                                    if (!empty($animal['barangay'])) {
                                        $full_address[] = htmlspecialchars($animal['barangay']);
                                    }
                                    echo !empty($full_address) ? implode(', ', $full_address) : 'N/A';
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-section mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-prescription-bottle me-2 text-secondary"></i>
                                    Medication History
                                </h5>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMedicationModal">
                                    <i class="fas fa-plus me-1"></i> Add Medication
                                </button>
                            </div>
                            <!-- Add Medication Modal -->
                            <div class="modal fade" id="addMedicationModal" tabindex="-1" aria-labelledby="addMedicationModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header bg-success">
                                                <h5 class="modal-title" id="addMedicationModalLabel">
                                                    <i class="fas fa-plus me-2"></i> Add Medication
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php
                                                // Fetch all medication types and names for dropdown
                                                $med_types = [];
                                                $med_type_stmt = $conn->prepare("SELECT DISTINCT type FROM medication ORDER BY type ASC");
                                                $med_type_stmt->execute();
                                                $med_type_result = $med_type_stmt->get_result();
                                                while ($row = $med_type_result->fetch_assoc()) {
                                                    $med_types[] = $row['type'];
                                                }
                                                $med_type_stmt->close();
                                                ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Medicine Type</label>
                                                    <select id="addMedType" class="form-select" onchange="filterMedNames()" required>
                                                        <option value="">Select Type</option>
                                                        <?php foreach ($med_types as $type): ?>
                                                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Medicine Name</label>
                                                    <select name="med_name" id="addMedName" class="form-select" required>
                                                        <option value="">Select Medicine</option>
                                                        <?php
                                                        // Fetch all medicines for all types (will be filtered by JS)
                                                        $all_meds = [];
                                                        $med_stmt = $conn->prepare("SELECT name, type FROM medication ORDER BY type, name");
                                                        $med_stmt->execute();
                                                        $med_result = $med_stmt->get_result();
                                                        while ($row = $med_result->fetch_assoc()) {
                                                            $all_meds[] = $row;
                                                        }
                                                        $med_stmt->close();
                                                        foreach ($all_meds as $medrow): ?>
                                                            <option value="<?= htmlspecialchars($medrow['name']) ?>" data-type="<?= htmlspecialchars($medrow['type']) ?>">
                                                                <?= htmlspecialchars($medrow['name']) ?> (<?= htmlspecialchars($medrow['type']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Dosage</label>
                                                    <input type="text" name="dosage" class="form-control" >
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Date Given</label>
                                                    <input type="date" name="date_given" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="add_animal_medication" class="btn btn-success">
                                                    <i class="fas fa-save me-2"></i> Add Medication
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                        // JS to filter medicine names by type
                        function filterMedNames() {
                            var type = document.getElementById('addMedType').value;
                            var medSelect = document.getElementById('addMedName');
                            for (var i = 0; i < medSelect.options.length; i++) {
                                var opt = medSelect.options[i];
                                if (!type || !opt.getAttribute('data-type')) {
                                    opt.style.display = '';
                                } else if (opt.getAttribute('data-type') === type) {
                                    opt.style.display = '';
                                } else {
                                    opt.style.display = 'none';
                                }
                            }
                            medSelect.value = '';
                        }
                        </script>
                        <h5> 
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>
                        </h5>
                        <div class="medication-history">
                            <?php if (!empty($medications)): ?>
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            <th>Date Given</th>
                                            <th>Given By</th>
                                            <th>Action</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medications as $med): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($med['name']) ?>
                                                    <small class="d-block text-muted">
                                                        <?= htmlspecialchars($med['type']) ?>
                                                    </small>
                                                </td>
                                                <td><?= htmlspecialchars($med['dosage']) ?></td>
                                                <td><?= date('M d, Y', strtotime($med['date_given'])) ?></td>
                                                <td><?= htmlspecialchars(!empty($med['updated_by']) ? $med['updated_by'] : $med['added_by']) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $med['animed_id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <!-- Delete Button triggers modal -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $med['animed_id'] ?>">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>

                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?= $med['animed_id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $med['animed_id'] ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title" id="deleteModal<?= $med['animed_id'] ?>">
                                                                        <i class="fas fa-exclamation-triangle me-2"></i> Confirm Deletion
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="" method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="animed_id" value="<?= $med['animed_id'] ?>">
                                                                        <input type="hidden" name="animal_id" value="<?= htmlspecialchars($animal['animal_id']) ?>">
                                                                        <p>Are you sure you want to delete the medication <strong><?= htmlspecialchars($med['name']) ?></strong> given on <strong><?= date('M d, Y', strtotime($med['date_given'])) ?></strong>?</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="delete_animal_medication" class="btn btn-danger">
                                                                            <i class="fas fa-trash-alt me-2"></i> Delete Medicine
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- EditModal-->
                                                    <div class="modal fade" id="editModal<?php echo $med['animed_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $med['animed_id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning">
                                                                    <h5 class="modal-title" id="editModalLabel<?php echo $med['animed_id']; ?>">
                                                                        <i class="fas fa-edit me-2"></i> Edit Medication
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>

                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="animed_id" value="<?php echo $med['animed_id']; ?>">
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Medicine Name</label>
                                                                            <?php
                                                                            // Fetch current medicine name and type for this medication
                                                                            $current_medicine_name = $med['name'];
                                                                            $current_type = $med['type'];
                                                                            
                                                                            // Fetch all medicine names of the same type
                                                                            $medicine_options = [];
                                                                            $stmt = $conn->prepare("SELECT name FROM medication WHERE type = ?");
                                                                            $stmt->bind_param("s", $current_type);
                                                                            $stmt->execute();
                                                                            $result = $stmt->get_result();
                                                                            while ($row = $result->fetch_assoc()) {
                                                                                $medicine_options[] = $row['name'];
                                                                            }
                                                                            $stmt->close();
                                                                            ?>
                                                                        <div class="mb-1">
                                                                            <strong>Current:</strong> <?= htmlspecialchars($current_medicine_name) ?>
                                                                        </div>
                                                                        <select name="name" class="form-select" required>
                                                                            <option value="">Select Medicine</option>
                                                                            <?php foreach ($medicine_options as $option): ?>
                                                                                <option value="<?= htmlspecialchars($option) ?>" <?= ($option == $current_medicine_name) ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($option) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Dosage</label>
                                                                            <input type="text" name="dosage" class="form-control" value="<?= htmlspecialchars($med['dosage']) ?>" required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Date Given</label>
                                                                            <input type="date" name="date_given" class="form-control" value="<?= htmlspecialchars($med['date_given']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_animal_medication" class="btn btn-warning">
                                                                            <i class="fas fa-save me-2"></i> Save Changes
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                

                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">No medication history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer text-center d-md-none">
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times me-2"></i>Close
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
