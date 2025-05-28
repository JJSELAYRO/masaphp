<?php
require_once(__DIR__ . '/../config/db.php');

// Get room id from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: website.php");
    exit();
}
$room_id = intval($_GET['id']);

// Get room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: website.php");
    exit();
}

// For image preview
$imgSrc = (!empty($room['image_path']) && file_exists(__DIR__ . '/../' . $room['image_path']))
    ? '../' . htmlspecialchars($room['image_path'])
    : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80';

$statusBadge = [
    'vacant' => 'bg-primary',
    'occupied' => 'bg-success',
    'maintenance' => 'bg-warning text-dark'
];

// Handle application form submission
$application_submitted = false;
$application_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_room'])) {
    $applicant_name = trim($_POST['applicant_name'] ?? '');
    $applicant_email = trim($_POST['applicant_email'] ?? '');
    $applicant_phone = trim($_POST['applicant_phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($applicant_name && $applicant_email && $applicant_phone) {
        // Insert application to DB
        $stmt = $conn->prepare("INSERT INTO room_applications (room_id, applicant_name, applicant_email, applicant_phone, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param('issss', $room_id, $applicant_name, $applicant_email, $applicant_phone, $message);
        $stmt->execute();
        $stmt->close();
        $application_submitted = true;
    } else {
        $application_error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apartment #<?= htmlspecialchars($room['number']) ?> Details | PropertyPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3f37c9;
            --primary-light: #4895ef;
            --accent: #4cc9f0;
            --success: #43aa8b;
            --warning: #f8961e;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #f1f3f5;
        }
        
        body {
            font-family: 'Manrope', sans-serif;
            background-color: #fafbff;
            color: var(--dark);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }
        
        .apartment-header {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(63, 55, 201, 0.05) 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .apartment-img {
            border-radius: 16px;
            object-fit: cover;
            width: 100%;
            height: 400px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.1);
        }
        
        .status-badge {
            font-size: 1rem;
            padding: 0.5em 1.25em;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .detail-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.05);
            transition: all 0.3s ease;
        }
        
        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.1);
        }
        
        .detail-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .application-form {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.05);
            padding: 2rem;
            border: 1px solid rgba(67, 97, 238, 0.1);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(67, 97, 238, 0.2);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .success-message {
            background: rgba(67, 238, 156, 0.1);
            border-left: 4px solid var(--success);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }
        
        .developer-credit {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 3rem;
            text-align: center;
        }
        
        .developer-credit a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .developer-credit a:hover {
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="apartment-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <a href="website.php#apartments" class="btn btn-outline-primary back-btn mb-4"><i class="fas fa-arrow-left me-2"></i>Back to Listings</a>
                    <h1 class="mb-3">Apartment  <?= htmlspecialchars($room['number']) ?></h1>
                    <span class="badge <?= $statusBadge[$room['status']] ?? 'bg-secondary' ?> status-badge text-capitalize">
                        <?= htmlspecialchars($room['status']) ?>
                    </span>
                    <p class="lead mb-0">Floor <?= htmlspecialchars($room['floor']) ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="website.php#apartments" class="btn btn-primary px-4"><i class="fas fa-building me-2"></i>View All Apartments</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <img src="<?= $imgSrc ?>" alt="Apartment #<?= htmlspecialchars($room['number']) ?>" class="apartment-img w-100 mb-4">
                
                <?php if(!empty($room['description'])): ?>
                <div class="card detail-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4"><i class="fas fa-info-circle text-primary me-2"></i>Description</h4>
                        <div class="card-text"><?= nl2br(htmlspecialchars($room['description'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card detail-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4"><i class="fas fa-map-marker-alt text-primary me-2"></i>Location Details</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="detail-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Building</h6>
                                        <p class="text-muted mb-0">PropertyPro Tower</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="detail-icon">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Floor</h6>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($room['floor']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="detail-icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Room Number</h6>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($room['number']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="detail-icon">
                                        <i class="fas fa-ruler-combined"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Size</h6>
                                        <p class="text-muted mb-0">Approx. 45 sqm</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5">
                <?php if ($application_submitted): ?>
                <div class="success-message">
                    <div class="text-center mb-3">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h3 class="mb-3">Application Submitted!</h3>
                    </div>
                    <p class="text-center">Thank you for your interest in our property. We have received your application and it is currently under review.</p>
                    <p class="text-center mb-0">Our property management team will contact you within 12-24 hours regarding the next steps.</p>
                </div>
                <?php else: ?>
                <div class="application-form">
                    <h3 class="mb-4"><i class="fas fa-file-signature text-primary me-2"></i>Apply for this Apartment</h3>
                    
                    <?php if ($application_error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($application_error) ?></div>
                    <?php endif; ?>
                    
                    <form method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="applicant_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="applicant_name" name="applicant_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="applicant_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="applicant_email" name="applicant_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="applicant_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="applicant_phone" name="applicant_phone" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Additional Information</label>
                            <textarea class="form-control" id="message" name="message" rows="3" placeholder="Tell us about your move-in timeline, special requests, etc."></textarea>
                        </div>
                        <button type="submit" name="apply_room" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-paper-plane me-2"></i>Submit Application
                        </button>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="small text-muted">By submitting this form, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card detail-card mt-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4"><i class="fas fa-question-circle text-primary me-2"></i>Have Questions?</h4>
                        <p class="card-text">Our leasing team is available to answer any questions you may have about this apartment or the application process.</p>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-phone text-primary me-3"></i>
                            <span>09056937413</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-envelope text-primary me-3"></i>
                            <span>PropertyPro@gmail.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="developer-credit">
            <p class="mb-1">Powered by <a href="#" target="_blank">J7 IT Solutions and Services</a></p>
            <p class="mb-0">Developed by <a href="#" target="_blank">Jazel Jade Selayro</a></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>