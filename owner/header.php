<?php
include '../db/conn.php';


$owner_id = $_SESSION['owner_id'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['notif_id']) && isset($_POST['is_read'])) {
      $notif_id = intval($_POST['notif_id']);
      $stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE notif_id = ?");
      $stmt->bind_param("i", $notif_id);
      $stmt->execute();
      $stmt->close();
      exit; // End script for AJAX
  }
  if (isset($_POST['mark_all_read'])) {
      $owner_id = $_SESSION['owner_id'] ?? 0;
      $stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE owner_id = ?");
      $stmt->bind_param("i", $owner_id);
      $stmt->execute();
      $stmt->close();
      exit; // End script for AJAX
  }
}

// Count unread notifications
$notifCountStmt = $conn->prepare("SELECT COUNT(*) FROM notification WHERE owner_id = ? AND is_read = 0");
$notifCountStmt->bind_param("i", $owner_id);
$notifCountStmt->execute();
$notifCountStmt->bind_result($unreadNotifCount);
$notifCountStmt->fetch();
$notifCountStmt->close();

// Get owner details
$ownerStmt = $conn->prepare("SELECT first_name, last_name FROM owners WHERE owner_id = ?");
$ownerStmt->bind_param("i", $owner_id);
$ownerStmt->execute();
$owner_result = $ownerStmt->get_result();
$owner_details= $owner_result->fetch_assoc();
$ownerStmt->close();
?>

<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<style>
  .bottom-nav {
    display: none; /* Hide by default */
  }

  /* Show bottom navigation only on mobile */
  @media (max-width: 768px) {
    .bottom-nav {
      display: flex;
    }
    .notif-badge {
      background: var(--danger);
      color: white;
      padding: 2px 6px;
      border-radius: 10px;
      font-size: 0.75rem;
      position: absolute;
      right: 28%;
    }
    .mapStyle {
      width: 100% !important;
      height: 80% !important;
    }
    /* Mobile-specific styles for the logout modal */
    #logoutModal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    .modal-content {
      background-color: #fff;
      margin: auto;
      padding: 30px 24px 20px 24px;
      border-radius: 10px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      width: 90%;
      max-width: 350px;
      text-align: center;
      position: relative;
      top: 20vh;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      margin: auto;
      width: 100vw;
      height: 100vh;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
      justify-content: center;
      align-items: center;
    }
  }
</style>

<div class="floating-card">
  <div class="header-flex">
    <div class="profile-area">
      <div>
        <div class="welcome-title">Welcome,
          <span class="highlight"><?php echo htmlspecialchars($owner_details['first_name'] . ' ' . $owner_details['last_name']); ?></span>
        </div>
        <div class="subtitle"><i class="fa fa-paw"></i> My Pet Dashboard</div>
      </div>
    </div>

    <div class="hamburger-menu">
      
    </div>
  

    <div class="nav-menu-row">
      <nav class="navbar-nav">
        <ul class="navbar">
          <li><a href="index_owner.php" class="<?= $currentPage == 'index_owner.php' ? 'active' : '' ?>">Home</a></li>

          <li><a href="owner_registered_animal.php" class="<?= in_array($currentPage, ['owner_registered_animal.php','animal_reports.php','scan_map.php']) ? 'active' : '' ?>">Registered Animals</a></li>
          
          <!-- Notification Dropdown -->
          <li class="notification-dropdown">
            <a href="#" class="notif-toggle <?= $currentPage == 'notification.php' ? 'active' : '' ?>">Notifications
              <?php if ($unreadNotifCount > 0): ?>
                <span class="notif-badge"><?= $unreadNotifCount ?></span>
              <?php endif; ?>
            </a>
            <div class="notif-dropdown" style="width: 400px; ">
              <div class="notification-header" style="padding:10px;">
                <span style="cursor:pointer;" onclick="window.location.href='owner_notification.php'">See All</span>
                <span style="color:#388E3C; cursor:pointer;" onclick="markAllAsRead()">Mark All as Read</span>
              </div>

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
                  $date = date('M j, Y', strtotime($row['date_notify']));
                  $lat = $row['latitude'];
                  $lng = $row['longitude'];
                  $notif_id = $row['notif_id'];
                  $animal_name = htmlspecialchars($row['animal_name']);
                  $animal_image = '../admin/' . htmlspecialchars($row['animal_image']);
                  if ($animal_image && !preg_match('/^https?:\/\//', $animal_image)) {
                      $animal_image =  $animal_image; // Adjust as needed
                  }
                  if (!$animal_image) {
                      $animal_image = 'images/default-animal.png'; // fallback
                  }
               

                  echo "
                  <div class='notif-item" . ($row['is_read'] ? '' : ' unread') . "' id='notif-$notif_id'>
                    <div onclick='markAsRead($notif_id)' class='notif-content'>
                      <strong>$owner</strong> - $message <div class='notif-time'>$date</div><br>
                      <div class='notif-buttons'>";
      
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
          </li>

          <li>
            <div class="profile-actions">
              <a href="#" class="logout-btn" id="logoutBtn">Logout</a>
            </div>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>



