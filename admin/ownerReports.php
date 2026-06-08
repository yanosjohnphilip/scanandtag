<?php
include '../db/conn.php';
$admin_id = $_SESSION['admin_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$barangay_filter = isset($_GET['barangay']) ? $_GET['barangay'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$query = "SELECT o.*, COUNT(a.animal_id) as animal_count 
          FROM owners o 
          LEFT JOIN animals a ON o.owner_id = a.owner_id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM owners o WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND o.status = '" . $conn->real_escape_string($status_filter) . "'";
    $count_query .= " AND o.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($barangay_filter)) {
    $query .= " AND o.barangay = '" . $conn->real_escape_string($barangay_filter) . "'";
    $count_query .= " AND o.barangay = '" . $conn->real_escape_string($barangay_filter) . "'";
}

if (!empty($start_date)) {
    $query .= " AND DATE(o.verified_at) >= '" . $conn->real_escape_string($start_date) . "'";
    $count_query .= " AND DATE(o.verified_at) >= '" . $conn->real_escape_string($start_date) . "'";
}

if (!empty($end_date)) {
    $query .= " AND DATE(o.verified_at) <= '" . $conn->real_escape_string($end_date) . "'";
    $count_query .= " AND DATE(o.verified_at) <= '" . $conn->real_escape_string($end_date) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (o.first_name LIKE '%$search_term%' OR o.last_name LIKE '%$search_term%' OR o.email LIKE '%$search_term%' OR o.phone LIKE '%$search_term%')";
    $count_query .= " AND (o.first_name LIKE '%$search_term%' OR o.last_name LIKE '%$search_term%' OR o.email LIKE '%$search_term%' OR o.phone LIKE '%$search_term%')";
}

$query .= " GROUP BY o.owner_id ORDER BY o.verified_at DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="owner_reports_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Get all data for export (without pagination)
        $export_query = str_replace("LIMIT $limit OFFSET $offset", "", $query);
        $export_result = $conn->query($export_query);
        
        // Add header information
        fputcsv($output, ['Owner Reports - Generated on ' . date('F d, Y H:i:s')]);
        fputcsv($output, ['Total Records: ' . $export_result->num_rows]);
        fputcsv($output, []); // Empty row
        
        // Add column headers
        fputcsv($output, [
            
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'Barangay',
            'Status',
            'Animal Count',
            'Registration Date',
            'Verified At'
        ]);
        
        // Add data rows
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
               
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['address'],
                $row['barangay'],
                ucfirst($row['status']),
                $row['animal_count'],
                !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '',
                !empty($row['verified_at']) ? date('M d, Y H:i:s', strtotime($row['verified_at'])) : 'Not verified'
            ]);
        }
        
        fclose($output);
        exit();
        
    } elseif ($export_type === 'pdf') {
        // Get all data for export (without pagination)
        $export_query = str_replace("LIMIT $limit OFFSET $offset", "", $query);
        $export_result = $conn->query($export_query);
        
        // Output HTML that will be converted to PDF and downloaded
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Owner Reports</title>
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
                .status-verified { color: #155724; font-weight: bold; }
                .status-pending { color: #856404; font-weight: bold; }
                .status-rejected { color: #721c24; font-weight: bold; }
            </style>
        </head>
        <body>
            <div id="loading">
                <i class="fas fa-spinner fa-spin"></i> Generating PDF... Please wait.
            </div>
            
            <div id="content">
                <h1>Owner Reports</h1>
                <div class="report-info">
                    Generated on: <?php echo date('F d, Y H:i:s'); ?><br>
                    Total Records: <?php echo $total_records; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Animals</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $export_result->fetch_assoc()): ?>
                        <tr>
                           
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
                            <td><?php echo $row['animal_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
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
                        filename: 'owner_reports_<?php echo date('Y-m-d_H-i-s'); ?>.pdf',
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
    <title>Owner Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <link rel="stylesheet" href="css/reports.css">
    
</head>
<body>
    <div class="container ">
        <div >
            <?php include 'templates/sidebar.php'; ?>
        </div>
        <div class="main-content ">
        
            <div class="header">
                
                
                <div class="header-title">
                    <h1 class="h3 mt-4 fw-bold">Owner Reports</h1>
                    <p class="mb-5">Comprehensive owner registration and management reports</p>
                    
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
                        <a class="nav-link " href="animalReports.php">
                            <i class="fas fa-file-alt me-2"></i>Animals
                        </a>
                        <a class="nav-link" href="medicationReports.php">
                            <i class="fas fa-file-medical me-2"></i>Medication
                        </a>
                        <a class="nav-link" href="lostAndFoundReports.php">
                            <i class="fas fa-search me-2"></i>Lost & Found
                        </a>
                        <a class="nav-link active" href="ownerReports.php">
                            <i class="fas fa-user-circle me-2"></i>Owners
                        </a>
                        <a class="nav-link" href="staffPerformanceReports.php">
                            <i class="fas fa-chart-bar me-2"></i>Staff Performance
                        </a>
                    </nav>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: white;"><i class="fas fa-table me-2" style="color: white;"></i>Owner Records</h5>
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
                        <?php if ($result->num_rows > 0): ?>
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Animals</th>
                                        <th>Registration Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['animal_count']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['verified_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-users"></i>
                                <h3>No owners found</h3>
                                <p>No owners match your current filter criteria.</p>
                            </div>
                        <?php endif; ?>
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
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="barangay" class="form-label">Barangay</label>
                                <select class="form-select" id="barangay" name="barangay">
                                    <option value="">All Barangays</option>
                                    <?php
                                    $barangays_query = "SELECT DISTINCT barangay FROM owners WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay";
                                    $barangays_result = $conn->query($barangays_query);
                                    while ($row = $barangays_result->fetch_assoc()) {
                                        $selected = (isset($_GET['barangay']) && $_GET['barangay'] === $row['barangay']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['barangay']) . "' $selected>" . htmlspecialchars($row['barangay']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="col-12">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or phone">
                            </div>
                        </div>
                        <div class="modal-footer mt-4">
                            <a href="ownerReports.php" class="btn btn-secondary">Reset</a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportToCSV() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            
            // Redirect to export handler
            window.location.href = '?' + params.toString();
        }
        
        function exportToPDF() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            
            // Open in new tab to keep current page intact
            window.open('?' + params.toString(), '_blank');
        }
    </script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
