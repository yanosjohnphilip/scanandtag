
<?php
include __DIR__ . '/../../db/conn.php';
// Ensure session is started to access session variables

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];

// Query to count unread notifications for the logged-in admin
$unreadQuery = "SELECT COUNT(*) as unread_count FROM notification WHERE admin_id = ? AND is_read = 0";
$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param("i", $admin_id);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result();
$unreadCount = $unreadResult->fetch_assoc()['unread_count'];
$unreadStmt->close();

// Query to count pending owner registration requests
$pendingRegCount = 0;
$pendingRegQuery = "SELECT COUNT(*) AS pending_count FROM owners WHERE status = 'pending'";
$pendingResult = $conn->query($pendingRegQuery);
if ($pendingResult) {
    $pendingRow = $pendingResult->fetch_assoc();
    $pendingRegCount = isset($pendingRow['pending_count']) ? (int)$pendingRow['pending_count'] : 0;
}

// Get the current script name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>La Carlota City Veterinary Office - Animal Registration</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* Modern Mobile Sidebar Styles for La Carlota City Veterinary Office */
    :root {
      --vet-green: #16a34a;
      --vet-green-dark: #388E3C;
      --vet-green-light: #86efac;
      --primary: #16a34a;
      --primary-dark: #388E3C;
      --white: #fff;
      --dark: #333;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      --sidebar-width: 280px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    body {
      background-color: #f5f5f5;
      color: var(--dark);
      min-height: 100vh;
    }
    /* Modern Sidebar Styles */
    .sidebar {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      
      width: var(--sidebar-width);
      background: #ffffff;
      box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* Veterinary Green Header */
    .sidebar-header {
      background: linear-gradient(135deg, var(--vet-green) 0%, var(--vet-green-dark) 100%);
      padding: 1.5rem 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
      min-height: 100px;
    }

    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: 1rem;
      width: 100%;
    }

    .sidebar-logo img {
      width: 60px;
      height: 60px;
      object-fit: contain;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.2);
      padding: 5px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      flex-shrink: 0;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: var(--transition);
    }
    
    .sidebar-logo:hover img {
      transform: scale(1.05);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .header-text {
      flex: 1;
    }


    .sidebar-logo h2 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
      line-height: 1.3;
      letter-spacing: 0.5px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: var(--transition);
    }
    
    .sidebar-logo:hover h2 {
      transform: translateX(3px);
    }

    .sidebar-logo p {
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.9);
      margin: 0;
      font-weight: 400;
    }

    /* Sidebar Content */
    .sidebar-menu {
      flex: 1;
      padding: 1.5rem 0.75rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: var(--medium-gray) transparent;
    }
    
    .sidebar-menu::-webkit-scrollbar {
      width: 6px;
    }
    
    .sidebar-menu::-webkit-scrollbar-track {
      background: transparent;
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb {
      background-color: var(--medium-gray);
      border-radius: 10px;
    }

    .sidebar-menu::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar-menu::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    /* Navigation Items */
    .menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.8rem 1.25rem;
      margin: 0;
      border-radius: 10px;
      color: var(--dark);
      font-weight: 500;
      font-size: 0.95rem;
      transition: var(--transition);
      position: relative;
      background: transparent;
      text-decoration: none;
      border: none;
      height: 50px;
      overflow: hidden;
    }
    
    .menu-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: var(--vet-green);
      transform: scaleY(0);
      transform-origin: top;
      transition: transform 0.3s ease;
    }

    .menu-item i {
      font-size: 1.2rem;
      width: 24px;
      text-align: center;
      color: var(--text-gray);
      transition: var(--transition);
      position: relative;
      z-index: 1;
    }

    .menu-item span {
      flex: 1;
    }

    .menu-item:hover {
      background: rgba(16, 185, 129, 0.1);
      color: var(--vet-green-dark);
      transform: translateX(4px);
    }
    
    .menu-item:hover::before {
      transform: scaleY(1);
    }

    .menu-item:hover i {
      color: var(--vet-green);
      transform: scale(1.1);
    }

    .menu-item.active {
      background: rgba(16, 185, 129, 0.12);
      color: var(--vet-green-dark);
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
    }
    
    .menu-item.active::before {
      transform: scaleY(1);
    }

    .menu-item.active i {
      color: var(--vet-green);
    }

    /* Notification Badges */
    .menu-badge,
    .notification-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      max-width: 25px;
      height: 30px;
      padding: 0 5px;
      font-size: 0.65rem;
      font-weight: 700;
      border-radius: 50%;
      margin-left: auto;
      background: #ef4444;
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: var(--transition);
      position: relative;
      z-index: 1;
    }
    
    .menu-item:hover .menu-badge,
    .menu-item:hover .notification-badge {
      transform: scale(1.1);
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
    }

    .menu-divider {
      height: 1px;
      background-color: #e5e7eb;
      margin: 0.5rem 1rem;
    }

    /* Logout Link Styling */
    #logoutBtn {
      color: #dc2626 !important;
      margin-top: auto;
      border-radius: 10px !important;
      margin: 1rem 0.5rem 1.5rem;
      background: rgba(220, 38, 38, 0.05) !important;
      border: 1px dashed rgba(220, 38, 38, 0.2) !important;
    }

    #logoutBtn:hover {
      background: rgba(220, 38, 38, 0.1) !important;
      color: #b91c1c !important;
      transform: translateX(4px) !important;
    }

    #logoutBtn i {
      color: #dc2626 !important;
    }

    #logoutBtn:hover i {
      color: #b91c1c !important;
      animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-2px); }
      75% { transform: translateX(2px); }
    }

    /* Sidebar Overlay for Mobile */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1040;
      opacity: 0;
      visibility: hidden;
      transition: var(--transition);
    }

    .sidebar-overlay.show {
      opacity: 1;
      visibility: visible;
    }

    /* Desktop View - Sidebar Always Visible */
    @media (min-width: 769px) {
      .sidebar {
        transform: translateX(0);
        top: 0;
        height: 100vh;
        
      }

      .sidebar-overlay,
      .mobile-header {
        display: none;
      }

      .main-content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
      }

      body {
        padding-top: 0;
      }
    }


    /* Modal Styles */
    .modal-logout {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
      justify-content: center;
      align-items: center;
    }
    .modal-logout-content {
      background-color: #fff;
      margin: auto;
      padding: 30px 24px 20px 24px;
      border-radius: 10px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      width: 90%;
      max-width: 350px;
      text-align: center;
      position: relative;
      
    }
    .modal-logout-content h3 {
      margin-top: 0;
      color: var(--primary-dark);
    }
    .modal-logout-buttons {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-top: 18px;
    }
    .confirm-btn {
      background-color: var(--danger);
      color: #fff;
      border: none;
      padding: 8px 18px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.2s;
    }
    .confirm-btn:hover {
      background-color: var(--primary-dark);
    }
    .cancel-btn {
      background-color: #eee;
      color: #333;
      border: none;
      padding: 8px 18px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.2s;
    }
    .cancel-btn:hover {
      background-color: #ccc;
    }

    /* Hamburger toggle */
