<?php
include '../db/conn.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}
// Fetch registered owners
$owners = [];
$result = $conn->query("SELECT owner_id, first_name, last_name FROM owners WHERE status = 'verified' ORDER BY last_name ASC ");
while ($row = $result->fetch_assoc()) {
    $owners[] = $row;
}

// Fetch medicines grouped by type
$medicines = [];
$result = $conn->query("SELECT * FROM medication");
while ($row = $result->fetch_assoc()) {
    $medicines[$row['type']][] = $row;
}

// Retrieve and clear the error message from the session
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
    <title>Animal Registration | City Veterinary System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 CSS for enhanced dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-color: #388E3C;
            --primary-light: #4CAF50;
            --primary-dark: #2E7D32;
            --secondary-color: #FFC107;
            --accent-color: #FF5722;
            --dark: #263238;
            --light: #c6c2c2;
            --gray: #757575;
            --light-gray: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: var(--dark);
            min-height: 100vh;
        }
        
        .card {
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .card-header h4 {
            font-weight: 600;
            margin: 0;
            color: white;
        }
        
        .card-body {
            padding: 2rem;
        }
        .main-content {
         justify-content: center;
         align-items: center;
         padding: 10px;
         margin: auto;
         min-height: 100vh;
     }
        .section-title {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s;
        }
        
        .btn-success:active {
            transform: scale(0.98);
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .input-group-text {
            background-color: var(--light);
            border-radius: 10px 0 0 10px;
        }
        
        .medication-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .medication-type {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        
        .medication-list {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .medication-item {
            padding: 10px 15px;
            margin: 8px 0;
            background-color: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        
        .medication-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .medication-item .form-check {
            margin: 0;
        }
        
        .medication-item .form-check-input:checked + .form-check-label {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .medication-item .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .medicine-date:disabled {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #6c757d;
        }
        
        .medicine-date:enabled {
            background-color: white;
            border-color: var(--primary-color);
        }
        
        .medicine-dosage:disabled {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #6c757d;
        }
        
        .medicine-dosage:enabled {
            background-color: white;
            border-color: var(--primary-color);
        }
        
        .image-upload-container {
            border: 2px dashed var(--light-gray);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .image-upload-container:hover {
            border-color: var(--primary-color);
        }
        
        .image-upload-container i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .image-upload-container p {
            margin-bottom: 0;
            color: var(--gray);
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            margin-top: 1rem;
        }
        
        .animal-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(56, 142, 60, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            height: calc(3.5rem + 2px);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
        }
        
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: 10px;
            border: 1px solid var(--primary-color);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown .select2-search__field {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
        }
        
        .select2-container--bootstrap-5 .select2-dropdown .select2-search__field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.15);
        }
        
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            .main-content{
           
            margin: 0  ;
            
            
        }
        }
    </style>
</head>
<body>
<div class="container ">
    <div >
        <?php include 'templates/sidebar.php'; ?>
    </div>
    <div class="main-content ">
        
            <div class="header">
                <button id="sidebar-toggle" style="display: none;">            
                </button>
                
                <div class="header-title">
                   <h1 class="h3 mt-4 fw-bold">Animal Registration</h1>
                    <p class="mb-5">Owner must be Registered!</p>
                </div>
                <div class="admin-header">
                    <a href="scanner.php" class="action-link">
                        <i class="fas fa-qrcode" style="font-size: large;"></i>
                    </a>
                    <?php include'templates/admin_header.php'; ?>
                </div>
            </div>
            <div class="row ms-12 justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg">
                        <div class="card-header text-center">
                            <h4><i class="fas fa-paw me-2"></i>Animal Registration</h4>
                        </div>
                        <div class="card-body">
                            <form id="animalRegistrationForm" action="register_animal.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Display error message if exists -->
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <div><?php echo $error_message; ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="section-title">
                                    <span class="animal-icon"><i class="fas fa-paw"></i></span>
                                    Animal Information
                                </h5>

                                <div class="row g-3">
                                    <!-- Owner Selection with Search Functionality -->
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="owner_id" class="form-label required-field">
                                                <i class="fas fa-user me-1"></i>Select Owner
                                            </label>
                                            <select name="owner_id" id="ownerSelect" class="form-select" required>
                                                <option value="" selected disabled>-- Search and select owner --</option>
                                                <?php foreach ($owners as $owner): ?>
                                                    <option value="<?php echo $owner['owner_id']; ?>" <?php echo (isset($_POST['owner_id']) && $_POST['owner_id'] == $owner['owner_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($owner['last_name'] . ', ' . $owner['first_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Please select an owner.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Animal Name -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="animal_name" class="form-label required-field">
                                                <i class="fas fa-tag me-1"></i>Animal Name
                                            </label>
                                            <input type="text" name="animal_name" class="form-control" value="<?php echo htmlspecialchars($_POST['animal_name'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please provide animal name.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Sex -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sex" class="form-label required-field">
                                                <i class="fas fa-venus-mars me-1"></i>Gender
                                            </label>
                                            <select name="sex" class="form-select" required>
                                                <option value="" selected disabled>Select Gender</option>
                                                <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                            <div class="invalid-feedback">Please select Gender.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Birthdate -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="birthdate" class="form-label required-field">
                                                <i class="fas fa-birthday-cake me-1"></i>Date of Birth
                                            </label>
                                            <input type="date" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please provide birthdate.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Color -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="color" class="form-label required-field">
                                                <i class="fas fa-palette me-1"></i>Color
                                            </label>
                                            <input type="text" name="color" class="form-control" value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please provide color.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Behavior -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="behavior" class="form-label required-field">
                                                <i class="fas fa-brain me-1"></i>Behavior
                                            </label>
                                            <select name="behavior" class="form-select" required>
                                                <option value="Calm" <?php echo (isset($_POST['behavior']) && $_POST['behavior'] == 'Calm') ? 'selected' : ''; ?>>Calm</option>
                                                <option value="Aggressive" <?php echo (isset($_POST['behavior']) && $_POST['behavior'] == 'Aggressive') ? 'selected' : ''; ?>>Aggressive</option>
                                            </select>
                                            <div class="invalid-feedback">Please select behavior.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Animal Class -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="animal_class" class="form-label required-field">
                                                <i class="fas fa-layer-group me-1"></i>Animal Class
                                            </label>
                                            <select name="animal_class" id="animal_class" class="form-select" required>
                                                <option value="" selected disabled>Select Animal Class</option>
                                                <option value="Domestic" <?php echo (isset($_POST['animal_class']) && $_POST['animal_class'] == 'Domestic') ? 'selected' : ''; ?>>Domestic</option>
                                                <option value="Livestock" <?php echo (isset($_POST['animal_class']) && $_POST['animal_class'] == 'Livestock') ? 'selected' : ''; ?>>Livestock</option>
                                            </select>
                                            <div class="invalid-feedback">Please select animal class.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Species -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="species" class="form-label required-field">
                                                <i class="fas fa-dna me-1"></i>Species
                                            </label>
                                            <select name="species" id="species" class="form-select" required>
                                                <option value="" selected disabled>Select Species</option>
                                            </select>
                                            <div class="invalid-feedback">Please select species.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Breed -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="breed" class="form-label required-field">
                                                <i class="fas fa-dog me-1"></i>Breed
                                            </label>
                                            <input type="text" name="breed" id="breed" class="form-control" value="<?php echo htmlspecialchars($_POST['breed'] ?? ''); ?>" placeholder="Enter breed" required>
                                            <div class="invalid-feedback">Please provide breed.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Vaccination Status -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="vaccination_status" class="form-label required-field">
                                                <i class="fas fa-syringe me-1"></i>Vaccination Status
                                            </label>
                                            <select name="vaccination_status" id="vaccination_status" class="form-select" required>
                                                <option value="Not Vaccinated" <?php echo (isset($_POST['vaccination_status']) && $_POST['vaccination_status'] == 'Not Vaccinated') ? 'selected' : ''; ?>>Not Vaccinated</option>
                                                <option value="Vaccinated" <?php echo (isset($_POST['vaccination_status']) && $_POST['vaccination_status'] == 'Vaccinated') ? 'selected' : ''; ?>>Vaccinated</option>
                                            </select>
                                            <div class="invalid-feedback">Please select vaccination status.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Image Upload -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-camera me-1"></i>Animal Image
                                            </label>
                                            <div class="image-upload-container" onclick="document.getElementById('animal_image_input').click()">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p>Click to upload animal photo</p>
                                                <input type="file" id="animal_image_input" name="animal_image" class="d-none" accept="image/*" capture="environment">
                                                <img id="image_preview" class="image-preview" alt="Animal preview">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Medication Information -->
                                <div id="medication_section" style="display: none;">
                                    <hr class="my-4">
                                    <h5 class="section-title">
                                        <span class="animal-icon"><i class="fas fa-pills"></i></span>
                                        Medication / Vaccines / Vitamins
                                    </h5>
                                    <p class="text-muted mb-4">Select the medications, vaccines, or vitamins given to the animal</p>
                                    
                                    <?php foreach ($medicines as $type => $medList): ?>
                                        <div class="medication-card">
                                            <div class="medication-type">
                                                <i class="fas fa-prescription-bottle-alt me-2"></i>
                                                <?php echo htmlspecialchars($type); ?>
                                            </div>
                                            <div class="medication-list">
                                                <?php foreach ($medList as $medicine): ?>
                                                    <div class="medication-item">
                                                        <div class="row g-3 align-items-center">
                                                            <div class="col-md-4">
                                                                <div class="form-check">
                                                                    <input class="form-check-input medication-checkbox" type="checkbox" 
                                                                           name="medicine_ids[]" 
                                                                           value="<?php echo $medicine['med_id']; ?>" 
                                                                           id="med_<?php echo $medicine['med_id']; ?>"
                                                                           data-med-id="<?php echo $medicine['med_id']; ?>">
                                                                    <label class="form-check-label" for="med_<?php echo $medicine['med_id']; ?>">
                                                                        <?php echo htmlspecialchars($medicine['name']); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Dosage (Optional)</label>
                                                                <input type="text" 
                                                                       name="medicine_dosages[<?php echo $medicine['med_id']; ?>]" 
                                                                       class="form-control medicine-dosage" 
                                                                       id="dosage_med_<?php echo $medicine['med_id']; ?>"
                                                                       placeholder="e.g., 1ml, 2 tablets"
                                                                       disabled>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Date Given</label>
                                                                <input type="date" 
                                                                       name="medicine_dates[<?php echo $medicine['med_id']; ?>]" 
                                                                       class="form-control medicine-date" 
                                                                       id="date_med_<?php echo $medicine['med_id']; ?>"
                                                                       disabled>
                                                            </div>
                                                          
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" name="register_animal" class="btn btn-success btn-lg">
                                        <i class="fas fa-save me-2"></i>Register Animal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // Initialize Select2 for owner dropdown with search functionality
        $(document).ready(function() {
            $('#ownerSelect').select2({
                theme: 'bootstrap-5',
                placeholder: "Search and select owner",
                allowClear: true,
                width: '100%'
            });
        });
    
        // Show/Hide Medication Section Based on Vaccination Status
        document.getElementById("vaccination_status").addEventListener("change", function() {
            let medicationSection = document.getElementById("medication_section");
            if (this.value === "Vaccinated") {
                medicationSection.style.display = "block";
            } else {
                medicationSection.style.display = "none";
            }
        });

        // Image preview functionality
        document.getElementById('animal_image_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('image_preview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Animal Class Change Event
        document.getElementById("animal_class").addEventListener("change", function() {
            let speciesDropdown = document.getElementById("species");
            speciesDropdown.innerHTML = '<option value="" selected disabled>Select Species</option>';
            // Breed is now a text input, no need to reset dropdown

            let animalClass = this.value;
            let speciesOptions = {
                "Domestic": [
                    { value: "Cat", text: "Cat (Felis catus)" },
                    { value: "Dog", text: "Dog (Canis lupus familiaris)" }
                ],
                "Livestock": [
                    { value: "Cow", text: "Cow (Bos taurus)" },
                    { value: "Carabao", text: "Carabao (Bubalus bubalis)" },
                    { value: "Goat", text: "Goat (Capra aegagrus hircus)" }
                ]
            };

            if (speciesOptions[animalClass]) {
                speciesOptions[animalClass].forEach(species => {
                    let option = document.createElement("option");
                    option.value = species.value;
                    option.text = species.text;
                    speciesDropdown.appendChild(option);
                });
            }
        });

        // Species Change Event - Breed is now a text input, no need to populate dropdown

        // Form validation
        (function() {
            'use strict';
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        // Check if location is selected
                        const vaccinationStatus = document.getElementById('vaccination_status').value;
                        
                        if (vaccinationStatus === 'Vaccinated') {
                            let checkedMedicines = Array.from(document.querySelectorAll('input[name="medicine_ids[]"]:checked'));
                            let atLeastOneFilled = false;
                            
                            // Check if at least one medicine is selected and has a corresponding date
                            checkedMedicines.forEach(checkbox => {
                                let medId = checkbox.getAttribute('data-med-id');
                                let correspondingDateInput = document.getElementById('date_med_' + medId);
                                
                                if (correspondingDateInput && correspondingDateInput.value) {
                                    atLeastOneFilled = true;
                                }
                            });
                            
                            if (!atLeastOneFilled) {
                                event.preventDefault();
                                event.stopPropagation();
                                alert('Please select at least one medication/vaccine/vitamin and provide the date given for vaccinated animals.');
                                document.getElementById('medication_section').scrollIntoView({ behavior: 'smooth' });
                                return false;
                            }
                        }
                        
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    
        // Add JavaScript to handle medication selection and date validation
        document.addEventListener('DOMContentLoaded', function() {
            // Handle medication checkbox changes
            document.querySelectorAll('.medication-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    let medId = this.getAttribute('data-med-id');
                    let dateInput = document.getElementById('date_med_' + medId);
                    let dosageInput = document.getElementById('dosage_med_' + medId);
                    
                    // If medicine is selected, enable date and dosage fields
                    if (this.checked) {
                        dateInput.disabled = false;
                        dateInput.required = true;
                        dateInput.style.borderColor = '#dc3545';
                        dateInput.focus();
                        
                        // Enable dosage field (optional)
                        dosageInput.disabled = false;
                        dosageInput.style.borderColor = '#e9ecef';
                    } else {
                        // If medicine is unchecked, disable and clear both fields
                        dateInput.disabled = true;
                        dateInput.required = false;
                        dateInput.value = '';
                        dateInput.style.borderColor = '#e9ecef';
                        
                        dosageInput.disabled = true;
                        dosageInput.value = '';
                        dosageInput.style.borderColor = '#e9ecef';
                    }
                });
            });
        });
    </script>
</body>
</html>