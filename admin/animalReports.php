<?php
include '../db/conn.php';
$admin_id = $_SESSION['admin_id'];
// Query to count unread notifications for the logged-in admin
$unreadQuery = "SELECT COUNT(*) as unread_count FROM notification WHERE admin_id = ? AND is_read = 0";
$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param("i", $admin_id);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
$unreadStmt->close();

// Get filter parameters
$species_filter = isset($_GET['species']) ? $_GET['species'] : '';
$barangay_filter = isset($_GET['barangay']) ? $_GET['barangay'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$vaccination_filter = isset($_GET['vaccination']) ? $_GET['vaccination'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$query = "SELECT a.*, o.first_name, o.last_name , o.barangay
          FROM animals a 
          LEFT JOIN owners o ON a.owner_id = o.owner_id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total 
                FROM animals a 
                LEFT JOIN owners o ON a.owner_id = o.owner_id 
                WHERE 1=1";

$filter_conditions = '';

if (!empty($species_filter)) {
    $condition = " AND a.species = '$species_filter'";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

if (!empty($barangay_filter)) {
    $condition = " AND o.barangay = '$barangay_filter'";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

if (!empty($status_filter)) {
    $condition = " AND a.status = '$status_filter'";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

if (!empty($vaccination_filter)) {
    $condition = " AND a.vaccination_status = '$vaccination_filter'";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

if (!empty($start_date) && !empty($end_date)) {
    $condition = " AND DATE(a.date_registered) BETWEEN '$start_date' AND '$end_date'";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

if (!empty($search)) {
    $condition = " AND (a.animal_name LIKE '%$search%' OR o.first_name LIKE '%$search%' OR o.last_name LIKE '%$search%')";
    $query .= $condition;
    $count_query .= $condition;
    $filter_conditions .= $condition;
}

// Preview API (JSON response) available before rendering the main page
if (isset($_GET['preview'])) {
    $preview_query = "SELECT a.*, o.first_name, o.last_name, o.barangay
                      FROM animals a
                      LEFT JOIN owners o ON a.owner_id = o.owner_id
                      WHERE 1=1" . $filter_conditions . " ORDER BY a.date_registered DESC LIMIT 50";
    $preview_result = $conn->query($preview_query);
    $preview_rows = [];
    while ($row = $preview_result->fetch_assoc()) {
        $preview_rows[] = [
            'animal_id' => $row['animal_id'],
            'animal_name' => $row['animal_name'],
            'species' => $row['species'],
            'animal_class' => $row['animal_class'],
            'breed' => $row['breed'],
            'owner' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'status' => ucfirst($row['status']),
            'vaccination_status' => $row['vaccination_status'],
            'date_registered' => date('M d, Y', strtotime($row['date_registered']))
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'generated_at' => date('F d, Y H:i:s'),
        'total_preview_records' => count($preview_rows),
        'filters' => [
            'species' => $species_filter,
            'barangay' => $barangay_filter,
            'status' => $status_filter,
            'vaccination' => $vaccination_filter,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'search' => $search
        ],
        'rows' => $preview_rows
    ]);
    exit();
}

// Get total count
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Add sorting and pagination
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_registered';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$query .= " ORDER BY $sort $order LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Get summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_animals,
    SUM(CASE WHEN vaccination_status = 'Vaccinated' THEN 1 ELSE 0 END) as vaccinated_count,
    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) as found_count,
    SUM(CASE WHEN status = 'safe' THEN 1 ELSE 0 END) as safe_count
    FROM animals";

$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();

// Get species distribution
$species_query = "SELECT species, COUNT(*) as count FROM animals GROUP BY species";
$species_result = $conn->query($species_query);
$species_data = [];
while ($row = $species_result->fetch_assoc()) {
    $species_data[$row['species']] = $row['count'];
}

// Get barangay distribution
$barangay_query = "SELECT o.barangay, COUNT(*) as count 
                  FROM animals a 
                  LEFT JOIN owners o ON a.owner_id = o.owner_id 
                  WHERE o.barangay IS NOT NULL 
                  GROUP BY o.barangay";
$barangay_result = $conn->query($barangay_query);
$barangay_data = [];
while ($row = $barangay_result->fetch_assoc()) {
    $barangay_data[$row['barangay']] = $row['count'];
}

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build export query (without pagination)
    $export_query = "SELECT a.*, o.first_name, o.last_name 
                     FROM animals a 
                     LEFT JOIN owners o ON a.owner_id = o.owner_id 
                     WHERE 1=1";
    
    if (!empty($species_filter)) {
        $export_query .= " AND a.species = '$species_filter'";
    }
    if (!empty($barangay_filter)) {
        $export_query .= " AND o.barangay = '$barangay_filter'";
    }
    if (!empty($status_filter)) {
        $export_query .= " AND a.status = '$status_filter'";
    }
    if (!empty($vaccination_filter)) {
        $export_query .= " AND a.vaccination_status = '$vaccination_filter'";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $export_query .= " AND DATE(a.date_registered) BETWEEN '$start_date' AND '$end_date'";
    }
    if (!empty($search)) {
        $export_query .= " AND (a.animal_name LIKE '%$search%' OR o.first_name LIKE '%$search%' OR o.last_name LIKE '%$search%')";
    }
    
    $export_query .= " ORDER BY a.date_registered DESC";
    $export_result = $conn->query($export_query);
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="animal_reports_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header information
        fputcsv($output, ['Animal Reports - Generated on ' . date('F d, Y H:i:s')]);
        fputcsv($output, ['Total Records: ' . $export_result->num_rows]);
        fputcsv($output, []); // Empty row
        
        // Add column headers
        fputcsv($output, ['Animal ID', 'Name', 'Species', 'Class', 'Breed', 'Owner', 'Status', 'Vaccination', 'Date Registered']);
        
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['animal_id'],
                $row['animal_name'],
                $row['species'],
                $row['animal_class'],
                $row['breed'],
                $row['first_name'] . ' ' . $row['last_name'],
                ucfirst($row['status']),
                $row['vaccination_status'],
                date('M d, Y', strtotime($row['date_registered']))
            ]);
        }
        
        fclose($output);
        exit();
        
    } elseif ($export_type === 'pdf') {
        // Output HTML that will be converted to PDF and downloaded
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Animal Reports</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px;
                    background: white;
                }
                h1 { 
                    color: #198754; 
                    text-align: center; 
                    margin-bottom: 20px;
                    font-size: 24px;
                }
                .report-info { 
                    margin-bottom: 20px; 
                    font-size: 12px; 
                    color: #666;
                    text-align: center;
                }
                #loading {
                    text-align: center;
                    padding: 50px;
                    font-size: 18px;
                    color: #198754;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px; 
                    font-size: 9px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 4px; 
                    text-align: left;
                }
                th { 
                    background-color: #198754; 
                    color: white; 
                    font-weight: bold;
                }
                tr:nth-child(even) { 
                    background-color: #f2f2f2; 
                }
            </style>
        </head>
        <body>
            <div id="loading">
                <i class="fas fa-spinner fa-spin"></i> Generating PDF... Please wait.
            </div>
            
            <div id="content">
                <h1>Animal Reports</h1>
                <div class="report-info">
                    Generated on: <?php echo date('F d, Y H:i:s'); ?><br>
                    Total Records: <?php echo $export_result->num_rows; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Species</th>
                            <th>Class</th>
                            <th>Breed</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Vaccination</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $export_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['animal_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['animal_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['species']); ?></td>
                            <td><?php echo htmlspecialchars($row['animal_class']); ?></td>
                            <td><?php echo htmlspecialchars($row['breed']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo $row['vaccination_status']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_registered'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
                window.onload = function() {
                    // Hide loading message
                    document.getElementById('loading').style.display = 'none';
                    
                    const element = document.getElementById('content');
                    const opt = {
                        margin: 10,
                        filename: 'animal_reports_<?php echo date('Y-m-d_H-i-s'); ?>.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, logging: false },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                    };
                    
                    // Generate PDF and download
                    html2pdf().set(opt).from(element).save().then(function() {
                        // Close window after download starts
                        setTimeout(function() {
                            window.close();
                        }, 1000);
                    });
                };
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Animal Reports | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/reports.css">
    
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
                    <h1 class="h3 mt-4 fw-bold">Animal Reports</h1>
                    <p class="mb-5">Comprehensive overview of registered animals and their status</p>
                    
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
            <!-- Reports Navigation Tabs -->
            <div class="reports-tabs mb-4">
                <nav class="nav nav-pills nav-fill">
                    <a class="nav-link active" href="animalReports.php">
                        <i class="fas fa-file-alt me-2"></i>Animals
                    </a>
                    <a class="nav-link" href="medicationReports.php">
                        <i class="fas fa-file-medical me-2"></i>Medication
                    </a>
                    <a class="nav-link" href="lostAndFoundReports.php">
                        <i class="fas fa-search me-2"></i>Lost & Found
                    </a>
                    <a class="nav-link" href="ownerReports.php">
                        <i class="fas fa-user-circle me-2"></i>Owners
                    </a>
                    <a class="nav-link" href="staffPerformanceReports.php">
                        <i class="fas fa-chart-bar me-2"></i>Staff Performance
                    </a>
                </nav>
            </div>

            <!-- Filter Button -->
            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-paw"></i>
                        </div>
                        <div class="stat-number"><?php echo $summary['total_animals']; ?></div>
                        <div class="stat-label">Total Registered Animals</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3"style="height: 180px;">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-syringe"></i>
                        </div>
                        <div class="stat-number"><?php echo $summary['vaccinated_count']; ?></div>
                        <div class="stat-label">Vaccinated Animals</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="stat-number"><?php echo $summary['lost_count']; ?></div>
                        <div class="stat-label">Lost Animals</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                    <div class="card stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-number"><?php echo $summary['found_count']; ?></div>
                        <div class="stat-label">Found Animals</div>
                    </div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color: white;"><i class="fas fa-table me-2" style="color: white;"></i>Animal Records</h5>
                    <div class="d-flex align-items-center">
                        
                        <button type="button" class="btn btn-filter btn-outline-success" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i> Filter
                        </button>
                    
                        <div class="export-buttons d-flex align-items-center gap-2">
                            <button class="btn btn-export-csv" onclick="exportToCSV()">
                                <i class="fas fa-file-csv me-1"></i> CSV
                            </button>
                            <button class="btn btn-export-pdf" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                            <button class="btn btn-outline-secondary" onclick="showPreview('csv')">
                                <i class="fas fa-eye me-1"></i> Preview CSV
                            </button>
                            <button class="btn btn-outline-secondary" onclick="showPreview('pdf')">
                                <i class="fas fa-eye me-1"></i> Preview PDF
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'animal_name', 'order' => $sort == 'animal_name' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Name
                                            <span class="sort-icon"><?php echo $sort == 'animal_name' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'species', 'order' => $sort == 'species' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Species
                                            <span class="sort-icon"><?php echo $sort == 'species' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'barangay', 'order' => $sort == 'barangay' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Barangay
                                            <span class="sort-icon"><?php echo $sort == 'barangay' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                    <th>Breed</th>
                                    <th>Owner</th>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Status
                                            <span class="sort-icon"><?php echo $sort == 'status' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'vaccination_status', 'order' => $sort == 'vaccination_status' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Vaccination
                                            <span class="sort-icon"><?php echo $sort == 'vaccination_status' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date_registered', 'order' => $sort == 'date_registered' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                            Registered
                                            <span class="sort-icon"><?php echo $sort == 'date_registered' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['animal_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['species']); ?></td>
                                            <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                            <td><?php echo htmlspecialchars($row['breed']); ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'lost'): ?>
                                                    <span class="badge badge-lost">Lost</span>
                                                <?php elseif ($row['status'] == 'found'): ?>
                                                    <span class="badge badge-found">Found</span>
                                                <?php else: ?>
                                                    <span class="badge badge-safe">Safe</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['vaccination_status'] == 'Vaccinated'): ?>
                                                    <span class="badge badge-vaccinated">Vaccinated</span>
                                                <?php else: ?>
                                                    <span class="badge badge-not-vaccinated">Not Vaccinated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_registered'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">No animals found matching your criteria</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="width: 90%;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="filterModalLabel"><i class="fas fa-filter me-2"></i>Filter Records</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="species" class="form-label">Species</label>
                                <select class="form-select" id="species" name="species">
                                    <option value="">All Species</option>
                                    <?php
                                    $species_query = "SELECT DISTINCT species FROM animals ORDER BY species ASC";
                                    $species_result = $conn->query($species_query);
                                    while ($row = $species_result->fetch_assoc()) {
                                        $selected = (isset($_GET['species']) && $_GET['species'] == $row['species']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['species']) . "' $selected>" . ucfirst($row['species']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select" id="barangay" name="barangay">
                                    <option value="">All Barangays</option>
                                    <?php 
                                    $barangays_query = "SELECT DISTINCT barangay FROM owners WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
                                    $barangays_result = $conn->query($barangays_query);
                                    while ($barangay = $barangays_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($barangay['barangay']) . '" ';
                                        echo ($barangay_filter == $barangay['barangay']) ? 'selected' : '';
                                        echo '>' . htmlspecialchars($barangay['barangay']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="Safe" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Safe') ? 'selected' : ''; ?>>Safe</option>
                                    <option value="Lost" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Lost') ? 'selected' : ''; ?>>Lost</option>
                                    <option value="Found" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Found') ? 'selected' : ''; ?>>Found</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vaccination" class="form-label">Vaccination</label>
                                <select class="form-select" id="vaccination" name="vaccination">
                                    <option value="">All</option>
                                    <option value="Vaccinated" <?php echo (isset($_GET['vaccination']) && $_GET['vaccination'] == 'Vaccinated') ? 'selected' : ''; ?>>Vaccinated</option>
                                    <option value="Not Vaccinated" <?php echo (isset($_GET['vaccination']) && $_GET['vaccination'] == 'Not Vaccinated') ? 'selected' : ''; ?>>Not Vaccinated</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                            </div>
                        </div>
                        <div class="modal-footer mt-4">
                            <a href="animalReports.php" class="btn btn-outline-secondary me-auto">Reset All</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("input[type=date]", {
            allowInput: true,
            dateFormat: "Y-m-d",
        });
        
        // Toggle filters on mobile
        function toggleFilters() {
            const filterSection = document.getElementById('filterSection');
            const toggleBtn = document.querySelector('.filter-toggle');
            
            if (filterSection.classList.contains('show')) {
                filterSection.classList.remove('show');
                toggleBtn.innerHTML = '<i class="fas fa-filter me-2"></i> Show Filters';
            } else {
                filterSection.classList.add('show');
                toggleBtn.innerHTML = '<i class="fas fa-times me-2"></i> Hide Filters';
            }
        }
        
        // Export functions
        function exportToCSV() {
            // Get current filter parameters and add export type
            let params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }
        
        function exportToPDF() {
            // Get current filter parameters and add export type
            let params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            // Open in new tab to keep current page intact
            window.open('?' + params.toString(), '_blank');
        }
        
        // Responsive table handling
        function handleTableResponsive() {
            const table = document.querySelector('.table');
            if (window.innerWidth < 768) {
                table.classList.add('table-sm');
            } else {
                table.classList.remove('table-sm');
            }
        }
        
        window.addEventListener('resize', handleTableResponsive);
        handleTableResponsive();
    </script>
</body>
</html>