/* Floating mobile toggle */
.mobile-toggle {
  display: none;


}
    .menu-section-title {
    padding: 15px 20px 5px 25px; /* Increased left padding to move right */
    font-size: 0.9rem;
    font-weight: 700; /* Made bolder */
    color: #555; /* Slightly darker color for better visibility */
    text-transform: uppercase;
    letter-spacing: 0.5px;
}



/* Sidebar active state for mobile */
@media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
                z-index: 1050;
            }


            .mobile-toggle {
                display: block;
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: var(--primary);
                color: #fff;
                border-radius: 50%;
                width: 55px;
                height: 55px;
                align-items: center;
                justify-content: center;
                font-size: 1.8rem;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                cursor: pointer;
                z-index: 1200;
                
             
            }
            .mobile-toggle i {
                text-align: center;
                margin-top: 13px;
                margin-left: 14px;
              
                
             
            }

            .header-search input {
                width: 180px;
            }
        
              }
      


  .main-content {
      transition: margin-left 0.3s ease;
      margin-left: 210px; 
      
      
  }
  .sidebar-collapsed .main-content {
            margin-left: 40px;
            margin-right: -30px
        }
  
  
      
    .menu-divider {
      height: 1px;
      background-color: #eee;
      margin: 10px 0;
  }


  /* Inline pill badge for menu items */

  /* Collapsible Sidebar */
  .sidebar-toggle {
    position: absolute;
    top: 20px;
    right: -15px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.3s ease;
    z-index: 1001;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }

  .sidebar-toggle:hover {
    background: var(--primary-dark);
    transform: scale(1.1);
  }

  .sidebar.collapsed {
    width: 80px;
  }

  .sidebar.collapsed .sidebar-logo h2,
  .sidebar.collapsed .sidebar-logo p,
  .sidebar.collapsed .menu-item span,
  .sidebar.collapsed .menu-badge {
    display: none;
  }

  .sidebar.collapsed .sidebar-logo {
    justify-content: center;
  }

  .sidebar.collapsed .menu-item {
    justify-content: center;
    padding: 12px;
  }

  .sidebar.collapsed .menu-item i {
    margin: 0;
  }



  /* Mobile Header Bar */
  .mobile-header {
    display: none;
    position: fixed;
    width: 100%;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(135deg, var(--vet-green) 0%, var(--vet-green-dark) 100%);
    z-index: 1100;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 0 1rem;
    align-items: center;
    justify-content: space-between;
  }

  .mobile-header-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .mobile-header-logo img {
    width: 35px;
    height: 35px;
    object-fit: contain;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px;
  }

  .mobile-header-text {
    display: flex;
    flex-direction: column;
  }

  .mobile-header-text h1 {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    line-height: 1.2;
  }

  .mobile-header-text p {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
  }

  .mobile-menu-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s ease;
  }

  .mobile-menu-btn:hover {
    background: rgba(255, 255, 255, 0.1);
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
    /* Show mobile header bar */
    .mobile-header {
      display: flex;
      
    }

    /* Adjust sidebar for mobile */
    .sidebar {
      transform: translateX(-100%);
      top: 60px;
      height: calc(100vh - 60px);
    }

    .sidebar.active {
      transform: translateX(0);
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
    }

    /* Hide sidebar header on mobile (already have mobile header at top) */
    .sidebar-header {
      display: none;
    }

    /* Adjust menu items for mobile */
    .menu-item {
      padding: 1rem 1.5rem;
      font-size: 0.95rem;
    }

    .main-content {
      margin-left: 0 !important;
      padding: 1rem;
       
      padding-top: calc(60px + 1rem);
    }

    
  }
      

  </style>
