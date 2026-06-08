<?php
include '../db/conn.php'; // Ensure the database connection is included
$currentPage = basename($_SERVER['PHP_SELF']);
$admin_id = $_SESSION['admin_id']; // Ensure this line ends with a semicolon

// Check if the request is a POST request to update the notification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notif_id']) && isset($_POST['is_read'])) {
        $notif_id = $_POST['notif_id'];
        $is_read = $_POST['is_read'];

        // Update the is_read status for a single notification
        $updateQuery = "UPDATE notification SET is_read = ? WHERE notif_id = ? AND admin_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("iii", $is_read, $notif_id, $admin_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo "Notification updated successfully";
        exit;
    } elseif (isset($_POST['mark_all_read'])) {
        // Update the is_read status for all notifications under the admin ID
        $updateAllQuery = "UPDATE notification SET is_read = 1 WHERE admin_id = ?";
        $updateAllStmt = $conn->prepare($updateAllQuery);
        $updateAllStmt->bind_param("i", $admin_id);
        $updateAllStmt->execute();
        $updateAllStmt->close();

        echo "All notifications marked as read";
        exit;
    } elseif (isset($_POST['delete_notif_id'])) {
        // Delete a single notification
        $delete_id = $_POST['delete_notif_id'];
        $deleteQuery = "DELETE FROM notification WHERE notif_id = ? AND admin_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ii", $delete_id, $admin_id);
        $deleteStmt->execute();
        $deleteStmt->close();
        echo "Notification deleted successfully";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Notifications | La Carlota City Veterinary Office</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 5px;
        }
        .main-content {
            justify-content: center;
            align-items: center;
            padding: 10px;
            margin: auto;
            min-height: 100vh;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #388E3C;
        }
        .notification-panel {
            width: 100%;
            max-width: 800px;
            overflow-y: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            margin: 0 auto;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
            cursor: pointer; /* Add cursor pointer to indicate clickable */
        }
        .notification-item:hover {
            background:#cff0c1;
        }
        .notification-item.unread {
            background:#cdf1cf; /* Light blue background for unread notifications */
        }
        .notification-avatar {
            width: 40px;
            height: 40px;
            background: #ccc;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .notification-content {
            flex: 1;
        }
        .notification-message {
            font-size: 16px;
            margin-bottom: 4px;
            color: #333;
            line-height: 1.4;
        }
        .notification-time {
            font-size: 12px;
            color: #555;
            margin-left: 10px;
            white-space: nowrap;
        }
        .notification-buttons {
            margin-top: 8px;
        }
        .btn {
            padding: 8px 14px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            margin-right: 8px;
            margin-top: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .notification-content {
            flex: 1;
            padding: 0 10px;
        }
        .btn-confirm {
            background-color: #388E3C;
            color: white;
        }
        .btn-delete {
            color: white;
            background-color: #dc3545;
        }
        
        /* Notification type styles */
        .notification-item.notification-report {
            border-left: 4px solid #ffc107; /* Yellow for report notifications */
        }
        .notification-item.notification-scan {
            border-left: 4px solid #dc3545; /* Red for scan notifications */
        }
        .notification-item.notification-normal {
            border-left: 4px solid #28a745; /* Green for normal notifications */
        }
        
        /* Hover states */
        .notification-item.notification-report:hover {
            background-color: #fff8e1;
        }
        .notification-item.notification-scan:hover {
            background-color: #fde8e8;
        }
        .notification-item.notification-normal:hover {
            background-color: #e8f5e9;
        }
        
        /* Unread states */
        .notification-item.notification-report.unread {
            background-color: #fff8e1;
        }
        .notification-item.notification-scan.unread {
            background-color: #fde8e8;
        }
        .notification-item.notification-normal.unread {
            background-color: #e8f5e9;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
            .mapStyle {
              width: 100% !important;
              height: 80% !important;
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
    <div class="container">
         
        
        <div class="sidebar-container">
            <?php include'templates/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
           
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                    <h1 class="h3 mt-4 fw-bold">Notifications</h1>
                    <p class="mb-5">Latest Alerts!</p>
                </div>

                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
        
        <div class="notification-panel">
            <div class="notification-header">
                <span>Notifications</span>
                <span style="color:#388E3C; cursor:pointer;" onclick="markAllAsRead()">Mark All as Read</span>
            </div>
            <div class="notif-dropdown">

            <?php
                $query = "
                SELECT n.*, 
                        o.first_name, o.last_name,
                        COALESCE(s.latitude, f.latitude) AS latitude, 
                        COALESCE(s.longitude, f.longitude) AS longitude,
                        a.animal_name, a.animal_image
                FROM notification n
                LEFT JOIN owners o ON n.owner_id = o.owner_id
                LEFT JOIN scan_history s ON n.scan_id = s.scan_id
                LEFT JOIN found_reports f ON n.report_id = f.report_id
                LEFT JOIN lost_found_history l ON n.lf_id = l.lf_id
                LEFT JOIN animals a ON COALESCE(s.animal_id, f.animal_id , l.animal_id) = a.animal_id
                WHERE n.admin_id = ? AND NOT (n.owner_id IS NOT NULL AND n.lf_id IS NOT NULL AND n.scan_id IS NULL AND n.report_id IS NULL)
                ORDER BY n.date_notify DESC
                ";
                $notifStmt = $conn->prepare($query);
                $notifStmt->bind_param("i", $admin_id);
                $notifStmt->execute();
                $result = $notifStmt->get_result();

                if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $owner = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                    $message = htmlspecialchars($row['message']);
                    $date = date('F j, Y h:i A', strtotime($row['date_notify'])); // Updated to show full date and time
                    $lat = $row['latitude'];
                    $lng = $row['longitude'];
                    $notif_id = $row['notif_id'];
                    $animal_name = htmlspecialchars($row['animal_name']);
                    $animal_image_path =  $row['animal_image'];
                    $animal_image = !empty($row['animal_image']) && file_exists($animal_image_path)
                        ? htmlspecialchars($animal_image_path)
                        : 'images/animals/default_animal.png';

                    // Determine notification type and set appropriate class
                    $notificationType = 'notification-normal'; // Default to normal
                    if (!empty($row['report_id'])) {
                        $notificationType = 'notification-report'; // Yellow for report notifications
                    } elseif (!empty($row['scan_id'])) {
                        $notificationType = 'notification-scan'; // Red for scan notifications
                    }

                    // Add 'unread' class if the notification is not read
                    $unreadClass = $row['is_read'] ? '' : ' unread';

                    // Fetch report details if report_id is present
                    $reportDetails = '';
                    if (!empty($row['report_id'])) {
                        $report_id = $row['report_id'];
                        // Get all needed fields directly from found_reports
                        $reportQuery = "SELECT fr.message, fr.report_time, fr.reporter_name, fr.reporter_phone, fr.reporter_address, fr.image_proof, a.animal_name
                            FROM found_reports fr 
                            LEFT JOIN animals a ON fr.animal_Id = a.animal_id
                            WHERE report_id = ?";

                        $reportStmt = $conn->prepare($reportQuery);
                        $reportStmt->bind_param("i", $report_id);
                        $reportStmt->execute();
                        $reportResult = $reportStmt->get_result();
                        if ($reportRow = $reportResult->fetch_assoc()) {
                            $animalName = htmlspecialchars($reportRow['animal_name']);
                            $reporterName = htmlspecialchars($reportRow['reporter_name']);
                            $reporterPhone = htmlspecialchars($reportRow['reporter_phone']);
                            $reporterAddress = htmlspecialchars($reportRow['reporter_address']);
                            $imageProof = !empty($reportRow['image_proof']) && file_exists('../report_proof/' . $reportRow['image_proof'])
                                ? '../report_proof/' . htmlspecialchars($reportRow['image_proof'])
                                : '../images/animals/default_animal.png';
                            // Pass all details as a JSON object for easier JS handling
                            $reportDetailsArr = [
                                'description' => htmlspecialchars($reportRow['message']),
                                'report_time' => htmlspecialchars($reportRow['report_time']),
                                'animal_name' => $animal_name,
                                'reporter_name' => $reporterName,
                                'reporter_phone' => $reporterPhone,
                                'reporter_address' => $reporterAddress,
                                'image_proof' => $imageProof
                            ];
                            $reportDetails = json_encode($reportDetailsArr);
                        }
                        $reportStmt->close();
                    }

                    echo "
                    <div class='notification-item $notificationType$unreadClass' onclick='markAsRead($notif_id)'>
                        <div class='notification-avatar' style='background-image: url($animal_image); background-size: cover; height: 100px; width: 100px; border-radius: 50%;'></div>
                        <div class='notification-content'>
                            <div style='display: flex; justify-content: space-between; align-items: flex-start;'>
                                <div class='notification-content'>
                        <div style='display: flex; justify-content: space-between; align-items: center; width: 100%;'>
                            <div class='notification-message'>$message</div>
                            <div class='notification-time' style='color: #666; font-size: 0.85em;'>$date</div>
                        </div>
                                </div>
                                <button class='btn btn-delete' style='margin-left:10px;' onclick='event.stopPropagation(); deleteNotification($notif_id, this)'>
                                    <i class='fas fa-trash' style='font-size: 1rem;'></i>
                                </button>
                            </div>
                            <div class='notification-buttons'>";
                        
                    // Check if scan_id or report_id is present
                    if (!empty($row['scan_id']) || !empty($row['report_id'])) {
                        // Pass reportDetails as an extra argument (as JSON)
                        echo "<button onclick='event.stopPropagation();openMapModal($lat, $lng, $notif_id, " . json_encode($animal_name) . ", " . json_encode($animal_image) . ", $reportDetails)' class='btn btn-confirm'>View location</button>";
                    }

                    echo "</div>
                    </div>
                    </div>";
                }
                } else {
                echo "<div class='no-notifications'>No notifications</div>";
                }
                $notifStmt->close();
                ?>
            </div>
        </div>
    </div>
</div>
<div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="mapStyle" style="background:#fff; width:80%; height:80%; border-radius:10px; position:relative; padding:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
            <h3 style="margin:0;">Location Details</h3>
            <button onclick="closeMapModal()" 
                    style="font-size:18px; background:none; border:none; cursor:pointer; padding:5px 10px;">✖</button>
            </div>
    <div id="mapAdminNotif" style="width:100%; height:calc(100% - 50px); border-radius:10px;"></div>
    </div>
</div>

<script>
    // Store map instance globally
    let mapInstance = null;

    function openMapModal(lat, lng, notificationId, animalName, animalImage, reportDetails) {
    event.stopPropagation();
    document.getElementById('mapModal').style.display = 'flex';

    setTimeout(() => {
        if (!mapInstance) {
            mapInstance = L.map('mapAdminNotif').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapInstance);
        } else {
            mapInstance.setView([lat, lng], 16);
            // Clear existing markers and circles
            mapInstance.eachLayer((layer) => {
                if (layer instanceof L.Marker || layer instanceof L.Circle) {
                    mapInstance.removeLayer(layer);
                }
            });
        }

        let notifPopupContent = '';
        // If reportDetails is an object, show report info
        if (reportDetails && typeof reportDetails === 'object') {
            notifPopupContent = `
                <div style="text-align:left;max-width:240px;">
                    <div style="font-weight:bold;margin-bottom:5px;">Report Details of ${reportDetails.animal_name || ''}</div>
                    <div style="margin:12px 0; text-align:center;">
                        <img src="${reportDetails.image_proof || 'images/animals/default_animal.png'}" alt="Proof" style="width:180px;height:180px;object-fit:cover;border-radius:12px;border:1px solid #ccc;">
                    </div>
                    <div><b>Description:</b> ${reportDetails.description || ''}</div>
                    <div><b>Date Reported:</b> ${reportDetails.report_time || ''}</div>
                    <div><b>Reporter Name:</b> ${reportDetails.reporter_name || ''}</div>
                    <div><b>Reporter Phone:</b> ${reportDetails.reporter_phone || ''}</div>
                    <div><b>Reporter Address:</b> ${reportDetails.reporter_address || ''}</div>

                </div>
            `;
        } else {
            notifPopupContent = `
                <div style="text-align:center;">
                    <img src="${animalImage}" alt="Animal Image" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
                    <div style="font-weight:bold;">${animalName}</div>
                </div>
            `;
        }
        // If reportDetails is a JSON string, parse it
        if (typeof reportDetails === 'string') {
            try {
                let obj = JSON.parse(reportDetails);
                notifPopupContent = `
                    <div style="text-align:left;max-width:240px;">
                        <div style="font-weight:bold;margin-bottom:5px;">Report Details of ${obj.animal_name || ''}</div>
                         <div style="margin:12px 0; text-align:center;">
                            <img src="${obj.image_proof || 'images/animals/default_animal.png'}" alt="Proof" style="width:180px;height:180px;object-fit:cover;border-radius:12px;border:1px solid #ccc;">
                        </div>
                        <div><b>Description:</b> ${obj.messsage || ''}</div>
                        <div><b>Date Reported:</b> ${obj.report_time || ''}</div>  
                        <div><b>Reporter Name:</b> ${obj.reporter_name || ''}</div>
                        <div><b>Reporter Phone:</b> ${obj.reporter_phone || ''}</div>
                        <div><b>Reporter Address:</b> ${obj.reporter_address || ''}</div>
                       
                        </div>
                `;
            } catch (e) { /* fallback to animal info */ }
        }

        // Add marker
        L.marker([lat, lng]).addTo(mapInstance).bindPopup(notifPopupContent).openPopup();
        // Determine label text based on scan or report
        let areaLabel = '';
        if (reportDetails && typeof reportDetails === 'string') {
            try {
                let obj = JSON.parse(reportDetails);
                areaLabel = 'This is the area where the last animal was reported.';
            } catch (e) {
                areaLabel = 'This is the area where the last animal scan occurred.';
            }
        } else if (reportDetails && typeof reportDetails === 'object') {
            areaLabel = 'This is the area where the last animal was reported.';
        } else {
            areaLabel = 'This is the area where the last animal scan occurred.';
        }
        // Add 1km radius circle with label
        const rangeCircle = L.circle([lat, lng], {radius: 1000, color: '#388E3C', fillColor: '#cff0c1', fillOpacity: 0.2}).addTo(mapInstance);
        rangeCircle.bindTooltip(areaLabel, {permanent: true, direction: 'center', className: 'range-label'}).openTooltip();

        mapInstance.invalidateSize();

        markAsRead(notificationId);
    }, 100);
    }

    function closeMapModal() {
    document.getElementById('mapModal').style.display = 'none';
    location.reload(); // Reload the page when the modal is closed
    }

    document.getElementById('mapModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeMapModal();
           
        }
    });

    function markAsRead(notificationId) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "notification.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Do NOT reload here!
                //  // <-- Remove or comment out this line
            }
        };

        xhr.send("notif_id=" + notificationId + "&is_read=1");
    }

    function markAllAsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "notification.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Reload the page to reflect the change
                location.reload();
            }
        };

        xhr.send("mark_all_read=1");
    }
    function deleteNotification(notifId, btn) {
        if (!confirm('Are you sure you want to delete this notification?')) return;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "notification.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Remove the notification item from the DOM
                var notifItem = btn.closest('.notification-item');
                if (notifItem) notifItem.remove();
            }
        };
        xhr.send("delete_notif_id=" + notifId);
    }
</script>

</body>
</html>
