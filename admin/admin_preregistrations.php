<?php
session_start();
include '../db/conn.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$success_message = "";
$error_message = "";

// Handle owner verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $owner_id = $_POST['owner_id'];
    $action = $_POST['action'];
  
    
    if ($action === 'approve') {
        $status = 'verified';
        $verified_by = $_SESSION['admin_id'];
        $verified_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE owners SET status = ?,  verified_by = ?, verified_at = ? WHERE owner_id = ?");
        $stmt->bind_param("ssss", $status, $verified_by, $verified_at, $owner_id);
        
        if ($stmt->execute()) {
            $success_message = "Owner account approved successfully!";
        } else {
            $error_message = "Error approving owner: " . $stmt->error;
        }
        $stmt->close();
        
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $verified_by = $_SESSION['admin_id'];
        $verified_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE owners SET status = ?,  verified_by = ?, verified_at = ? WHERE owner_id = ?");
        $stmt->bind_param("ssss", $status,  $verified_by, $verified_at, $owner_id);
        
        if ($stmt->execute()) {
            $success_message = "Owner account rejected.";
        } else {
            $error_message = "Error rejecting owner: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch pending owners
$pending_owners = [];
$result = $conn->query("
    SELECT o.*, a.username as verified_by_name 
    FROM owners o 
    LEFT JOIN admins a ON o.verified_by = a.admin_id 
    WHERE o.status = 'pending' 
    ORDER BY o.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $pending_owners[] = $row;
}

// Fetch verified owners
$verified_owners = [];
$result = $conn->query("
    SELECT o.*, a.username as verified_by_name 
    FROM owners o 
    LEFT JOIN admins a ON o.verified_by = a.admin_id 
    WHERE o.status = 'verified' 
    ORDER BY o.verified_at DESC
");
while ($row = $result->fetch_assoc()) {
    $verified_owners[] = $row;
}

// Fetch rejected owners
$rejected_owners = [];
$result = $conn->query("
    SELECT o.*, a.username as verified_by_name 
    FROM owners o 
    LEFT JOIN admins a ON o.verified_by = a.admin_id 
    WHERE o.status = 'rejected' 
    ORDER BY o.verified_at DESC
");
while ($row = $result->fetch_assoc()) {
    $rejected_owners[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Owner Pre-Registrations | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            background-color: var(--light-bg);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
        }
        .text-muted{
            color: #16a34a;
            font-weight: bold;
            
        }
        .main-content {
            flex: 1;
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
       
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-verified {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-suspended {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-approve {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .btn-reject {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .owner-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .owner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-pills .nav-link {
            color: var(--primary-color);
            border-radius: 20px;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .btn-info {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #15803d;
            border-color: #15803d;
        }
        
        .modal-lg {
            max-width: 1200px;
        }
        
        .modal-xl {
            max-width: 990px;
        }
        
        .modal-xxl {
            max-width: 1800px;
            
        }
        
        .form-label {
            color: #495057;
            font-size: 0.9rem;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
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
                    <h1 class="h3 mt-4 fw-bold" >Registration Request</h1>
                    <p class="mb-5">Owner pre-registrations</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
                   
                    
                

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count($pending_owners); ?></h3>
                                <p class="text-muted mb-0">Pending Approvals</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo count($verified_owners); ?></h3>
                                <p class="text-muted mb-0">Verified Owners</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-danger"><?php echo count($rejected_owners); ?></h3>
                                <p class="text-muted mb-0">Rejected Applications</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button" role="tab">
                            <i class="fas fa-clock me-2"></i>Pending (<?php echo count($pending_owners); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="verified-tab" data-bs-toggle="pill" data-bs-target="#verified" type="button" role="tab">
                            <i class="fas fa-check-circle me-2"></i>Verified (<?php echo count($verified_owners); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rejected-tab" data-bs-toggle="pill" data-bs-target="#rejected" type="button" role="tab">
                            <i class="fas fa-times-circle me-2"></i>Rejected (<?php echo count($rejected_owners); ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="pills-tabContent">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <?php if (empty($pending_owners)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">No pending approvals</h5>
                                <p class="text-muted">All owner applications have been processed</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($pending_owners as $owner): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card owner-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1"><?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?></h5>
                                                        
                                                    </div>
                                                    <span class="status-badge status-pending">Pending</span>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-8">
                                                        <small class="text-muted">Email</small>
                                                        <p class="mb-1"><?php echo $owner['email']; ?></p>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Age</small>
                                                        <p class="mb-1"><?php 
                                                            $today = new DateTime();
                                                            $dob = new DateTime($owner['birthdate']);
                                                            echo $dob->diff($today)->y . ' years old';
                                                        ?></p>
                                                    </div>
                                                    
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-8">
                                                        <small class="text-muted">Barangay</small>
                                                        <p class="mb-1"><?php echo $owner['barangay']; ?></p>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Phone</small>
                                                        <p class="mb-1"><?php echo $owner['phone']; ?></p>
                                                    </div>
                                                    
                                                </div>
                                                
                                                <small class="text-muted">Applied: <?php echo date('M j, Y g:i A', strtotime($owner['created_at'])); ?></small>
                                                
                                                <div class="mt-3">
                                                    <button class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#viewInfoModal" 
                                                            data-owner-id="<?php echo $owner['owner_id']; ?>"
                                                            data-owner-name="<?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?>">
                                                        <i class="fas fa-eye me-1"></i>View Info
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Verified Tab -->
                    <div class="tab-pane fade" id="verified" role="tabpanel">
                        <?php if (empty($verified_owners)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No verified owners yet</h5>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($verified_owners as $owner): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card owner-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1"><?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?></h5>
                                                        <p class="text-muted small mb-0">Owner ID: <?php echo $owner['owner_id']; ?></p>
                                                    </div>
                                                    <span class="status-badge status-verified">Verified</span>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-8">
                                                        <small class="text-muted">Email</small>
                                                        <p class="mb-1"><?php echo $owner['email']; ?></p>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Phone</small>
                                                        <p class="mb-1"><?php echo $owner['phone']; ?></p>
                                                    </div>
                                                </div>
                                                
                                                <small class="text-muted">
                                                    Verified by: <?php echo $owner['verified_by_name'] ?? 'System'; ?><br>
                                                    On: <?php echo date('M j, Y g:i A', strtotime($owner['verified_at'])); ?>
                                                </small>
                                                
                                                <div class="mt-3">
                                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewVerifiedInfoModal" 
                                                            data-owner-id="<?php echo $owner['owner_id']; ?>"
                                                            data-owner-name="<?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?>">
                                                        <i class="fas fa-eye me-1"></i>View Info
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rejected Tab -->
                    <div class="tab-pane fade" id="rejected" role="tabpanel">
                        <?php if (empty($rejected_owners)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No rejected applications</h5>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($rejected_owners as $owner): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card owner-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1"><?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?></h5>
                                                        <p class="text-muted small mb-0">Owner ID: <?php echo $owner['owner_id']; ?></p>
                                                    </div>
                                                    <span class="status-badge status-suspended">Rejected</span>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-8">
                                                        <small class="text-muted">Email</small>
                                                        <p class="mb-1"><?php echo $owner['email']; ?></p>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Phone</small>
                                                        <p class="mb-1"><?php echo $owner['phone']; ?></p>
                                                    </div>
                                                </div>
                                                
                                                <small class="text-muted">
                                                    Rejected by: <?php echo $owner['verified_by_name'] ?? 'System'; ?><br>
                                                    On: <?php echo date('M j, Y g:i A', strtotime($owner['verified_at'])); ?>
                                                </small>
                                                
                                                <div class="mt-3">
                                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewRejectedInfoModal" 
                                                            data-owner-id="<?php echo $owner['owner_id']; ?>"
                                                            data-owner-name="<?php echo $owner['first_name'] . ' ' . $owner['last_name']; ?>">
                                                        <i class="fas fa-eye me-1"></i>View Info
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Information Modal -->
    <div class="modal fade" id="viewInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content"  >
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">Owner Information Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="ownerDetails">
                    <!-- Owner details will be loaded here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger me-2" id="rejectFromViewBtn">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                    <button type="button" class="btn btn-success" id="approveFromViewBtn">
                        <i class="fas fa-check me-1"></i>Approve
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Owner</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="owner_id" id="approve_owner_id">
                        <p>Are you sure you want to approve <strong id="approve_owner_name"></strong>?</p>
                       
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Owner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Owner</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="owner_id" id="reject_owner_id">
                        <p>Are you sure you want to reject <strong id="reject_owner_name"></strong>?</p>
                       
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Owner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Verified Owner Information Modal -->
    <div class="modal fade" id="viewVerifiedInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">Verified Owner Information Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="verifiedOwnerDetails">
                    <!-- Verified owner details will be loaded here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Rejected Owner Information Modal -->
    <div class="modal fade" id="viewRejectedInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" >
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">Rejected Owner Information Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="rejectedOwnerDetails">
                    <!-- Rejected owner details will be loaded here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentOwnerId = null;
        let currentOwnerName = null;

        // Handle view info modal
        document.querySelectorAll('[data-bs-target="#viewInfoModal"]').forEach(button => {
            button.addEventListener('click', function() {
                currentOwnerId = this.dataset.ownerId;
                currentOwnerName = this.dataset.ownerName;
                loadOwnerDetails(currentOwnerId);
            });
        });

        // Handle view verified info modal
        document.querySelectorAll('[data-bs-target="#viewVerifiedInfoModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const ownerId = this.dataset.ownerId;
                loadVerifiedOwnerDetails(ownerId);
            });
        });

        // Handle view rejected info modal
        document.querySelectorAll('[data-bs-target="#viewRejectedInfoModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const ownerId = this.dataset.ownerId;
                loadRejectedOwnerDetails(ownerId);
            });
        });

        // Load owner details function
        function loadOwnerDetails(ownerId) {
            // Find the owner data from the PHP arrays
            const pendingOwners = <?php echo json_encode($pending_owners); ?>;
            const owner = pendingOwners.find(o => o.owner_id == ownerId);
            
            if (owner) {
                const today = new Date();
                const dob = new Date(owner.birthdate);
                const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                
                const detailsHtml = `
                    <div class="row g-4">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6 ">Full Name:</label>
                                        <p class="mb-0 fs-5">${owner.first_name} ${owner.middle_name || ''} ${owner.last_name}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Email:</label>
                                        <p class="mb-0 fs-5">${owner.email}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Phone:</label>
                                        <p class="mb-0 fs-5">${owner.phone}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Birthdate:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} </p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Status:</label>
                                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">Pending</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Address:</label>
                                        <p class="mb-0 fs-5">${owner.address}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Barangay:</label>
                                        <p class="mb-0 fs-5">${owner.barangay}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">City:</label>
                                        <p class="mb-0 fs-5">${owner.city}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Province:</label>
                                        <p class="mb-0 fs-5">${owner.province}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Application Date:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                `;
                
                document.getElementById('ownerDetails').innerHTML = detailsHtml;
            }
        }

        // Load verified owner details function
        function loadVerifiedOwnerDetails(ownerId) {
            const verifiedOwners = <?php echo json_encode($verified_owners); ?>;
            const owner = verifiedOwners.find(o => o.owner_id == ownerId);
            
            if (owner) {
                const today = new Date();
                const dob = new Date(owner.birthdate);
                const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                
                const detailsHtml = `
                    <div class="row g-4">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Full Name:</label>
                                        <p class="mb-0 fs-5">${owner.first_name} ${owner.middle_name || ''} ${owner.last_name}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Email:</label>
                                        <p class="mb-0 fs-5">${owner.email}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Phone:</label>
                                        <p class="mb-0 fs-5">${owner.phone}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Birthdate:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Status:</label>
                                        <span class="badge bg-success text-white fs-6 px-3 py-2">Verified</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Address:</label>
                                        <p class="mb-0 fs-5">${owner.address}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Barangay:</label>
                                        <p class="mb-0 fs-5">${owner.barangay}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">City:</label>
                                        <p class="mb-0 fs-5">${owner.city}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Province:</label>
                                        <p class="mb-0 fs-5">${owner.province}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Verified Date:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.verified_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                  
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('verifiedOwnerDetails').innerHTML = detailsHtml;
            }
        }

        // Load rejected owner details function
        function loadRejectedOwnerDetails(ownerId) {
            const rejectedOwners = <?php echo json_encode($rejected_owners); ?>;
            const owner = rejectedOwners.find(o => o.owner_id == ownerId);
            
            if (owner) {
                const today = new Date();
                const dob = new Date(owner.birthdate);
                const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                
                const detailsHtml = `
                    <div class="row g-4">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Full Name:</label>
                                        <p class="mb-0 fs-5">${owner.first_name} ${owner.middle_name || ''} ${owner.last_name}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Email:</label>
                                        <p class="mb-0 fs-5">${owner.email}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Phone:</label>
                                        <p class="mb-0 fs-5">${owner.phone}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Birthdate:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Status:</label>
                                        <span class="badge bg-danger text-white fs-6 px-3 py-2">Rejected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-lg-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                                </div>
                                <div class="card-body p-4 text-start">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Address:</label>
                                        <p class="mb-0 fs-5">${owner.address}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Barangay:</label>
                                        <p class="mb-0 fs-5">${owner.barangay}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">City:</label>
                                        <p class="mb-0 fs-5">${owner.city}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Province:</label>
                                        <p class="mb-0 fs-5">${owner.province}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-primary fs-6">Rejected Date:</label>
                                        <p class="mb-0 fs-5">${new Date(owner.verified_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('rejectedOwnerDetails').innerHTML = detailsHtml;
            }
        }

        // Handle approve from view modal
        document.getElementById('approveFromViewBtn').addEventListener('click', function() {
            if (currentOwnerId) {
                document.getElementById('approve_owner_id').value = currentOwnerId;
                document.getElementById('approve_owner_name').textContent = currentOwnerName;
                
                // Close view modal and open approve modal
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewInfoModal'));
                viewModal.hide();
                
                setTimeout(() => {
                    const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
                    approveModal.show();
                }, 300);
            }
        });

        // Handle reject from view modal
        document.getElementById('rejectFromViewBtn').addEventListener('click', function() {
            if (currentOwnerId) {
                document.getElementById('reject_owner_id').value = currentOwnerId;
                document.getElementById('reject_owner_name').textContent = currentOwnerName;
                
                // Close view modal and open reject modal
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewInfoModal'));
                viewModal.hide();
                
                setTimeout(() => {
                    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
                    rejectModal.show();
                }, 300);
            }
        });

        // Handle approve modal (for direct approve buttons if any remain)
        document.querySelectorAll('[data-bs-target="#approveModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('approve_owner_id').value = this.dataset.ownerId;
                document.getElementById('approve_owner_name').textContent = this.dataset.ownerName;
            });
        });

        // Handle reject modal (for direct reject buttons if any remain)
        document.querySelectorAll('[data-bs-target="#rejectModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('reject_owner_id').value = this.dataset.ownerId;
                document.getElementById('reject_owner_name').textContent = this.dataset.ownerName;
            });
        });
    </script>
</body>
</html>
