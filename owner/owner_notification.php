<?php
include '../db/conn.php';

$owner_id = $_SESSION['owner_id'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']); // Fixed missing semicolon

// --- Handle AJAX requests for marking notifications as read ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all as read
    if (isset($_POST['mark_all_read']) && $owner_id) {
        $stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE owner_id = ?");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $stmt->close();
        echo "All notifications marked as read";
        exit;
    }
    // Mark single notification as read
    if (isset($_POST['notif_id']) && isset($_POST['is_read'])) {
        $notif_id = intval($_POST['notif_id']);
        $is_read = intval($_POST['is_read']);
        $stmt = $conn->prepare("UPDATE notification SET is_read = ? WHERE notif_id = ? AND owner_id = ?");
        $stmt->bind_param("iii", $is_read, $notif_id, $owner_id);
        $stmt->execute();
        $stmt->close();
        echo "Notification marked as read";
        exit;
    }
}

$ownerStmt = $conn->prepare("SELECT first_name, last_name FROM owners WHERE owner_id = ?");
$ownerStmt->bind_param("i", $owner_id);
$ownerStmt->execute();
$owner_result = $ownerStmt->get_result();
$owner_details= $owner_result->fetch_assoc();
$ownerStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
<title>Owner Notification</title>
<!-- Google Fonts: Inter -->
<link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="header.css"> <!-- Link to external stylesheet -->
<style>
         
        .main-content {
            flex: 1;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
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
            font-size: 14px;
            margin-bottom: 5px;
        }
        .notification-time {
            font-size: 12px;
            color: #555;
        }
        .notification-buttons {
            margin-top: 5px;
        }
        .btn {
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 5px;
            margin-right: 5px;
            cursor: pointer;
        }
        .btn-confirm {
            background-color:#388E3C;
            color: white;
        }
        .btn-delete {
            background-color: #e4e6eb;
            color: #050505;
        }
        .leaflet-tooltip-ownermap {
            background-color: rgba(56, 142, 60, 0.8);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-weight: bold;
        }
        .notif-item {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            border-radius: 8px;
            transition: background 0.3s;
            cursor: pointer;
            margin-bottom: 10px;
            border-left: 4px solid transparent;
        }
        .notif-item:hover {
            background: #cff0c1;
        }
        .notif-item.unread {
            background: #cdf1cf;
            border-left-color: #388E3C;
        }
        .notif-content {
            flex: 1;
        }
        .notif-time {
            font-size: 12px;
            color: #555;
            margin-left: 10px;
            white-space: nowrap;
        }
        .notif-buttons {
            margin-top: 10px;
        }
        .view-location {
            background-color: #388E3C;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .view-location:hover {
            background-color: #2e7d32;
        }
        .no-notifications {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 16px;
        }
        .mapStyle {
            background: #fff;
            width: 90%;
            max-width: 900px;
            height: 70%;
            border-radius: 10px;
            position: relative;
            padding: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        #mapNotif {
            width: 100%;
            height: calc(100% - 60px);
            border-radius: 10px;
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
<div class="container-wrapper">
    <div class="position-relative z-3"> 
      <?php include 'header.php'; ?>
    </div>
    <div class="container position-relative z-1">
      <div class="notification-panel" style="width: 100%;">
          <div class="notification-header">
              <span>Notifications</span>
              <span style="color:#388E3C; cursor:pointer;" onclick="markAllAsRead()">Mark All as Read</span>
          </div>

        <!-- HEADER CARD -->
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
          LEFT JOIN animals a ON a.animal_id = COALESCE(s.animal_id, f.animal_id)
          WHERE n.owner_id = ?
          ORDER BY n.date_notify DESC
        ";
        $notifStmt = $conn->prepare($query);
        $notifStmt->bind_param("i", $owner_id);
        $notifStmt->execute();
        $result = $notifStmt->get_result();

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            $owner = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
            $message = htmlspecialchars($row['message']);
            $date = date('F j, Y h:i A', strtotime($row['date_notify'])); // Changed to show full month, date, year, and time with AM/PM
            $lat = $row['latitude'];
            $lng = $row['longitude'];
            $notif_id = $row['notif_id'];
            $animal_name = htmlspecialchars($row['animal_name']);
            $animal_image = '../admin/' . htmlspecialchars($row['animal_image']);

            echo "
            <div class='notif-item" . ($row['is_read'] ? '' : ' unread') . "' id='notif-$notif_id'>
              <div onclick='markAsRead($notif_id)' class='notif-content'>
                <div style='display: flex; justify-content: space-between; align-items: center; width: 100%;'>
                  <div>
                    <strong>$owner</strong> - $message
                  </div>
                  <div class='notif-time' style='color: #666; font-size: 0.85em;'>$date</div>
                </div>
                <div class='notif-buttons' style='margin-top: 8px;'>";

                    // Only show View location button if notification has scan_id or report_id
                    if (!empty($row['scan_id']) || !empty($row['report_id'])) {
                    echo "<button onclick='openMapModal($lat, $lng, $notif_id, " . json_encode($animal_name) . ", " . json_encode($animal_image) . ")' class='view-location'>View location</button>";
                    }

                 echo "
                </div>
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
<div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
  background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
  <div class="mapStyle" style="background:#fff; width:100%; height:70%; border-radius:10px; position:relative; padding:10px;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
      <h3 style="margin:0;">Location Details</h3>
      <button onclick="closeMapModal()" 
              style="font-size:18px; background:none; border:none; cursor:pointer; padding:5px 10px;">✖</button>
    </div>
    <div id="mapNotif" style="width:100%; height:calc(100% - 60px); border-radius:10px;"></div>
  </div>
</div>
<script>
    function handleNotificationClick(notifId, lat, lng, animalName, animalImage) {
        markAsRead(notifId);
        openMapModal(lat, lng, notifId, animalName, animalImage);
    }

    function markAsRead(notificationId) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "owner_notification.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var notifElem = document.getElementById('notif-' + notificationId);
                if (notifElem) {
                    notifElem.classList.remove('unread');
                }
            }
        };

        xhr.send("notif_id=" + notificationId + "&is_read=1");
    }

    function markAllAsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "owner_notification.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Remove all unread styles and buttons
                document.querySelectorAll('.notif-item.unread').forEach(function(item) {
                    item.classList.remove('unread');
                    var btn = item.querySelector('.btn-outline-success');
                    if (btn) btn.remove();
                });
            }
        };

        xhr.send("mark_all_read=1");
    }
</script>