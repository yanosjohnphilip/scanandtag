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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$barangay_filter = isset($_GET['barangay']) ? $_GET['barangay'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$query = "SELECT a.*, o.first_name, o.last_name, o.barangay, 
          (SELECT COUNT(*) FROM found_reports fr WHERE fr.animal_id = a.animal_id) as found_reports_count
          FROM animals a 
          LEFT JOIN owners o ON a.owner_id = o.owner_id 
          WHERE (a.status = 'lost' OR a.status = 'found')";

$count_query = "SELECT COUNT(*) as total 
                FROM animals a 
                LEFT JOIN owners o ON a.owner_id = o.owner_id 
                WHERE (a.status = 'lost' OR a.status = 'found')";

if (!empty($species_filter)) {
    $query .= " AND a.species = '$species_filter'";
    $count_query .= " AND a.species = '$species_filter'";
}

if (!empty($status_filter)) {
    $query .= " AND a.status = '$status_filter'";
    $count_query .= " AND a.status = '$status_filter'";
}

if (!empty($barangay_filter)) {
    $query .= " AND o.barangay = '$barangay_filter'";
    $count_query .= " AND o.barangay = '$barangay_filter'";
}

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(a.date_registered) BETWEEN '$start_date' AND '$end_date'";
    $count_query .= " AND DATE(a.date_registered) BETWEEN '$start_date' AND '$end_date'";
}

if (!empty($search)) {
    $query .= " AND (a.animal_name LIKE '%$search%' OR o.first_name LIKE '%$search%' OR o.last_name LIKE '%$search%')";
    $count_query .= " AND (a.animal_name LIKE '%$search%' OR o.first_name LIKE '%$search%' OR o.last_name LIKE '%$search%')";
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
    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) as found_count,
    SUM(CASE WHEN status = 'safe' THEN 1 ELSE 0 END) as safe_count
    FROM animals";

$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();

// Get recovery rate
$recovery_query = "SELECT 
    COUNT(DISTINCT fr.animal_id) as recovered_animals,
    (SELECT COUNT(*) FROM animals WHERE status = 'lost') as total_lost
    FROM found_reports fr";

