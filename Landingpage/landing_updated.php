<?php
// Landing page for MacJ Pest Control Services
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
 header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; frame-src 'self' https://www.google.com; img-src 'self' https://*.google.com;");

// Define constants for paths
define('ASSETS_PATH', 'assets/');
define('UPLOADS_PATH', '../uploads/');
define('CACHE_PATH', 'cache/');
define('SERVICES_UPLOAD_PATH', UPLOADS_PATH . 'services/');
define('TESTIMONIAL_IMG_PATH', ASSETS_PATH . 'img/testimonials/');

$isLoggedIn = isset($_SESSION['role']) && $_SESSION['role'] === 'client';
if ($isLoggedIn) {
    session_regenerate_id(true);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>MacJ Pest Control Services</title>
  <meta name="description" content="Professional pest control services for homes and businesses">
  <meta name="keywords" content="pest control, termite control, rodent control, pest management, MacJ">
  <link rel="canonical" href="https://macjpestcontrol.com/landing_updated.php">

  <!-- Structured Data for Local Business -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "MacJ Pest Control Services",
    "description": "Professional pest control services for homes and businesses with over 21 years of experience",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "30 Sto. Tomas St.",
      "addressLocality": "Quezon City",
      "addressRegion": "Metro Manila",
      "postalCode": "1100",
      "addressCountry": "PH"
    },
    "telephone": "(02)7369-3904",
    "email": "info@macjpestcontrol.com",
    "url": "https://macjpestcontrol.com",
    "image": "https://macjpestcontrol.com/assets/img/MACJLOGO.png",
    "priceRange": "$$",
    "openingHours": "Mo-Fr 08:00-17:00",
    "sameAs": [
      "https://facebook.com/macjpestcontrol",
      "https://twitter.com/macjpestcontrol",
      "https://instagram.com/macjpestcontrol"
    ]
  }
  </script>

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://macjpestcontrol.com/landing_updated.php">
  <meta property="og:title" content="MacJ Pest Control Services">
  <meta property="og:description" content="Professional pest control services for homes and businesses">
  <meta property="og:image" content="https://macjpestcontrol.com/assets/img/MACJLOGO.png">

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="https://macjpestcontrol.com/landing_updated.php">
  <meta property="twitter:title" content="MacJ Pest Control Services">
  <meta property="twitter:description" content="Professional pest control services for homes and businesses">
  <meta property="twitter:image" content="https://macjpestcontrol.com/assets/img/MACJLOGO.png">

  <!-- Favicons -->
  <link rel="icon" href="assets/img/favicon.png">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://googleapis.com" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">

  <!-- Main CSS File -->
   <link href="assets/css/main.css" rel="stylesheet">

  <!-- Inline styles for smooth scrolling -->
  <style>html { scroll-behavior: smooth; }</style>

  <?php if ($isLoggedIn): ?>
  <!-- Client-side CSS for logged-in users -->
  <link href="../Client Side/css/variables.css" rel="stylesheet">
  <link href="../Client Side/css/main.css" rel="stylesheet">
  <link href="../Client Side/css/header.css" rel="stylesheet">
  <link href="../Client Side/css/sidebar.css" rel="stylesheet">
  <link href="../Client Side/css/client-common.css" rel="stylesheet">
  <link href="../Client Side/css/footer.css" rel="stylesheet">
  <link href="../Client Side/css/landing-integration.css" rel="stylesheet">
  <link href="../Client Side/css/form-validation-fix.css" rel="stylesheet">
  <link href="../Client Side/css/content-spacing-fix.css" rel="stylesheet">
  <?php endif; ?>
