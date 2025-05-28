<?php
// Include your database connection
require_once(__DIR__ . '/../config/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PropertyPro | Premium Apartment Living</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Modern Sans Serif -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
            color: var(--dark);
            overflow-x: hidden;
            background-color: #fafbff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }
        
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.85);
            box-shadow: 0 2px 20px rgba(67, 97, 238, 0.08);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            padding: 0.5rem 0;
            box-shadow: 0 4px 30px rgba(67, 97, 238, 0.1);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover i {
            transform: rotate(-15deg);
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.95) 0%, rgba(63, 55, 201, 0.95) 100%), 
                        url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="%23f8f9fa"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" fill="%23f8f9fa"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23f8f9fa"/></svg>');
            background-size: cover;
            background-repeat: no-repeat;
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.25);
        }
        
        .btn-outline-light:hover {
            color: var(--primary);
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
            background: white;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .apartment-card {
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.05);
        }
        
        .apartment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.15);
        }
        
        /* Add this to your existing CSS */
.apartment-card .card-img-container {
    height: 250px; /* Fixed height */
    overflow: hidden; /* Hide overflow */
    position: relative;
}

.apartment-card .card-img-top {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensures image covers the container */
    transition: transform 0.3s ease;
}

.apartment-card:hover .card-img-top {
    transform: scale(1.05); /* Slight zoom on hover */
}
        .amenities-section {
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.9) 0%, rgba(241, 243, 245, 0.9) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .amenities-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Q50,80 0,100 Z" fill="%23ffffff" opacity="0.2"/></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }
        
        .amenity-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .amenity-icon:hover {
            transform: scale(1.1);
            background-color: var(--primary);
            color: white;
        }
        
        .testimonial-card {
            border-radius: 16px;
            border: none;
            background: white;
            box-shadow: 0 5px 25px rgba(67, 97, 238, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(67, 97, 238, 0.15);
        }
        
        .testimonial-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }
        
        .footer {
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 100%);
            color: white;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .section-title {
            position: relative;
            display: inline-block;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            width: 50%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            bottom: -12px;
            left: 0;
            border-radius: 5px;
        }
        
        .property-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .property-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .property-detail-icon {
            color: var(--primary);
            margin-right: 8px;
            font-size: 1.1rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.25);
        }
        
        .nav-pills .nav-link {
            color: var(--dark);
            transition: all 0.3s ease;
        }
        
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            transition: all 0.3s ease;
        }
        
        .back-to-top.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-to-top:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Q50,80 0,100 Z" fill="%23ffffff" opacity="0.05"/></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }
        
        .developer-credit {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 1rem;
        }
        
        .developer-credit a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .developer-credit a:hover {
            color: var(--accent);
        }
        
        /* Modern form styles */
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(67, 97, 238, 0.2);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        /* Floating animation */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        .floating-img {
            animation: floating 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home"><i class="fas fa-building me-2"></i>PropertyPro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#apartments">Apartments</a></li>
                    <li class="nav-item"><a class="nav-link" href="#amenities">Amenities</a></li>
                    <li class="nav-item"><a class="nav-link" href="#location">Location</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
                </ul>
                <div class="ms-lg-3 mt-3 mt-lg-0">
                    <a href="login.php" class="btn btn-primary px-4"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-white" id="home">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7" data-aos="fade-up">
                    <h1 class="display-4 fw-bold mb-4">Premium Living in General Santos City</h1>
                    <p class="lead mb-5">PropertyPro offers luxurious apartments with world-class amenities, designed for those who appreciate quality and comfort in the heart of the city.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#apartments" class="btn btn-light btn-lg px-4 py-3">Explore Properties <i class="fas fa-arrow-down ms-2"></i></a>
                        <a href="#contact" class="btn btn-outline-light btn-lg px-4 py-3"><i class="fas fa-vr-cardboard me-2"></i>Virtual Tour</a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left">
                    <div class="position-relative floating-img">
                        <img src="https://images.unsplash.com/photo-1580041065738-e72023775cdc?q=80&w=1740&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" 
                             alt="Luxury Apartment" 
                             class="img-fluid rounded-4 shadow-lg">
                        <div class="position-absolute bottom-0 start-0 bg-primary p-3 rounded-top-end shadow">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt fa-lg me-2"></i>
                                <span>General Santos City</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Apartments Section -->
    <section class="py-5" id="apartments">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title display-5 fw-bold">Featured Properties</h2>
                <p class="lead text-muted mt-3">Discover our selection of premium apartments designed for modern living</p>
            </div>
            <div class="row g-4">
                <?php
                $sql = "SELECT * FROM rooms WHERE status = 'vacant'";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0):
                    while($room = $result->fetch_assoc()):
                        // Fix the image path reference
                        $imgSrc = (!empty($room['image_path']) && file_exists(__DIR__ . '/../' . $room['image_path']))
                            ? '../' . htmlspecialchars($room['image_path'])
                            : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80';
                ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="apartment-card card h-100">
                        <div class="position-relative">
                            <img src="<?= $imgSrc ?>"
                                 class="card-img-top"
                                 alt="Room <?= htmlspecialchars($room['number']) ?>">
                            <div class="property-badge">Available</div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Apartment #<?= htmlspecialchars($room['number']) ?></h5>
                            <p class="card-text text-muted"><i class="fas fa-layer-group me-2"></i>Floor: <?= htmlspecialchars($room['floor']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge bg-light text-primary"><?= htmlspecialchars(ucfirst($room['status'])) ?></span>
                                <a href="room_details.php?id=<?= $room['id'] ?>" class="btn btn-sm btn-primary">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    endwhile;
                else:
                ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-4">No vacant rooms available at the moment. Please check back later.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<!-- Amenities Section -->
<section class="amenities-section py-5" id="amenities">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="section-title display-6 fw-bold">World-Class Amenities</h2>
            <p class="lead text-muted mt-3">Enjoy exclusive facilities and lifestyle services designed for your comfort</p>
        </div>
        <div class="row g-4">
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-dumbbell"></i></div>
                    <h6>Fitness Center</h6>
                    <p class="small text-muted mt-2">24/7 access with premium equipment</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-wifi"></i></div>
                    <h6>High-Speed Wi-Fi</h6>
                    <p class="small text-muted mt-2">Fiber optic throughout the property</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-shield-alt"></i></div>
                    <h6>24/7 Security</h6>
                    <p class="small text-muted mt-2">Trained personnel and CCTV</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="400">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-parking"></i></div>
                    <h6>Secure Parking</h6>
                    <p class="small text-muted mt-2">Covered parking with EV charging</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="500">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-tree"></i></div>
                    <h6>Green Spaces</h6>
                    <p class="small text-muted mt-2">Landscaped gardens and terraces</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="600">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-dog"></i></div>
                    <h6>Pet Friendly</h6>
                    <p class="small text-muted mt-2">Pet spa and walking areas</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="700">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-store"></i></div>
                    <h6>Convenience Store</h6>
                    <p class="small text-muted mt-2">Daily essentials just steps away</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="800">
                <div class="text-center p-3">
                    <div class="amenity-icon mx-auto"><i class="fas fa-bolt"></i></div>
                    <h6>Backup Generator</h6>
                    <p class="small text-muted mt-2">Uninterrupted power during outages</p>
                </div>
            </div>
        </div>
    </div>
</section>

    <section class="py-5" id="location">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title display-6 fw-bold">Prime Location</h2>
                <p class="lead text-muted mt-3">Strategically located in the heart of General Santos City</p>
            </div>
            <div class="row g-4 align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="rounded-4 overflow-hidden shadow">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.037292874897!2d125.1667673152946!3d6.1128335955678!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f79f8a6f8a6f8f%3A0x6f8a6f8a6f8a6f8f!2sGeneral%20Santos%20City!5e0!3m2!1sen!2sph!4v1620000000000!5m2!1sen!2sph" 
                                width="100%" 
                                height="400" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy"></iframe>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="ps-lg-5">
                        <h4 class="mb-4">Everything You Need Within Reach</h4>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex align-items-center py-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                                    <i class="fas fa-school fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Top Schools & Universities</h6>
                                    <p class="small text-muted mb-0">Within 2km radius from the property</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center py-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                                    <i class="fas fa-hospital fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Premium Medical Facilities</h6>
                                    <p class="small text-muted mb-0">24/7 emergency services nearby</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center py-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                                    <i class="fas fa-shopping-bag fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Shopping & Dining</h6>
                                    <p class="small text-muted mb-0">Lifestyle centers just minutes away</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center py-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                                    <i class="fas fa-subway fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Transportation Hub</h6>
                                    <p class="small text-muted mb-0">Easy access to public transport</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5" id="testimonials">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title display-6 fw-bold">Resident Experiences</h2>
                <p class="lead text-muted mt-3">What our residents say about living at PropertyPro</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Resident" class="testimonial-img me-3">
                            <div>
                                <h6 class="mb-0 fw-bold">Anna D.</h6>
                                <small class="text-muted">Tenant since 2020</small>
                                <div class="mt-1 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"PropertyPro truly feels like home. The amenities are top-notch and the staff are always helpful. I love living here!"</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Resident" class="testimonial-img me-3">
                            <div>
                                <h6 class="mb-0 fw-bold">Mark S.</h6>
                                <small class="text-muted">Tenant since 2021</small>
                                <div class="mt-1 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"The location is perfect and the community is very welcoming. My family feels safe and comfortable every day."</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card p-4 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://randomuser.me/api/portraits/women/12.jpg" alt="Resident" class="testimonial-img me-3">
                            <div>
                                <h6 class="mb-0 fw-bold">Grace L.</h6>
                                <small class="text-muted">Tenant since 2019</small>
                                <div class="mt-1 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"The best decision I made was moving into PropertyPro. Modern, clean, and so many amenities. Highly recommended!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-5 text-white" id="contact">
        <div class="container py-4">
            <div class="row align-items-center text-center text-lg-start">
                <div class="col-lg-8 mb-4 mb-lg-0" data-aos="fade-right">
                    <h2 class="fw-bold mb-3">Ready to Experience Premium Living?</h2>
                    <p class="lead mb-0">Contact us today to
                                            <p class="lead mb-0">Contact us today to schedule a tour or ask about availability.</p>
                </div>
                <div class="col-lg-4" data-aos="fade-left">
                    <a href="mailto:info@propertypro.com" class="btn btn-outline-light btn-lg px-5 py-3"><i class="fas fa-envelope me-2"></i>Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-white mb-4"><i class="fas fa-building me-2"></i>PropertyPro</h5>
                    <p class="text-muted">Premium apartment living in the heart of General Santos City. Experience luxury, comfort, and convenience like never before.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-white mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home">Home</a></li>
                        <li class="mb-2"><a href="#apartments">Apartments</a></li>
                        <li class="mb-2"><a href="#amenities">Amenities</a></li>
                        <li class="mb-2"><a href="#location">Location</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                    </ul>
                </div>
               <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
    <h5 class="text-white mb-4">Contact Us</h5>
    <ul class="list-unstyled text-white">
        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Poblacion District, General Santos City</li>
        <li class="mb-2"><i class="fas fa-phone me-2"></i> 090556937413</li>
        <li class="mb-2"><i class="fas fa-envelope me-2"></i> PropertyPro</li>
    </ul>
</div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-4">Connect With Us</h5>
                    <div class="d-flex gap-3 mb-4">
                        <a href="https://www.facebook.com/" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://x.com/" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                   <div class="developer-credit text-center text-lg-start text-white">
    <p class="small mb-1">
        <i class="fas fa-bolt me-2"></i>Powered by 
        <a href="#" class="text-decoration-none text-white fw-semibold" target="_blank">J7 IT Solutions And Services</a>
    </p>
    <p class="small mb-0">
        <i class="fas fa-code me-2"></i>Crafted with care by 
        <a href="#" class="text-decoration-none text-white fw-semibold" target="_blank">Jazel Jade Selayro</a>
    </p>
</div>

                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row align-items-center">
               
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="small me-3">Privacy Policy</a>
                    <a href="#" class="small me-3">Terms of Service</a>
                    <a href="#" class="small">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="btn btn-primary btn-lg rounded-circle shadow back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animations
        AOS.init({ 
            duration: 800, 
            easing: 'ease-in-out', 
            once: true, 
            offset: 100 
        });

        // Navbar scroll effect
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Back to top button
            const backToTop = document.getElementById('backToTop');
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('show');
                } else {
                    backToTop.classList.remove('show');
                }
            });
            
            backToTop.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 70,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>