$recovery_result = $conn->query($recovery_query);
$recovery_data = $recovery_result->fetch_assoc();
$recovery_rate = $recovery_data['total_lost'] > 0 ? 
    round(($recovery_data['recovered_animals'] / $recovery_data['total_lost']) * 100, 2) : 0;

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build export query (without pagination)
    $export_query = "SELECT a.*, o.first_name, o.last_name, o.barangay, 
                     (SELECT COUNT(*) FROM found_reports fr WHERE fr.animal_id = a.animal_id) as found_reports_count
                     FROM animals a 
                     LEFT JOIN owners o ON a.owner_id = o.owner_id 
                     WHERE (a.status = 'lost' OR a.status = 'found')";
    
    if (!empty($species_filter)) {
        $export_query .= " AND a.species = '$species_filter'";
    }
    if (!empty($status_filter)) {
        $export_query .= " AND a.status = '$status_filter'";
    }
    if (!empty($barangay_filter)) {
        $export_query .= " AND o.barangay = '$barangay_filter'";
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
        header('Content-Disposition: attachment; filename="lost_found_reports_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header information
        fputcsv($output, ['Lost & Found Reports - Generated on ' . date('F d, Y H:i:s')]);
        fputcsv($output, ['Total Records: ' . $export_result->num_rows]);
        fputcsv($output, []); // Empty row
        
        // Add column headers
        fputcsv($output, ['Animal ID', 'Name', 'Species', 'Breed', 'Owner', 'Barangay', 'Status', 'Found Reports', 'Date Registered']);
        
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['animal_id'],
                $row['animal_name'],
                $row['species'],
                $row['breed'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['barangay'],
                ucfirst($row['status']),
                $row['found_reports_count'],
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
            <title>Lost & Found Reports</title>
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
                <h1>Lost & Found Reports</h1>
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
                            <th>Breed</th>
                            <th>Owner</th>
                            <th>Barangay</th>
                            <th>Status</th>
                            <th>Found Reports</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $export_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['animal_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['animal_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['species']); ?></td>
                            <td><?php echo htmlspecialchars($row['breed']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo $row['found_reports_count']; ?></td>
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
                        filename: 'lost_found_reports_<?php echo date('Y-m-d_H-i-s'); ?>.pdf',
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
    <title>Lost & Found Reports | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/reports.css">
    
</head>
<body>
<div class="container">
      
      <div class="sidebar-container">
          <?php include'templates/sidebar.php'; ?>
      </div>
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                
                <div class="header">
                
                    
                    <div class="header-title">
                        <h1 class="h3 mt-4 fw-bold">Lost & Found Reports</h1>
                        <p class="mb-5">Track lost animals and recovery rates</p>
                        
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
                        <a class="nav-link" href="animalReports.php">
                            <i class="fas fa-file-alt me-2"></i>Animals
                        </a>
                        <a class="nav-link" href="medicationReports.php">
                            <i class="fas fa-file-medical me-2"></i>Medication
                        </a>
                        <a class="nav-link active" href="lostAndFoundReports.php">
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
                
                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                        <div class="card stat-card">
                            <div class="stat-icon stat-icon-total">
                                <i class="fas fa-paw"></i>
                            </div>
                            <div class="stat-number"><?php echo $summary['total_animals']; ?></div>
                            <div class="stat-label">Total Animals</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                        <div class="card stat-card">
                            <div class="stat-icon stat-icon-lost">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="stat-number"><?php echo $summary['lost_count']; ?></div>
                            <div class="stat-label">Currently Lost</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                        <div class="card stat-card">
                            <div class="stat-icon stat-icon-found">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="stat-number"><?php echo $summary['found_count']; ?></div>
                            <div class="stat-label">Found Animals</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3" style="height: 180px;">
                        <div class="card stat-card">
                            <div class="stat-icon stat-icon-recovered">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $recovery_data['recovered_animals']; ?></div>
                            <div class="stat-label">Recovered Animals</div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: white;"><i class="fas fa-table me-2" style="color: white;"></i>Lost & Found Animal Records</h5>
                        <div class="d-flex align-items-center">
                           
                            <button type="button" class="btn btn-filter btn-outline-success" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="fas fa-filter me-2"></i> Filter
                            </button>
                        
                            <div class="export-buttons">
                                <button class="btn btn-export-csv" onclick="exportToCSV()">
                                    <i class="fas fa-file-csv me-1"></i> CSV
                                </button>
                                <button class="btn btn-export-pdf ms-2" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf me-1"></i> PDF
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
                                                Animal
                                                <span class="sort-icon"><?php echo $sort == 'animal_name' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'species', 'order' => $sort == 'species' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Species
                                                <span class="sort-icon"><?php echo $sort == 'species' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>Breed</th>
                                        <th>Owner</th>
                                        <th>Barangay</th>
                                                                                <th>
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Status
                                                <span class="sort-icon"><?php echo $sort == 'status' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>Found Reports</th>
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
                                                <td><?php echo htmlspecialchars($row['breed']); ?></td>
                                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                                <td>
                                                    <?php if ($row['status'] == 'lost'): ?>
                                                        <span class="badge badge-lost">Lost</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-found">Found</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['found_reports_count']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date_registered'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">No lost or found animals matching your criteria</td>
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
                                    $species_query = "SELECT DISTINCT species FROM animals WHERE status = 'lost' OR status = 'found' ORDER BY species ASC";
                                    $species_result = $conn->query($species_query);
                                    while ($row = $species_result->fetch_assoc()) {
                                        $selected = (isset($_GET['species']) && $_GET['species'] == $row['species']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['species']) . "' $selected>" . ucfirst($row['species']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="lost" <?php echo (isset($_GET['status']) && $_GET['status'] == 'lost') ? 'selected' : ''; ?>>Lost</option>
                                    <option value="found" <?php echo (isset($_GET['status']) && $_GET['status'] == 'found') ? 'selected' : ''; ?>>Found</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select" id="barangay" name="barangay">
                                    <option value="">All Barangays</option>
                                    <?php
                                    $barangay_query = "SELECT DISTINCT o.barangay FROM owners o 
                                                       INNER JOIN animals a ON o.owner_id = a.owner_id 
                                                       WHERE (a.status = 'lost' OR a.status = 'found') 
                                                       ORDER BY o.barangay ASC";
                                    $barangay_result = $conn->query($barangay_query);
                                    while ($row = $barangay_result->fetch_assoc()) {
                                        $selected = (isset($_GET['barangay']) && $_GET['barangay'] == $row['barangay']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['barangay']) . "' $selected>" . htmlspecialchars($row['barangay']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Animal or Owner name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
                            <a href="lostAndFoundReports.php" class="btn btn-outline-secondary me-auto">Reset All</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Apply Filters</button>
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
            
            if (filterSection && filterSection.classList.contains('show')) {
                filterSection.classList.remove('show');
                toggleBtn.innerHTML = '<i class="fas fa-filter me-2"></i> Show Filters';
            } else if (filterSection) {
                filterSection.classList.add('show');
                toggleBtn.innerHTML = '<i class="fas fa-times me-2"></i> Hide Filters';
            }
        }
        
        // Export functions
        function exportToCSV() {
            let params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }
        
        function exportToPDF() {
            let params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
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