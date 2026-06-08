<?php
include '../db/conn.php';
// Start the session
session_start();

// Ensure admin_id is set
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

if (isset($_POST['register_animal'])) {
    // Collect form data
    $owner_id = $_POST['owner_id'];
    $animal_name = $_POST['animal_name'];
    $birthdate = $_POST['birthdate'];
    $color = $_POST['color'];
    $behavior = $_POST['behavior'];
    $animal_class = $_POST['animal_class'];
    $species = $_POST['species'];
    $breed = $_POST['breed'];
    $sex = $_POST['sex'];
    $vaccination_status = $_POST['vaccination_status'];

    // Check if an animal with the same details already exists for this owner
    $checkQuery = "SELECT a.animal_name, a.species, a.breed, a.color, o.first_name, o.last_name 
                  FROM animals a 
                  JOIN owners o ON a.owner_id = o.owner_id 
                  WHERE a.owner_id = ? 
                  AND LOWER(a.animal_name) = LOWER(?) 
                  AND LOWER(a.species) = LOWER(?) 
                  AND LOWER(a.breed) = LOWER(?) 
                  AND LOWER(a.color) = LOWER(?)";
    
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('issss', $owner_id, $animal_name, $species, $breed, $color);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existing = $checkResult->fetch_assoc();
        $ownerName = htmlspecialchars($existing['first_name'] . ' ' . $existing['last_name']);
        $animalDetails = sprintf(
            "Name: %s, Species: %s, Breed: %s, Color: %s",
            htmlspecialchars($existing['animal_name']),
            htmlspecialchars($existing['species']),
            htmlspecialchars($existing['breed']),
            htmlspecialchars($existing['color'])
        );
        $_SESSION['error_message'] = "An identical animal already exists for $ownerName. Details: $animalDetails";
        header("Location: animal_Registration_Form.php");
        exit();
    }
    $checkStmt->close();

    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['animal_image']) && $_FILES['animal_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/animals/';
        $ext = pathinfo($_FILES['animal_image']['name'], PATHINFO_EXTENSION);
        $imagePath = $uploadDir . 'animal_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['animal_image']['tmp_name'], $imagePath);
    }

    // Fetch admin name
    $admin_name = '';
    if ($admin_id !== null) {
        $admin_name_stmt = $conn->prepare("SELECT first_name, last_name FROM admins WHERE admin_id = ?");
        $admin_name_stmt->bind_param('i', $admin_id);
        $admin_name_stmt->execute();
        $admin_name_stmt->bind_result($first_name, $last_name);
        if ($admin_name_stmt->fetch()) {
            $admin_name = $first_name . ' ' . $last_name;
        }
        $admin_name_stmt->close();
    } else {
        $_SESSION['error_message'] = " Admin ID is not set.";
        header("Location: animal_Registration_Form.php");
        exit();
    }

    // Insert animal data including image_path
    $query = "INSERT INTO animals (owner_id, animal_name, birthdate, sex, color, behavior, animal_class, species, breed, vaccination_status, animal_image, added_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssssssssss', $owner_id, $animal_name, $birthdate, $sex, $color, $behavior, $animal_class, $species, $breed, $vaccination_status, $imagePath, $admin_name);
    $stmt->execute();

    $animal_id = $conn->insert_id;

    // Insert medication info if applicable
    if ($vaccination_status === 'Vaccinated' && isset($_POST['medicine_ids'])) {
        $medicine_ids = $_POST['medicine_ids'];
        $medicine_dates = $_POST['medicine_dates']; // Array of dates for each individual medicine

        // Prepare statement to check if med_id exists
        $check_med_stmt = $conn->prepare("SELECT med_id FROM medication WHERE med_id = ?");

        foreach ($medicine_ids as $med_id) {
            // Only require medicine ID
            if ($med_id === '') {
                continue;
            }

            // Get the corresponding date for this specific medicine
            $date_given = isset($medicine_dates[$med_id]) ? trim($medicine_dates[$med_id]) : '';

            // Only proceed if we have both medicine ID and date
            if ($date_given === '') {
                continue;
            }

            // Validate med_id exists
            $check_med_stmt->bind_param('i', $med_id);
            $check_med_stmt->execute();
            $result = $check_med_stmt->get_result();
            
            if ($result->num_rows > 0 && $admin_id !== null) {
                $med_query = "INSERT INTO animal_medication (animal_id, med_id, date_given, added_by) 
                              VALUES (?, ?, ?, ?)";
                $med_stmt = $conn->prepare($med_query);
                $med_stmt->bind_param('iiss', $animal_id, $med_id, $date_given, $admin_name);
                $med_stmt->execute();
                $med_stmt->close();

                $adminQuery = "SELECT admin_id FROM admins";
                $adminResult = $conn->query($adminQuery);
        
            } // else skip invalid med_id or missing admin_id
        }
        $check_med_stmt->close();
    }

    // Generate QR code
    $qr_data = "http://localhost/CTVET(%20FINAL%20BACK%20UP%20FOR%20FINAL%20DEFENSE)/animal.php?id=" . $animal_id;
    $qr_code_path = "QR/ID" . $animal_id . ".png";
    file_put_contents($qr_code_path, file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data)));

    // Update animal with QR path
    $stmt = $conn->prepare("UPDATE animals SET qr_code = ? WHERE animal_id = ?");
    $stmt->bind_param("si", $qr_code_path, $animal_id);
    $stmt->execute();
    $stmt->close();

    // Redirect
    header("Location: success.php?animal_id=" . $animal_id);
    exit();
} else {
    $_SESSION['error_message'] = "Error: " . $stmt->error;
    header("Location: animal_Registration_Form.php");
    exit();
}

$conn->close();
?>
