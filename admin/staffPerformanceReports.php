
<?php
include '../db/conn.php';


// Get filter parameters
$staff_filter = isset($_GET['staff']) ? $_GET['staff'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get all staff members for filter dropdown (including admins who haven't performed actions yet)
$staff_query = "SELECT DISTINCT name FROM (
    SELECT CONCAT(a.first_name, ' ', a.last_name) as name, a.created_at 
    FROM admins a
    WHERE a.first_name IS NOT NULL AND a.first_name != ''
    
    UNION
    
    SELECT added_by as name, NULL as created_at 
    FROM (
        SELECT added_by FROM animals WHERE added_by IS NOT NULL AND added_by != ''
        UNION 
        SELECT added_by FROM animal_medication WHERE added_by IS NOT NULL AND added_by != ''
    ) AS staff_actions
) AS all_staff 
ORDER BY name";
$staff_result = $conn->query($staff_query);
$staff_members = [];
if ($staff_result) {
    while ($row = $staff_result->fetch_assoc()) {
        if (!empty($row['name'])) {
            $staff_members[] = $row['name'];
        }
    }
}

// Get admin roles for filter
$roles_query = "SELECT DISTINCT role_name FROM admins ORDER BY role_name";
$roles_result = $conn->query($roles_query);
$roles = [];
if ($roles_result) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row['role_name'];
    }
}

// Build query for staff performance - Include all admins even with no activities
$query = "SELECT 
    CONCAT(a.first_name, ' ', a.last_name) as staff_member,
    COALESCE(animal_counts.animal_count, 0) as animals_registered,
    COALESCE(medication_counts.medication_count, 0) as medications_administered,
    COALESCE(GREATEST(animal_counts.latest_animal, medication_counts.latest_medication), NULL) as last_activity,
    COALESCE(a.role_name, 'Unknown') as role,
    a.created_at as registration_date
    FROM admins a
    LEFT JOIN (
        SELECT 
            added_by as staff_member,
            COUNT(*) as animal_count,
            MAX(date_registered) as latest_animal
        FROM animals 
        WHERE added_by IS NOT NULL AND added_by != ''
        " . (!empty($start_date) && !empty($end_date) ? 
            " AND DATE(date_registered) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'" : '') . "
        GROUP BY added_by
    ) AS animal_counts ON animal_counts.staff_member = CONCAT(a.first_name, ' ', a.last_name)
    LEFT JOIN (
        SELECT 
            added_by as staff_member,
            COUNT(*) as medication_count,
            MAX(created_at) as latest_medication
        FROM animal_medication 
        WHERE added_by IS NOT NULL AND added_by != ''
        " . (!empty($start_date) && !empty($end_date) ? 
            " AND DATE(created_at) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'" : '') . "
        GROUP BY added_by
    ) AS medication_counts ON medication_counts.staff_member = CONCAT(a.first_name, ' ', a.last_name)
    WHERE a.first_name IS NOT NULL AND a.first_name != ''";

$count_query = "SELECT COUNT(DISTINCT a.admin_id) as total 
FROM admins a
WHERE a.first_name IS NOT NULL AND a.first_name != ''";

if (!empty($staff_filter)) {
    $query .= " AND CONCAT(a.first_name, ' ', a.last_name) = '" . $conn->real_escape_string($staff_filter) . "'";
    $count_query .= " AND CONCAT(a.first_name, ' ', a.last_name) = '" . $conn->real_escape_string($staff_filter) . "'";
}

if (!empty($role_filter)) {
    $query .= " AND (a.role_name = '" . $conn->real_escape_string($role_filter) . "' OR (a.role_name IS NULL AND 'Unknown' = '" . $conn->real_escape_string($role_filter) . "'))";
    $count_query .= " AND (a.role_name = '" . $conn->real_escape_string($role_filter) . "' OR (a.role_name IS NULL AND 'Unknown' = '" . $conn->real_escape_string($role_filter) . "'))";
}

