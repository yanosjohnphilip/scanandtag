<?php
include '../db/conn.php';

// Fetch all animals with additional details (only from verified owners)
$result = $conn->query("SELECT a.animal_id, a.animal_name, a.species, a.breed, a.birthdate, a.animal_class, a.date_registered, a.animal_image, o.first_name, o.last_name 
                        FROM animals a 
                        JOIN owners o ON a.owner_id = o.owner_id
                        WHERE o.status = 'verified'");
$animals = [];
while ($row = $result->fetch_assoc()) {
    $animals[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Registered Animals | La Carlota City Veterinary Office</title>
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1.2rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: var(--secondary);
            border-bottom-width: 2px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
            color: var(--dark);
        }
        
        .table tr:hover {
            background-color: #f8fafc;
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.75rem;
        }
        
        .badge-cat, .badge-dog {
            background-color: #8b5cf6;
            color: white;
        }
        
        .badge-bird {
            background-color: #06b6d4;
            color: white;
        }
        
        .badge-livestock {
            background-color: #10b981;
            color: white;
        }
        
        .badge-exotic {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .btn-view {
            background-color: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }
        
        .btn-view:hover {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .animal-name {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .animal-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 1.25rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .stats-card {
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stats-label {
            color: var(--secondary);
            font-size: 0.875rem;
        }
        
        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .search-wrapper .form-control {
            padding-left: 2.5rem;
            height: 48px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--secondary);
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination {
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .page-link {
            border-radius: 8px;
            margin: 0 2px;
            color: var(--primary);
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.75rem;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .table-responsive {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 1.04rem;
            background: #faf8ff;
            border-radius: 14px;
            overflow-x: auto;
            margin-bottom: 0;
            box-shadow: 0 2px 6px #d1c5fa14;
        }
        
        @media (max-width: 768px) {
            .table th, .table td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .btn-action {
                padding: 0.35rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .animal-avatar {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
            
            .stats-value {
                font-size: 1.5rem;
            }
            
            .search-wrapper .form-control {
                height: 42px;
            }
            .card-animals{
                display: grid;
                grid-template-columns: repeat(2, 1fr); /* 2 columns */
                grid-template-rows: repeat(2, auto);
            }
        
      
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .header-search input {
                width: 180px;
            }
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
                    <h1 class="h3 mt-4 fw-bold">Animal Management</h1>
                    <p class="mb-5">Registered Animals Information</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
        
           
            <div class="card-animals row g-3 mb-4">
                <!-- Stats Cards -->
                <div class="col-md-3 col-sm-6  ">                  
                    <div class="card stats-card bg-white">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-paw"></i>
                        </div>
                        <div class="stats-value"><?= count($animals) ?></div>
                        <div class="stats-label">Total Animals</div>
                    </div>
                </div>
                
                <?php
                // Calculate species counts
                $speciesCounts = [];
                foreach ($animals as $animal) {
                    $species = $animal['species'];
                    if (!isset($speciesCounts[$species])) {
                        $speciesCounts[$species] = 0;
                    }
                    $speciesCounts[$species]++;
                }
                
                // Get the top 3 species
                arsort($speciesCounts);
                $topSpecies = array_slice($speciesCounts, 0, 3, true);
                
                $iconClasses = [
                    'Dog' => 'fa-dog text-primary',
                    'Cat' => 'fa-cat text-info',
                    'Bird' => 'fa-dove text-warning',
                    'Horse' => 'fa-horse text-success',
                    'Rabbit' => 'fa-rabbit text-danger',
                    'Goat' => 'fa-hippo text-muted',        
                    'Cow' => 'fa-cow text-brown',           
                    'Carabao' => 'fa-water text-dark',      
                    'default' => 'fa-paw text-secondary'
                ];
                
                $i = 0;
                foreach ($topSpecies as $species => $count):
                    $i++;
                    $iconClass = isset($iconClasses[$species]) ? $iconClasses[$species] : $iconClasses['default'];
                    $colors = ['primary', 'info', 'success'];
                    $color = $colors[($i - 1) % count($colors)];
                ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card stats-card bg-white">
                            <div class="stats-icon bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                <i class="fas <?= $iconClass ?>"></i>
                            </div>
                            <div class="stats-value"><?= $count ?></div>
                            <div class="stats-label"><?= htmlspecialchars($species) ?>s</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        
        
        <!-- Main Content Card -->
        <div class="card">
            <div class="card-header text-white">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Registered Animals</h5>
                    <button class="btn btn-sm btn-light" onclick="window.location.href='animal_Registration_Form.php'">
                        <i class="fas fa-plus me-1"></i> Add Animal
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filters -->
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search animals...">
                </div>
                
                <div class="filters">
                    <button class="filter-btn active">All</button>
                    <?php 
                    $uniqueSpecies = array_unique(array_column($animals, 'species'));
                    foreach ($uniqueSpecies as $species): 
                    ?>
                    <button class="filter-btn"><?= htmlspecialchars($species) ?></button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Animal</th>
                                <th>Species</th>
                                <th>Breed</th>
                                <th>Owner</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($animals as $index => $animal): 
                                $species = strtolower($animal['species']);
                                $badgeClass = 'badge-' . $species;
                                // Default to exotic if not found
                                if (!in_array($species, ['cat', 'dog', 'bird', 'livestock'])) {
                                    $badgeClass = 'badge-exotic';
                                }
                                
                                // Get first letter of animal name for avatar
                                $firstLetter = substr($animal['animal_name'], 0, 1);
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($animal['animal_image'])): ?>
                                            <img src="<?= htmlspecialchars($animal['animal_image']) ?>" alt="<?= htmlspecialchars($animal['animal_name']) ?>" class="animal-avatar">
                                        <?php else: ?>
                                            <div class="animal-avatar">
                                                <?= htmlspecialchars($firstLetter) ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="animal-name"><?= htmlspecialchars($animal['animal_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($animal['species']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($animal['breed']) ?></td>
                                <td><?= htmlspecialchars($animal['first_name'] . ' ' . $animal['last_name']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-action btn-view" 
                                                onclick="window.location.href='animal_details.php?id=<?= $animal['animal_id'] ?>'">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                        <button class="btn btn-action btn-outline-secondary" 
                                                onclick="window.location.href='edit_animal.php?id=<?= $animal['animal_id'] ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
            
            

<!-- Animal Details Modal -->
<div class="modal fade" id="animalDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Animal Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('tbody tr');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const species = this.textContent;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter table
            tableRows.forEach(row => {
                if (species === 'All') {
                    row.style.display = '';
                } else {
                    const rowSpecies = row.querySelector('td:nth-child(2)').textContent;
                    row.style.display = (rowSpecies === species) ? '' : 'none';
                }
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const animalName = row.querySelector('.animal-name').textContent.toLowerCase();
            const species = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const breed = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const owner = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            
            if (animalName.includes(searchTerm) || 
                species.includes(searchTerm) || 
                breed.includes(searchTerm) || 
                owner.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Reset active state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.textContent.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                if (filterValue === 'all') {
                    row.style.display = '';
                } else {
                    const species = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    if (species.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    });
});
</script>
</body>
</html>