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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Dashboard | La Carlota City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    <link rel="stylesheet" href="css/index.css">
    
    <style>
        
     
       
        
     
        
            .action-icon {
                background-color: #e8f5e9;
                color: #2b9348;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                
            }

              /* New chart container styles */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.chart-header {
    margin-bottom: 15px;
}

.chart-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
}

.chart-wrapper {
    position: relative;
    height: 250px;
}


      
    </style>
    
</head>
<body>
    <div class="container">
         
        
        <div class="sidebar-container">
            <?php include'templates/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
           
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                    <h1 class="h3 mt-4 fw-bold">Dashboard</h1>
                    <p class="mb-5">Welcome back, Administrator!</p>
                </div>

                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-paw"></i>
                        </div>
                    </div>
                    <?php
                    $animalCount = 0;
                    $result = $conn->query("SELECT COUNT(*) AS total FROM animals");
                    if ($result && $row = $result->fetch_assoc()) {
                        $animalCount = $row['total'];
                    }
                    ?>
                <div class="stat-body">
                    <h3 id="total-animals"><?= $animalCount ?></h3>
                    <p>Total Animals</p>
                </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <?php
                    $ownerCount = 0;
                    $result = $conn->query("SELECT COUNT(*) AS total FROM owners WHERE status = 'verified'");
                    if ($result && $row = $result->fetch_assoc()) {
                        $ownerCount = $row['total'];
                    }
                    ?>
                    <div class="stat-body">
                    <h3 id="total-owners"><?= $ownerCount ?></h3>
                    <p>Registered Owners</p>
                </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <?php
                    $vaccinatedCount = 0;
                    $result = $conn->query("SELECT COUNT(*) AS total FROM animals WHERE vaccination_status = 'vaccinated'");
                    if ($result && $row = $result->fetch_assoc()) {
                        $vaccinatedCount = $row['total'];
                    }
                    ?>
                    <div class="stat-body">
                        <h3 id="total-vaccinated"><?= $vaccinatedCount ?></h3>
                        <p>Vaccinated Animals</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                    </div>
                    <?php
                    $scanCount = 0;
                    $result = $conn->query("SELECT COUNT(*) AS total FROM scan_history");
                    if ($result && $row = $result->fetch_assoc()) {
                        $scanCount = $row['total'];
                    }
                    ?>
                    <div class="stat-body">
                        <h3 id="total-scans"><?= $scanCount ?></h3>
                        <p>Total Lost Animal Scans</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Animal Registration Trends</h3>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-action active" data-period="week">Weekly</button>
                            <button class="chart-action" data-period="month">Monthly</button>
                            <button class="chart-action" data-period="year">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="registration-chart" style="width: 100%; height: 300px;"></canvas>

                    </div>
                </div>

                <?php
                // Get species distribution
                $speciesData = [];
                $result = $conn->query("SELECT species, COUNT(*) as count FROM animals GROUP BY species");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $speciesData[] = [
                            'species' => $row['species'],
                            'count' => $row['count']
                        ];
                    }
                }

                // Get Domestic and Livestock count from animal_class
                $animalClassData = ['Domestic' => 0, 'Livestock' => 0];
                $classResult = $conn->query("SELECT animal_class, COUNT(*) as count FROM animals WHERE animal_class IN ('Domestic', 'Livestock') GROUP BY animal_class");
                if ($classResult) {
                    while ($row = $classResult->fetch_assoc()) {
                        $animalClassData[$row['animal_class']] = $row['count'];
                    }
                }

                $totalAnimals = array_sum($animalClassData);
                $domesticPercentage = ($totalAnimals > 0) ? round(($animalClassData['Domestic'] / $totalAnimals) * 100) : 0;
                $livestockPercentage = ($totalAnimals > 0) ? round(($animalClassData['Livestock'] / $totalAnimals) * 100) : 0;
                ?>
                

                        <div class="chart-card">
                        <div class="chart-header text-center">
                <div class="chart-title">
                    <h3>Animal Distribution</h3>
                    <p>
                        Domestic: <?= $animalClassData['Domestic'] ?> (<?= $domesticPercentage ?>%) &nbsp; | &nbsp;
                        Livestock: <?= $animalClassData['Livestock'] ?> (<?= $livestockPercentage ?>%)
                    </p>
                    <p>Total Animals: <?= $totalAnimals ?></p>
                </div>
            </div>

                <div class="chart-body">
                    <canvas id="animal-distribution-chart" style="max-width: 100%; height: auto;"></canvas>
                </div>
            </div>
            </div>

            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const speciesLabels = <?= json_encode(array_column($speciesData, 'species')) ?>;
                const speciesCounts = <?= json_encode(array_column($speciesData, 'count')) ?>;

                const ctx = document.getElementById('animal-distribution-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: speciesLabels,
                        datasets: [{
                            data: speciesCounts,
                            backgroundColor: [
                                '#2b9348', '#55a630', '#80b918', '#aacc00', '#bfd200', '#d4d700', '#dddf00'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            </script>
            <?php
                // Get current active lost animals
                $activeLostSql = "SELECT COUNT(*) as count FROM animals WHERE status = 'lost'";
                $activeLostResult = $conn->query($activeLostSql);
                $activeLostCount = $activeLostResult ? $activeLostResult->fetch_assoc()['count'] : 0;

                // Get total found animals
                $totalFoundSql = "SELECT COUNT(*) as count FROM animals WHERE status = 'found'";
                $totalFoundResult = $conn->query($totalFoundSql);
                $totalFoundCount = $totalFoundResult ? $totalFoundResult->fetch_assoc()['count'] : 0;

                // Get vaccination coverage data
                $vaccinatedCount = 0;
                $notVaccinatedCount = 0;
                $vaccinationSql = "SELECT vaccination_status, COUNT(*) as count FROM animals GROUP BY vaccination_status";
                $vaccinationResult = $conn->query($vaccinationSql);
                if ($vaccinationResult) {
                    while ($row = $vaccinationResult->fetch_assoc()) {
                        if ($row['vaccination_status'] === 'Vaccinated') {
                            $vaccinatedCount = $row['count'];
                        } else {
                            $notVaccinatedCount = $row['count'];
                        }
                    }
                }
                $totalVaccination = $vaccinatedCount + $notVaccinatedCount;
                $vaccinatedPercentage = $totalVaccination > 0 ? round(($vaccinatedCount / $totalVaccination) * 100) : 0;
                $notVaccinatedPercentage = $totalVaccination > 0 ? round(($notVaccinatedCount / $totalVaccination) * 100) : 0;
                ?>
                <!-- New Charts Section -->
                <div class="charts-grid">
                    <!-- Lost and Found Bar Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h4>Lost & Found Animals</h4>
                            <p>Currently Lost: <?= $activeLostCount ?>  </p>
                            <p>Total Found: <?= $totalFoundCount ?> </p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="lost-found-chart"></canvas>
                        </div>
                    </div>

                    <!-- Vaccination Coverage Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h4>Vaccination Coverage</h4>
                            <p><?= $vaccinatedCount ?> vaccinated (<?= $vaccinatedPercentage ?>%) | <?= $notVaccinatedCount ?> not vaccinated (<?= $notVaccinatedPercentage ?>%)</p>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="vaccination-chart"></canvas>
                        </div>
                    </div>
                </div>
                <script>
                // Lost and Found Bar Chart
                const lostFoundCtx = document.getElementById('lost-found-chart').getContext('2d');
                const lostFoundChart = new Chart(lostFoundCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Active Lost', 'Total Found'],
                        datasets: [{
                            label: 'Number of Animals',
                            data: [<?= $activeLostCount ?>, <?= $totalFoundCount ?>],
                            backgroundColor: ['#e74c3c', '#27ae60'],
                            borderColor: ['#c0392b', '#229954'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Animals'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Status'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // Vaccination Coverage Chart
                const vaccinationCtx = document.getElementById('vaccination-chart').getContext('2d');
                const vaccinationChart = new Chart(vaccinationCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Vaccinated', 'Not Vaccinated'],
                        datasets: [{
                            data: [<?= $vaccinatedCount ?>, <?= $notVaccinatedCount ?>],
                            backgroundColor: ['#27ae60', '#e74c3c'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                </script>




            <!-- Tables Section -->
            <div class="tables-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Recent Scans and Reports</h3>
                    </div>
                    <div class="table-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="recent-activities">
                                <?php
                                $activities = [];


                                // Fetch recent scans (last 2 days)
                                $recentScanSql = "SELECT s.animal_id, s.scan_time, a.animal_name 
                                    FROM scan_history s 
                                    LEFT JOIN animals a ON s.animal_id = a.animal_id 
                                    WHERE s.scan_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                                    ORDER BY s.scan_time DESC 
                                    ";
                                $recentScanResult = $conn->query($recentScanSql);
                                while ($row = $recentScanResult->fetch_assoc()) {
                                    $activities[] = [
                                        'action' => 'Scan',
                                        'badge' => 'warning',
                                        'description' => 'A lost Animal Named ' . htmlspecialchars($row['animal_name'] ?? 'Unknown') . ' was scanned',
                                        'date' => $row['scan_time']
                                    ];
                                }

                                // Fetch recent reports (last 7 days)
                                $recentReportSql = "SELECT f.reporter_name, f.animal_id, f.report_time, a.animal_name 
                                    FROM found_reports f 
                                    LEFT JOIN animals a ON f.animal_id = a.animal_id 
                                    WHERE f.report_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                                    ORDER BY f.report_time DESC 
                                    ";
                                $recentReportResult = $conn->query($recentReportSql);
                                while ($row = $recentReportResult->fetch_assoc()) {
                                    $activities[] = [
                                        'action' => 'Report',
                                        'badge' => 'danger',
                                        'description' => htmlspecialchars($row['reporter_name']) . ' Reported a Lost Animal Named ' . htmlspecialchars($row['animal_name'] ?? 'Unknown'),
                                        'date' => $row['report_time']
                                    ];
                                }

                                // Sort all activities by date descending
                                usort($activities, function($a, $b) {
                                    return strtotime($b['date']) - strtotime($a['date']);
                                });

                                // Output the sorted activities (limit to 10 for display)
                                $displayCount = 0;
                                foreach ($activities as $activity) {
                                    if ($displayCount++ >= 30) break;
                                    echo '<tr>
                                        <td><span class="table-badge badge-' . $activity['badge'] . '">' . $activity['action'] . '</span></td>
                                        <td>' . $activity['description'] . '</td>
                                        <td>' . date('M d, Y h:i A', strtotime($activity['date'])) . '</td>
                                    </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="table-card">
                    <div class="table-header">
                        <h3>Lost And Found Animals</h3>
                    </div>
                    <div class="table-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th></th>
                                    <th>Species</th>
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody id="lost-found-animals">
                                <?php
                                // Fetch lost/found/returned animals from the animals table
                                $lostFoundSql = "SELECT animal_name, species, status, date_registered, animal_image 
                                                 FROM animals 
                                                 WHERE status IN ('lost','found') 
                                                   AND date_registered 
                                                 ORDER BY date_registered DESC 
                                                 ";
                                $lostFoundResult = $conn->query($lostFoundSql);

                                while ($row = $lostFoundResult->fetch_assoc()) {
                                    // Choose badge color based on status
                                    $badge = 'dark';

                                    if ($row['status'] === 'lost') $badge = 'danger';
                                    if ($row['status'] === 'found') $badge = 'warning';
                                   

                                    // Use image if available, otherwise placeholder
                                    $imgSrc = !empty($row['animal_image']) ? htmlspecialchars($row['animal_image']) : '/api/placeholder/32/32';

                                    echo '<tr>
                                        <td>
                                            <img src="' . $imgSrc . '" alt="Animal" class="avatar">
                                        
                                        </td>
                                        <td>' . htmlspecialchars($row['animal_name']) . '</td>
                                        <td>' . htmlspecialchars($row['species']) . '</td>
                                        <td><span class="table-badge badge-' . $badge . '">' . htmlspecialchars($row['status']) . '</span></td>
                                        
                                    </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
<?php
// WEEKLY: Last 7 days, grouped by day
$weekLabels = [];
$weeklyCounts = [];
$weekSql = "SELECT DATE(date_registered) as reg_date, COUNT(*) as total 
            FROM animals 
            WHERE date_registered >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY reg_date
            ORDER BY reg_date";
$weekResult = $conn->query($weekSql);
$weekData = [];
if ($weekResult) {
    while ($row = $weekResult->fetch_assoc()) {
        $weekData[$row['reg_date']] = (int)$row['total'];
    }
}
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekLabels[] = date('D', strtotime($date));
    $weeklyCounts[] = isset($weekData[$date]) ? $weekData[$date] : 0;
}

// MONTHLY: Current month, grouped by week
$monthLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
$monthlyCounts = [0, 0, 0, 0, 0];
$monthSql = "SELECT WEEK(date_registered, 1) - WEEK(DATE_SUB(date_registered, INTERVAL DAYOFMONTH(date_registered)-1 DAY), 1) + 1 as week_of_month, COUNT(*) as total
             FROM animals
             WHERE YEAR(date_registered) = YEAR(CURDATE()) AND MONTH(date_registered) = MONTH(CURDATE())
             GROUP BY week_of_month";
$monthResult = $conn->query($monthSql);
if ($monthResult) {
    while ($row = $monthResult->fetch_assoc()) {
        $idx = (int)$row['week_of_month'] - 1;
        if ($idx >= 0 && $idx < 5) {
            $monthlyCounts[$idx] = (int)$row['total'];
        }
    }
}

// YEARLY: Current year, grouped by month
$yearLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$yearlyCounts = array_fill(0, 12, 0);
$yearSql = "SELECT MONTH(date_registered) as reg_month, COUNT(*) as total
            FROM animals
            WHERE YEAR(date_registered) = YEAR(CURDATE())
            GROUP BY reg_month";
$yearResult = $conn->query($yearSql);
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $idx = (int)$row['reg_month'] - 1;
        $yearlyCounts[$idx] = (int)$row['total'];
    }
}
?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Toggle Sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

   

        // Registration Chart
        
    // Registration trend data from PHP
    const weekLabels = <?= json_encode($weekLabels) ?>;
    const weeklyCounts = <?= json_encode($weeklyCounts) ?>;
    const monthLabels = <?= json_encode($monthLabels) ?>;
    const monthlyCounts = <?= json_encode($monthlyCounts) ?>;
    const yearLabels = <?= json_encode($yearLabels) ?>;
    const yearlyCounts = <?= json_encode($yearlyCounts) ?>;

    // Registration Chart
    const registrationCtx = document.getElementById('registration-chart').getContext('2d');
    const registrationChart = new Chart(registrationCtx, {
        type: 'line',
        data: {
            labels: weekLabels,
            datasets: [{
                label: 'Animal Registrations',
                data: weeklyCounts,
                fill: true,
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderColor: '#4CAF50',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

        

        // Connect to Database (In production)
        // This would be replaced with real AJAX calls to your PHP backend
        // For demonstration, we're using mock data displayed above

        // In a real implementation, you would fetch data like this:
        
        function fetchDashboardData() {
            fetch('get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-animals').innerText = data.totalAnimals;
                    document.getElementById('total-owners').innerText = data.totalOwners;
                    document.getElementById('total-admins').innerText = data.totalAdmins;
                    document.getElementById('total-scans').innerText = data.totalScans;
                    
                    // Update charts with real data
                    registrationChart.data.datasets[0].data = data.registrationTrend;
                    registrationChart.update();
                    
                    distributionChart.data.datasets[0].data = [
                        data.animalDistribution.dogs,
                        data.animalDistribution.cats,
                        data.animalDistribution.birds,
                        data.animalDistribution.livestock,
                        data.animalDistribution.others
                    ];
                    distributionChart.update();
                    
                    // Update tables
                    updateActivitiesTable(data.recentActivities);
                    updateLostFoundTable(data.lostFoundAnimals);
                })
                .catch(error => console.error('Error fetching dashboard data:', error));
        }

        function updateActivitiesTable(activities) {
            const tbody = document.getElementById('recent-activities');
            tbody.innerHTML = '';
            
            activities.forEach(activity => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><span class="table-badge badge-${activity.badgeType}">${activity.action}</span></td>
                    <td>${activity.description}</td>
                    <td>${activity.date}</td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateLostFoundTable(animals) {
            const tbody = document.getElementById('lost-found-animals');
            tbody.innerHTML = '';
            
            animals.forEach(animal => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <img src="${animal.image}" alt="Animal" class="avatar">
                        ${animal.name}
                    </td>
                    <td>${animal.species}</td>
                    <td><span class="table-badge badge-${animal.statusBadge}">${animal.status}</span></td>
                    <td>${animal.lastUpdated}</td>
                `;
                tbody.appendChild(row);
            });
        }
        
        // Call on page load
        fetchDashboardData();
        
        // Refresh data every 5 minutes
        setInterval(fetchDashboardData, 300000);

        // Chart period switcher
        document.querySelectorAll('.chart-action').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.chart-action').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const period = this.getAttribute('data-period');
                if (period === 'week') {
                    registrationChart.data.labels = weekLabels;
                    registrationChart.data.datasets[0].data = weeklyCounts;
                } else if (period === 'month') {
                    registrationChart.data.labels = monthLabels;
                    registrationChart.data.datasets[0].data = monthlyCounts;
                } else if (period === 'year') {
                    registrationChart.data.labels = yearLabels;
                    registrationChart.data.datasets[0].data = yearlyCounts;
                }
                registrationChart.update();
            });
        });
        

       
    </script>
    
</body>
</html>
