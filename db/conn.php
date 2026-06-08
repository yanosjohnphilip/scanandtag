<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} // Start the session

$host = "localhost";
$user = "root"; 
$pass = "";
$dbname = "u879336411_scanandtag";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// List of publicly accessible pages (No login required)
$public_pages = ['animal.php','owner_preregister.php', 'save_scan.php', 'insert_repots.php', 'report_form.php','logout.php']; // Add other public pages here

//  List of pages that owners can access
$owner_pages = [ 'change_password.php','update_animal_image.php','update_owner_details.php','pending_owners.php','owner_notification.php','index_owner.php','header.php','owner_registered_animal.php', 'login.php', 'logout.php', 'scan_map.php', 'fetch_scan_history.php', 'save_scan.php', 'insert_reports.php',  'animal_reports.php', 'get_found_reports.php'];
// Redirect to login if user is NOT logged in and the page is NOT public
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['owner_id']) && !in_array($current_page, $public_pages) && $current_page !== 'login.php') {
    header("Location: ../login.php");
    exit();
}

//  Prevent owners from accessing admin pages
if (isset($_SESSION['owner_id']) && !in_array($current_page, array_merge($owner_pages, $public_pages))) {
    header("Location:owner/index_owner.php");
    exit();
}

// Prevent admins from accessing owner pages
if (isset($_SESSION['admin_id']) && in_array($current_page, $owner_pages)) {
    header("Location:admin/index.php");
    exit();
}

?>