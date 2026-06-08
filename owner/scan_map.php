<?php
include '../db/conn.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$animal_id = isset($_GET['animal_id']) ? intval($_GET['animal_id']) : 0;

// Get animal details including profile image if available
$stmt = $conn->prepare("SELECT animal_name FROM animals WHERE animal_id = ? AND owner_id = ?");
$stmt->bind_param("ii", $animal_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Animal not found or access denied");
}

$animal = $result->fetch_assoc();
$stmt->close();

// Get scan locations with additional data
$stmt = $conn->prepare("SELECT 
    latitude, 
    longitude, 
    scan_time
    FROM scan_history 
    WHERE animal_id = ? 
    ORDER BY scan_time ASC");
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$scan_result = $stmt->get_result();

$scan_data = $scan_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate some stats
$total_scans = count($scan_data);
$first_scan = $total_scans ? $scan_data[0]['scan_time'] : null;
$last_scan = $total_scans ? end($scan_data)['scan_time'] : null;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Scan Locations for <?php echo htmlspecialchars($animal['animal_name']); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.css" />
    <style>
        :root {
            --primary-color: #489c4f;
            --secondary-color: #489c4f;
            --accent-color: #489c4f;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .animal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
        }
        
       
    
        
        #map-container {
            position: relative;
            height: 70vh;
            width: 100%;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        .map-controls {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .control-btn {
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--dark-color);
        }
        
        .control-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .stat-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .timeline-container {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 800px;
            transition: all 0.3s ease;
        }
        
        .timeline-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px;
            overflow: hidden;
        }
        
        .timeline-scroller {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 5px;
            scrollbar-width: thin;
        }
        
        .timeline-item {
            flex: 0 0 auto;
            width: 120px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timeline-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .timeline-item.active {
            border: 3px solid var(--primary-color);
        }
        
        .timeline-item .time-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px;
            font-size: 12px;
            text-align: center;
        }
        
        .no-data-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .leaflet-control-geocoder, 
