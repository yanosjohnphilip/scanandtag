<?php
include '../db/conn.php';

// Fetch owners and their animals (only verified owners)
$result = $conn->query("SELECT o.owner_id, o.first_name, o.last_name, o.phone, o.address, o.barangay, o.latitude, o.longitude, 
                               a.animal_id, a.animal_name AS animal_name, a.species, a.breed, a.sex, a.birthdate
                        FROM owners o 
                        LEFT JOIN animals a ON o.owner_id = a.owner_id
                        WHERE o.status = 'verified'");

$owners = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['owner_id'];
    if (!isset($owners[$id])) {
        $owners[$id] = [
            'owner_id' => $row['owner_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'barangay' => $row['barangay'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'animals' => []
        ];
    }
    if (!empty($row['animal_id'])) {
        $owners[$id]['animals'][] = [
            'animal_id' => $row['animal_id'],
            'animal_name' => $row['animal_name'],
            'species' => $row['species'],
            'breed' => $row['breed'],
            'sex' => $row['sex'],
            'birthdate' => $row['birthdate']
        ];
    }
}
$owners = array_values($owners);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Registered Owners | La Carlota City Veterinary Office</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #38b000;
            --warning: #ff9e00;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
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
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
      
        .card-header {
            background: #2b9348;
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        
        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            z-index: 1;
        }
        
        #ownerInfoPanel {
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .owner-info-header {
            background: #2b9348;
            color: white;
            padding: 1rem 1.5rem;
            flex-shrink: 0;
        }
        
        .owner-info-body {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .owner-details-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .animals-container {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .table-container {
                border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            border: none;
            padding: 1rem;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
 
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid rgba(0,0,0,0.03);
        }
        
        .badge-pill {
            border-radius: 50px;
            padding: 5px 10px;
            font-weight: 500;
        }
        
        .animal-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--secondary);
        }
        
        .marker-icon {
            background-color: var(--primary);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .marker-icon.selected {
            background-color: var(--success);
            width: 36px;
            height: 36px;
        }
        
        .pet-icon {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            border-radius: 50%;
            background-color: var(--secondary);
            color: white;
            font-size: 10px;
        }
        
        .age-badge {
            background-color: var(--warning);
            color: white;
        }
        
        /* Custom scrollbar styles */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        @media (max-width: 768px) {
            #map, #ownerInfoPanel {
                height: 350px;
            }
            .main-content {
                margin-left: 0;
            }

            .animals-container {
                max-height: 150px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
                   <h1 class="h3 mt-4 fw-bold">Owner Management</h1>
                    <p class="mb-5">Registered Owner Information</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Map Column -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-map-marked-alt me-2"></i>
                                <span>Owners Location Map</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <select id="barangayFilter" class="form-select form-select-sm me-2" style="width: 200px;">
                                    <option value="">-- All Barangay --</option>
                                    <option value="Ara-al">Ara-al</option>
                                    <option value="Ayungon">Ayungon</option>
                                    <option value="Balabag">Balabag</option>
                                    <option value="Batuan">Batuan</option>
                                    <option value="Cubay">Cubay</option>
                                    <option value="Haguimit">Haguimit</option>
                                    <option value="La Granja">La Granja</option>
                                    <option value="Nagasi">Nagasi</option>
                                    <option value="Barangay I (Poblacion)">Barangay I (Poblacion)</option>
                                    <option value="Barangay II (Poblacion)">Barangay II (Poblacion)</option>
                                    <option value="Barangay III (Poblacion)">Barangay III (Poblacion)</option>
                                    <option value="Barangay RSB (Consuelo)">Barangay RSB (Consuelo)</option>
                                    <option value="San Miguel">San Miguel</option>
                                    <option value="Yubo">Yubo</option>
                                </select>
                                <span class="badge bg-primary rounded-pill" id="ownerCountBadge">
                                    <?= count($owners) ?> owners registered
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Owner Info Column -->
                <div class="col-lg-4">
                    <div class="card h-100" id="ownerInfoPanel">
                        <div class="owner-info-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user-circle me-2"></i>
                                <span>Owner Details</span>
                            </div>
                            <span class="badge bg-light text-dark rounded-pill" id="petCountBadge">0 pets</span>
                        </div>
                        <div class="owner-info-body">
                            <div class="owner-details-content" id="ownerDetails">
                                <div class="text-center py-4">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                        style="width: 80px; height: 80px;">
                                        <i class="fas fa-map-marker-alt fa-2x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">Select a location</h5>
                                    <p class="text-muted small">Click on a map marker or table row to view owner details</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Owners Table -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-2"></i>
                        <span>Registered Owners</span>
                    </div>
                    <div>
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search owners..." style="width: 200px;">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover" id="ownersTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Location</th>
                                        <th>Pets</th>
                                        <th>Barangay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($owners as $owner): ?>
                                        <tr data-owner-id="<?= $owner['owner_id'] ?>" 
                                            data-lat="<?= $owner['latitude'] ?>" 
                                            data-lng="<?= $owner['longitude'] ?>"
                                            data-barangay="<?= htmlspecialchars($owner['barangay']) ?>"
                                            onclick="showOwnerDetails(<?= $owner['owner_id'] ?>)">
                                            <td>
                                                <strong><?= htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($owner['phone']) ?></td>
                                            <td>
                                                <?php if ($owner['latitude'] && $owner['longitude']): ?>
                                                    <span class="badge bg-success rounded-pill">Located</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary rounded-pill">No location</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?= count($owner['animals']) ?> pet<?= count($owner['animals']) !== 1 ? 's' : '' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($owner['barangay']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const owners = <?= json_encode($owners) ?>;
        const map = L.map('map').setView([10.3, 122.9], 10);
        let markers = [];
        let selectedMarker = null;

        // Initialize map with OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Create custom marker icon
        function createMarkerIcon(selected = false) {
            return L.divIcon({
                className: '',
                html: `<div class="marker-icon ${selected ? 'selected' : ''}">
                          <i class="fas fa-user"></i>
                       </div>`,
                iconSize: selected ? [36, 36] : [30, 30],
                iconAnchor: selected ? [18, 18] : [15, 15]
            });
        }

        // Add markers to the map
        function addMarkers(filterBarangay = '') {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            let filteredOwners = owners;
            if (filterBarangay) {
                filteredOwners = owners.filter(owner => owner.barangay === filterBarangay);
            }
            
            // Update owner count badge
            document.getElementById('ownerCountBadge').textContent = 
                filteredOwners.length + ' owner' + (filteredOwners.length !== 1 ? 's' : '');
            
            // Add filtered markers
            filteredOwners.forEach(owner => {
                if (owner.latitude && owner.longitude) {
                    const marker = L.marker([owner.latitude, owner.longitude], {
                        icon: createMarkerIcon()
                    }).addTo(map);
                    
                    marker.ownerId = owner.owner_id;
                    markers.push(marker);
                    
                    marker.on('click', () => {
                        showOwnerDetails(owner.owner_id);
                    });
                }
            });
            
            // Fit bounds to show filtered markers
            const bounds = filteredOwners
                .filter(o => o.latitude && o.longitude)
                .map(o => [o.latitude, o.longitude]);
            
            if (bounds.length > 0) {
                map.fitBounds(bounds);
            } else {
                map.setView([10.3, 122.9], 10); // Reset to default view if no markers
            }
            
            // Filter table rows
            document.querySelectorAll('#ownersTable tbody tr').forEach(row => {
                const barangay = row.getAttribute('data-barangay');
                if (!filterBarangay || barangay === filterBarangay) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initial load of all markers
        addMarkers();

        // Barangay filter change handler
        document.getElementById('barangayFilter').addEventListener('change', function() {
            addMarkers(this.value);
        });

        // Function to show owner details
        function showOwnerDetails(ownerId) {
            const owner = owners.find(o => o.owner_id == ownerId);
            if (!owner) return;
            
            // Update the selected marker appearance
            markers.forEach(marker => {
                if (marker.ownerId == ownerId) {
                    marker.setIcon(createMarkerIcon(true));
                    selectedMarker = marker;
                    map.setView(marker.getLatLng(), 14);
                } else {
                    marker.setIcon(createMarkerIcon());
                }
            });
            
            // Highlight the table row
            document.querySelectorAll('#ownersTable tbody tr').forEach(row => {
                if (row.getAttribute('data-owner-id') == ownerId) {
                    row.style.backgroundColor = 'rgba(67, 97, 238, 0.08)';
                } else {
                    row.style.backgroundColor = '';
                }
            });
            
            // Calculate age from birthdate
            function calculateAge(birthdate) {
                if (!birthdate) return 'Unknown';
                const today = new Date();
                const birthDate = new Date(birthdate);
                let years = today.getFullYear() - birthDate.getFullYear();
                let months = today.getMonth() - birthDate.getMonth();
                if (today.getDate() < birthDate.getDate()) {
                    months--;
                }
                if (months < 0) {
                    years--;
                    months += 12;
                }
                let yearStr = years > 0 ? years + ' year' + (years !== 1 ? 's' : '') : '';
                let monthStr = months > 0 ? months + ' month' + (months !== 1 ? 's' : '') : '';
                if (yearStr && monthStr) return yearStr + ' ' + monthStr;
                if (yearStr) return yearStr;
                if (monthStr) return monthStr;

                // Calculate days if less than a month
                const diffTime = Math.abs(today - birthDate);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                return diffDays + ' day' + (diffDays !== 1 ? 's' : '');
            }
            
            // Generate animals HTML
            let animalsHtml = '';
            if (owner.animals.length > 0) {
                animalsHtml = `<div class="animals-container">
                                   <h6 class="d-flex align-items-center">
                                       <i class="fas fa-paw me-2"></i>
                                       <span>Registered Pets</span>
                                   </h6>`;
                
                owner.animals.forEach(animal => {
                    animalsHtml += `
                        <div class="animal-card mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h5 class="mb-0">${animal.animal_name}</h5>
                                <span class="badge bg-secondary badge-pill">${animal.species}</span>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Breed:</small>
                                    <p class="mb-1">${animal.breed || 'Unknown'}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Sex:</small>
                                    <p class="mb-1">${animal.sex || 'Unknown'}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Age:</small>
                                <span class="badge age-badge badge-pill">${calculateAge(animal.birthdate)}</span>
                            </div>
                        </div>
                    `;
                });
                
                animalsHtml += `</div>`;
            } else {
                animalsHtml = `<div class="alert alert-light mt-3">
                                  <i class="fas fa-info-circle me-2"></i>
                                  No pets registered for this owner
                              </div>`;
            }
            
            // Update the owner details panel
            document.getElementById('ownerDetails').innerHTML = `
                <div class="text-center mb-3">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                         style="width: 60px; height: 60px;">
                        <i class="fas fa-user fa-lg text-white"></i>
                    </div>
                    <h4>${owner.first_name} ${owner.last_name}</h4>
                    <p class="text-muted small">${owner.barangay}</p>
                </div>
                
                <div class="mb-2">
                    <h6 class="d-flex align-items-center">
                        <i class="fas fa-phone me-2"></i>
                        <span>Contact Information</span>
                    </h6>
                    <p class="ms-4">${owner.phone || 'Not provided'}</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span>Address</span>
                    </h6>
                    <p class="ms-4">${owner.address || 'Not provided'}</p>
                </div>
                
                ${animalsHtml}
                
              
            `;
            
            // Update pet count badge
            document.getElementById('petCountBadge').textContent = 
                owner.animals.length + ' pet' + (owner.animals.length !== 1 ? 's' : '');
            
            // Scroll to top of details panel
            document.getElementById('ownerDetails').scrollTop = 0;
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const selectedBarangay = document.getElementById('barangayFilter').value;
            
            document.querySelectorAll('#ownersTable tbody tr').forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const barangay = row.getAttribute('data-barangay');
                
                // Check if row matches both barangay filter and search term
                const matchesBarangay = !selectedBarangay || barangay === selectedBarangay;
                const matchesSearch = name.includes(searchTerm) || barangay.toLowerCase().includes(searchTerm);
                
                if (matchesBarangay && matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
