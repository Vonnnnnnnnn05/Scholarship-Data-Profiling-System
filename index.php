<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKSU Scholars Data Profiling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS Variables for Deepseek Blue Palette */
        :root {
            --deepseek-blue: #1a73e8;
            --deepseek-blue-light: #4285f4;
            --deepseek-blue-dark: #0d47a1;
            --deepseek-blue-very-light: #e8f0fe;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --text-dark: #202124;
            --text-light: #5f6368;
        }

        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--white);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        section {
            padding: 80px 0;
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        section.section-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--deepseek-blue);
            color: var(--white);
            border: 2px solid var(--deepseek-blue);
        }

        .btn-primary:hover {
            background-color: var(--deepseek-blue-dark);
            border-color: var(--deepseek-blue-dark);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--deepseek-blue);
            border: 2px solid var(--deepseek-blue);
        }

        .btn-outline:hover {
            background-color: var(--deepseek-blue-very-light);
        }

        h1, h2, h3, h4 {
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        p {
            margin-bottom: 15px;
            color: var(--text-light);
        }

        /* Header Styles */
        header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 40px;
            margin-right: 10px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--deepseek-blue);
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links li:last-child a {
            background-color: var(--deepseek-blue);
            color: var(--white);
            padding: 8px 20px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-links li:last-child a:hover {
            background-color: var(--deepseek-blue-dark);
            color: var(--white);
        }

        .nav-links a:hover {
            color: var(--deepseek-blue);
        }

        .mobile-menu {
            display: none;
            font-size: 24px;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--deepseek-blue) 0%, var(--deepseek-blue-light) 100%);
            color: var(--white);
            padding: 160px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('images/image.png');
            background-size: cover;
            background-position: center;
            opacity: 0.10   ;
            z-index: 1;
        }

        .hero .container {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--white);
        }

        .hero-callout {
            display: inline-flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px 22px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 10px 30px rgba(13, 71, 161, 0.25);
            backdrop-filter: blur(6px);
            margin: 18px auto 24px;
            max-width: 760px;
            text-align: center;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: var(--white);
            background: rgba(255, 255, 255, 0.18);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 11px;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--white);
            opacity: 0.95;
        }

        .hero-promo {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 0.3px;
            color: var(--white);
            line-height: 1.2;
        }

        .hero-promo span {
            display: block;
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }

        .hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto 30px;
            color: var(--white);
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            width: 100%;
            margin-top: 30px;
        }

        .hero .btn {
            padding: 14px 40px;
            font-size: 16px;
        }

        .hero .btn-primary {
            background-color: var(--white);
            color: var(--deepseek-blue);
            border-color: var(--white);
        }

        .hero .btn-primary:hover {
            background-color: var(--light-gray);
            border-color: var(--light-gray);
        }

        .hero .btn-outline {
            background-color: transparent;
            color: var(--white);
            border-color: var(--white);
        }

        .hero .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Features Section */
        .features {
            background-color: var(--white);
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 36px;
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }

        .section-title h2:after {
            content: '';
            position: absolute;
            width: 70px;
            height: 3px;
            background-color: var(--deepseek-blue);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background-color: var(--deepseek-blue-very-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--deepseek-blue);
            font-size: 28px;
        }

        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
        }

        /* Stats Section */
        .stats {
            background-color: var(--deepseek-blue-very-light);
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: var(--deepseek-blue);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 18px;
            color: var(--text-light);
        }

        /* How It Works Section */
        .how-it-works {
            background-color: var(--white);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }

        .steps:before {
            content: '';
            position: absolute;
            top: 40px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--deepseek-blue);
            z-index: 1;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background-color: var(--deepseek-blue);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 700;
            margin: 0 auto 20px;
            border: 5px solid var(--white);
        }

        .step h3 {
            margin-bottom: 10px;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--deepseek-blue) 0%, var(--deepseek-blue-dark) 100%);
            color: var(--white);
            text-align: center;
            padding: 100px 0;
        }

        .cta h2 {
            color: var(--white);
            font-size: 36px;
            margin-bottom: 20px;
        }

        .cta p {
            color: var(--white);
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 30px;
        }

        .cta .btn {
            padding: 14px 35px;
            font-size: 16px;
        }

        .cta .btn-primary {
            background-color: var(--white);
            color: var(--deepseek-blue);
            border-color: var(--white);
        }

        .cta .btn-primary:hover {
            background-color: var(--light-gray);
            border-color: var(--light-gray);
        }

        /* Footer */
        footer {
            background-color: var(--text-dark);
            color: var(--white);
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            color: var(--white);
            margin-bottom: 25px;
            font-size: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 2px;
            background-color: var(--deepseek-blue);
            bottom: 0;
            left: 0;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #b0b3b8;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--deepseek-blue);
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: var(--white);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--deepseek-blue);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b0b3b8;
            font-size: 14px;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                padding: 0 30px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 0;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu {
                display: block;
            }
            
            .hero {
                padding: 120px 0 80px;
            }

            .hero h1 {
                font-size: 32px;
                line-height: 1.3;
            }
            
            .hero p {
                font-size: 16px;
                padding: 0 10px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 12px;
                padding: 0 20px;
            }
            
            .hero-buttons .btn {
                width: 100%;
                max-width: 350px;
                padding: 14px 30px;
                text-align: center;
                font-size: 15px;
            }

            .btn {
                padding: 12px 24px;
                font-size: 14px;
            }

            .cta .btn {
                width: 100%;
                max-width: 300px;
                display: block;
                margin: 0 auto;
            }
            
            .steps {
                flex-direction: column;
                gap: 40px;
            }
            
            .steps:before {
                display: none;
            }

            .feature-card {
                padding: 25px;
            }

            section {
                padding: 60px 0;
            }

            .container {
                padding: 0 20px;
            }
        }

        @media (max-width: 576px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .section-title h2 {
                font-size: 24px;
            }

            .hero h1 {
                font-size: 28px;
            }

            .hero p {
                font-size: 15px;
            }

            .hero-buttons .btn {
                max-width: 100%;
                padding: 12px 20px;
                font-size: 14px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }

            .logo-text {
                font-size: 18px;
            }

            .cta h2 {
                font-size: 26px;
            }

            .cta p {
                font-size: 15px;
                padding: 0 10px;
            }

            .container {
                padding: 0 15px;
            }
        }

        @media (max-width: 400px) {
            .hero h1 {
                font-size: 24px;
            }

            .hero-buttons .btn {
                padding: 10px 16px;
                font-size: 13px;
            }

            .section-title h2 {
                font-size: 22px;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0);
            opacity: 0;
            transition: opacity 0.4s ease, background-color 0.4s ease;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            position: relative;
            transform: scale(0.7);
            opacity: 0;
            transition: transform 0.4s ease, opacity 0.4s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        /* SweetAlert2 customization */
        .swal2-container {
            z-index: 9999 !important;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--text-dark);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-header h2 {
            color: var(--deepseek-blue);
            margin-bottom: 10px;
        }

        .modal-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--deepseek-blue);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-options label {
            display: flex;
            align-items: center;
            color: var(--text-light);
            cursor: pointer;
        }

        .form-options input[type="checkbox"] {
            margin-right: 5px;
        }

        .form-options a {
            color: var(--deepseek-blue);
            text-decoration: none;
        }

        .form-options a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--deepseek-blue);
            color: var(--white);
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: var(--deepseek-blue-dark);
        }

        .modal-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-light);
        }

        .modal-footer a {
            color: var(--deepseek-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .modal-footer a:hover {
            text-decoration: underline;
        }

        /* Promo modal trigger */
        .promo-launch {
            position: fixed;
            bottom: 120px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 900;
        }

        .promo-label {
            background: var(--white);
            color: #1f2933;
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(31, 41, 51, 0.15);
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .promo-fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #2f3ddf;
            color: var(--white);
            border: none;
            box-shadow: 0 10px 24px rgba(47, 61, 223, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .promo-fab:hover {
            background: #2431c2;
            transform: translateY(-2px);
        }

        .promo-modal .modal-content {
            max-width: 360px;
            width: 90%;
            position: fixed;
            right: 24px;
            bottom: 140px;
            margin: 0;
            border-radius: 18px;
        }

        .promo-modal .modal-header h2 {
            margin-bottom: 12px;
        }

        .subsystem-fab {
            position: fixed;
            bottom: 190px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--deepseek-blue-light);
            color: var(--white);
            border: none;
            box-shadow: 0 10px 25px rgba(13, 71, 161, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
            z-index: 900;
        }

        .subsystem-fab:hover {
            background: var(--deepseek-blue-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="images/logo.png" alt="SKSU Building">
                    <div class="logo-text">SKSU Scholars</div>
                </div>
                <ul class="nav-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#" id="loginBtn">Login</a></li>
                </ul>
                <div class="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">

            <h1>SKSU Scholars Data Profiling System</h1>
            <p>Comprehensive data management and analytics platform for tracking scholar performance, research output, and academic progress at Sultan Kudarat State University.</p>
            <div class="hero-buttons">

            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Key Features</h2>
                <p>Our platform offers comprehensive tools for scholar data management</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Performance Analytics</h3>
                    <p>Track and visualize scholar performance metrics with interactive dashboards and detailed reports.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Academic Progress</h3>
                    <p>Monitor academic milestones, course completion, and research progress for all scholars.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Research Tracking</h3>
                    <p>Manage research publications, projects, and collaborations in one centralized system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>Achievement Records</h3>
                    <p>Document and celebrate scholar achievements, awards, and recognitions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Collaboration Tools</h3>
                    <p>Facilitate communication and collaboration between scholars, advisors, and administrators.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Data Security</h3>
                    <p>Ensure the privacy and security of sensitive scholar information with advanced protection.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
   

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Simple steps to manage scholar data effectively</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Data Collection</h3>
                    <p>Gather comprehensive information from scholars through secure forms and integrations.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Data Processing</h3>
                    <p>Automated systems validate, clean, and organize the collected data for analysis.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Analysis & Insights</h3>
                    <p>Generate meaningful insights through advanced analytics and visualization tools.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Reporting</h3>
                    <p>Create comprehensive reports for stakeholders and decision-makers.</p>
                </div>
            </div>
        </div>
    </section>

   

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>SKSU Scholars</h3>
                    <p>Comprehensive data profiling system for Sultan Kudarat State University scholars.</p>
                    
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">User Guides</a></li>
                        <li><a href="#">API Reference</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Access, Tacurong City, Sultan Kudarat</li>
                        <li><i class="fas fa-phone"></i> (064) 200-8250</li>
                        <li><i class="fas fa-envelope"></i> info@sksu.edu.ph</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 SKSU Scholars Data Profiling System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div class="modal-header">
                <h2>Login</h2>
                <p>Welcome back! Please login to your account.</p>
            </div>
            <form id="loginForm" method="post" action="loginProcess.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    
                </div>
                <div id="loginError" style="color: #d32f2f; font-size: 14px; margin-bottom: 15px; display: none;"></div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <div class="modal-footer">
               
            </div>
        </div>
    </div>

    <!-- Promo Modal Trigger -->
    <div class="promo-launch">
        <div class="promo-label">Register Now! <span aria-hidden="true"></span></div>
        <button class="promo-fab" id="promoBtn" aria-label="Open scholarship promo">
            <i class="fas fa-comment-dots"></i>
        </button>
    </div>

    <!-- Promo Modal -->
    <div id="promoModal" class="modal promo-modal">
        <div class="modal-content">
            <span class="modal-close" data-close="promo">&times;</span>
            <div class="modal-header">
                <h2>Scholarship Opportunity</h2>
                <p>We invite qualified students to apply for the scholarship program. Please register to begin your application.</p>
            </div>
            <div style="text-align: center;">
                <a class="btn btn-primary" href="http://localhost/scholarship">Register Now</a>
            </div>
        </div>
    </div>

    <script>
        // Login Modal functionality
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeBtn = document.querySelector('.modal-close');
        const promoModal = document.getElementById('promoModal');
        const promoBtn = document.getElementById('promoBtn');

        function openModal() {
            loginModal.classList.add('active');
            setTimeout(() => {
                loginModal.classList.add('show');
            }, 10);
        }

        function closeModal() {
            loginModal.classList.remove('show');
            setTimeout(() => {
                loginModal.classList.remove('active');
            }, 400);
        }

        function openPromoModal() {
            promoModal.classList.add('active');
            setTimeout(() => {
                promoModal.classList.add('show');
            }, 10);
        }

        function closePromoModal() {
            promoModal.classList.remove('show');
            setTimeout(() => {
                promoModal.classList.remove('active');
            }, 400);
        }

        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });

        closeBtn.addEventListener('click', function() {
            closeModal();
        });

        promoBtn.addEventListener('click', function() {
            openPromoModal();
        });

        window.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeModal();
            }
            if (e.target === promoModal) {
                closePromoModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && loginModal.classList.contains('active')) {
                closeModal();
            }
            if (e.key === 'Escape' && promoModal.classList.contains('active')) {
                closePromoModal();
            }
        });

        document.querySelectorAll('[data-close="promo"]').forEach(button => {
            button.addEventListener('click', function() {
                closePromoModal();
            });
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.querySelector('.login-btn');
            
            // Disable button and show loading
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            
            // Create form data
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            // Send AJAX request
            fetch('loginProcess.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: data.message,
                        confirmButtonColor: '#1a73e8'
                    });
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Login';
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonColor: '#1a73e8'
                });
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            });
        });

        // Mobile menu toggle
        document.querySelector('.mobile-menu').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Intersection Observer for section animations
        const sections = document.querySelectorAll('section');
        
        const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const sectionObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('section-visible');
                }
            });
        }, observerOptions);
        
        sections.forEach(section => {
            sectionObserver.observe(section);
        });
    </script>
    
    <!-- Tidio Live Chat -->
    <script src="https://code.tidio.co/vpc1zm4uqouejkzwo9qhuwcpfb05krd4.js" async></script>
</body>
</html>