</head>
<body>

<!-- Mobile Header Bar (Only visible on mobile) -->
<header class="mobile-header">
  <div class="mobile-header-logo">
    <img src="../images/scanAndTagLogo.png" alt="La Carlota City Veterinary Office Logo" onerror="this.style.display='none'" />
    <div class="mobile-header-text">
      <h1>La Carlota City Veterinary Office</h1>
      <p>Animal Registration</p>
    </div>
  </div>
  <button class="mobile-menu-btn" id="mobile-menu-btn">
    <i class="fas fa-bars"></i>
  </button>
</header>

<!-- Modern Mobile Sidebar -->
<aside class="sidebar" id="sidebar">
  <!-- Veterinary Green Header -->
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="../images/scanAndTagLogo.png" alt="La Carlota City Veterinary Office Logo" onerror="this.style.display='none'" />
      <div class="header-text">
        <h2>La Carlota City Veterinary Office</h2>
        <p>Animal Registration</p>
      </div>
    </div>
  </div>
  
  <!-- Menu Items -->
  <div class="sidebar-menu" id="sidebar-modal">
    <a href="index.php" class="menu-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
      <i class="fas fa-th-large"></i>
      <span>Dashboard</span>
    </a>
    
    <a href="owner_registration.php" class="menu-item <?php echo $current_page == 'owner_registration.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-plus"></i>
      <span>Owner Registration</span>
    </a>
    
    <a href="animal_Registration_Form.php" class="menu-item <?php echo $current_page == 'animal_Registration_Form.php' ? 'active' : ''; ?>">
      <i class="fas fa-paw"></i>
      <span>Animal Registration</span>
    </a>
    
    <a href="owners.php" class="menu-item <?php echo $current_page == 'owners.php' ? 'active' : ''; ?>">
      <i class="fas fa-users"></i>
      <span>Registered Owners</span>
    </a>
    
    <a href="animals.php" class="menu-item <?php echo in_array($current_page, ['animals.php', 'animal_details.php', 'edit_animal.php']) ? 'active' : ''; ?>">
      <i class="fas fa-clipboard-list"></i>
      <span>Registered Animals</span>
    </a>
    
    <a href="admin_preregistrations.php" class="menu-item <?php echo $current_page == 'admin_preregistrations.php' ? 'active' : ''; ?>">
      <i class="fas fa-file-alt"></i>
      <span>Registration Request</span>
      <?php if ($pendingRegCount > 0): ?>
        <span class="menu-badge"><?php echo $pendingRegCount; ?></span>
      <?php endif; ?>
    </a>
    
    <a href="register_admin.php" class="menu-item <?php echo $current_page == 'register_admin.php' ? 'active' : ''; ?>">
      <i class="fas fa-user-shield"></i>
      <span>Admin Management</span>
    </a>
    
    <a href="medicine.php" class="menu-item <?php echo $current_page == 'medicine.php' ? 'active' : ''; ?>">
      <i class="fas fa-pills"></i>
      <span>Medicine Management</span>
    </a>
    
    
    <a href="animalReports.php" class="menu-item <?php echo in_array($current_page, ['animalReports.php', 'medicationReports.php', 'lostAndFoundReports.php', 'ownerReports.php', 'staffPerformanceReports.php']) ? 'active' : ''; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Reports</span>
    </a>
    
   
    
    <!-- Logout at Bottom -->
    <a href="" class="menu-item" id="logoutBtn">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div id="logoutModal" class="modal-logout">
  <div class="modal-logout-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to log out?</p>
    <div class="modal-logout-buttons">
      <button id="confirmLogout" class="confirm-btn">Yes, Logout</button>
      <button id="cancelLogout" class="cancel-btn">Cancel</button>
    </div>
  </div>
