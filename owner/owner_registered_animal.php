<?php
include '../db/conn.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s');
$owner_id = $_SESSION['owner_id'];

// Handle Lost/Found Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status'])) {
    $animal_id = $_POST['animal_id'];
    $new_status = $_POST['status'];

    // Get current status before update to check for changes
    $current_status_sql = "SELECT status FROM animals WHERE animal_id = ? AND owner_id = ?";
    $current_status_stmt = $conn->prepare($current_status_sql);
    $current_status_stmt->bind_param("ii", $animal_id, $owner_id);
    $current_status_stmt->execute();
    $current_status_result = $current_status_stmt->get_result();
    $current_status_row = $current_status_result->fetch_assoc();
    $current_status = $current_status_row['status'];
    $current_status_stmt->close();

    // Fetch animal name and owner name
    $stmt = $conn->prepare("SELECT a.animal_name, o.first_name, o.last_name FROM animals a JOIN owners o ON a.owner_id = o.owner_id WHERE a.animal_id = ? AND a.owner_id = ?");
    $stmt->bind_param("ii", $animal_id, $owner_id);
    $stmt->execute();
    $stmt->bind_result($animalName, $ownerFirstName, $ownerLastName);
    $stmt->fetch();
    $stmt->close();

    $ownerName = $ownerFirstName . ' ' . $ownerLastName;

    $stmt = $conn->prepare("UPDATE animals SET status = ? WHERE animal_id = ? AND owner_id = ?");
    $stmt->bind_param("sii", $new_status, $animal_id, $owner_id);
    
    if ($stmt->execute()) {
        // Check if any rows were actually updated
        if ($stmt->affected_rows === 0) {
            $_SESSION['message'] = "No animal found to update or no changes made.";
            $_SESSION['message_type'] = "warning";
        } else {
            $_SESSION['message'] = "Animal status updated successfully!";
            $_SESSION['message_type'] = "success";
        
            // Check if status has changed and insert into lost_found_history
            if ($current_status !== $new_status) {
            // Get owner name for updated_by field
            $owner_name = "Unknown";
            if (isset($_SESSION['owner_id'])) {
                $owner_query = "SELECT CONCAT(first_name, ' ', last_name) as owner_name FROM owners WHERE owner_id = ?";
                $owner_stmt = $conn->prepare($owner_query);
                $owner_stmt->bind_param("i", $_SESSION['owner_id']);
                $owner_stmt->execute();
                $owner_result = $owner_stmt->get_result();
                if ($owner_row = $owner_result->fetch_assoc()) {
                    $owner_name = $owner_row['owner_name'];
                }
                $owner_stmt->close();
            }
            
            $history_sql = "INSERT INTO lost_found_history (animal_id, status, updated_by, update_date) VALUES (?, ?, ?,?)";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("isss", $animal_id, $new_status, $owner_name,$current_time);
            
            if ($history_stmt->execute()) {
                $_SESSION['message'] .= " Status change has been recorded.";
                
                // Get the lf_id of the newly inserted record
                $lf_id = $conn->insert_id;
                
                // Get animal name from lost_found_history table via animal_id
                $animal_name_query = "SELECT a.animal_name FROM animals a 
                                     JOIN lost_found_history lf ON a.animal_id = lf.animal_id 
                                     WHERE lf.lf_id = ?";
                $animal_name_stmt = $conn->prepare($animal_name_query);
                $animal_name_stmt->bind_param("i", $lf_id);
                $animal_name_stmt->execute();
                $animal_name_result = $animal_name_stmt->get_result();
                $animal_name_row = $animal_name_result->fetch_assoc();
                $animal_name_from_history = $animal_name_row['animal_name'];
                $animal_name_stmt->close();
                
                // Fetch all admin IDs and send notifications using lf_id
                $adminQuery = "SELECT admin_id FROM admins";
                $adminResult = $conn->query($adminQuery);

                if ($adminResult->num_rows > 0) {
                    while ($adminRow = $adminResult->fetch_assoc()) {
                        $admin_id = $adminRow['admin_id'];

                        // Insert notification for each admin using lf_id from lost_found_history
                        $notification_message = "The status of animal named $animal_name_from_history has been updated to $new_status by its owner named $ownerName.";
                        $notifStmt = $conn->prepare("INSERT INTO notification (admin_id, lf_id, message, date_notify) VALUES (?, ?, ?, ?)");
                        $notifStmt->bind_param("iiss", $admin_id, $lf_id, $notification_message,$current_time);
                        
                        if (!$notifStmt->execute()) {
                            // If foreign key constraint fails, we need to fix the database schema
                            error_log("Foreign key constraint error: " . $notifStmt->error);
                            $_SESSION['message'] .= " (Warning: Notification failed due to database constraint - lf_id foreign key needs to reference lost_found_history.lf_id)";
                        }
                        $notifStmt->close();
                    }
                }
            } else {
                $_SESSION['message'] = "Status updated but failed to record status change: " . $conn->error;
                $_SESSION['message_type'] = "warning";
            }
            
            $history_stmt->close();
        } else {
            // If status hasn't changed, still send notifications but without lf_id
            // Fetch all admin IDs
            $adminQuery = "SELECT admin_id FROM admins";
            $adminResult = $conn->query($adminQuery);

            if ($adminResult->num_rows > 0) {
                while ($adminRow = $adminResult->fetch_assoc()) {
                    $admin_id = $adminRow['admin_id'];

                    // Insert notification for each admin using animal_id (fallback for unchanged status)
                    $notification_message = "The status of animal named $animalName has been updated to $new_status by its owner named $ownerName.";
                    $notifStmt = $conn->prepare("INSERT INTO notification (admin_id, animal_id, message, date_notify) VALUES (?, ?, ?, ?)");
                    $notifStmt->bind_param("iiss", $admin_id, $animal_id, $notification_message,$current_time);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
        }
        }
    } else {
        $_SESSION['message'] = "Failed to update status: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }

    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
    exit();
}


// Get owner's registered animals
$stmt = $conn->prepare("SELECT animal_id, animal_name, species, breed, birthdate, color, behavior, vaccination_status, status 
                        FROM animals WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$animals = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
<title>Owner Registered Animal</title>
<!-- Google Fonts: Inter -->
<link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root {
    --primary: #2c3e50;
    --primary-light: #34495e;
    --primary-dark: #1a252f;
    --accent: #3498db;
    --success: #2ecc71;
    --warning: #f1c40f;
    --danger: #e74c3c;
    --gray: #95a5a6;
    --light-gray: #ecf0f1;
    --white: #ffffff;
    --navbar-gradient: linear-gradient(90deg, var(--primary) 60%, var(--primary-light) 100%);
    --gradient-bg: linear-gradient(135deg, var(--light-gray) 0%, var(--white) 100%);
    --gradient-card: linear-gradient(110deg, var(--white) 60%, var(--light-gray) 100%);
    --card-border: #bdc3c7;
    --shadow-main: 0 4px 28px 0 rgba(44, 62, 80, 0.14);
    --shadow-card: 0 2px 14px 0 rgba(44, 62, 80, 0.08);
  }
  html, body {
    background: var(--gradient-bg);
    margin: 0;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    color: #22203a;
    min-height: 100vh;
  }
  body {
    padding: 0;
  }
  .container {
   
    margin: 0 auto;
    padding: 32px 16px 36px;
  }
 
  /* Animal Table Section */
  .card-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px 20px 20px 20px;
    margin: 20px auto 0 auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    max-width: 1100px;
    width: 100%;
    position: relative;
    min-width: 280px;
    border: 1px solid #e9ecef;
    overflow: hidden;
    z-index: 10;
  }

  .card-section::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 6px;
    height: 100%;
    background: linear-gradient(135deg, #2c7a3c, #4CAF50);
    z-index: 1;
  }
  .section-title-row {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    position: relative;
    z-index: 2;
    margin-bottom: 0;
  }

  .owner-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #2c7a3c, #4CAF50);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    box-shadow: 0 2px 8px rgba(44, 122, 60, 0.3);
  }

  .owner-icon i {
    color: white;
    font-size: 1.2rem;
  }

  .owner-title-section {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .owner-title {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.25rem;
  }
  .register-btn {
    background: #2c3e50;
    color: #fff;
    font-weight: 600;
    border: none;
    padding: 0.62rem 1.2rem;
    font-size: 1.04rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px #a589fb22;
    cursor: pointer;
    margin-left: 10px;
    transition: filter 0.13s,transform 0.13s;
  }
  .register-btn:hover { filter: brightness(1.11); transform: scale(1.07);}
  .pets-table {
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
  .pets-table th {
    color: var(--primary);
    font-weight: 700;
    padding: 14px 10px;
    border-top: none;
    font-size: 1rem;
    border-top-left-radius: 13px;
    border-top-right-radius: 13px;
    border-bottom: 2px solid #ede6fe;
  }
  .pets-table th:last-child { border-top-right-radius: 13px;}
  .pets-table th:first-child { border-top-left-radius: 13px;}
  .pets-table td {
    padding: 13px 10px;
    border-bottom: 1.1px solid #ede6fb;
    color: #232650;
    font-weight: 500;
    vertical-align: middle;
    background: transparent;
  }
  .pets-table tr:last-child td { border-bottom: none;}
  .pets-table td:last-child { text-align: right; }
  /* Badges */
  .badge {
    display: inline-block;
    padding: 0.19em 1.1em;
    border-radius: 999px;
    font-size: 0.95em;
    font-weight: 600;
    margin-right: 2px;
    margin-bottom: 2px;
    border: 1px solid transparent;
    letter-spacing: 0.03em;
    background: #f7f5fe;
    color: #8457c4;
  }
  .badge.green { background: #dbffe9; color: #15b76c; border: 1.5px solid #aaf2da; }
  .badge.red { background: #ffe6ea; color: #d7263d; border: 1.5px solid #febebe;}
  .badge.yellow { background: #fff9dc; color: #b08502; border: 1.5px solid #fbe1a3;}
  .badge.gray { background: #f4f5fd; color: #50507d; border: 1.5px solid #eee;}
  /* Status tags */
  .status-badge {
    background: var(--light-gray);
    color: var(--primary);
    border: 1px solid var(--card-border);
  }
  
  .status-green { 
    background: #d5f5e3; 
    color: var(--success);
    border: 1px solid var(--success);
  }
  
  .status-red { 
    background: #fadbd8; 
    color: var(--danger);
    border: 1px solid var(--danger);
  }
  
  .status-yellow { 
    background: #fef9e7; 
    color: var(--warning);
    border: 1px solid var(--warning);
  }
  .status-dead {
    background: #212529;
    color: #fff;
    border: 1px solid #212529;
  }
  /* Table action btns */
  .table-btn {
    font-weight: 600;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 1em;
    border: none;
    margin-bottom: 5px;
    margin-top: 2px;
    margin-left: 2px;
    transition: background .18s, color .15s,transform .14s;
    cursor: pointer;
    box-shadow: 0 2px 8px #e5d8fa06;
    min-width: 112px;
    display: inline-block;
  }
  .btn-danger {
    background: var(--red);
    color: #fff;
  }
  .btn-danger:hover { background: #aa1836; }
  .btn-success {
    background: var(--green);
    color: #fff;
  }
  .btn-success:hover { background: #129458; }
  .btn-info {
    background: #e14949ed;
    text-decoration:none;
    color: #fff;
  }


  .btn-info:hover { background: #9f3232ed; }
  .btn-disabled, .btn-disabled:disabled {
    background: #ece8fe;
    color: #bcb3e0;
    cursor: not-allowed;
    font-style: italic;
  }
  .btn-view {
    background: #f1c40f;
    text-decoration:none;
    color: #fff;
  }
  .btn-view:hover { background: #b08f0bff; }
  @media (max-width: 900px) {
    .floating-card { padding: 18px 5px 12px 5px;}
    .profile-img { width: 50px; height: 50px; }
    .welcome-title { font-size: 1.3rem; }
    .subtitle { font-size: 0.97rem; }
    .card-section { padding: 13px 2px 7px 2px; }
  }
  @media (max-width: 600px) {
    .container { padding: 8px 2px;}
    .floating-card, .card-section { border-radius: 11px; padding: 7px 0 3px 0;}
    .header-flex { flex-direction: column; align-items: flex-start;}
    .profile-img { width: 40px; height: 40px;}
    .section-title-row { flex-direction: column; gap: 13px; align-items: flex-start;}
    .section-title { font-size: 1.06rem;}
    .pets-table th, .pets-table td { font-size: .94rem; padding: 7px 2px;}
  }
  .card-section{
    padding: 10px;
  }
  
  
  /* Update button styles */
  .btn-warning {
    background: var(--warning);
    border: none;
    color: var(--primary-dark);
  }
  
  .btn-success {
    background: var(--success);
    border: none;
    color: var(--white);
  }
  
  .btn-danger {
    background: var(--danger);
    border: none;
    color: var(--white);
  }
  
  .btn-secondary {
    background: var(--gray);
    border: none;
    color: var(--white);
  }
  /* Ensures modal appears above other elements */
.modal-backdrop {
    z-index: 1050 !important; /* Make sure backdrop is above other elements */
}

.modal {
    z-index: 1051 !important; /* Ensure modal content is above the backdrop */
}
.table-btn{
  width: 150px;
  text-align: center;
}
table {
  border-collapse: separate;
  border-spacing: 10px; /* adjust as needed */
}

  
</style>
</head>
<body>
  <div class="container-wrapper">
    <div class="position-relative z-3"> 
      <?php include 'header.php'; ?>
    </div>
    <div class="container position-relative z-1">
      <!-- HEADER CARD -->

      <!-- PETS TABLE SECTION -->
      <section class="card-section">
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="section-title-row border-bottom">
          <div class="owner-icon">
            <i class="fas fa-list"></i>
          </div>
          <div class="owner-title-section">
            <h5 class="owner-title">Your Registered Animals</h5>
          </div>
        </div>
        <div class="table-responsive" style="position: relative; z-index: 2; padding-top: 10px;"> <!-- Added table-responsive class -->
          <table class="table table-striped table-hover pets-table"> <!-- Added Bootstrap classes -->
            <thead>
              <tr>
                <th class="text-center text-nowrap ">ANIMAL NAME</th>
                <th class="text-center text-nowrap">SPECIES</th>
                <th class="text-center text-nowrap">BREED</th>
                <th class="text-center text-nowrap">BIRTHDATE</th>
                <th class="text-center text-nowrap">COLOR</th>
                <th class="text-center text-nowrap">BEHAVIOR</th>
                <th class="text-center text-nowrap">VACCINATION</th>
                <th class="text-center text-nowrap">STATUS</th>
                <th class="text-center text-nowrap">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($animal = $animals->fetch_assoc()): ?>
              <tr>
                <td class="text-center"><?php echo htmlspecialchars($animal['animal_name']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($animal['species']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($animal['breed']); ?></td>
                <td class="text-center"><?php echo date('M j, Y', strtotime($animal['birthdate'])); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($animal['color']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($animal['behavior']); ?></td>
                <td>
                  <?php if ($animal['vaccination_status'] === 'Vaccinated'): ?>
                    <span class="badge green">Vaccinated</span>
                  <?php else: ?>
                    <span class="badge red">Not Vaccinated</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $status = strtolower($animal['status']);
                    $statusClass = 'gray';
                    $statusText = strtoupper($status ?: 'UNKNOWN');
                    if ($status === 'safe') { $statusClass = 'green'; $statusText='SAFE'; }
                    elseif ($status === 'lost') { $statusClass = 'red'; $statusText='LOST'; }
                    elseif ($status === 'found') { $statusClass = 'yellow'; $statusText='FOUND'; }
                    elseif ($status === 'dead') { $statusClass = 'dead'; $statusText='DEAD'; }
                  ?>
                  <span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </td>
                <td>
                  <?php if ($status === 'safe'): ?>
                    <button class="table-btn btn-danger report-lost-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      </i>Report Lost
                    </button>
                    <button class="table-btn btn-dark mark-dead-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Mark Dead
                    </button>
                  <?php elseif ($status === 'found'): ?>
                    <button class="table-btn btn-success mark-safe-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Mark as Safe
                    </button>
                    <button class="table-btn btn-danger report-lost-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Report Lost Again
                    </button>
                    <button class="table-btn btn-dark mark-dead-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Mark Dead
                    </button>
                  <?php elseif ($status === 'dead'): ?>
                    <a href="animal_reports.php?animal_id=<?php echo $animal['animal_id']; ?>"
                      class="table-btn btn-info">
                      View Reports
                    </a>
                    <a href="scan_map.php?animal_id=<?php echo $animal['animal_id']; ?>"
                      class="table-btn btn-view">
                      View Scans
                    </a>
                  <?php else: ?>
                    <button class="table-btn btn-success report-found-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Mark Found
                    </button>
                    <a href="animal_reports.php?animal_id=<?php echo $animal['animal_id']; ?>"
                      class="table-btn btn-info">
                      View Reports
                    </a>
                    <a href="scan_map.php?animal_id=<?php echo $animal['animal_id']; ?>"
                      class="table-btn btn-view">
                      View Scans
                    </a>
                    <button class="table-btn btn-dark mark-dead-btn"
                            data-animal-id="<?php echo $animal['animal_id']; ?>"
                            data-animal-name="<?php echo htmlspecialchars($animal['animal_name']); ?>">
                      Mark Dead
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

    <!-- Report Lost Confirmation Modal -->
    <div class="modal fade" id="reportLostModal" tabindex="-1" aria-labelledby="reportLostModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header bg-warning text-white">
                      <h5 class="modal-title" id="reportLostModalLabel">Report Pet as Lost</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to report <strong><span id="petNameLost"></span></strong> as lost?</p>
                      <p class="text-danger">This will notify all users that your pet is missing.</p>
                  </div>
                  <div class="modal-footer">
                      <form id="reportLostForm" method="post">
                          <input type="hidden" name="animal_id" id="animalIdLost">
                          <input type="hidden" name="status" value="lost">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-warning">Confirm Report</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>

      <!-- Report Found Confirmation Modal -->
      <div class="modal fade" id="reportFoundModal" tabindex="-1" aria-labelledby="reportFoundModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                      <h5 class="modal-title" id="reportFoundModalLabel">Report Pet as Found</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to report <strong><span id="petNameFound"></span></strong> as found?</p>
                      <p class="text-success">This will update your pet's status to found.</p>
                  </div>
                  <div class="modal-footer">
                      <form id="reportFoundForm" method="post">
                          <input type="hidden" name="animal_id" id="animalIdFound">
                          <input type="hidden" name="status" value="found">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-success">Confirm Found</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>

      <!-- Mark as Safe Confirmation Modal -->
      <div class="modal fade" id="markSafeModal" tabindex="-1" aria-labelledby="markSafeModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                      <h5 class="modal-title" id="markSafeModalLabel">Mark Pet as Safe</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to mark <strong><span id="petNameSafe"></span></strong> as safe?</p>
                      <p class="text-primary">This will update your pet's status to safe and notify that your pet is back home.</p>
                  </div>
                  <div class="modal-footer">
                      <form id="markSafeForm" method="post">
                          <input type="hidden" name="animal_id" id="animalIdSafe">
                          <input type="hidden" name="status" value="safe">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Mark as Safe</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>

      <!-- Mark Dead Confirmation Modal -->
      <div class="modal fade" id="markDeadModal" tabindex="-1" aria-labelledby="markDeadModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header bg-dark text-white">
                      <h5 class="modal-title" id="markDeadModalLabel">Mark Pet as Dead</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to mark <strong><span id="petNameDead"></span></strong> as dead?</p>
                      <p class="text-dark">This status cannot be undone without administrator support.</p>
                  </div>
                  <div class="modal-footer">
                      <form id="markDeadForm" method="post">
                          <input type="hidden" name="animal_id" id="animalIdDead">
                          <input type="hidden" name="status" value="dead">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-dark">Confirm</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>

      <!-- Bootstrap & JavaScript -->
      <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
      <script>
          // Your existing JavaScript code
          document.querySelectorAll('.report-lost-btn').forEach(button => {
              button.addEventListener('click', function() {
                  const animalId = this.getAttribute('data-animal-id');
                  const petName = this.getAttribute('data-animal-name');
                  
                  document.getElementById('petNameLost').textContent = petName;
                  document.getElementById('animalIdLost').value = animalId;
                  
                  const modal = new bootstrap.Modal(document.getElementById('reportLostModal'));
                  modal.show();
              });
          });

          document.querySelectorAll('.report-found-btn').forEach(button => {
              button.addEventListener('click', function() {
                  const animalId = this.getAttribute('data-animal-id');
                  const petName = this.getAttribute('data-animal-name');
                  
                  document.getElementById('petNameFound').textContent = petName;
                  document.getElementById('animalIdFound').value = animalId;
                  
                  const modal = new bootstrap.Modal(document.getElementById('reportFoundModal'));
                  modal.show();
              });
          });

          document.querySelectorAll('.mark-safe-btn').forEach(button => {
              button.addEventListener('click', function() {
                  const animalId = this.getAttribute('data-animal-id');
                  const petName = this.getAttribute('data-animal-name');
                  
                  document.getElementById('petNameSafe').textContent = petName;
                  document.getElementById('animalIdSafe').value = animalId;
                  
                  const modal = new bootstrap.Modal(document.getElementById('markSafeModal'));
                  modal.show();
              });
          });

          document.querySelectorAll('.mark-dead-btn').forEach(button => {
              button.addEventListener('click', function() {
                  const animalId = this.getAttribute('data-animal-id');
                  const petName = this.getAttribute('data-animal-name');
                  document.getElementById('petNameDead').textContent = petName;
                  document.getElementById('animalIdDead').value = animalId;
                  const modal = new bootstrap.Modal(document.getElementById('markDeadModal'));
                  modal.show();
              });
          });

          // QR Scanner
          let qrScanner;
          function onScanSuccess(decodedText) {
              console.log("Scanned QR Code:", decodedText);
              document.getElementById('qr-result').innerText = decodedText;

              const qrModal = bootstrap.Modal.getInstance(document.getElementById('qrScannerModal'));
              if (qrModal) qrModal.hide();

              setTimeout(() => {
                  new bootstrap.Modal(document.getElementById('successModal')).show();
              }, 500);

              setTimeout(() => {
                  window.location.href = decodedText.startsWith("http") ? decodedText 
                      : "animal_details.php?id=" + encodeURIComponent(decodedText);
              }, 2500);
          }

          function onScanError(errorMessage) {
              console.error("QR Scan Error:", errorMessage);
          }

          document.addEventListener("DOMContentLoaded", function() {
              document.getElementById("qrScannerModal").addEventListener("shown.bs.modal", function() {
                  if (!qrScanner) {
                      qrScanner = new Html5QrcodeScanner("qr-reader", { 
                          fps: 10, 
                          qrbox: { width: 250, height: 250 } 
                      });
                      qrScanner.render(onScanSuccess, onScanError);
                  }
              });

              document.getElementById("qrScannerModal").addEventListener("hidden.bs.modal", function() {
                  if (qrScanner) {
                      qrScanner.clear();
                      qrScanner = null;
                  }
              });
          });
          // Add these to your existing JavaScript
  let map;
  let markers = [];

  function initFoundReportsModal(animalId, petName) {
      document.getElementById('modalPetName').textContent = petName;
      
      // Initialize empty map
      initMap();
      
      // Load reports via AJAX
      loadFoundReports(animalId);
  }

  function initMap() {
      if (map) {
          map.remove();
      }
      
      map = L.map('reportsMap').setView([14.5995, 120.9842], 13); // Default to Manila coordinates
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);
      
      // Clear existing markers
      markers.forEach(marker => map.removeLayer(marker));
      markers = [];
  }
  function loadFoundReports(animalId) {
      fetch(`get_found_reports.php?animal_id=${animalId}`)
          .then(response => response.json())
          .then(data => {
              const reportsList = document.getElementById('reportsList');
              reportsList.innerHTML = '';
              
              if (data.length === 0) {
                  reportsList.innerHTML = `
                      <div class="text-center py-5">
                          <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                          <p>No found reports yet for this pet.</p>
                      </div>
                  `;
                  return;
              }
              
              data.forEach((report, index) => {
                  // Create list item
                  const reportItem = document.createElement('div');
                  reportItem.className = `list-group-item report-card ${index === 0 ? 'active' : ''}`;
                  reportItem.innerHTML = `
                      <div class="d-flex justify-content-between align-items-start">
                          <div>
                              <h6 class="mb-1">Reported by: ${report.reporter_name}</h6>
                              <small class="report-time">${new Date(report.report_time).toLocaleString()}</small>
                          </div>
                          <span class="badge bg-primary">#${report.report_id}</span>
                      </div>
                      <p class="mb-1 mt-2">${report.message || 'No additional message'}</p>
                      ${report.image_proof ? `<img src="report_proof/${report.image_proof}" class="report-image mt-2" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="report_proof/${report.image_proof}">` : ''}
                  `;
                  
                  // Add click event to focus on marker
                  reportItem.addEventListener('click', () => {
                      document.querySelectorAll('.report-card').forEach(card => {
                          card.classList.remove('active');
                      });
                      reportItem.classList.add('active');
                      map.setView([report.latitude, report.longitude], 15);
                  });
                  
                  reportsList.appendChild(reportItem);
                  
                  // Add marker to map
                  const marker = L.marker([report.latitude, report.longitude]).addTo(map)
                      .bindPopup(`
                          <b>Reported by:</b> ${report.reporter_name}<br>
                          <b>Time:</b> ${new Date(report.report_time).toLocaleString()}<br>
                          <b>Phone:</b> ${report.reporter_phone || 'N/A'}<br>
                          <b>Address:</b> ${report.reporter_address || 'N/A'}<br>
                          <b>Message:</b> ${report.message || 'None'}<br>
                          ${report.image_proof ? `<img src="report_proof/${report.image_proof}" class="img-fluid mt-2">` : ''}
                      `);
                  
                  markers.push(marker);
                  
                  // Fit bounds to show all markers
                  if (index === 0) {
                      map.setView([report.latitude, report.longitude], 15);
                  }
              });
              
              // Fit map to show all markers
              if (data.length > 1) {
                  const group = new L.featureGroup(markers);
                  map.fitBounds(group.getBounds().pad(0.2));
              }
          })
          .catch(error => {
              console.error('Error loading reports:', error);
              document.getElementById('reportsList').innerHTML = `
                  <div class="alert alert-danger">
                      Failed to load reports. Please try again later.
                  </div>
              `;
          });
  }

  // Image preview modal
  const imageModal = new bootstrap.Modal(document.createElement('div').innerHTML = `
      <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <div class="modal-header">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center">
                      <img id="modalImage" src="" class="img-fluid" style="max-height: 70vh;">
                  </div>
              </div>
          </div>
      </div>
  `).firstChild;
  document.body.appendChild(imageModal);

  document.addEventListener('click', function(e) {
      if (e.target.classList.contains('report-image')) {
          document.getElementById('modalImage').src = e.target.getAttribute('data-image');
          new bootstrap.Modal(document.getElementById('imageModal')).show();
      }
  });

  // Add this to your existing event listeners
  document.addEventListener("DOMContentLoaded", function() {
      // Add click event to view found reports button (you'll need to add this button)
      document.querySelectorAll('.view-reports-btn').forEach(button => {
          button.addEventListener('click', function() {
              const animalId = this.getAttribute('data-animal-id');
              const petName = this.getAttribute('data-animal-name');
              initFoundReportsModal(animalId, petName);
              new bootstrap.Modal(document.getElementById('foundReportsModal')).show();
          });
      });
  });
      </script>
  </body>
  </html>