// Apply activity date filter to activity counts but still show all admins
$activity_date_condition = "";
if (!empty($start_date) && !empty($end_date)) {
    $activity_date_condition = " AND DATE(activity_date) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
}

// Add registration date filter
if (!empty($reg_start_date) && !empty($reg_end_date)) {
    $query .= " AND (a.created_at IS NULL OR DATE(a.created_at) BETWEEN '" . $conn->real_escape_string($reg_start_date) . "' AND '" . $conn->real_escape_string($reg_end_date) . "')";
    $count_query .= " AND (a.created_at IS NULL OR DATE(a.created_at) BETWEEN '" . $conn->real_escape_string($reg_start_date) . "' AND '" . $conn->real_escape_string($reg_end_date) . "')";
}

if (!empty($search)) {
    $query .= " AND CONCAT(a.first_name, ' ', a.last_name) LIKE '%" . $conn->real_escape_string($search) . "%'";
    $count_query .= " AND CONCAT(a.first_name, ' ', a.last_name) LIKE '%" . $conn->real_escape_string($search) . "%'";
}

// Get total count
$total_count = 0;
$count_result = $conn->query($count_query);
if ($count_result) {
    $total_count = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_count / $limit);

// Add grouping and pagination
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'animals_registered';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$query .= " GROUP BY a.admin_id, a.first_name, a.last_name, a.role_name, a.created_at ORDER BY $sort $order LIMIT $limit OFFSET $offset";

$result = $conn->query($query);
$staff_data = [];
$max_animals = 0;
$max_medications = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $staff_data[] = $row;
        if ($row['animals_registered'] > $max_animals) {
            $max_animals = $row['animals_registered'];
        }
        if ($row['medications_administered'] > $max_medications) {
            $max_medications = $row['medications_administered'];
        }
    }
}

// Get detailed activity for selected staff member with date filtering
$detailed_activities = [];
if (!empty($staff_filter)) {
    $detail_query = "SELECT 
        activity_type,
        description,
        activity_date,
        additional_info
        FROM (
            SELECT 
                'Animal Registration' as activity_type,
                a.animal_name as description,
                a.date_registered as activity_date,
                CONCAT('Species: ', a.species, ', Breed: ', COALESCE(a.breed, 'N/A')) as additional_info,
                a.added_by as staff_member
            FROM animals a
            WHERE a.added_by IS NOT NULL AND a.added_by != ''
            
            UNION ALL
            
            SELECT 
                'Medication Administered' as activity_type,
                COALESCE(m.name, 'Unknown Medication') as description,
                am.date_given as activity_date,
                CONCAT('Animal ID: ', am.animal_id, ', Dosage: ', COALESCE(am.dosage, 'N/A')) as additional_info,
                am.added_by as staff_member
            FROM animal_medication am
            LEFT JOIN medication m ON am.med_id = m.med_id
            WHERE am.added_by IS NOT NULL AND am.added_by != ''
        ) AS activities
        WHERE staff_member = '" . $conn->real_escape_string($staff_filter) . "'";
    
    // Apply date filter only to the detailed activities
    if (!empty($start_date) && !empty($end_date)) {
        $detail_query .= " AND DATE(activity_date) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
    }
    
    $detail_query .= " ORDER BY activity_date DESC";
    $detail_result = $conn->query($detail_query);
    
    if ($detail_result) {
        while ($row = $detail_result->fetch_assoc()) {
            $detailed_activities[] = $row;
        }
    }
}

