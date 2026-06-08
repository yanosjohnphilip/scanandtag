<?php
include '../db/conn.php';

$success_message = "";
$error_message = "";

// Retrieve admin ID from session
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

// Fetch admin name
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
}

// Handle medicine deletion
if (isset($_POST['delete_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    
    $delete_sql = "DELETE FROM medication WHERE med_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $medicine_id);
        
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

// Handle medicine update
if (isset($_POST['update_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    $name = $_POST['edit_name'];
    $type = $_POST['edit_type'];
    $description = $_POST['edit_description'];
    
    // Input validation
    if (empty($name) || empty($type) || empty($description)) {
        $error_message = "All fields are required for updating!";
    } else {
        // Update medication record
        $update_sql = "UPDATE medication SET name = ?, type = ?, description = ?, updated_by = ? WHERE med_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param("ssssi", $name, $type, $description, $admin_name, $medicine_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Medicine updated successfully!";
            } else {
                $error_message = "Error updating medicine: " . $update_stmt->error;
            }
            
            $update_stmt->close();
        } else {
            $error_message = "Error preparing update statement: " . $conn->error;
        }
    }
}

// Handle adding new medicine
if (isset($_POST['add_medicine'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    // Input validation
    if (empty($name) || empty($type) || empty($description)) {
        $error_message = "All fields are required!";
    } else {
        // Insert into medication table
        $sql = "INSERT INTO medication (name, type, description, added_by, created_at) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $type, $description, $admin_name);

            if ($stmt->execute()) {
                // Success
                $success_message = "Medicine added successfully!";
            } else {
                // Database error
                $error_message = "Error adding medicine: " . $stmt->error;
            }

            $stmt->close();
        } else {
            // Error preparing the statement
            $error_message = "Error preparing the statement: " . $conn->error;
        }
    }
}

// Pagination settings
$records_per_page = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records = $conn->query("SELECT COUNT(*) as count FROM medication")->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);
$page = max(1, min($page, $total_pages)); // Ensure page is within valid range
$offset = ($page - 1) * $records_per_page;

// Fetch medications with pagination
$sql = "SELECT * FROM medication ORDER BY name ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$medications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $medications[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Medicine Management | City Vet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-light: #4CAF50;
            --primary-dark: #388E3C;
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
            margin-bottom: 2rem;
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
            padding: 1rem;
        }
        .main-content {
            flex: 1;
            justify-content: center;
            align-items: center;
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
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #bb2d3b;
        }
        
        .btn-warning {
            background-color: var(--secondary-color);
            border: none;
            color: var(--dark);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        
        .btn-warning:hover {
            background-color: #e6ae06;
        }
        
        .table-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
            border: none;
            padding: 0.83rem 1rem;
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.83rem 1rem;
            color: var(--dark);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(76, 175, 80, 0.05);
        }
        
       
        .table tbody tr {
            height: 60px; 
        }
        
        
        .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px; 
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.5em 0.85em;
            border-radius: 6px;
        }
        
        .badge-vaccination {
            background-color: #4CAF50;
            color: white;
        }
        
        .badge-deworming {
            background-color: #2196F3;
            color: white;
        }
        
        .badge-vitamins {
            background-color: #FFC107;
            color: var(--dark);
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Pagination styles */
        .pagination {
            margin-top: 1.5rem;
            justify-content: center;
        }
        
        .pagination .page-link {
            color: var(--primary-color);
            border: 1px solid var(--light-gray);
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.2s;
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            min-width: 36px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            border-color: var(--primary-dark);
            color: white;
        }
        
        .pagination .page-link:hover {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        
        .pagination .page-link:focus {
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
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
        
        .modal-content {
            border-radius: 16px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            border-bottom: none;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color);
            color: white !important;
            border: 1px solid var(--primary-color);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--primary-light);
            color: white !important;
            border: 1px solid var(--primary-light);
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            padding: 0.4rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            padding: 0.2rem;
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
                padding: 1rem;
            }
            
            .card {
                margin-top: 10px;
            }
            
            .card-header h4 {
                margin-top: 10px;
                font-size: 1.25rem;
            }

            .mobile-toggle {
                display: block;
            }

            .header-search input {
                width: 180px;
            }
            
            .table-container {
                padding: 0.75rem;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 0.25rem;
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
                    <h1 class="h3 mt-4 fw-bold">Medicine Management</h1>
                    <p class="mb-5">Add Medicine</p>
                    
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>

            <!-- Display messages -->
            <?php if (!empty($success_message)) { echo "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i> $success_message</div>"; } ?>
            <?php if (!empty($error_message)) { echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i> $error_message</div>"; } ?>
            
            <div class="row">
                <div class="col-lg-4">
                    <!-- Add Medicine Card -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h4><i class="fas fa-plus-circle me-2"></i> Add New Medicine</h4>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label"><i class="fas fa-prescription-bottle-alt me-2"></i>Medicine Name</label>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter medicine name" required>
                                </div>
                            
                                <div class="mb-3">
                                    <label for="type" class="form-label"><i class="fas fa-tags me-2"></i>Medicine Type</label>
                                    <select id="type" name="type" class="form-select" required>
                                        <option value="" disabled selected>Select medicine type</option>
                                        <option value="Vaccination">Vaccination</option>
                                        <option value="Deworming">Deworming</option>
                                        <option value="Vitamins">Vitamins</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label"><i class="fas fa-file-alt me-2"></i>Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter medicine description" required></textarea>
                                </div>
                                
                                <button type="submit" name="add_medicine" class="btn btn-success w-100"><i class="fas fa-plus-circle me-2"></i>Add Medicine</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <!-- Medicines Table Card -->
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-list me-2"></i> Medicine Inventory</h4>
                            <div class="ms-0" style="width: 250px; height: 20px;margin-bottom: 15px; margin-top: -10px;">
                                <div class="dataTables_filter">
                                    <label class="form-label mb-0">
                                        <input type="search" class="form-control form-control-sm" placeholder="Search..." aria-controls="medicinesTable">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="medicinesTable" class="table table-striped table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medications as $medicine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                            <td>
                                                <?php 
                                                $type = htmlspecialchars($medicine['type']);
                                                $badgeClass = 'badge-';
                                                switch($type) {
                                                    case 'Vaccination':
                                                        $badgeClass .= 'vaccination';
                                                        break;
                                                    case 'Deworming':
                                                        $badgeClass .= 'deworming';
                                                        break;
                                                    case 'Vitamins':
                                                        $badgeClass .= 'vitamins';
                                                        break;
                                                    default:
                                                        $badgeClass .= 'secondary';
                                                }
                                                echo "<span class='badge {$badgeClass}'>{$type}</span>";
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                           
                                        
                                            <td>
                                                <div class="action-btns">
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $medicine['med_id']; ?>">
                                                        <i class="fas fa-edit"></i> 
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $medicine['med_id']; ?>">
                                                        <i class="fas fa-trash-alt"></i> 
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $medicine['med_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $medicine['med_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel<?php echo $medicine['med_id']; ?>">
                                                            <i class="fas fa-edit me-2"></i> Edit Medicine
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="medicine_id" value="<?php echo $medicine['med_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $medicine['med_id']; ?>" class="form-label">Medicine Name</label>
                                                                <input type="text" id="edit_name<?php echo $medicine['med_id']; ?>" name="edit_name" class="form-control" value="<?php echo htmlspecialchars($medicine['name']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="edit_type<?php echo $medicine['med_id']; ?>" class="form-label">Medicine Type</label>
                                                                <select id="edit_type<?php echo $medicine['med_id']; ?>" name="edit_type" class="form-select" required>
                                                                    <option value="Vaccination" <?php echo ($medicine['type'] == 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                                                                    <option value="Deworming" <?php echo ($medicine['type'] == 'Deworming') ? 'selected' : ''; ?>>Deworming</option>
                                                                    <option value="Vitamins" <?php echo ($medicine['type'] == 'Vitamins') ? 'selected' : ''; ?>>Vitamins</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $medicine['med_id']; ?>" class="form-label">Description</label>
                                                                <textarea id="edit_description<?php echo $medicine['med_id']; ?>" name="edit_description" class="form-control" rows="3" required><?php echo htmlspecialchars($medicine['description']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_medicine" class="btn btn-primary">
                                                                <i class="fas fa-save me-2"></i> Save Changes
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $medicine['med_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $medicine['med_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $medicine['med_id']; ?>">
                                                            <i class="fas fa-exclamation-triangle me-2"></i> Confirm Deletion
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="medicine_id" value="<?php echo $medicine['med_id']; ?>">
                                                            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($medicine['name']); ?></strong>?</p>
                                                            <p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i> This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete_medicine" class="btn btn-danger">
                                                                <i class="fas fa-trash-alt me-2"></i> Delete Medicine
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Medicine pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous Page Link -->
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo; Previous</span>
                                            </a>
                                        </li>
                                        
                                        <!-- Page Numbers -->
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Next Page Link -->
                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">Next &raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            var table = $('#medicinesTable').DataTable({
                "paging": false, // Disable DataTables pagination since we're using custom pagination
                "searching": true,
                "ordering": true,
                "info": false,
                "responsive": true,
                "dom": 'lrtip' // Hide the default search box
            });
            
            // Connect custom search box to DataTables
            $('.dataTables_filter input').on('keyup', function() {
                table.search(this.value).draw();
            });
        });
    </script>
</body>
</html>