</head>

  <body class="index-page">
    <!-- Skip to main content link for accessibility -->
    <a href="#main" class="sr-only sr-only-focusable">Skip to main content</a>

  <!-- Header -->
  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">

      <a href="#hero" class="logo d-flex align-items-center">
        <img src="assets/img/MACJLOGO.png" alt="MACJ Pest Control" class="img-fluid" loading="lazy">
      </a>

      <nav id="navmenu" class="navmenu" role="navigation" aria-label="Main navigation">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#service-area">Service Area</a></li>
          <li><a href="#footer">Contact</a></li>
        </ul>

      </nav>

      <div class="header-buttons d-flex align-items-center">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a class="btn-getstarted" href="SignIn.php">Admin</a>
        <?php else: ?>
          <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none; color: var(--accent-color);">
              <i class="bi bi-menu-button-wide" style="font-size: 24px;"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#hero">Home</a></li>
              <li><a class="dropdown-item" href="#about">About</a></li>
              <li><a class="dropdown-item" href="#services">Services</a></li>
              <li><a class="dropdown-item" href="#service-area">Service Area</a></li>
              <li><a class="dropdown-item" href="#footer">Contact</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="SignIn.php">Admin Login</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <main id="main" class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section" role="banner">
      <div class="container">
        <div class="row gy-5 align-items-center">
          <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center">
            <h1>Professional Pest Control Solutions</h1>
            <p class="hero-text">We understand the importance of a pest-free environment for your home, business, and health. Our experienced team of licensed professionals is dedicated to providing top-notch pest control solutions tailored to meet your unique needs.</p>
            <div class="d-flex gap-4 mt-4">
              <a href="#footer" class="btn-get-started">Get a Quotation!</a>
            </div>
          </div>
          <div class="col-lg-6 order-1 order-lg-2 hero-img">
            <img src="assets/img/macj-groupImg.jpg" class="img-fluid rounded-4 shadow-lg" alt="MACJ Pest Control Team" loading="lazy">
          </div>
        </div>
      </div>
    </section><!-- End Hero Section -->



    <!-- About Section -->
    <section id="about" class="about section light-background" aria-labelledby="about-title">

      <!-- Section Title -->
      <div class="container section-title">
        <h2 id="about-title">About Us</h2>
        <p>Learn more about our company and our commitment to excellence</p>
      </div><!-- End Section Title -->

      <div class="container">
        <div class="row gy-5 align-items-center">

          <div class="content col-lg-5">
            <div class="about-img position-relative mb-4">
              <img src="assets/img/teammacj.jpg" class="img-fluid rounded-4 shadow" alt="MACJ Pest Control Team" loading="lazy">
              <div class="experience-badge">
                <div class="stat-item">
                  <span class="years">21+</span>
                  <span class="text">Years</span>
                </div>
                <div class="stat-item">
                  <span class="years">1000+</span>
                  <span class="text">Projects</span>
                </div>
              </div>
            </div>
            <h3 class="mb-3">MACJ PEST CONTROL</h3>
            <p class="mb-4">
              Was founded by a licensed pest control professional with over twenty-one years of experience who is committed to developing and applying innovative solutions for various pest issues.
            </p>
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-check-circle-fill me-2 text-success"></i>
              <p class="mb-0">Licensed and certified professionals</p>
            </div>
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-check-circle-fill me-2 text-success"></i>
              <p class="mb-0">Eco-friendly pest control solutions</p>
            </div>
            <div class="d-flex align-items-center mb-4">
              <i class="bi bi-check-circle-fill me-2 text-success"></i>
              <p class="mb-0">Customized treatment plans</p>
            </div>

          </div>

      

          <div class="col-lg-7">
            <div class="row gy-4">

              <div class="col-md-6">
                <div class="icon-box">
                  <i class="fas fa-bullseye text-primary"></i>
                  <h4>MISSION</h4>
                  <p>To build and establish a successful relationship with our clients as well as our suppliers. To provide our clients high quality and high standard service. To provide more jobs in order to contribute to our economy as well as providing our people an employee program that will enhance their personal growth.</p>
                </div>
              </div><!-- Icon-Box -->

              <div class="col-md-6">
                <div class="icon-box">
                  <i class="fas fa-eye text-primary"></i>
                  <h4>VISION</h4>
                  <p>To evolve as the "most excellent service provider" in the market, providing quality and honest service that every customer deserves.</p>
                </div>
              </div><!-- Icon-Box -->

               <div class="col-md-6">
                 <div class="icon-box">
                   <i class="fas fa-award text-primary"></i>
                   <h4>CERTIFICATIONS</h4>
                    <ul class="certifications-list">
                      <li><i class="fas fa-certificate"></i> DUNS Accredited</li>
                      <li><i class="fas fa-certificate"></i> FPA License Fumigator and Exterminator</li>
                      <li><i class="fas fa-certificate"></i> FDA License to Operate</li>
                      <li><i class="fas fa-certificate"></i> Member of KAPESTCOPI INC.</li>
                   </ul>
                   </div>
               </div><!-- Icon-Box -->

              <div class="col-md-6">
                <div class="icon-box">
                  <i class="fas fa-shield-alt text-primary"></i>
                  <h4>OUR VALUES</h4>
                  <p>We are committed to integrity, excellence, innovation, and customer satisfaction in every service we provide. Your safety and satisfaction are our top priorities.</p>
                </div>
              </div><!-- Icon-Box -->

            </div>
          </div>

        </div>
      </div>

    </section><!-- End About Section -->

    <!-- Services Section -->
    <section id="services" class="services section" aria-labelledby="services-title">
      <div class="container">
        <!-- Section Title -->
        <div class="section-title">
          <h2 id="services-title">Our Services</h2>
          <p>Professional pest control solutions for your needs</p>
        </div>

        <?php
        // Include database connection
        require_once '../db_connect.php';

        // Retrieve active services from database
        $services = [];
        try {
            $query = "SELECT service_id, name, description, icon, image, status FROM services WHERE status = 'active' ORDER BY name";
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $services[] = $row;
                }
            }
        } catch (Exception $e) {
            // Handle error silently or log
            error_log("Error fetching services: " . $e->getMessage());
        }



        // If no services found, show default message
        if (empty($services)) {
            echo '<div class="row mb-5">
                    <div class="col-12 text-center">
                      <div class="alert alert-info">
                        <h4>No Services Available</h4>
                        <p>Please check back later for our service offerings.</p>
                      </div>
                    </div>
                  </div>';
        } else {
            // Start the grid row
            echo '<div class="row">';

            // Display services in a grid
            foreach ($services as $index => $service) {
                // Set image path
                if (!empty($service['image']) && file_exists(SERVICES_UPLOAD_PATH . $service['image'])) {
                    $image_path = SERVICES_UPLOAD_PATH . $service['image'];
                } else {
                    // Use a default image if the service image doesn't exist
                    $image_path = ASSETS_PATH . 'img/default-service.jpg';
                }

                // Output the service card
                $safe_name = htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8');
                $safe_description = htmlspecialchars(substr($service['description'], 0, 100) . (strlen($service['description']) > 100 ? '...' : ''), ENT_QUOTES, 'UTF-8');
                $full_description = htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8');
                echo "
                <div class='col-md-6 col-lg-4 mb-4'>
                  <div class='service-card'>
                    <div class='service-img-container'>
                      <img src='{$image_path}' class='img-fluid service-img' alt='{$safe_name}' loading='lazy'>
                      <div class='service-card-content'>
                        <h4>{$safe_name}</h4>
                        <p class='service-description'>{$safe_description}</p>
                      </div>
                    </div>
                  </div>
                </div>";
            }

            // Close the grid row
            echo '</div>';
        }
        ?>
      </div>
    </section><!-- End Services Section -->

    <!-- Service Area Section -->
    <section id="service-area" class="service-area section light-background" aria-labelledby="service-area-title">
      <div class="container">
        <!-- Section Title -->
        <div class="section-title">
          <h2 id="service-area-title">Service Area</h2>
          <p>Find our location and see if we serve your area</p>
        </div>

        <div class="row">
          <div class="col-lg-6">
            <div class="map-container">
              <iframe src="https://www.google.com/maps/embed?pb=!1m10!1m8!1m3!1d1055!2d121.0002446!3d14.617143!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1713000000000&q=30%20Sto.%20Tomas%20St.%20Brgy%20Don%20Manuel%20Quezon%20City" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
          <div class="col-lg-6 d-flex flex-column justify-content-center">
            <h3>Do We Serve Your Area?</h3>
            <p>We provide professional pest control services primarily in Quezon City and surrounding areas. Our team is equipped to handle pest issues in residential and commercial properties within our service radius.</p>
            <p>If you're unsure whether we cover your location, please contact us for a consultation. We'll be happy to discuss your needs and determine the best way to assist you.</p>
            <a href="#footer" class="btn btn-primary mt-3">Contact Us</a>
          </div>
        </div>
      </div>
    </section><!-- End Service Area Section -->

    <!-- Call to Action Section -->
    <section id="cta" class="cta section" aria-labelledby="cta-title">
      <div class="container">
        <div class="row g-5">
          <div class="col-lg-8 col-md-6 content d-flex flex-column justify-content-center order-last order-md-first">
            <h3 id="cta-title">Ready for a Pest-Free Environment?</h3>
            <p>Schedule a consultation with our pest control experts today. We'll create a customized treatment plan tailored to your specific needs.</p>
          </div>
          <div class="col-lg-4 col-md-6 order-first order-md-last d-flex align-items-center">
          </div>
        </div>
      </div>
    </section><!-- End Call to Action Section -->
  </main>

  <footer id="footer" class="footer" role="contentinfo">

    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-info">
          <a href="landing_updated.php" class="logo d-flex align-items-center mb-3">
            <img src="assets/img/MACJLOGO.png" alt="MACJ Pest Control" class="img-fluid" style="max-height: 60px;" loading="lazy">
          </a>
          <p>MacJ Pest Control Services provides professional pest management solutions for residential and commercial properties. With over 21 years of experience, we deliver effective and eco-friendly pest control services.</p>
          <h4 class="mt-4">Connect With Us</h4>
          <div class="social-links d-flex mt-3">
            <a href="https://www.facebook.com/MACJPEST" class="facebook" aria-label="Follow us on Facebook"><i class="fab fa-facebook-f"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="#hero">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#service-area">Service Area</a></li>
            <li><a href="SignIn.php">Sign In</a></li>
            <li><a href="#footer">Contact</a></li>
          </ul>
        </div>



        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Contact Us</h4>
          <p>
            30 Sto. Tomas St. <br>
            Brgy Don Manuel<br>
            Quezon City <br><br>
            <strong>Phone:</strong> (02)7 369.3904/8 805.5404<br>
            <strong>Mobile:</strong> +63 905.515.8398<br>
            <strong>Email:</strong> macpest@yahoo.com<br>
          </p>
          <!-- Contact Form 
          <form action="contact.php" method="post" class="mt-3">
            <div class="mb-2">
              <input type="text" name="name" class="form-control" placeholder="Your Name" required aria-label="Your Name">
            </div>
            <div class="mb-2">
              <input type="email" name="email" class="form-control" placeholder="Your Email" required aria-label="Your Email">
            </div>
            <div class="mb-2">
              <textarea name="message" class="form-control" rows="3" placeholder="Your Message" required aria-label="Your Message"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Send Message</button>
          </form>
        </div> -->

      </div>
    </div>

    <div class="container mt-4">
      <div class="copyright text-center">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">MacJ Pest Control Services</strong> <span>All Rights Reserved</span></p>
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="fas fa-arrow-up"></i></a>



  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Main JS File (Simplified) -->
  <script src="assets/js/main-simple.js"></script>

  <?php if ($isLoggedIn): ?>
  <!-- Client-side JS for logged-in users -->
  <script src="../Client Side/js/main.js"></script>
  <script src="../Client Side/js/sidebar.js"></script>
  <script src="../Client Side/js/form-validation-fix.js"></script>
  <?php endif; ?>

  <!-- Additional script for services section animations on scroll -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add animation to service items when they come into view
      const serviceItems = document.querySelectorAll('.service-item');

      if (serviceItems.length > 0) {
        // Simple animation when scrolling to service items
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0)';
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.1 });

        // Set initial styles and observe each service item
        serviceItems.forEach(item => {
          item.style.opacity = '0';
          item.style.transform = 'translateY(20px)';
          item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          observer.observe(item);
        });
      }


    });
  </script>

</body>

</html>
