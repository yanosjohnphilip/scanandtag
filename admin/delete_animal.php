<?php
include '../db/conn.php';

// Check if animal ID is provided
if (!isset($_POST['animal_id']) || empty($_POST['animal_id'])) {
    header("Location: animals.php?error=No animal ID provided");
    exit;
}

$animal_id = intval($_POST['animal_id']);

// First, check if the animal exists
$check_sql = "SELECT animal_id, animal_image FROM animals WHERE animal_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $animal_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: animals.php?error=Animal not found");
    exit;
}

$animal = $check_result->fetch_assoc();
$check_stmt->close();

// Delete medication records first (to maintain referential integrity)
$delete_meds_sql = "DELETE FROM animal_medication WHERE animal_id = ?";
$delete_meds_stmt = $conn->prepare($delete_meds_sql);
$delete_meds_stmt->bind_param("i", $animal_id);
$delete_meds_stmt->execute();
$delete_meds_stmt->close();

// First, delete related notifications
// Find all report_ids associated with this animal
$findReportsQuery = "SELECT report_id FROM found_reports WHERE animal_id = ?";
$findReportsStmt = $conn->prepare($findReportsQuery);
$findReportsStmt->bind_param("i", $animal_id);
$findReportsStmt->execute();
$reportResult = $findReportsStmt->get_result();

// Delete notifications linked to these reports
while ($reportRow = $reportResult->fetch_assoc()) {
    $report_id = $reportRow['report_id'];
    
    // Delete notifications linked to this report
    $deleteNotifQuery = "DELETE FROM notification WHERE report_id = ?";
    $deleteNotifStmt = $conn->prepare($deleteNotifQuery);
    $deleteNotifStmt->bind_param("i", $report_id);
    $deleteNotifStmt->execute();
    $deleteNotifStmt->close();
}
$findReportsStmt->close();

// Also delete notifications linked directly to the animal through scan_history
$findScansQuery = "SELECT scan_id FROM scan_history WHERE animal_id = ?";
$findScansStmt = $conn->prepare($findScansQuery);
$findScansStmt->bind_param("i", $animal_id);
$findScansStmt->execute();
$scanResult = $findScansStmt->get_result();

// Delete notifications linked to these scans
while ($scanRow = $scanResult->fetch_assoc()) {
    $scan_id = $scanRow['scan_id'];
    
    // Delete notifications linked to this scan
    $deleteNotifQuery = "DELETE FROM notification WHERE scan_id = ?";
    $deleteNotifStmt = $conn->prepare($deleteNotifQuery);
    $deleteNotifStmt->bind_param("i", $scan_id);
    $deleteNotifStmt->execute();
    $deleteNotifStmt->close();
}
$findScansStmt->close();

// Now proceed with the original deletion code
// Now delete the animal
$delete_sql = "DELETE FROM animals WHERE animal_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $animal_id);

if ($delete_stmt->execute()) {
    // If the animal had an image, delete it from the server
    if (!empty($animal['animal_image'])) {
        $image_path = $animal['animal_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    header("Location: animals.php?success=Animal deleted successfully");
} else {
    header("Location: animals.php?error=Error deleting animal: " . $conn->error);
}


$delete_stmt->close();
$conn->close();
exit;
?>