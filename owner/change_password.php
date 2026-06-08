<?php
// Turn off error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(0);

session_start();

// Set JSON header first
header('Content-Type: application/json');

// Use simple database connection without redirects
include '../db/conn.php';

// Check if connection was successful
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // 1. Check if owner is logged in
    if (!isset($_SESSION['owner_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    // 2. Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    $owner_id = $_SESSION['owner_id'];
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    // 3. Validate input
    if (empty($current_password) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Both passwords required']);
        exit();
    }

    $requirements = [
        [
            'message' => 'Password must be at least 8 characters long.',
            'test' => function ($pw) { return strlen($pw) >= 8; }
        ],
        [
            'message' => 'Password must not contain spaces.',
            'test' => function ($pw) { return preg_match('/\s/', $pw) === 0; }
        ],
        [
            'message' => 'Password must contain at least one uppercase letter.',
            'test' => function ($pw) { return preg_match('/[A-Z]/', $pw) === 1; }
        ],
        [
            'message' => 'Password must contain at least one lowercase letter.',
            'test' => function ($pw) { return preg_match('/[a-z]/', $pw) === 1; }
        ],
        [
            'message' => 'Password must contain at least one number.',
            'test' => function ($pw) { return preg_match('/[0-9]/', $pw) === 1; }
        ],
        [
            'message' => 'Password must contain at least one special character.',
            'test' => function ($pw) { return preg_match('/[^A-Za-z0-9]/', $pw) === 1; }
        ],
    ];

    foreach ($requirements as $requirement) {
        if (!$requirement['test']($new_password)) {
            echo json_encode(['success' => false, 'message' => $requirement['message']]);
            exit();
        }
    }

    // 4. RETRIEVE owner's current password from database
    $stmt = $conn->prepare("SELECT password FROM owners WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Owner not found']);
        exit();
    }

    $owner_data = $result->fetch_assoc();
    $stored_password = $owner_data['password'];
    $stmt->close();

    // 5. VERIFY current password matches what owner input
    $password_matches = false;
    
    // Try hashed password first
    if (password_verify($current_password, $stored_password)) {
        $password_matches = true;
    }
    // Try plain text if hash fails
    else if ($current_password === $stored_password) {
        $password_matches = true;
    }

    if (!$password_matches) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }

    // 6. UPDATE password with new password (hashed)
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update_stmt = $conn->prepare("UPDATE owners SET password = ? WHERE owner_id = ?");
    $update_stmt->bind_param("si", $new_password_hash, $owner_id);

    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }

    $update_stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>