</div>
<script>

  document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuItems = document.querySelectorAll('.menu-item');
    
    // Check if mobile
    function isMobile() {
      return window.innerWidth <= 768;
    }
    
    // Open sidebar
    function openSidebar() {
      sidebar.classList.add('active');
      overlay.classList.add('show');
      if (isMobile()) {
        document.body.style.overflow = 'hidden';
      }
      // Change icon to X
      const icon = mobileMenuBtn.querySelector('i');
      icon.classList.remove('fa-bars');
      icon.classList.add('fa-times');
    }
    
    // Close sidebar
    function closeSidebar() {
      sidebar.classList.remove('active');
      overlay.classList.remove('show');
      document.body.style.overflow = '';
      // Change icon back to bars
      const icon = mobileMenuBtn.querySelector('i');
      icon.classList.remove('fa-times');
      icon.classList.add('fa-bars');
    }
    
    // Toggle sidebar
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('active')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });
    }
    
    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
      closeSidebar();
    });
    
    // Close sidebar when clicking menu items on mobile
    menuItems.forEach(item => {
      item.addEventListener('click', function(e) {
        if (isMobile() && !item.id.includes('logout')) {
          setTimeout(() => {
            closeSidebar();
          }, 200);
        }
      });
    });
    
    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        closeSidebar();
      }
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        if (!isMobile()) {
          closeSidebar();
        }
      }, 250);
    });
    
    // Smooth scroll to active item
    const activeItem = document.querySelector('.menu-item.active');
    if (activeItem) {
      setTimeout(() => {
        activeItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 100);
    }
    
    
  });

  // Logout Modal Functionality
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutModal = document.getElementById('logoutModal');
  const confirmLogout = document.getElementById('confirmLogout');
  const cancelLogout = document.getElementById('cancelLogout');

  logoutBtn.addEventListener('click', function(e) {
    e.preventDefault();
    logoutModal.style.display = 'flex';
  });

  confirmLogout.addEventListener('click', function() {
    window.location.href = '../logout.php';
  });

  cancelLogout.addEventListener('click', function() {
    logoutModal.style.display = 'none';
  });

  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    if (event.target == logoutModal) {
      logoutModal.style.display = 'none';
    }
  });
</script>


</body>
</html>