.leaflet-control-search {
    display: none !important;
}

        .no-data-icon {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }
        
        .leaflet-popup-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-width: 250px;
        }
        
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 3px 14px rgba(0,0,0,0.4);
        }
        
        .scan-detail-item {
            margin-bottom: 8px;
        }
        
        .scan-detail-label {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .scan-detail-value {
            font-weight: 400;
        }
        
        .accuracy-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .accuracy-high { background-color: #2ecc71; }
        .accuracy-medium { background-color: #f39c12; }
        .accuracy-low { background-color: #e74c3c; }
        
        .heatmap-control {
            position: absolute;
            bottom: 120px;
            right: 15px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .playback-controls {
            position: absolute;
            bottom: 170px;
            right: 15px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 5px;
        }
        
        .playback-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .animal-header {
                text-align: center;
                flex-direction: column;
            }
            
            .timeline-container {
                width: 95%;
            }
            
            .heatmap-control, .playback-controls {
                bottom: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="position-relative z-3">
            <?php include 'header.php'; ?>
        </div>
        
        <div class="container py-4 position-relative z-1">
    
            <div class="card mb-4">
                <div class="animal-header d-flex align-items-center gap-4">
                    
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($animal['animal_name']); ?></h2>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt me-1"></i> 
                            <span id="current-location">Loading location...</span>
                        </p>
                    </div>
                </div>
                
                <?php if (empty($scan_data)): ?>
                    <div class="no-data-card">
                        <div class="no-data-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h4 class="mb-3">No Scan Locations Found</h4>
                        <p class="text-muted">This animal hasn't been scanned yet. Check back later to see location data.</p>
                        <a href="index_owner.php" class="btn btn-success mt-2">
                            <i class="fas fa-home me-1"></i> Return to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div id="map-container">
                        <div id="map"></div>
                        
                        <!-- Map Controls -->
                        <div class="map-controls">
                            <button class="control-btn" id="zoom-in" title="Zoom In">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="control-btn" id="zoom-out" title="Zoom Out">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="control-btn" id="locate-me" title="My Location">
                                <i class="fas fa-location-arrow"></i>
                            </button>
                            <button class="control-btn" id="fit-bounds" title="Fit to Animal Locations">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button class="control-btn" id="toggle-timeline" title="Toggle Timeline">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                        
                        <!-- Heatmap Control -->
                        <div class="heatmap-control">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="heatmap-toggle">
                                <label class="form-check-label" for="heatmap-toggle">Heatmap</label>
                            </div>
                        </div>
                        
                        <!-- Playback Controls -->
                        <div class="playback-controls">
                            <button class="playback-btn" id="playback-prev" title="Previous Scan">
                                <i class="fas fa-step-backward"></i>
                            </button>
                            <button class="playback-btn" id="playback-start" title="Play Animation">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="playback-btn" id="playback-next" title="Next Scan">
                                <i class="fas fa-step-forward"></i>
                            </button>
                        </div>
                        
                        <!-- Stats Panel -->
                        
                        <div class="stats-card" style="position: absolute; top: 15px; left: 15px; z-index: 1000; max-width: 280px;">
                        
                            <h5><i class="fas fa-chart-line me-2"></i>Travel Statistics</h5>
                            <div class="stat-item">
                                <span class="stat-label">Total Scans:</span>
                                <span class="stat-value"><?php echo $total_scans; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Tracking Since:</span>
                                <span class="stat-value"><?php echo date('M j, Y', strtotime($first_scan)); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Last Scan:</span>
                                <span class="stat-value" id="last-scan-time"><?php echo date('M j, Y g:i a', strtotime($last_scan)); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Current Zoom:</span>
                                <span class="stat-value" id="zoom-level">12</span>
                            </div>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="timeline-container" id="timeline-container">
                            <div class="timeline-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Scan Timeline</h6>
                                    <small class="text-muted"><?php echo $total_scans; ?> locations</small>
                                </div>
                                <div class="timeline-scroller" id="scan-timeline">
                                    <?php foreach ($scan_data as $index => $scan): ?>
                                        <div class="timeline-item" data-index="<?php echo $index; ?>">
                                            <i class="fas fa-map-pin text-success fa-2x"></i>
                                            <div class="time-label">
                                                <?php echo date('M j, g:i a', strtotime($scan['scan_time'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Details Panel -->
                    <div class="row mt-4 p-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Scan Details</h5>
                                    <div id="scan-details">
                                        <div class="alert alert-success">
                                            Select a scan point on the map or timeline to view details
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Activity Summary</h5>
                                    <div id="activity-summary">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <span class="stat-label">Most Active Day:</span>
                                                    <span class="stat-value" id="most-active-day">-</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <span class="stat-label">Most Active Hour:</span>
                                                    <span class="stat-value" id="most-active-hour">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3" id="activity-chart" style="height: 150px;">
                                            <canvas id="hourlyActivityChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const scanData = <?php echo json_encode($scan_data); ?>;
            
            if (scanData.length > 0) {
                // Initialize main map
                const map = L.map('map', {
                    zoomControl: false
                }).setView([
                    scanData[0].latitude, 
                    scanData[0].longitude
                ], 13);
                
                // Add map tiles (using modern OSM variant)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);
                
                // Add geocoder control
                L.Control.geocoder({
                    defaultMarkGeocode: false,
                    position: 'topleft'
                })
                .on('markgeocode', function(e) {
                    map.fitBounds(e.geocode.bbox, {padding: [50, 50]});
                })
                .addTo(map);
                
                // Create marker cluster group
                const markers = L.markerClusterGroup({
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    maxClusterRadius: 60
                });
                
                // Custom icon with different colors based on accuracy
                function getMarkerIcon(accuracy) {
                    let color;
                    if (accuracy > 50) {
                        color = '#e74c3c'; // Red for low accuracy
                    } else if (accuracy > 20) {
                        color = '#f39c12'; // Orange for medium accuracy
                    } else {
                        color = '#2ecc71'; // Green for high accuracy
                    }
                    
                    return L.divIcon({
                        html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid white; box-shadow: 0 0 0 2px ${color}80;"><i class="fas fa-paw" style="font-size: 10px;"></i></div>`,
                        className: 'custom-pet-icon',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });
                }
                
                // Add markers for each scan location
                scanData.forEach((scan, index) => {
                    const accuracy = scan.accuracy || 30; // Default accuracy if not provided
                    const marker = L.marker([scan.latitude, scan.longitude], {
                        icon: getMarkerIcon(accuracy)
                    }).bindPopup(`
                        <div>
                            <h5 style="color: var(--primary-color); margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                <i class="fas fa-map-marker-alt me-2"></i>Scan #${index + 1}
                            </h5>
                            <div class="scan-detail-item">
                                <span class="scan-detail-label">Time:</span>
                                <span class="scan-detail-value">${new Date(scan.scan_time).toLocaleString()}</span>
                            </div>
                            <div class="scan-detail-item">
                                <span class="scan-detail-label">Coordinates:</span>
                                <span class="scan-detail-value">${Number(scan.latitude).toFixed(6)}, ${Number(scan.longitude).toFixed(6)}</span>
                            </div>
                           
                            
                         <div class="d-grid gap-2 mt-3">
                           <a href="https://www.google.com/maps?q=${scan.latitude},${scan.longitude}" 
                            target="_blank" 
                            class="btn btn-sm btn-success" 
                            style="color: #ffffff;">
                                <i class="fas fa-external-link-alt me-1" style="color: #ffffff;"></i> Open in Google Maps
                            </a>

                        </div>
                        </div>
                    `);
                    
                    // Store scan data with marker
                    marker.scanData = scan;
                    marker.scanIndex = index;
                    markers.addLayer(marker);
                    
                    // Add click event to update details panel
                    marker.on('click', function() {
                        updateScanDetails(this.scanData);
                        
                        showActiveRadius(this.scanData.latitude, this.scanData.longitude);
                        highlightTimelineItem(this.scanIndex);
                    });
                });
                
                // Add markers to map
                map.addLayer(markers);
                
                // Add a polyline connecting all points
                const pathLine = L.polyline(
                    scanData.map(scan => [scan.latitude, scan.longitude]), 
                    {
                        color: '#489c4f',
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '5, 5'
                    }
                ).addTo(map);

                // Add 1km radius circle for active pinned location
                let activeRadiusCircle = null;
                
                function updateActiveRadius(lat, lng) {
                    // Remove existing radius circle
                    if (activeRadiusCircle) {
                        map.removeLayer(activeRadiusCircle);
                    }
                    
                    // Create new 1km radius circle
                    activeRadiusCircle = L.circle([lat, lng], {
                        radius: 1000, // 1km in meters
                        color: "#489c4f",
                        weight: 2,
                        fillColor: "#489c4f",
                        fillOpacity: 0.1
                    }).addTo(map);
                }
                
                // Initialize heatmap layer
                const heatData = scanData.map(scan => [scan.latitude, scan.longitude, 0.5]);
                const heatmapLayer = L.heatLayer(heatData, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17,
                    gradient: {0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1.0: 'red'}
                });
                
                
                // 1km radius around active pinned location
                let activeCircle = null;
                function showActiveRadius(lat, lng) {
                    if (activeCircle) {
                        map.removeLayer(activeCircle);
                    }
                    activeCircle = L.circle([lat, lng], {
                        radius: 1000,
                        color: "#489c4f",
                        weight: 2,
                        dashArray: "4,4",
                        fillColor: "#489c4f",
                        fillOpacity: 0.1
                    }).addTo(map);
                }
// Fit map to markers with padding
                map.fitBounds(markers.getBounds(), {
                    padding: [50, 50]
                });
                
                // Update zoom level display
                map.on('zoomend', function() {
                    document.getElementById('zoom-level').textContent = map.getZoom();
                });
                
                // Control buttons functionality
                document.getElementById('zoom-in').addEventListener('click', () => {
                    map.zoomIn();
                });
                
                document.getElementById('zoom-out').addEventListener('click', () => {
                    map.zoomOut();
                });
                
                document.getElementById('locate-me').addEventListener('click', () => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            map.flyTo([pos.coords.latitude, pos.coords.longitude], 15);
                        }, () => {
                            alert('Could not get your location');
                        });
                    } else {
                        alert('Geolocation is not supported by your browser');
                    }
                });
                
                document.getElementById('fit-bounds').addEventListener('click', () => {
                    map.fitBounds(markers.getBounds(), {
                        padding: [50, 50]
                    });
                });
                
                // Toggle timeline visibility
                document.getElementById('toggle-timeline').addEventListener('click', () => {
                    const timeline = document.getElementById('timeline-container');
                    timeline.style.display = timeline.style.display === 'none' ? 'block' : 'none';
                });
                
                // Toggle heatmap
                document.getElementById('heatmap-toggle').addEventListener('change', function() {
                    if (this.checked) {
                        map.addLayer(heatmapLayer);
                    } else {
                        map.removeLayer(heatmapLayer);
                    }
                });
                
                // Timeline interaction
                const timelineItems = document.querySelectorAll('.timeline-item');
                
                function highlightTimelineItem(index) {
                    timelineItems.forEach(i => i.classList.remove('active'));
                    timelineItems[index].classList.add('active');
                    
                    // Scroll to show the active item
                    const timeline = document.getElementById('scan-timeline');
                    const activeItem = document.querySelector(`.timeline-item[data-index="${index}"]`);
                    timeline.scrollLeft = activeItem.offsetLeft - (timeline.offsetWidth / 2) + (activeItem.offsetWidth / 2);
                }
                
                timelineItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        const scan = scanData[index];
                        
                        // Highlight selected timeline item
                        highlightTimelineItem(index);
                        
                        // Fly to the location
                        map.flyTo([scan.latitude, scan.longitude], 17);
                        
                        
                        showActiveRadius(scan.latitude, scan.longitude);
                        // Find and open the corresponding marker popup
                        markers.getLayers().forEach(layer => {
                            if (layer.scanIndex === index) {
                                layer.openPopup();
                                updateScanDetails(scan);
                                updateActiveRadius(scan.latitude, scan.longitude);
                            }
                        });
                    });
                });
                
                // Playback functionality
                let currentPlaybackIndex = 0;
                let playbackInterval;
                
                function playScanAnimation() {
                    clearInterval(playbackInterval);
                    currentPlaybackIndex = 0;
                    
                    playbackInterval = setInterval(() => {
                        if (currentPlaybackIndex >= scanData.length) {
                            clearInterval(playbackInterval);
                            document.getElementById('playback-start').innerHTML = '<i class="fas fa-play"></i>';
                            return;
                        }
                        
                        const scan = scanData[currentPlaybackIndex];
                        map.flyTo([scan.latitude, scan.longitude], 15);
                        
                        showActiveRadius(scan.latitude, scan.longitude);
                        highlightTimelineItem(currentPlaybackIndex);
                        updateScanDetails(scan);
                        
                        currentPlaybackIndex++;
                    }, 1500);
                }
                
                document.getElementById('playback-start').addEventListener('click', function() {
                    if (playbackInterval) {
                        clearInterval(playbackInterval);
                        playbackInterval = null;
                        this.innerHTML = '<i class="fas fa-play"></i>';
                    } else {
                        this.innerHTML = '<i class="fas fa-pause"></i>';
                        playScanAnimation();
                    }
                });
                
                document.getElementById('playback-prev').addEventListener('click', function() {
                    if (currentPlaybackIndex > 0) {
                        currentPlaybackIndex--;
                        const scan = scanData[currentPlaybackIndex];
                        map.flyTo([scan.latitude, scan.longitude], 15);
                        
                        showActiveRadius(scan.latitude, scan.longitude);
                        highlightTimelineItem(currentPlaybackIndex);
                        updateScanDetails(scan);
                    }
                });
                
                document.getElementById('playback-next').addEventListener('click', function() {
                    if (currentPlaybackIndex < scanData.length - 1) {
                        currentPlaybackIndex++;
                        const scan = scanData[currentPlaybackIndex];
                        map.flyTo([scan.latitude, scan.longitude], 15);
                        
                        showActiveRadius(scan.latitude, scan.longitude);
                        highlightTimelineItem(currentPlaybackIndex);
                        updateScanDetails(scan);
                    }
                });
                
                // Update scan details panel
                function updateScanDetails(scan) {
                    const detailsPanel = document.getElementById('scan-details');
                    
                    
                    detailsPanel.innerHTML = `
                        <div class="scan-detail-item">
                            <span class="scan-detail-label">Scan Time:</span>
                            <span class="scan-detail-value">${new Date(scan.scan_time).toLocaleString()}</span>
                        </div>
                        
                        <div class="scan-detail-item">
                            <span class="scan-detail-label">Coordinates:</span>
                            <span class="scan-detail-value">${Number(scan.latitude).toFixed(6)}, ${Number(scan.longitude).toFixed(6)}</span>
                        </div>
                        ${scan.device_info ? `
                        <div class="scan-detail-item">
                            <span class="scan-detail-label">Detected By:</span>
                            <span class="scan-detail-value">${scan.device_info}</span>
                        </div>` : ''}
                        <div class="mt-3">
                            <a href="https://www.openstreetmap.org/?mlat=${scan.latitude}&mlon=${scan.longitude}&zoom=17" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-success me-2">
                                <i class="fas fa-map me-1"></i> View on Map
                            </a>
                            <button class="btn btn-sm btn-outline-success" onclick="navigator.clipboard.writeText('${scan.latitude}, ${scan.longitude}')">
                                <i class="fas fa-copy me-1"></i> Copy Coordinates
                            </button>
                        </div>
                    `;
                    
                    // Update last scan time
                    document.getElementById('last-scan-time').textContent = new Date(scan.scan_time).toLocaleString();
                }
                
                // Initialize activity chart
                function initActivityChart() {
                    // Group scans by hour of day
                    const hours = Array(24).fill(0);
                    const days = Array(7).fill(0);
                    
                    scanData.forEach(scan => {
                        const date = new Date(scan.scan_time);
                        const hour = date.getHours();
                        const day = date.getDay();
                        
                        hours[hour]++;
                        days[day]++;
                    });
                    
                    // Find most active day and hour
                    const mostActiveHour = hours.indexOf(Math.max(...hours));
                    const mostActiveDay = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][
                        days.indexOf(Math.max(...days))
                    ];
                    
                    document.getElementById('most-active-hour').textContent = `${mostActiveHour}:00 - ${mostActiveHour+1}:00`;
                    document.getElementById('most-active-day').textContent = mostActiveDay;
                    
                    // Create hourly activity chart
                    const ctx = document.getElementById('hourlyActivityChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                            datasets: [{
                                label: 'Scans per Hour',
                                data: hours,
                                backgroundColor: '#489c4f',
                                borderColor: '#3a0ca3',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.parsed.y} scan${context.parsed.y !== 1 ? 's' : ''}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                
                initActivityChart();
                
                // Reverse geocode the first location to display address
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${scanData[0].latitude}&lon=${scanData[0].longitude}`)
                    .then(response => response.json())
                    .then(data => {
                        const address = data.display_name || 'Unknown location';
                        document.getElementById('current-location').textContent = address.split(',')[0];
                    })
                    .catch(() => {
                        document.getElementById('current-location').textContent = 'Location unknown';
                    });
                
                // Initialize with first scan selected
                updateScanDetails(scanData[0]);
                
                showActiveRadius(scanData[0].latitude, scanData[0].longitude);
                highlightTimelineItem(0);
                updateActiveRadius(scanData[0].latitude, scanData[0].longitude);
            }
        });
    </script>
</body>
</html>