// Get summary statistics
$summary_query = "SELECT 
    COUNT(DISTINCT staff_member) as total_staff,
    COALESCE(SUM(animals_registered), 0) as total_animals,
    COALESCE(SUM(medications_administered), 0) as total_medications
    FROM (
        SELECT 
            staff_member,
            COUNT(animal_id) as animals_registered,
            COUNT(medication_id) as medications_administered
        FROM (
            SELECT added_by as staff_member, animal_id, NULL as medication_id FROM animals WHERE added_by IS NOT NULL AND added_by != ''
            UNION ALL
            SELECT added_by as staff_member, NULL as animal_id, animed_id as medication_id FROM animal_medication WHERE added_by IS NOT NULL AND added_by != ''
        ) AS activities
        LEFT JOIN admins a ON activities.staff_member = CONCAT(a.first_name, ' ', a.last_name)
        WHERE 1=1";
        
if (!empty($role_filter)) {
    $summary_query .= " AND (a.role_name = '" . $conn->real_escape_string($role_filter) . "' OR (a.role_name IS NULL AND 'Unknown' = '" . $conn->real_escape_string($role_filter) . "'))";
}

if (!empty($start_date) && !empty($end_date)) {
    $summary_query .= " AND DATE(activity_date) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
}

$summary_query .= " GROUP BY staff_member) AS staff_stats";

$summary_result = $conn->query($summary_query);
$summary = ['total_staff' => 0, 'total_animals' => 0, 'total_medications' => 0];
if ($summary_result) {
    $summary = $summary_result->fetch_assoc();
}

// Get role distribution for summary
$role_summary_query = "SELECT 
    COALESCE(a.role_name, 'Unknown') as role,
    COUNT(DISTINCT activities.staff_member) as staff_count,
    COALESCE(SUM(CASE WHEN animal_id IS NOT NULL THEN 1 ELSE 0 END), 0) as animals_registered,
    COALESCE(SUM(CASE WHEN medication_id IS NOT NULL THEN 1 ELSE 0 END), 0) as medications_administered
    FROM (
        SELECT added_by as staff_member, animal_id, NULL as medication_id FROM animals WHERE added_by IS NOT NULL AND added_by != ''
        UNION ALL
        SELECT added_by as staff_member, NULL as animal_id, animed_id as medication_id FROM animal_medication WHERE added_by IS NOT NULL AND added_by != ''
    ) AS activities
    LEFT JOIN admins a ON activities.staff_member = CONCAT(a.first_name, ' ', a.last_name)
    WHERE 1=1";
    
if (!empty($start_date) && !empty($end_date)) {
    $role_summary_query .= " AND DATE(activity_date) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
}

$role_summary_query .= " GROUP BY COALESCE(a.role_name, 'Unknown')";
$role_summary_result = $conn->query($role_summary_query);
$role_summary = [];
if ($role_summary_result) {
    while ($row = $role_summary_result->fetch_assoc()) {
        $role_summary[$row['role']] = $row;
    }
}

// Handle export functionality
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build export query (without pagination)
    $export_query = "SELECT 
                     staff_member,
                     COUNT(animal_id) as animals_registered,
                     COUNT(medication_id) as medications_administered,
                     MAX(last_activity) as last_activity,
                     COALESCE(a.role_name, 'Unknown') as role
                     FROM (
                         SELECT 
                             added_by as staff_member,
                             animal_id,
                             NULL as medication_id,
                             date_registered as activity_date,
                             date_registered as last_activity
                         FROM animals 
                         WHERE added_by IS NOT NULL AND added_by != ''
                         
                         UNION ALL
                         
                         SELECT 
                             added_by as staff_member,
                             NULL as animal_id,
                             animed_id as medication_id,
                             date_given as activity_date,
                             created_at as last_activity
                         FROM animal_medication 
                         WHERE added_by IS NOT NULL AND added_by != ''
                     ) AS activities
                     LEFT JOIN admins a ON activities.staff_member = CONCAT(a.first_name, ' ', a.last_name)
                     WHERE 1=1";
    
    if (!empty($staff_filter)) {
        $export_query .= " AND staff_member = '" . $conn->real_escape_string($staff_filter) . "'";
    }
    if (!empty($role_filter)) {
        $export_query .= " AND (a.role_name = '" . $conn->real_escape_string($role_filter) . "' OR (a.role_name IS NULL AND 'Unknown' = '" . $conn->real_escape_string($role_filter) . "'))";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $export_query .= " AND DATE(activity_date) BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
    }
    if (!empty($search)) {
        $export_query .= " AND staff_member LIKE '%" . $conn->real_escape_string($search) . "%'";
    }
    
    $export_query .= " GROUP BY staff_member ORDER BY animals_registered DESC";
    $export_result = $conn->query($export_query);
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="staff_performance_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add header information
        fputcsv($output, ['Staff Performance Report - Generated on ' . date('F d, Y H:i:s')]);
        fputcsv($output, ['Total Records: ' . $export_result->num_rows]);
        fputcsv($output, []); // Empty row
        
        // Add column headers
        fputcsv($output, ['Staff Member', 'Role', 'Animals Registered', 'Medications Administered', 'Last Activity']);
        
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['staff_member'],
                $row['role'],
                $row['animals_registered'],
                $row['medications_administered'],
                $row['last_activity'] ? date('M d, Y H:i:s', strtotime($row['last_activity'])) : 'N/A'
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
            <title>Staff Performance Report</title>
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
                <h1>Staff Performance Report</h1>
                <div class="report-info">
                    Generated on: <?php echo date('F d, Y H:i:s'); ?><br>
                    Total Records: <?php echo $export_result->num_rows; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Animals Registered</th>
                            <th>Medications Administered</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $export_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['staff_member']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo $row['animals_registered']; ?></td>
                            <td><?php echo $row['medications_administered']; ?></td>
                            <td><?php echo $row['last_activity'] ? date('M d, Y H:i:s', strtotime($row['last_activity'])) : 'N/A'; ?></td>
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
                        filename: 'staff_performance_<?php echo date('Y-m-d_H-i-s'); ?>.pdf',
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
    <title>Staff Performance Report | La Carlota City Veterinary Office</title>
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
                    
                    
                    <div class="header-title">
                        <h1 class="h3 mt-4 fw-bold">Staff Performance Report</h1>
                        <p class="mb-5">Track animal registrations and medication administration by staff members</p>
                        
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
                        <a class="nav-link" href="lostAndFoundReports.php">
                            <i class="fas fa-search me-2"></i>Lost & Found
                        </a>
                        <a class="nav-link" href="ownerReports.php">
                            <i class="fas fa-user-circle me-2"></i>Owners
                        </a>
                        <a class="nav-link active" href="staffPerformanceReports.php">
                            <i class="fas fa-chart-bar me-2"></i>Staff Performance
                        </a>
                    </nav>
                </div>
                
                <!-- Combined Summary Statistics -->
                <div class="row mb-4">
                    <!-- Role Summary Cards -->
                    <?php if (!empty($role_summary)): ?>
                        <?php foreach ($role_summary as $role => $data): ?>
                        <div class="col">
                            <div class="card stat-card h-80">
                                <div class="stat-icon">
                                    <i class="fas <?php echo $role == 'Veterinarian' ? 'fa-user-md' : 'fa-user-tie' ?>"></i>
                                </div>
                                <div class="stat-number"><?php echo $data['staff_count']; ?></div>
                                <div class="stat-label"><?php echo $role; ?></div>
                                <div class="stat-details">
                                    <span><i class="fas fa-paw me-1"></i><?php echo $data['animals_registered']; ?></span>
                                    <span><i class="fas fa-syringe ms-2 me-1"></i><?php echo $data['medications_administered']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Summary Stats Cards -->
                 
                    <div class="col">
                        <div class="card stat-card h-80">
                            <div class="stat-icon">
                                <i class="fas fa-paw"></i>
                            </div>
                            <div class="stat-number"><?php echo $summary['total_animals']; ?></div>
                            <div class="stat-label">Animals Registered</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card stat-card h-80">
                            <div class="stat-icon">
                                <i class="fas fa-syringe"></i>
                            </div>
                            <div class="stat-number"><?php echo $summary['total_medications']; ?></div>
                            <div class="stat-label">Medications</div>
                        </div>
                    </div>
                </div>

                <!-- Staff Performance Table -->
                <div class="card">
                    
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: white;"><i class="fas fa-table me-2" style="color: white;"></i>Staff Performance</h5>
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
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'staff_member', 'order' => $sort == 'staff_member' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Staff Member
                                                <span class="sort-icon"><?php echo $sort == 'staff_member' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>Role</th>
                                        <th>
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'animals_registered', 'order' => $sort == 'animals_registered' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Animals Registered
                                                <span class="sort-icon"><?php echo $sort == 'animals_registered' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'medications_administered', 'order' => $sort == 'medications_administered' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Medications Administered
                                                <span class="sort-icon"><?php echo $sort == 'medications_administered' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>
                                            <a class="sortable-header" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'last_activity', 'order' => $sort == 'last_activity' && $order == 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                                Last Activity
                                                <span class="sort-icon"><?php echo $sort == 'last_activity' ? ($order == 'ASC' ? '↑' : '↓') : ''; ?></span>
                                            </a>
                                        </th>
                                        <th>Performance Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($staff_data)): ?>
                                        <?php foreach ($staff_data as $row): 
                                            $animal_percent = $max_animals > 0 ? ($row['animals_registered'] / $max_animals) * 100 : 0;
                                            $medication_percent = $max_medications > 0 ? ($row['medications_administered'] / $max_medications) * 100 : 0;
                                            $performance_score = round(($animal_percent + $medication_percent) / 2);
                                            $role_class = strtolower($row['role']);
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['staff_member']); ?></td>
                                                <td>
                                                    <?php if ($row['role'] == 'Veterinarian'): ?>
                                                        <span class="badge badge-role-veterinarian">Veterinarian</span>
                                                    <?php elseif ($row['role'] == 'Staff'): ?>
                                                        <span class="badge badge-role-staff">Staff</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-role-unknown"><?php echo $row['role']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo $row['animals_registered']; ?></span>
                                                        <div class="staff-performance-bar" style="width: 100px;">
                                                            <div class="performance-fill bg-success" style="width: <?php echo $animal_percent; ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo $row['medications_administered']; ?></span>
                                                        <div class="staff-performance-bar" style="width: 100px;">
                                                            <div class="performance-fill bg-info" style="width: <?php echo $medication_percent; ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $row['last_activity'] ? date('M d, Y', strtotime($row['last_activity'])) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress" style="width: 100px;">
                                                            <div class="progress-bar 
                                                                <?php echo $performance_score >= 70 ? 'bg-success' : ($performance_score >= 40 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                                role="progressbar" 
                                                                style="width: <?php echo $performance_score; ?>%" 
                                                                aria-valuenow="<?php echo $performance_score; ?>" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="ms-2"><?php echo $performance_score; ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No staff performance data found matching your criteria</td>
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
                
                <!-- Detailed Activities for Selected Staff -->
                <?php if (!empty($staff_filter) && !empty($detailed_activities)): ?>
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detailed Activities for <?php echo $staff_filter; ?></h5>
                        <span class="results-count"><?php echo count($detailed_activities); ?> activities</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detailed_activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <?php if ($activity['activity_type'] == 'Animal Registration'): ?>
                                                    <span class="badge badge-success activity-badge">Registration</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info activity-badge">Medication</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($activity['additional_info']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $role_filter == $role ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role); ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search staff by name">
                            </div>
                        </div>
                        <div class="modal-footer mt-4">
                            <a href="staffPerformanceReports.php" class="btn btn-outline-secondary me-auto">Reset All</a>
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
            const tables = document.querySelectorAll('.table');
            tables.forEach(table => {
                if (window.innerWidth < 768) {
                    table.classList.add('table-sm');
                } else {
                    table.classList.remove('table-sm');
                }
            });
        }
        
        window.addEventListener('resize', handleTableResponsive);
        handleTableResponsive();
    </script>
</body>
</html>