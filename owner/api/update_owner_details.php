<?php
// Start output buffering to catch any unwanted output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them

session_start();
include '../../db/conn.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['owner_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$owner_id = $_SESSION['owner_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_owner') {
        // Validate and sanitize input data
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        
        // Basic validation
        if (empty($first_name) || empty($last_name)) {
            echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
            exit();
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }
        
        // Check if email is already taken by another owner
        if (!empty($email)) {
            $check_email_stmt = $conn->prepare("SELECT owner_id FROM owners WHERE email = ? AND owner_id != ?");
            $check_email_stmt->bind_param("si", $email, $owner_id);
            $check_email_stmt->execute();
            $result = $check_email_stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email address is already taken by another owner']);
                exit();
            }
            $check_email_stmt->close();
        }

        // Phone format: digits only, 11 characters, starts with 09
        if ($phone !== '' && !preg_match('/^09\d{9}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits and start with 09']);
            exit();
        }
        
        // Validate birthdate if provided
        if (!empty($birthdate)) {
            $birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
            if (!$birthdate_obj || $birthdate_obj->format('Y-m-d') !== $birthdate) {
                echo json_encode(['success' => false, 'message' => 'Invalid birthdate format']);
                exit();
            }
            
            // Check if birthdate is not in the future
            $today = new DateTime();
            if ($birthdate_obj > $today) {
                echo json_encode(['success' => false, 'message' => 'Birthdate cannot be in the future']);
                exit();
            }
        }
        
        // Update owner details
        $update_stmt = $conn->prepare("UPDATE owners SET 
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            birthdate = ?, 
            address = ?, 
            barangay = ?, 
            city = ?, 
            province = ?
            WHERE owner_id = ?");
        
        $update_stmt->bind_param("ssssssssssi", 
            $first_name, 
            $middle_name, 
            $last_name, 
            $email, 
            $phone, 
            $birthdate, 
            $address, 
            $barangay, 
            $city, 
            $province, 
            $owner_id
        );
        
        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Owner details updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes were made']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update owner details: ' . $update_stmt->error]);
        }
        
        $update_stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}

$conn->close();
?>
