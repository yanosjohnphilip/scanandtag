<?php
include __DIR__ . '/../../db/conn.php';
// Ensure session is started to access session variables
$admin_id = $_SESSION['admin_id'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Get admin details
$adminStmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$adminStmt->bind_param("i", $admin_id);
$adminStmt->execute();
$admin_result = $adminStmt->get_result();
$admin_details = $admin_result->fetch_assoc();
$adminStmt->close();

// Query to count unread notifications
// Get admin's full name for comparison
$adminNameQuery = "SELECT CONCAT(first_name, ' ', last_name) as admin_name FROM admins WHERE admin_id = ?";
$adminStmt = $conn->prepare($adminNameQuery);
$adminStmt->bind_param("i", $admin_id);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();
$adminName = $adminResult->fetch_assoc()['admin_name'];
$adminStmt->close();

$unreadQuery = "SELECT COUNT(*) as unread_count 
                FROM notification n 
                LEFT JOIN lost_found_history lf ON n.lf_id = lf.lf_id 
                WHERE n.admin_id = ? 
                AND n.is_read = 0 
                AND (n.lf_id IS NULL OR lf.updated_by != ?)";
$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param("is", $admin_id, $adminName);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
$unreadStmt->close();


?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #198754;
    --primary-dark: #388E3C;
    --danger: #F44336;
    --dark: #333;
    --light: #f4f4f4;
    --gray: #757575;
    --white: #fff;
    --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.admin-header {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 24px;
    height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.admin-header .header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}
.admin-header .icon-btn {
    background: none;
    border: none;
    position: relative;
    font-size: 1.3rem;
    color: var(--dark);
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.2s;
}
.admin-header .icon-btn:hover {
    background: var(--light);
}
.admin-header .notification-badge {
    position: absolute;
    top: 6px;
    right: -2px;
    background: var(--danger);
    color: var(--white);
    border-radius: 50%;
    font-size: 0.75rem;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
.admin-header .profile-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background 0.2s;
}
.admin-header .profile-avatar:hover {
    background: var(--primary-dark);
}
.admin-header .dropdown-menu {
    position: absolute;
    right: 0;
    top: 60px;
    background: var(--white);
    border: 1px solid var(--gray);
    border-radius: 8px;
    box-shadow: var(--shadow);
    min-width: 500px;
    display: none;
    z-index: 200;
}
.admin-header .dropdown-menu.show {
    display: block;
}
.admin-header .dropdown-menu a,
.admin-header .dropdown-menu button {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    color: var(--dark);
    text-decoration: none;
    background: none;
    border: none;
    width: 100%;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
.admin-header .dropdown-menu a:hover,
.admin-header .dropdown-menu button:hover {
    background: var(--light);
}
    .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .header-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            
            color: var(--dark);
        }

        .header-title p {
            color: var(--gray);
            font-size: 0.95rem;
            font-weight: 400;
            margin-top: 5px;
        }

        .action-link {
            display: flex;
            align-items: center;
           
            color: #333;
            text-decoration: none;
        }
     


     

        .header-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .header-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-info {
            display: none;
        }

        @media (min-width: 768px) {
            .profile-info {
                display: block;
            }
            
            .profile-info h4 {
                font-size: 0.95rem;
                font-weight: 600;
            }
            

            .profile-info p {
                font-size: 0.85rem;
                color: var(--gray);
            }
        }
        

</style>

<header class="admin-header">
    <div class="header-actions">
        <!-- Notifications -->
        <button class="icon-btn" id="adminNotifBtn" onclick="window.location.href='notification.php'">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </button>

        <!-- Profile Avatar -->
        <div class="profile-avatar" id="adminProfileBtn">
            <?php echo strtoupper(substr($admin_details['first_name'], 0, 1)); ?>
        </div>

        <!-- Profile Dropdown -->
            
        <div class="dropdown-menu" id="adminProfileMenu" style="min-width: 260px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.12); padding: 20px; background: #fff;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                <div>
                    <div style="font-size: 1.1em; font-weight: 600; color: #222;"><?php echo htmlspecialchars($admin_details['first_name'] . ' ' . $admin_details['middle_name'] . ' ' . $admin_details['last_name']); ?></div>
                    <div style="font-size: 0.95em; color: #666;"> <?php echo htmlspecialchars($admin_details['username']); ?></div>
                </div>
            </div>
            <div style="margin-bottom: 10px; font-size: 0.95em; color: #444;">
                <span style="font-weight: 500;">Role:</span> <span style="color: #2196F3;"> <?php echo htmlspecialchars($admin_details['role_name']); ?></span>
            </div>
            <div style="margin-bottom: 18px; font-size: 0.95em; color: #444;">
                <span style="font-weight: 500;">Registered:</span> <span style="color: #8BC34A;"><?php echo date("F j, Y", strtotime($admin_details['created_at'])); ?></span>
            </div>
            
            <button onclick="showLogoutModal()" style="display: block; width: 100%; text-align: center; color: #fff; background: #f44336; border: none; border-radius: 8px; padding: 10px 0; font-weight: 600; cursor: pointer; transition: background 0.2s;">Logout</button>
        </div>
    </div>
</header>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
        <div style="text-align: center; margin-bottom: 20px;">
            <i class="fas fa-sign-out-alt" style="font-size: 48px; color: #f44336; margin-bottom: 15px;"></i>
            <h3 style="margin: 10px 0; color: #333; font-size: 1.3rem;">Confirm Logout</h3>
            <p style="color: #666; margin: 0;">Are you sure you want to log out?</p>
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="hideLogoutModal()" style="flex: 1; padding: 10px; border: 1px solid #ddd; background: #f5f5f5; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">Cancel</button>
            <a href="../logout.php" style="flex: 1; display: inline-block; text-align: center; padding: 10px; background: #f44336; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.2s;">Logout</a>
        </div>
    </div>
</div>

<script>
const adminProfileBtn = document.getElementById('adminProfileBtn');
const adminProfileMenu = document.getElementById('adminProfileMenu');
const adminNotifBtn = document.getElementById('adminNotifBtn');

// Profile dropdown
adminProfileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    adminProfileMenu.classList.toggle('show');
    adminNotifMenu.classList.remove('show');
});

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    if (!adminProfileMenu.contains(e.target) && !adminProfileBtn.contains(e.target)) {
        adminProfileMenu.classList.remove('show');
    }
});

// Logout Modal Functions
function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        hideLogoutModal();
    }
}

window.addEventListener('click', function(e) {
    if (!e.target.closest('#adminProfileBtn')) adminProfileMenu.classList.remove('show');
});

// Notification function for general use
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