<div class="bottom-nav">
  <a href="index_owner.php" class="<?= $currentPage == 'index_owner.php' ? 'active' : '' ?>">
    <i class="fas fa-home-alt"></i><span>Dashboard</span>
  </a>
  <a href="owner_registered_animal.php" class="<?= $currentPage == 'owner_registered_animal.php' ? 'active' : '' ?>">
    <i class="fas fa-paw"></i><span>Registered Animal</span>
  </a>
  <a href="owner_notification.php">
    <i class="fas fa-bell"></i><span>Notification</span>
    <?php if ($unreadNotifCount > 0): ?>
      <span class="notif-badge"><?= $unreadNotifCount ?></span>
    <?php endif; ?>
  </a>
  <a href="#" id="mobileLogoutBtn">
    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
  </a>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to log out?</p>
    <div class="modal-buttons">
      <button id="confirmLogout" class="confirm-btn">Yes, Logout</button>
      <button id="cancelLogout" class="cancel-btn">Cancel</button>
    </div>
  </div>
</div>

<script>
  // Logout Logic for Mobile
  document.getElementById('mobileLogoutBtn').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
  });

  document.getElementById('confirmLogout').addEventListener('click', () => window.location.href = '../logout.php');
  document.getElementById('cancelLogout').addEventListener('click', () => {
    document.getElementById('logoutModal').style.display = 'none';
  });
</script>
<!-- Map Modal -->
<div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
  background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
  <div class="mapStyle" style="background:#fff; width:60%; height:70%; border-radius:10px; position:relative; padding:10px;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #ddd;">
      <h3 style="margin:0;">Location Details</h3>
      <button onclick="closeMapModal()" 
              style="font-size:18px; background:none; border:none; cursor:pointer; padding:5px 10px;">✖</button>
    </div>
    <div id="mapNotif" style="width:100%; height:calc(100% - 60px); border-radius:10px;"></div>
  </div>
</div>

<script>
  document.querySelector('.hamburger-menu').addEventListener('click', function () {
    this.classList.toggle('active');
    document.querySelector('.navbar').classList.toggle('active');
  });
  let mapInstance = null;

  function openMapModal(lat, lng, notificationId, animalName, animalImage) {
    event.stopPropagation();
    document.getElementById('mapModal').style.display = 'flex';

    setTimeout(() => {
      if (!mapInstance) {
        mapInstance = L.map('mapNotif').setView([lat, lng], 16);
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

      // Show animal image and name in popup
      let notifPopupContent = `
        <div style="text-align:center;">
          <img src="${animalImage}" alt="Animal Image" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
          <div style="font-weight:bold;">${animalName}</div>
        </div>
      `;
      L.marker([lat, lng]).addTo(mapInstance).bindPopup(notifPopupContent).openPopup();

      // Add 1km radius circle with dynamic label
      let labelText = "This is the area where the last animal scan occurred.";
      // If you have a way to distinguish report vs scan, update labelText accordingly
      const rangeCircle = L.circle([lat, lng], {
        radius: 1000,
        color: '#388E3C',
        fillColor: '#cff0c1',
        fillOpacity: 0.2
      }).addTo(mapInstance);
      rangeCircle.bindTooltip(labelText, {permanent: true, direction: 'center', className: 'leaflet-tooltip-ownermap'});

      markAsRead(notificationId);
    }, 100);
  }

  function closeMapModal() {
    document.getElementById('mapModal').style.display = 'none';
  }


  function markAsRead(notificationId) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "header.php", true);
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

    // In your JS:
    function markAllAsRead() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "header.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.querySelectorAll('.notif-item.unread').forEach(function(item) {
                    item.classList.remove('unread');
                });
                document.querySelectorAll('.notif-badge').forEach(function(badge) {
                    badge.textContent = '';
                    badge.style.display = 'none';
                });
            }
        };

        xhr.send("mark_all_read=1");
    }

  // Logout Logic
  document.getElementById('logoutBtn').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'block';
  });
  document.getElementById('cancelLogout').addEventListener('click', () => {
    document.getElementById('logoutModal').style.display = 'none';
  });

  // Notification Dropdown Toggle
  document.querySelector('.notif-toggle').addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelector('.notif-dropdown').classList.toggle('active');
    document.querySelector('.notif-dropdown').style.display = 
      document.querySelector('.notif-dropdown').classList.contains('active') ? 'block' : 'none';
  });

  // Close dropdown when clicking outside
  window.addEventListener('click', function(e) {
    if (!e.target.closest('.notification-dropdown')) {
      const dropdown = document.querySelector('.notif-dropdown');
      dropdown.classList.remove('active');
      dropdown.style.display = 'none';
    }
  });
</script>
