<?php
// Start session and include DB connection
include '../../db/conn.php'; // make sure this file contains your DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $animalId = $_POST['animal_id']; // Ensure this line correctly retrieves the animal ID
    $targetDir = "../../admin/images/animals/"; // make sure this folder exists and is writable

    $imagePath = '';
    if (isset($_FILES['animal_image']) && $_FILES['animal_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../admin/images/animals/';
        $ext = strtolower(pathinfo($_FILES['animal_image']['name'], PATHINFO_EXTENSION));
        $fileName = 'animal_' . time() . '.' . $ext;
        $fullUploadPath = $uploadDir . $fileName;
        $dbImagePath = 'images/animals/' . $fileName; // Path to save in database

        // Allow only image file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowedTypes)) {
            // Upload file to server
            if (move_uploaded_file($_FILES["animal_image"]["tmp_name"], $fullUploadPath)) {
                // Update database with relative path
                $sql = "UPDATE animals SET animal_image = ? WHERE animal_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $_SESSION['error'] = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("si", $dbImagePath, $animalId);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Image updated successfully.";
                    } else {
                        $_SESSION['error'] = "Database update failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $_SESSION['error'] = "Failed to upload image. Error: " . $_FILES["animal_image"]["error"];
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    } else {
        $_SESSION['error'] = "No file selected or upload error. Error: " . $_FILES["animal_image"]["error"];
    }

    header("Location: ../index_owner.php?selected_animal=" . $_POST['animal_id']);
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../index_owner.php");
    exit();
}
?>
