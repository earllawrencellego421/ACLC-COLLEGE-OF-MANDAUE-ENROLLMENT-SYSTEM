<?php
session_start();
// NOTE: Make sure config.php is correctly set up for PDO database connection.
include 'config.php'; 

// =======================================================
// 1. SYSTEM ACCESS RESTRICTION (Redirects staff immediately)
// =======================================================

if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        $_SESSION['alert'] = "Please log out from the Admin Dashboard first to view the course page.";
        header('Location: admindashboard.php'); 
        exit();
    }
}
// =======================================================

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC Course Offerings</title>
    
    <link rel="icon" type="image/png" href="logo.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        
        /* --- FULL HD CONTAINER --- */
        .container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            /* Increased width for Full HD look */
            max-width: 1600px; 
            width: 95%; 
            overflow: hidden;
            backdrop-filter: blur(10px);
            margin-top: 60px; /* Space for the top button */
        }
        
        /* --- FLOATING LOGIN BUTTON (Top Right) --- */
        .staff-login-btn {
            position: fixed; /* Locks it to the screen */
            top: 30px;
            right: 40px;
            z-index: 1000; /* Ensures it's on top of everything */
            
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px; /* Pill shape */
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
        }

        .staff-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .navbar {
            background: linear-gradient(135deg, #2d3436 0%, #636e72 100%);
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: center; /* Centered Title */
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .navbar h1 {
            font-size: 32px; /* Bigger Title */
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .content {
            padding: 60px 40px;
        }
        
        .hero {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .hero h2 {
            color: #2d3436;
            font-size: 48px; /* Bigger Heading */
            margin-bottom: 20px;
            font-weight: 800;
        }
        
        .hero p {
            color: #636e72;
            font-size: 20px;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Wider cards */
            gap: 30px;
            margin-top: 50px;
        }
        
        .course-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #f0f3ff 100%);
            padding: 40px 30px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .course-card:hover::before {
            transform: scaleX(1);
        }
        
        .course-card:hover {
            border-color: #667eea;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            transform: translateY(-8px);
        }
        
        .course-card h3 {
            color: #2d3436;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 700;
        }

        .course-card p {
            color: #636e72;
            font-size: 16px;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 20px;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .navbar h1 { font-size: 24px; }
            .hero h2 { font-size: 32px; }
            .container { width: 100%; margin-top: 0; border-radius: 0; }
            .staff-login-btn { 
                top: 15px; right: 15px; 
                padding: 8px 15px; font-size: 14px; 
            }
        }
    </style>
</head>
<body>

    <a href="admin_login.php" class="staff-login-btn">Staff Login</a>

    <div class="container">
        <div class="navbar">
            <h1>🎓 ACLC College</h1>
        </div>
        
        <div class="content">
            <div class="hero">
                <h2>Our Academic Programs</h2>
                <p>Explore our comprehensive bachelor's degree courses designed to prepare you for career success.</p>
            </div>
            
            <div class="courses-grid">
                <div class="course-card">
                    <h3>BSIT</h3>
                    <p>Bachelor of Science in Information Technology</p>
                    <p style="font-size: 14px; color: #b2bec3; margin-top: 10px;">Modern IT solutions & software development</p>
                    <span class="badge">Technology</span>
                </div>
                
                <div class="course-card">
                    <h3>BSCS</h3>
                    <p>Bachelor of Science in Computer Science</p>
                    <p style="font-size: 14px; color: #b2bec3; margin-top: 10px;">Core computing & algorithm expertise</p>
                    <span class="badge">Computing</span>
                </div>
                
                <div class="course-card">
                    <h3>BSHM</h3>
                    <p>Bachelor of Science in Hotel Management</p>
                    <p style="font-size: 14px; color: #b2bec3; margin-top: 10px;">Hospitality industry leadership</p>
                    <span class="badge">Service</span>
                </div>
                
                <div class="course-card">
                    <h3>BSBA</h3>
                    <p>Bachelor of Science in Business Administration</p>
                    <p style="font-size: 14px; color: #b2bec3; margin-top: 10px;">Business acumen & management skills</p>
                    <span class="badge">Business</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // --- ALERT DISPLAY ---
        <?php if (isset($_SESSION['alert'])): ?>
            alert("<?php echo $_SESSION['alert']; ?>");
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>
    </script>
</body>
</html>