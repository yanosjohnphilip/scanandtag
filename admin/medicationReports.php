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
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$query = "SELECT m.*, 
          COUNT(am.med_id) as usage_count,
          GROUP_CONCAT(DISTINCT a.species) as species_used_on
          FROM medication m 
          LEFT JOIN animal_medication am ON m.med_id = am.med_id 
          LEFT JOIN animals a ON am.animal_id = a.animal_id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total 
                FROM medication m 
                WHERE 1=1";

if (!empty($type_filter)) {
    $query .= " AND m.type = '$type_filter'";
    $count_query .= " AND m.type = '$type_filter'";
}

if (!empty($search)) {
    $query .= " AND (m.name LIKE '%$search%' OR m.description LIKE '%$search%')";
    $count_query .= " AND (m.name LIKE '%$search%' OR m.description LIKE '%$search%')";
}

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(m.created_at) BETWEEN '$start_date' AND '$end_date'";
    $count_query .= " AND DATE(m.created_at) BETWEEN '$start_date' AND '$end_date'";
}

// Get total count
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Add sorting and pagination
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$query .= " GROUP BY m.med_id ORDER BY $sort $order LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Get summary statistics
$usage_by_type_query = "SELECT type, COUNT(*) as med_count, 
                        (SELECT COUNT(*) FROM animal_medication am WHERE am.med_id = m.med_id) as usage_count
                        FROM medication m 
                        GROUP BY type";
$usage_by_type_result = $conn->query($usage_by_type_query);
$usage_by_type_data = [];
while ($row = $usage_by_type_result->fetch_assoc()) {
    $usage_by_type_data[] = $row;
}

// Get top used medications
$top_used_query = "SELECT m.name, m.type, COUNT(am.animed_id) as usage_count
                   FROM medication m 
                   LEFT JOIN animal_medication am ON m.med_id = am.med_id 
                   GROUP BY m.med_id 
                   ORDER BY usage_count DESC 
                   LIMIT 10";
$top_used_result = $conn->query($top_used_query);
$top_used_data = [];
while ($row = $top_used_result->fetch_assoc()) {
    $top_used_data[] = $row;
}

// Get total usage count
$total_usage_query = "SELECT COUNT(*) as total_usage FROM animal_medication";
$total_usage_result = $conn->query($total_usage_query);
$total_usage = $total_usage_result->fetch_assoc()['total_usage'];

// Get medication count
$medication_count_query = "SELECT COUNT(*) as med_count FROM medication";
$medication_count_result = $conn->query($medication_count_query);
$medication_count = $medication_count_result->fetch_assoc()['med_count'];

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build export query (without pagination)
    $export_query = "SELECT m.*, 
                     COUNT(am.med_id) as usage_count,
                     GROUP_CONCAT(DISTINCT a.species) as species_used_on
                     FROM medication m 
                     LEFT JOIN animal_medication am ON m.med_id = am.med_id 
                     LEFT JOIN animals a ON am.animal_id = a.animal_id 
                     WHERE 1=1";
    
    if (!empty($type_filter)) {
        $export_query .= " AND m.type = '$type_filter'";
    }
    if (!empty($search)) {
        $export_query .= " AND (m.name LIKE '%$search%' OR m.description LIKE '%$search%')";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $export_query .= " AND DATE(m.created_at) BETWEEN '$start_date' AND '$end_date'";
    }
    
    $export_query .= " GROUP BY m.med_id ORDER BY m.created_at DESC";
    $export_result = $conn->query($export_query);
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="medication_reports_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header information
        fputcsv($output, ['Medication Reports - Generated on ' . date('F d, Y H:i:s')]);
        fputcsv($output, ['Total Records: ' . $export_result->num_rows]);
        fputcsv($output, []); // Empty row
        
        // Add column headers
        fputcsv($output, ['Medication', 'Type', 'Description', 'Times Used', 'Used On Species', 'Date Added', 'Added By']);
        
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['type'],
                $row['description'],
                $row['usage_count'],
                $row['species_used_on'] ? implode(', ', array_unique(explode(',', $row['species_used_on']))) : 'Not used yet',
                date('M d, Y', strtotime($row['created_at'])),
                $row['added_by']
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
            <title>Medication Reports</title>
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
                <h1>Medication Reports</h1>
                <div class="report-info">
                    Generated on: <?php echo date('F d, Y H:i:s'); ?><br>
                    Total Records: <?php echo $export_result->num_rows; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Times Used</th>
                            <th>Used On</th>
                            <th>Date Added</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $export_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo $row['usage_count']; ?></td>
                            <td><?php echo $row['species_used_on'] ? implode(', ', array_unique(explode(',', $row['species_used_on']))) : 'Not used yet'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($row['added_by']); ?></td>
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
                        filename: 'medication_reports_<?php echo date('Y-m-d_H-i-s'); ?>.pdf',
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
    <title>Medication Reports | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <h1 class="h3 mt-4 fw-bold">Medication Reports</h1>
                            <p class="mb-5">Track medication usage and inventory</p>
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
                        <a class="nav-link active" href="medicationReports.php">
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
                    
                    <!-- Data Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" style="color: white;">
                                <i class="fas fa-table me-2" style="color: white;"></i>Medication Records
                            </h5>
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
                                                <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => $sort == 'name' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                    Medication
                                                    <span class="sort-icon"><?php echo $sort == 'name' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                                </a>
                                            </th>
                                            <th>
                                                <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'type', 'order' => $sort == 'type' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                    Type
                                                    <span class="sort-icon"><?php echo $sort == 'type' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                                </a>
                                            </th>
                                            <th>Description</th>
                                            <th>
                                                <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'usage_count', 'order' => $sort == 'usage_count' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                    Times Used
                                                    <span class="sort-icon"><?php echo $sort == 'usage_count' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                                </a>
                                            </th>
                                            <th>Used On</th>
                                            <th>
                                                <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $sort == 'created_at' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                    Date Added
                                                    <span class="sort-icon"><?php echo $sort == 'created_at' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                                </a>
                                            </th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td>
                                                        <?php if ($row['type'] == 'Vaccination'): ?>
                                                            <span class="badge badge-vaccination">Vaccination</span>
                                                        <?php elseif ($row['type'] == 'Vitamins'): ?>
                                                            <span class="badge badge-vitamins">Vitamins</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-deworming">Deworming</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                    <td><?php echo $row['usage_count']; ?></td>
                                                    <td><?php echo $row['species_used_on'] ? implode(', ', array_unique(explode(',', $row['species_used_on']))) : 'Not used yet'; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['added_by']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">No medications found matching your criteria</td>
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
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Medication Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    <option value="Vaccination" <?php echo $type_filter == 'Vaccination' ? 'selected' : ''; ?>>Vaccination</option>
                                    <option value="Vitamins" <?php echo $type_filter == 'Vitamins' ? 'selected' : ''; ?>>Vitamins</option>
                                    <option value="Deworming" <?php echo $type_filter == 'Deworming' ? 'selected' : ''; ?>>Deworming</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by medication name or description">
                            </div>
                        
                        </div>
                        <div class="modal-footer mt-4">
                            <a href="medicationReports.php" class="btn btn-outline-secondary me-auto">Reset All</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }
        
        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.open('?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>