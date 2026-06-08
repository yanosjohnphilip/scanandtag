<?php
include '../db/conn.php';


if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$animal_id = isset($_GET['animal_id']) ? intval($_GET['animal_id']) : 0;

// Verify the animal belongs to the owner
$stmt = $conn->prepare("SELECT a.*, o.first_name, o.last_name 
                        FROM animals a 
                        JOIN owners o ON a.owner_id = o.owner_id
                        WHERE a.animal_id = ? AND a.owner_id = ?");
$stmt->bind_param("ii", $animal_id, $owner_id);
$stmt->execute();
$animal_result = $stmt->get_result();

if ($animal_result->num_rows === 0) {
    header("Location: owner_homepage.php");
    exit();
}

$animal = $animal_result->fetch_assoc();
$stmt->close();

// Get all found reports for this animal
$stmt = $conn->prepare("
    SELECT report_id, reporter_name, reporter_phone, reporter_address, 
           report_time, latitude, longitude, message, image_proof 
    FROM found_reports 
    WHERE animal_id = ? 
    ORDER BY report_time DESC
");
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$reports_result = $stmt->get_result();
$reports = $reports_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Found Reports for <?php echo htmlspecialchars($animal['animal_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #489c4f;
            --secondary-color: #489c4f;
            --success-color: #489c4f;
            --danger-color: #489c4f;
            --warning-color: #489c4f;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.15); /* Increased contrast */
}

.card-header {
    background-color: #489c4f;
    color: #ffffff;
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.report-card {
    border-left: 4px solid #489c4f;
    transition: all 0.3s ease;
    margin-bottom: 12px;
    border-radius: 0.5rem;
    background-color: #ffffff;
    color: #2c3e50;
}

.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Slightly stronger shadow */
   
}

.report-card.active {
    border-left-color: #e67e22; /* Strong orange for warning/active */
    background-color: #fdf2e9; /* Soft, readable warm background */
    color: #2c3e50;
}

        
        .report-time {
            font-size: 0.8rem;
           
        }
        
        .report-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .report-image:hover {
            transform: scale(1.02);
        }
        
        #reportsMap {
            height: 650px;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid #e3e6f0;
        }
        
        .map-marker-popup img {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 10px;
            border-radius: 0.25rem;
        }
        
        .reports-list-container {
            max-height: 650px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .reports-list-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .reports-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .reports-list-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .back-btn {
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
        }
        
        .contact-btn {
            transition: all 0.3s;
        }
        
        .contact-btn:hover {
            transform: scale(1.05);
        }
        
        .pet-badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
            background-color: #489c4f !important;
            color: #fff !important;
        }
        
        .info-card {
            border-left: 4px solid var(--primary-color);
        }
        
        @media (max-width: 768px) {
            #reportsMap {
                height: 350px;
                margin-bottom: 20px;
            }
            
            .reports-list-container {
                max-height: none;
            }
        }
            
    </style>
</head>
<body>
<div class="container-wrapper">
    <div class="position-relative z-3">
     <?php include 'header.php'; ?>
    </div>

    <div class="container my-1 position-relative z-1 ">
        <div class="row justify-content-center ">
            <div class="col-12 col-lg-10 col-xl-12">
                <div class=" ">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h2 class="d-inline-block mb-0 text-success">Found Reports for <?php echo htmlspecialchars($animal['animal_name']); ?></h2>
                        </div>
                        <div>
                            <span class="badge pet-badge bg-primary">
                                <i class="fas fa-paw me-1"></i> <?php echo htmlspecialchars($animal['species']); ?>
                            </span>
                            <span class="badge pet-badge bg-secondary ms-1">
                                <i class="fas fa-dna me-1"></i> <?php echo htmlspecialchars($animal['breed']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card mb-4 ">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Report Locations</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-lg-8 p-3">
                                    <div id="reportsMap"></div>
                                </div>
                                <div class="col-lg-4 p-3 ">
                                    <div class="reports-list-container">
                                        <?php if (empty($reports)): ?>
                                            <div class="alert alert-success text-center py-4">
                                                <i class="fas fa-info-circle fa-3x mb-3 text-success"></i>
                                                <h4>No Found Reports Yet</h4>
                                                <p class="mb-0">There are currently no reports for <?php echo htmlspecialchars($animal['animal_name']); ?>.</p>
                                            </div>
                                        <?php else: ?>
                                            <h5 class="mb-3 text-center"><i class="fas fa-list me-2"></i>All Reports</h5>
                                            <div class="list-group">
                                                <?php foreach ($reports as $index => $report): ?>
                                                    <div class="list-group-item report-card <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                         data-lat="<?php echo $report['latitude']; ?>" 
                                                         data-lng="<?php echo $report['longitude']; ?>">
                                                         <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1 text-success">Report #<?php echo $index + 1; ?></h6>
                                                                <p class="mb-1"><strong><i class="fas fa-user me-1"></i>Reported by:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                                                <small class="report-time"><i class="far fa-clock me-1"></i><?php echo date('M j, Y g:i a', strtotime($report['report_time'])); ?></small>
                                                            </div>
                                                            <span class="badge bg-success">#<?php echo $index + 1; ?></span>
                                                        </div>
                                                        <?php if ($report['reporter_phone']): ?>
                                                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                                                <span><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($report['reporter_phone']); ?></span>
                                                                <a href="tel:<?php echo htmlspecialchars($report['reporter_phone']); ?>" class="btn btn-sm btn-success contact-btn ">
                                                                    <i class="fas fa-phone-alt me-1"></i>Call
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="mt-2">
                                                            <p class="mb-1"><strong><i class="fas fa-map-marker-alt me-1"></i>Address:</strong></p>
                                                            <p class="mb-2"><?php echo htmlspecialchars($report['reporter_address'] ?: 'Address not provided'); ?></p>
                                                        </div>
                                                        <?php if ($report['message']): ?>
                                                            <div class="mt-2">
                                                                <p class="mb-1"><strong><i class="fas fa-comment me-1"></i>Message:</strong></p>
                                                                <p class="mb-2"><?php echo htmlspecialchars($report['message']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($report['image_proof']): ?>
                                                            <div class="mt-2">
                                                                <p class="mb-1"><strong><i class="fas fa-camera me-1"></i>Photo Proof:</strong></p>
                                                                <img src="../report_proof/<?php echo htmlspecialchars($report['image_proof']); ?>" 
                                                                     class="report-image mt-1" 
                                                                     data-image="../report_proof/<?php echo htmlspecialchars($report['image_proof']); ?>"
                                                                     alt="Report proof image">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card info-card">
                        <div class="card-header bg-light text-success">
                            <h5 class="mb-0"><i class="fas fa-paw me-2"></i>Pet Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-user me-1"></i>Owner:</strong></p>
                                        <p><?php echo htmlspecialchars($animal['first_name'] . ' ' . $animal['last_name']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-dna me-1"></i>Breed:</strong></p>
                                        <p><?php echo htmlspecialchars($animal['breed']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-palette me-1"></i>Color:</strong></p>
                                        <p><?php echo htmlspecialchars($animal['color']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-birthday-cake me-1"></i>Birthdate:</strong></p>
                                        <p><?php echo htmlspecialchars($animal['birthdate']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-brain me-1"></i>Behavior:</strong></p>
                                        <p><?php echo htmlspecialchars($animal['behavior']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong><i class="fas fa-syringe me-1"></i>Vaccination Status:</strong></p>
                                        <span class="badge bg-<?php echo $animal['vaccination_status'] === 'Vaccinated' ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars($animal['vaccination_status']); ?>
                                        </span>
                                        <?php if ($animal['vaccination_status'] === 'Vaccinated' && !empty($animal['last_vaccination_date'])): ?>
                                            <small class="text-muted ms-2">(Last: <?php echo htmlspecialchars($animal['last_vaccination_date']); ?>)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- bg-white rounded-4 shadow p-4 -->
            </div> <!-- col -->
        </div> <!-- row -->
    </div> <!-- container -->
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Report Image</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid rounded" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" id="downloadImageBtn">
                        <i class="fas fa-download me-1"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Share Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="shareLink" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyLinkBtn">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-primary share-btn" data-platform="facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-info share-btn" data-platform="twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-success share-btn" data-platform="whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="btn btn-danger share-btn" data-platform="email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize the map
            const map = L.map('reportsMap').setView([14.5995, 120.9842], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            <?php if (!empty($reports)): ?>
                // Create a feature group to store all markers
                const markers = L.featureGroup().addTo(map);
                
                // Custom icons
                const reportIcon = L.icon({
                    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                });

                const currentLocationIcon = L.icon({
                    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-blue.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                });

                <?php foreach ($reports as $index => $report): ?>
                    <?php if (!empty($report['latitude']) && !empty($report['longitude']) && 
                              is_numeric($report['latitude']) && is_numeric($report['longitude'])): ?>
                        // Add marker for each report
                        const marker<?php echo $index; ?> = L.marker(
                            [<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>],
                            {icon: reportIcon}
                        ).addTo(map);
                        
                        // Add popup content
                        marker<?php echo $index; ?>.bindPopup(`
                            <div style="max-width: 300px;">
                                <h6 class="text-primary">Report #<?php echo $report['report_id']; ?></h6>
                                <p><strong><i class="fas fa-user me-1"></i>Reported by:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                <p><strong><i class="far fa-clock me-1"></i>Date:</strong> <?php echo date('M j, Y g:i a', strtotime($report['report_time'])); ?></p>
                                <p><strong><i class="fas fa-map-marker-alt me-1"></i>Location:</strong><br>
                                    Latitude: <?php echo $report['latitude']; ?><br>
                                    Longitude: <?php echo $report['longitude']; ?>
                                </p>
                                <?php if ($report['reporter_phone']): ?>
                                    <p><strong><i class="fas fa-phone me-1"></i>Contact:</strong> <?php echo htmlspecialchars($report['reporter_phone']); ?></p>
                                <?php endif; ?>
                                <?php if ($report['image_proof']): ?>
                                    <img src="../report_proof/<?php echo htmlspecialchars($report['image_proof']); ?>" 
                                         class="img-fluid mt-2 rounded" 
                                         style="max-width: 100%; max-height: 200px; cursor: pointer;" 
                                         onclick="document.getElementById('modalImage').src=this.src; new bootstrap.Modal(document.getElementById('imageModal')).show();">
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mt-3">
                                    <a href="https://www.google.com/maps/dir/City+Veterinary+Office,+La+Carlota+City,+Negros+Occidental/@10.5284046,122.8664119/<?php echo $report['latitude']; ?>,<?php echo $report['longitude']; ?>"
                                       target="_blank" 
                                       class="btn btn-sm btn-primary text-white">
                                        <i class="fas fa-map-marker-alt"></i> Directions from Vet Office
                                    </a>
                                    <?php if ($report['reporter_phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($report['reporter_phone']); ?>" 
                                           class="btn btn-sm btn-success text-white">
                                            <i class="fas fa-phone-alt"></i> Call Now
                                        </a>
                                    <?php endif; ?>
 
                                </div>
                            </div>
                        `);
                        
                        // Add to markers group
                        markers.addLayer(marker<?php echo $index; ?>);
                        
                        // Connect report card to marker
                        const card<?php echo $index; ?> = document.querySelector('.report-card[data-lat="<?php echo $report['latitude']; ?>"][data-lng="<?php echo $report['longitude']; ?>"]');
                        if (card<?php echo $index; ?>) {
                            card<?php echo $index; ?>.addEventListener('click', function() {
                                // Highlight the clicked report
                                document.querySelectorAll('.report-card').forEach(card => {
                                    card.classList.remove('active');
                                });
                                this.classList.add('active');
                                
                                // Fly to the marker and open its popup
                                map.flyTo([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], 15);
                                marker<?php echo $index; ?>.openPopup();
                            });
                        }
                        
                        <?php if ($index === 0): ?>
                            // Center on first report by default
                            map.flyTo([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], 15);
                            marker<?php echo $index; ?>.openPopup();
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                // Fit map to show all markers if there are multiple
                if (markers.getLayers().length > 1) {
                    map.fitBounds(markers.getBounds().pad(0.2));
                }
                
                // Add current location button
                const locateControl = L.control.locate({
                    position: 'topright',
                    drawCircle: true,
                    follow: true,
                    setView: 'once',
                    keepCurrentZoomLevel: true,
                    markerStyle: {
                        weight: 1,
                        opacity: 0.8,
                        fillOpacity: 0.8
                    },
                    circleStyle: {
                        weight: 1,
                        clickable: false
                    },
                    icon: 'fas fa-location-arrow',
                    metric: true,
                    strings: {
                        title: "Show my location",
                        popup: "You are within {distance} {unit} from this point",
                        outsideMapBoundsMsg: "You seem located outside the map bounds"
                    },
                    locateOptions: {
                        maxZoom: 15,
                        watch: true,
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 10000
                    }
                }).addTo(map);
            <?php endif; ?>

            // Image modal handling
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('report-image')) {
                    const imageSrc = e.target.getAttribute('data-image');
                    const modalImage = document.getElementById('modalImage');
                    modalImage.src = imageSrc;
                    
                    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
                    modal.show();
                }
            });

            // Download image button
            document.getElementById('downloadImageBtn').addEventListener('click', function() {
                const imageSrc = document.getElementById('modalImage').src;
                const link = document.createElement('a');
                link.href = imageSrc;
                link.download = 'report-proof-' + new Date().getTime() + '.jpg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Share functionality
            document.querySelectorAll('.share-report-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const reportId = this.getAttribute('data-report-id');
                    const shareLink = window.location.href.split('?')[0] + '?animal_id=<?php echo $animal_id; ?>&highlight=' + reportId;
                    document.getElementById('shareLink').value = shareLink;
                    
                    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                    shareModal.show();
                });
            });

            // Copy link button
            document.getElementById('copyLinkBtn').addEventListener('click', function() {
                const shareLink = document.getElementById('shareLink');
                shareLink.select();
                document.execCommand('copy');
                
                // Show tooltip feedback
                const tooltip = new bootstrap.Tooltip(this, {
                    title: 'Copied!',
                    trigger: 'manual'
                });
                tooltip.show();
                setTimeout(() => tooltip.hide(), 1000);
            });

            // Social share buttons
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const platform = this.getAttribute('data-platform');
                    const text = `Found report for <?php echo htmlspecialchars($animal['animal_name']); ?>. Please check this link: `;
                    const shareLink = document.getElementById('shareLink').value;
                    
                    let url = '';
                    switch(platform) {
                        case 'facebook':
                            url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`;
                            break;
                        case 'twitter':
                            url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(shareLink)}`;
                            break;
                        case 'whatsapp':
                            url = `https://wa.me/?text=${encodeURIComponent(text + shareLink)}`;
                            break;
                        case 'email':
                            url = `mailto:?subject=Found Report for <?php echo htmlspecialchars($animal['animal_name']); ?>&body=${encodeURIComponent(text + shareLink)}`;
                            break;
                    }
                    
                    window.open(url, '_blank');
                });
            });

            // Highlight report if specified in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('highlight')) {
                const reportId = urlParams.get('highlight');
                const reportCard = document.querySelector(`.report-card span.badge[data-report-id="${reportId}"]`)?.closest('.report-card');
                if (reportCard) {
                    reportCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    reportCard.classList.add('active');
                    
                    const lat = reportCard.dataset.lat;
                    const lng = reportCard.dataset.lng;
                    if (lat && lng) {
                        map.flyTo([lat, lng], 15);
                    }
                }
            }
        });
    </script>
</body>
</html>