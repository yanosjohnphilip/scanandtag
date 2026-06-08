<?php
// Start the session
include '../db/conn.php';



if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];

// Get owner details including password
$owner_stmt = $conn->prepare("SELECT first_name, last_name, middle_name, email, phone, address, barangay, birthdate, city, province, status, password FROM owners WHERE owner_id = ?");
$owner_stmt->bind_param("i", $owner_id);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owners = $owner_result->fetch_assoc();
$owner_stmt->close();

// Get owner's registered animals
$stmt = $conn->prepare("SELECT animal_id, animal_name, species, breed, birthdate, color, behavior,qr_code, vaccination_status, status, animal_image FROM animals WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$animals = $stmt->get_result();
$stmt->close();
$conn->close();

$animal_list = [];
$total_animals = 0;
$not_vaccinated = 0;
$domestic_count = 0;
$livestock_count = 0;
while ($animal = $animals->fetch_assoc()) {
    $total_animals++;
    if (strtolower($animal['vaccination_status']) !== 'vaccinated') {
        $not_vaccinated++;
    }
    // Classify animal as domestic or livestock
    $species = strtolower($animal['species']);
    if (in_array($species, ['dog', 'cat'])) {
        $domestic_count++;
    } elseif (in_array($species, ['cow', 'goat', 'carabao'])) {
        $livestock_count++;
    }
    $animal_list[] = $animal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../images/scanAndTagLogo.png" />
  <title>Owner Dashboard</title>
  <link href="https://fonts.googleapis.com/css?family=Inter:400,600,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/owner_index.css">
</head>
<body>
  <div class="container-wrapper w-100">
    <div class="position-relative z-3">
      <?php include 'header.php'; ?>
    </div>
    <div class="container mt-4 position-relative z-1">
      <!-- Owner Details Section -->
      <div class="owner-details-container mb-4">
        <div class="card">
          <div class="card-header owner-header">
            <div class="owner-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="owner-title-section">
              <h5 class="owner-title">Owner Information</h5>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm edit-btn" onclick="toggleEditMode()">
                  <i class="fas fa-edit me-1"></i>Edit Details
                </button>
                <button class="btn btn-outline-success btn-sm" style="border: 2px solid #28a745; color: #28a745;" onclick="showChangePasswordModal()">
                  <i class="fas fa-key me-1"></i>Change Password
                </button>
              </div>
          </div>
            </div>
          
          <div class="card-body owner-content">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold ">Full Name</label>
                  <div class="info-value" id="display-name">
                    <?= htmlspecialchars(($owners['first_name'] ?? '') . ' ' . ($owners['middle_name'] ?? '') . ' ' .($owners['last_name'] ?? '')) ?>
                  </div>
                  <input type="hidden" id="first_name" value="<?= htmlspecialchars($owners['first_name'] ?? '') ?>">
                  <input type="hidden" id="middle_name" value="<?= htmlspecialchars($owners['middle_name'] ?? '') ?>">
                  <input type="hidden" id="last_name" value="<?= htmlspecialchars($owners['last_name'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label fw-bold ">Email</label>
                  <div class="info-value editable-value" id="display-email">
                    <?= htmlspecialchars($owners['email'] ?? 'N/A') ?>
                  </div>
                  <div class="info-edit editable-info" id="edit-email" style="display: none;">
                    <input type="email" class="form-control" id="email" 
                           value="<?= htmlspecialchars($owners['email'] ?? '') ?>" placeholder="Email Address">
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label fw-bold">Phone</label>
                  <div class="info-value editable-value" id="display-phone">
                    <?= htmlspecialchars($owners['phone'] ?? 'N/A') ?>
                  </div>
                  <div class="info-edit editable-info" id="edit-phone" style="display: none;">
                    <input 
                        type="tel" 
                        class="form-control" 
                        id="phone"
                        value="<?= htmlspecialchars($owners['phone'] ?? '') ?>" 
                        placeholder="Phone Number"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                        maxlength="11"
                    >
                </div>

                </div>
              </div>
              
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold ">Birthdate</label>
                  <div class="info-value" id="display-birthdate">
                    <?= !empty($owners['birthdate']) ? date('M d, Y', strtotime($owners['birthdate'])) : 'N/A' ?>
                  </div>
                  <input type="hidden" id="birthdate" value="<?= htmlspecialchars($owners['birthdate'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label fw-bold ">Address</label>
                  <div class="info-value" id="display-address">
                    <?php 
                    $full_address = [];
                    if (!empty($owners['address'])) {
                        $full_address[] = htmlspecialchars($owners['address']);
                    }
                    if (!empty($owners['barangay'])) {
                        $full_address[] = htmlspecialchars($owners['barangay']);
                    }
                    if (!empty($owners['city'])) {
                        $full_address[] = htmlspecialchars($owners['city']);
                    }
                    if (!empty($owners['province'])) {
                        $full_address[] = htmlspecialchars($owners['province']);
                    }
                    echo !empty($full_address) ? implode(', ', $full_address) : 'N/A';
                    ?>
                  </div>
                  <input type="hidden" id="address" value="<?= htmlspecialchars($owners['address'] ?? '') ?>">
                  <input type="hidden" id="barangay" value="<?= htmlspecialchars($owners['barangay'] ?? '') ?>">
                  <input type="hidden" id="city" value="<?= htmlspecialchars($owners['city'] ?? '') ?>">
                  <input type="hidden" id="province" value="<?= htmlspecialchars($owners['province'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label fw-bold ">Status</label>
                  <div class="info-value">
                    <span class="badge bg-<?= $owners['status'] === 'verified' ? 'success' : ($owners['status'] === 'pending' ? 'warning' : 'danger') ?>">
                      <?= ucfirst(htmlspecialchars($owners['status'])) ?>
                    </span>
                  </div>
                </div>
                
               
              </div>
            </div>
            
            <div class="edit-actions mt-4 pt-3 border-top" id="edit-actions" style="display: none;">
              <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-success" onclick="saveOwnerDetails()">
                  <i class="fas fa-save me-1"></i>Save Changes
        </button>
                <button class="btn btn-secondary" onclick="cancelEdit()">
                  <i class="fas fa-times me-1"></i>Cancel
        </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="container position-relative z-1">
      <div class="row"><!-- Statistics Card (Left) -->
        <div class="col-md-8 mb-4">
          <div class="card">
            <div class="card-header">
              <div class="owner-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
              <div class="owner-title-section">
                <h5 class="owner-title">Statistics</h5>
              </div>
            </div>
            <div class="card-body" style="min-height: 550px;">
              <div class="mb-3">
                <span class="fw-bold">Total Animals:</span>
                <span class="badge ms-6 fs-6 text-black"><?php echo $total_animals; ?></span>
              </div>
              <div class="row mt-4">
                <div class="col-md-6 col-12 mb-4">
                  <div class="mt-2 row-mt-2" >
                    <canvas id="animalStatsChart" width="100" height="100"></canvas>
                  </div>
                </div>
                <div class="col-md-6 col-12 mb-4">
                  <div class="mt-2 row-mt-2" >
                    <canvas id="animalTypeChart" width="100" height="100"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Inline Animal Info Card -->
          <div id="animalInfoInline" class="card my-4" style="display:block;">
            <div class="card-header">
              <div class="owner-icon">
                <i class="fas fa-paw"></i>
            </div>
              <div class="owner-title-section">
                <h5 class="owner-title">Animal Details</h5>
                <button id="editImageBtn" class="btn btn-success btn-sm"  ><i class="fas fa-edit"></i> Edit Image</button>
              </div>
            </div>
            <div class="card-body">
               <?php    
                  if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']); // Clear the error message after displaying
                  }

                  if (isset($_SESSION['success'])) {
                      echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                      unset($_SESSION['success']); // Clear the success message after displaying
                  }
                ?>
              <!-- Default message shown when no animal is selected -->
              <div id="animalDefaultMessage" class="alert alert-success text-center mb-0">

                <div class="default-icon">
                  <i class="fas fa-info-circle fa-3x mb-3 text-success"></i>
                </div>
                <h4 class="mb-3">No Animal Selected</h4>
                <p class="text-muted">Please Selected Animal to view details.</p>
                       
              </div>
              <div id="animalDetailsRow" class="row align-items-center" style="display:none;">
                <div class="col-md-6 text-center mb-3 mb-md-0" style="position:relative;">
                  <img id="inlineAnimalImg" src="" class="modal-animal-img mb-3" alt="Animal Image" style="width:280px;height:280px;"
                       onerror="this.onerror=null;this.src='../images/animals/default_animal.png';">
                       <!-- Camera icon overlay, initially hidden -->
                       <span id="cameraIconOverlay" style="display:none;position:absolute;top: 64%;left: 50%;transform:translate(-50%,-50%);z-index:2;cursor:pointer;">
                         <i class="fas fa-camera fa-2x text-secondary" style="background:rgba(217, 215, 215, 0.7);border-radius:25%;padding:10px;"></i>
                       </span>
                       <p><span id="inlineAnimalName" class="fw-bold fs-3 "></span></p>
                       <!-- Edit Image Inline Controls -->
                       <div id="editImageInlineControls" style="display:none;">
                         <form id="editImageForm" action="api/update_animal_image.php" method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                           <input type="hidden" name="animal_id" id="editAnimalIdInline">
                           <!-- File input is now hidden, only triggered by camera overlay -->
                           <input type="file" name="animal_image" id="animalImageInput" accept="image/*" style="display:none;" required>

                           <div class="mt-2">
                             <button type="submit" class="btn btn-success btn-sm me-2" id="confirmEditBtn">Confirm</button>
                             <button type="button" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</button>
                           </div>
                         </form>
                       </div>
                     </div>
                     <div class="col-md-6 text-start">
                       
                       <p><span class="fw-bold">Species:</span> <span id="inlineAnimalSpecies" class="text-muted"></span></p>
                       <p><span class="fw-bold">Breed:</span> <span id="inlineAnimalBreed" class="text-muted">-</span></p>
                       <p><span class="fw-bold">Birthdate:</span> <span id="inlineAnimalBirthdate" class="text-muted">-</span></p>
                       <p><span class="fw-bold">Color:</span> <span id="inlineAnimalColor" class="text-muted">-</span></p>
                       <p><span class="fw-bold">Behavior:</span> <span id="inlineAnimalBehavior" class="text-muted">-</span></p>
                       <p><span class="fw-bold">Vaccination:</span> <span id="inlineAnimalVaccination" class="text-muted">-</span></p>
                       <p><span class="fw-bold">Status:</span> <span id="inlineAnimalStatus" class="text-muted"></span></p>
                     </div>
              </div>
            </div>
          </div>
          
        </div>
        <!-- Registered Animals Card (Right) -->
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <div class="owner-icon">
                <i class="fas fa-list"></i>
              </div>
              <div class="owner-title-section">
                <h5 class="owner-title">Registered Animals</h5>
              </div>
            </div>
            <div class="card-body" style="height: 550px;">
              <?php if (empty($animal_list)): ?>
                <div class="alert alert-info text-center">
                  No animals registered yet.
                </div>
              <?php else: ?>
                <div class="animal-card-row">
                  <?php foreach ($animal_list as $animal): ?>
                    <div class="animal-card h-100 "
                      data-animal='<?php echo htmlspecialchars(json_encode($animal), ENT_QUOTES, 'UTF-8'); ?>'>
                      <div class="row align-items-center w-100">
                        <div class="col-2 text-center py-2">
                          <?php 
                            $image_path = !empty($animal['animal_image']) ? $animal['animal_image'] : '';
                            $full_image_path = '../admin/' . $image_path;
                            if ($image_path !== '' && file_exists('../admin/' . $image_path)) : ?>
                              <img src="<?php echo htmlspecialchars($full_image_path); ?>" class="animal-img-preview" alt="Animal Image" style="width:50px;height:50px;">
                            <?php else: 
                              // Choose icon based on species
                              $species = strtolower($animal['species']);
                              $icon = 'fa-paw'; // default
                              if ($species === 'cat') $icon = 'fa-cat';
                              elseif ($species === 'dog') $icon = 'fa-dog';
                              elseif ($species === 'carabao') $icon = 'fa-hippo';
                              elseif ($species === 'goat') $icon = 'fa-otter';
                              elseif ($species === 'cow') $icon = 'fa-cow';
                            ?>
                              <div class="bg-success rounded-circle d-flex align-items-center justify-content-center animal-img-preview" style="width:50px;height:50px;">
                                <i class="fas <?php echo $icon; ?> fa-2x text-white"></i>
                              </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-8">
                          <div class="card-body py-2 px-3">
                            <h6 class="card-title mb-2 fs-5"><?php echo htmlspecialchars($animal['animal_name']); ?></h6>
                            <div class="mb-1"><span class="badge bg-info fs-7"><?php echo htmlspecialchars($animal['species']); ?></span></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Add QR Code Container here -->
          <div class="card shadow-sm mt-4">
           <div class="card-header">
               <div class="owner-icon">
                 <i class="fas fa-qrcode"></i>
               </div>
               <div class="owner-title-section">
                 <h5 class="owner-title">Animal QR Code</h5>
               </div>
            </div>
            <div class="card-body">
            

              <div id="qrCodeNote" class="alert alert-success text-center mb-0" >
                <div class="default-icon">
                  <i class="fas fa-info-circle fa-3x mb-3 text-success"></i>
                </div>
                <h4 class="mb-3">No Animal Selected</h4>
                <p class="text-muted">Please Selected Animal to view details.</p>
              </div>
              

              <div id="animalQrContainer"  class="row align-items-center" >
                <div id="animalQrCode" style="display: inline-block;"></div>
              </div>
              
           
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
 
</div>
  <!-- Animal Info Modal -->
  <!-- The modal has been removed as requested -->
 
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Chart.js for animal statistics
    const ctx = document.getElementById('animalStatsChart').getContext('2d');
    const animalStatsChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Vaccinated', 'Not Vaccinated'],
        datasets: [{
          data: [<?php echo $total_animals - $not_vaccinated; ?>, <?php echo $not_vaccinated; ?>],
          backgroundColor: ['#198754', '#dc3545'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'bottom'
          },
          title: {
            display: true,
            text: 'Vaccination Status of Registered Animals'
          }
        }
      }
    });
    // Pie chart for domestic vs livestock
    const ctxType = document.getElementById('animalTypeChart').getContext('2d');
    const animalTypeChart = new Chart(ctxType, {
      type: 'doughnut',
      data: {
        labels: ['Domestic', 'Livestock'],
        datasets: [{
          data: [<?php echo $domestic_count; ?>, <?php echo $livestock_count; ?>],
          backgroundColor: ['#0d6efd', '#ffc107'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'bottom'
          },
          title: {
            display: true,
            text: 'Animal Class'
          }
        }
      }
    });
    // Variable to store the currently selected animal ID
    let selectedAnimalId = null;
    
    // Animal Card Click Handler
    document.querySelectorAll('.animal-card').forEach(function(card) {
  card.addEventListener('click', function() {
    const animal = JSON.parse(this.getAttribute('data-animal'));
    selectedAnimalId = animal.animal_id; // Store selected animal ID
    let imgSrc = '';
    if (animal.animal_image && animal.animal_image !== '' && animal.animal_image !== null) {
      imgSrc = '../admin/' + animal.animal_image;
    }
    // Status badge logic
    let statusBadge = '';
    if (animal.status) {
      const status = animal.status.toLowerCase();
      if (status === 'safe') statusBadge = '<span class="badge bg-success">SAFE</span>';
      else if (status === 'lost') statusBadge = '<span class="badge bg-danger">LOST</span>';
      else if (status === 'found') statusBadge = '<span class="badge bg-warning text-dark">FOUND</span>';
      else if (status === 'dead') statusBadge = '<span class="badge bg-dark">DEAD</span>';
      else statusBadge = '<span class="badge bg-secondary">' + animal.status + '</span>';
    } else {
      statusBadge = '';
    }
    // Show details, hide default message
    document.getElementById('animalDetailsRow').style.display = 'flex';
    document.getElementById('animalDefaultMessage').style.display = 'none';
    document.getElementById('editImageBtn').style.display = 'block'; // Ensure this line is executed
    document.getElementById('animalInfoInline').style.display = 'block';
    document.getElementById('inlineAnimalName').textContent = animal.animal_name ? animal.animal_name.toUpperCase() : 'No Animal Selected';
    document.getElementById('inlineAnimalImg').src = imgSrc || '../images/animals/default_animal.png';
    document.getElementById('inlineAnimalSpecies').textContent = animal.species || 'Please select an animal to view details.';
    document.getElementById('inlineAnimalBreed').textContent = animal.breed || '-';
    document.getElementById('inlineAnimalBirthdate').textContent = animal.birthdate ? new Date(animal.birthdate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';

    document.getElementById('inlineAnimalColor').textContent = animal.color || '-';
    document.getElementById('inlineAnimalBehavior').textContent = animal.behavior || '-';
    document.getElementById('inlineAnimalVaccination').innerHTML =
      (animal.vaccination_status && animal.vaccination_status.toLowerCase() === 'vaccinated')
        ? '<span class="badge bg-success">Vaccinated</span>'
        : '<span class="badge bg-danger">Not Vaccinated</span>';
    document.getElementById('inlineAnimalStatus').innerHTML = statusBadge;
    document.getElementById('animalDetailsRow').scrollIntoView({ behavior: 'smooth', block: 'center' });
   
    // --- QR CODE DISPLAY LOGIC ---
    const qrDiv = document.getElementById('animalQrCode');
    const qrNote = document.getElementById('qrCodeNote');
    const qrContainer = document.getElementById('animalQrContainer');
    const downloadBtn = document.getElementById('downloadQrBtn');
    qrDiv.innerHTML = '';
    
    if (animal.qr_code && animal.qr_code !== '') {
      // Ensure the path points to the QR folder in admin directory
      let qrPath = animal.qr_code;
      if (!qrPath.startsWith('../admin/QR/')) {
        if (qrPath.startsWith('QR/')) {
          qrPath = '../admin/' + qrPath;
        } else {
          qrPath = '../admin/QR/' + qrPath;
        }
      }
      
      // Create the QR code display with the same style as provided
      qrDiv.innerHTML = `
      <div class="qr-code-section">
        <img src="${qrPath}" 
            alt="Animal QR Code" 
            class="img-fluid" 
            style="width: 250px; height: 250px;background-color: #e8f5e9;
            border-radius: 10px;
            padding: 15px;
            text-align: center;"
            id="qrCodeImage">
        <p class="small mt-1 fs-6 text-muted"><strong>${animal.animal_name ? animal.animal_name.toUpperCase() : 'Animal'} Identification QR</strong></p>
        <button id="downloadQrBtn" class="btn btn-sm btn-outline-success" style="border:1px solid green;">
            <i class="fas fa-download me-1"></i> Download QR
        </button>
      </div>
      `;
      
      // Show QR container, hide note
      qrContainer.style.display = 'block';
      qrNote.style.display = 'none';
      
      // Add download handler to the new button
      document.getElementById('downloadQrBtn').addEventListener('click', function() {
        const qrCode = document.getElementById('qrCodeImage');
        if (!qrCode) return;
        const link = document.createElement('a');
        link.href = qrPath;
        link.download = animal.animal_name ? 
          `ID${animal.animal_id}.png` : 
          `ID${animal.animal_id}.png`;
        link.click();
      });
    } else {
      qrNote.style.display = 'block';
      qrContainer.style.display = 'none';
    }
    // --- END QR CODE DISPLAY LOGIC ---
  });
});

    // Set default values and show default message on page load
    window.addEventListener('DOMContentLoaded', function() {
      document.getElementById('animalDetailsRow').style.display = 'none';
      document.getElementById('animalDefaultMessage').style.display = 'block';
      document.getElementById('editImageBtn').style.display = 'none';
      document.getElementById('inlineAnimalName').textContent = '';
      document.getElementById('inlineAnimalImg').src = 'images/default_animal.png';
      document.getElementById('inlineAnimalSpecies').textContent = '';
      document.getElementById('inlineAnimalBreed').textContent = '-';
      document.getElementById('inlineAnimalBirthdate').textContent = '-';
      document.getElementById('inlineAnimalColor').textContent = '-';
      document.getElementById('inlineAnimalBehavior').textContent = '-';
      document.getElementById('inlineAnimalVaccination').textContent = '-';
      document.getElementById('inlineAnimalStatus').textContent = '';
      document.getElementById('editImageBtn').style.display = 'none';
    });
    // Edit Image Button Handler
     
  // Edit Image Button Handler - show inline controls and camera overlay
  // ... existing code ...
// Store the original image src before editing
let originalAnimalImgSrc = '';

document.getElementById('editImageBtn').addEventListener('click', function() {
  // Store the current image src so we can restore it on cancel
  originalAnimalImgSrc = document.getElementById('inlineAnimalImg').src;
  document.getElementById('editImageInlineControls').style.display = 'block';
  document.getElementById('editAnimalIdInline').value = selectedAnimalId;
  document.getElementById('animalImageInput').value = '';
  document.getElementById('cameraIconOverlay').style.display = 'block';
});

// Hide camera icon overlay and controls when cancel is clicked
document.getElementById('cancelEditBtn').addEventListener('click', function() {
  document.getElementById('editImageInlineControls').style.display = 'none';

  document.getElementById('cameraIconOverlay').style.display = 'none';
  // Restore the original animal image in the main container
  document.getElementById('inlineAnimalImg').src = originalAnimalImgSrc;
});

// Clicking the camera icon triggers the file input
document.getElementById('cameraIconOverlay').addEventListener('click', function() {
  document.getElementById('animalImageInput').click();
});

// Preview selected image in the main animal image container
document.getElementById('animalImageInput').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(evt) {
      // Show preview in the main animal image container
      document.getElementById('inlineAnimalImg').src = evt.target.result;
      // Optionally, also show in the preview image if you want
      document.getElementById('animalImagePreview').src = evt.target.result;
      document.getElementById('animalImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    // Reset preview if no file selected
    document.getElementById('animalImagePreview').src = '';
    document.getElementById('animalImagePreview').style.display = 'none';
  }
});
window.addEventListener('DOMContentLoaded', function() {
  // ... existing code ...
  // Check if a selected_animal is present in the URL
  const urlParams = new URLSearchParams(window.location.search);
  const selectedAnimalIdFromUrl = urlParams.get('selected_animal');
  if (selectedAnimalIdFromUrl) {
    // Find the animal card and trigger its click event to show details and scroll
    const card = Array.from(document.querySelectorAll('.animal-card')).find(card => {
      const animal = JSON.parse(card.getAttribute('data-animal'));
      return animal.animal_id == selectedAnimalIdFromUrl;
    });
    if (card) {
      card.click();
      // Scroll after the details are shown
      setTimeout(function() {
        document.getElementById('animalDetailsRow').scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300); // Delay to ensure details are visible
    }
  }
});

// Owner Details Edit Functions
let isEditMode = false;
let originalData = {};

function toggleEditMode() {
  isEditMode = !isEditMode;
  
  if (isEditMode) {
    // Store original data
    originalData = {
      first_name: document.getElementById('first_name').value,
      middle_name: document.getElementById('middle_name').value,
      last_name: document.getElementById('last_name').value,
      email: document.getElementById('email').value,
      phone: document.getElementById('phone').value,
      birthdate: document.getElementById('birthdate').value,
      address: document.getElementById('address').value,
      barangay: document.getElementById('barangay').value,
      city: document.getElementById('city').value,
      province: document.getElementById('province').value
    };
    
    // Show edit fields for editable values only
    document.querySelectorAll('.editable-info').forEach(edit => edit.style.display = 'block');
    document.querySelectorAll('.editable-value').forEach(value => value.style.display = 'none');
    document.getElementById('edit-actions').style.display = 'block';
    document.querySelector('.edit-btn').innerHTML = '<i class="fas fa-times me-1"></i>Cancel Edit';
    document.querySelector('.edit-btn').classList.remove('btn-outline-primary');
    document.querySelector('.edit-btn').classList.add('btn-outline-danger');
  } else {
    cancelEdit();
  }
}

function cancelEdit() {
  isEditMode = false;
  
  // Restore original data
  Object.keys(originalData).forEach(key => {
    const element = document.getElementById(key);
    if (element) {
      element.value = originalData[key];
    }
  });
  
  // Hide edit fields for editable values only
  document.querySelectorAll('.editable-info').forEach(edit => edit.style.display = 'none');
  document.querySelectorAll('.editable-value').forEach(value => value.style.display = 'block');
  document.getElementById('edit-actions').style.display = 'none';
  document.querySelector('.edit-btn').innerHTML = '<i class="fas fa-edit me-1"></i>Edit Details';
  document.querySelector('.edit-btn').classList.remove('btn-outline-danger');
  document.querySelector('.edit-btn').classList.add('btn-outline-primary');
}

function saveOwnerDetails() {
  // Collect form data
  const formData = new FormData();
  formData.append('action', 'update_owner');
  formData.append('first_name', document.getElementById('first_name').value);
  formData.append('middle_name', document.getElementById('middle_name').value);
  formData.append('last_name', document.getElementById('last_name').value);
  formData.append('email', document.getElementById('email').value);
  const phoneValue = document.getElementById('phone').value.trim();
  const phonePattern = /^09\d{9}$/;
  if (phoneValue !== '' && !phonePattern.test(phoneValue)) {
    showNotification('Phone number must be 11 digits and start with 09', 'error');
    return;
  }
  formData.append('phone', phoneValue);
  formData.append('birthdate', document.getElementById('birthdate').value);
  formData.append('address', document.getElementById('address').value);
  formData.append('barangay', document.getElementById('barangay').value);
  formData.append('city', document.getElementById('city').value);
  formData.append('province', document.getElementById('province').value);
  
  // Show loading state
  const saveBtn = document.querySelector('.edit-actions .btn-success');
  const originalText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
  saveBtn.disabled = true;
  
  // Send AJAX request
  fetch('api/update_owner_details.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    console.log('Response status:', response.status);
    return response.json();
  })
  .then(data => {
    console.log('Response data:', data);
    if (data.success) {
      // Update display values
      const firstName = document.getElementById('first_name').value;
      const middleName = document.getElementById('middle_name').value;
      const lastName = document.getElementById('last_name').value;
      const fullName = [firstName, middleName, lastName].filter(name => name.trim() !== '').join(' ');
      
      document.getElementById('display-name').textContent = fullName;
      document.getElementById('display-email').textContent = document.getElementById('email').value;
      document.getElementById('display-phone').textContent = document.getElementById('phone').value;
      document.getElementById('display-birthdate').textContent = 
        new Date(document.getElementById('birthdate').value).toLocaleDateString('en-US', {
          year: 'numeric', month: 'short', day: 'numeric'
        });
      
      // Update address display
      const addressParts = [
        document.getElementById('address').value,
        document.getElementById('barangay').value,
        document.getElementById('city').value,
        document.getElementById('province').value
      ].filter(part => part.trim() !== '');
      document.getElementById('display-address').textContent = 
        addressParts.length > 0 ? addressParts.join(', ') : 'N/A';
      
      // Show success message
      showNotification('Owner details updated successfully!', 'success');
      
      // Refresh the page after a short delay
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } else {
      showNotification(data.message || 'Failed to update owner details', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    console.error('Error details:', error.message);
    showNotification('An error occurred while updating details: ' + error.message, 'error');
  })
  .finally(() => {
    // Restore button state
    saveBtn.innerHTML = originalText;
    saveBtn.disabled = false;
  });
}

function showNotification(message, type) {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
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

// Password change functions
function showChangePasswordModal() {
  const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
  
  // Pre-fill current password for testing (remove in production)
  const storedPassword = '<?php echo addslashes($owner['password'] ?? ''); ?>';
  
  // Check if password looks like a hash or plain text
  if (storedPassword.startsWith('$2')) {
    // It's a hashed password, can't pre-fill
    document.getElementById('currentPassword').placeholder = 'Enter your current password';
  } else {
    // It's plain text, pre-fill it
    document.getElementById('currentPassword').value = storedPassword;
  }
  
  modal.show();
}

function togglePasswordVisibility(inputId, button) {
  const passwordInput = document.getElementById(inputId);
  const icon = button.querySelector('i');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
    button.setAttribute('title', 'Hide password');
  } else {
    passwordInput.type = 'password';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
    button.setAttribute('title', 'Show password');
  }
}

function changePassword() {
  const currentPassword = document.getElementById('currentPassword').value;
  const newPassword = document.getElementById('newPassword').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  
  // Validation
  if (!currentPassword || !newPassword || !confirmPassword) {
    showNotification('Please fill in all password fields', 'error');
    return;
  }
  
  if (newPassword !== confirmPassword) {
    showNotification('New passwords do not match', 'error');
    return;
  }

  const requirements = [
    { test: pw => pw.length >= 8, message: 'Password must be at least 8 characters long.' },
    { test: pw => !/\s/.test(pw), message: 'Password must not contain spaces.' },
    { test: pw => /[A-Z]/.test(pw), message: 'Password must contain at least one uppercase letter.' },
    { test: pw => /[a-z]/.test(pw), message: 'Password must contain at least one lowercase letter.' },
    { test: pw => /[0-9]/.test(pw), message: 'Password must contain at least one number.' },
    { test: pw => /[^A-Za-z0-9]/.test(pw), message: 'Password must contain at least one special character.' }
  ];

  for (const requirement of requirements) {
    if (!requirement.test(newPassword)) {
      showNotification(requirement.message, 'error');
      return;
    }
  }
  
  const changeBtn = document.getElementById('changePasswordBtn');
  const originalText = changeBtn.innerHTML;
  changeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Changing...';
  changeBtn.disabled = true;
  
  // Send password change request
  fetch('change_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      current_password: currentPassword,
      new_password: newPassword
    })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Get the response text first to debug
    return response.text().then(text => {
      console.log('Raw response:', text);
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('JSON parse error:', e);
        console.error('Response text:', text);
        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
      }
    });
  })
  .then(data => {
    if (data.success) {
      showNotification('Password changed successfully!', 'success');
      // Close modal and reset form
      bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
      document.getElementById('changePasswordForm').reset();
    } else {
      showNotification(data.message || 'Failed to change password', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred while changing password', 'error');
  })
  .finally(() => {
    changeBtn.innerHTML = originalText;
    changeBtn.disabled = false;
  });
}
     
  </script>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">
          <i class="fas fa-key me-2"></i>Change Password
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="currentPassword" required>
              <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('currentPassword', this)" title="Show password">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="newPassword" required minlength="8">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPassword', this)" title="Show password">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
            <div class="form-text">Password must be at least 8 characters long.</div>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirmPassword" required>
              <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirmPassword', this)" title="Show password">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        
        <button type="button" class="btn btn-success" id="changePasswordBtn" onclick="changePassword()">
          <i class="fas fa-key me-1"></i>Change Password
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

</body>

</html>


