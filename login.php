<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
$error = "";

// 1. በራሱ 1 አድሚን መኖሩን ማረጋገጥ (ከሌለ በራሱ ይፈጥረዋል)
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
)");

$checkAdmin = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
if ($checkAdmin && $checkAdmin->num_rows == 0) {
    $conn->query("INSERT INTO admins (username, password) VALUES ('admin', '" . md5('admin123') . "')");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input  = isset($_POST['email']) ? trim($_POST['email']) : '';
    $raw_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $md5_password = md5($raw_password);

    if (empty($login_input) || empty($raw_password)) {
        $error = "እባክዎ Email/Username እና Password ያስገቡ። / Mee Email fi Password galchaa.";
    } else {
        $safe_user = $conn->real_escape_string($login_input);

        // 2. አድሚን መሆኑን ማረጋገጥ
        $resAdmin = $conn->query("SELECT id, username, password FROM admins WHERE username = '$safe_user' LIMIT 1");

        if ($resAdmin && $resAdmin->num_rows > 0) {
            $adminRow = $resAdmin->fetch_assoc();
            if ($md5_password === $adminRow['password'] || $raw_password === $adminRow['password']) {
                $_SESSION['admin_id']   = $adminRow['id'];
                $_SESSION['admin_name'] = $adminRow['username'];

                if (file_exists('admin/dashboard.php')) {
                    header("Location: admin/dashboard.php");
                    echo "<script>window.location.href='admin/dashboard.php';</script>";
                    exit();
                } else {
                    $error = "ስህተት: 'admin/dashboard.php' የሚባል ፋይል አልተገኘም!";
                }
            } else {
                $error = "የተሳሳተ የአድሚን Password! (ትክክለኛው: admin123)";
            }
        } else {
            // 3. ተማሪ (Student) መሆኑን ማረጋገጥ (በእጅጉ የተስተካከለ የፍለጋ መጠይቅ)
            // እዚህ ጋር ኢሜል፣ አድሚሽን ቁጥር ወይም ስልክ ቁጥር በመጠቀም መግባት እንዲችል ይደረጋል
            $resStu = $conn->query("SELECT id, full_name, phone, password FROM students WHERE email = '$safe_user' OR admission_number = '$safe_user' OR phone = '$safe_user' LIMIT 1");
            
            if ($resStu && $resStu->num_rows > 0) {
                $stuRow = $resStu->fetch_assoc();
                
                // የይለፍ ቃል ማረጋገጫ (MD5 ወይም Plain text)
                if ($md5_password === $stuRow['password'] || $raw_password === $stuRow['password'] || password_verify($raw_password, $stuRow['password'])) {
                    $_SESSION['student_id']    = $stuRow['id'];
                    $_SESSION['student_name']  = $stuRow['full_name'];
                    $_SESSION['student_phone'] = $stuRow['phone'];

                    // ወደ ተማሪው ዳሽቦርድ በቀጥታ መውሰድ
                    header("Location: student/dashboard.php");
                    echo "<script>window.location.href='student/dashboard.php';</script>";
                    exit();
                } else {
                    $error = "የተሳሳተ የተማሪ Password አስገብተዋል! / Jecha darbii Barataa dogoggoraa.";
                }
            } else {
                $error = "ያስገቡት '$login_input' በ Admin ሆነ በ Student ዝርዝር ውስጥ አልተገኘም!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - ዩኒቨርሲቲ ፖርታል</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --et-green: #078930;
            --et-yellow: #fcdd09;
            --et-red: #da121a;
            --et-dark: #0f2942;
            --et-light: #f0f4f8;
            --transition-speed: 0.4s ease;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.8s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Navigation Bar */
        .et-navbar {
            background-color: var(--et-dark) !important;
            border-bottom: 4px solid;
            border-image: linear-gradient(to right, var(--et-green) 33%, var(--et-yellow) 33% 66%, var(--et-red) 66%) 1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .et-navbar .navbar-brand, 
        .et-navbar .nav-link {
            color: #ffffff !important;
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .et-navbar .nav-link:hover, 
        .et-navbar .nav-link.active {
            color: var(--et-yellow) !important;
            transform: translateY(-1px);
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        /* የማስታወቂያ ማሳያ (Marquee Bar) ዲዛይን */
        .announcement-bar {
            background: linear-gradient(to right, #fcdd09, #ffc107);
            color: #0f2942;
            font-weight: 700;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #da121a;
        }

        .btn-success {
            background-color: #078930;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #056b24;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(7, 137, 48, 0.4);
        }

        .form-control:focus {
            border-color: #078930;
            box-shadow: 0 0 0 0.2rem rgba(7, 137, 48, 0.25);
        }

        /* Footer */
        .et-footer {
            background-color: var(--et-dark);
            color: #ffffff;
            text-align: center;
            padding: 1.5rem 0;
            margin-top: auto;
            border-top: 3px solid var(--et-yellow);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Login Container Section -->
    <div class="container my-5 flex-grow-1 d-flex flex-column justify-content-center">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <!-- የሚንቀሳቀስ ማስታወቂያ (Marquee) -->
                <div class="announcement-bar">
                    <marquee behavior="scroll" direction="left" scrollamount="5">
                        <i class="fa-solid fa-bullhorn text-danger me-2"></i> WELCOME TO GRADE 12 UNIVERSITY SELECTION PORTAL!!! እንኳን ወደ ኦፊሴላዊው የዩኒቨርሲቲ ፖርታል በደህና መጡ!
                    </marquee>
                </div>

                <div class="card shadow-lg rounded-4 overflow-hidden text-dark">
                    <div class="card-header text-white text-center py-4" style="background: linear-gradient(135deg, #078930 0%, #0f2942 100%) !important; border-bottom: 3px solid #fcdd09;">
                        <h3 class="mb-0 fw-bold"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>የፖርታል መግቢያ</h3>
                        <p class="mb-0 text-light small mt-1">Portal Login System / Seensa Paartalii</p>
                    </div>
                    
                    <div class="card-body p-4 p-md-4 bg-white">
                        <?php if($error): ?>
                            <div class='alert alert-danger fw-semibold text-center py-2' role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off" id="loginForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fa-solid fa-user text-success me-1"></i> Student Email ወይም Admission ID
                                </label>
                                <input type="text" name="email" value="" class="form-control form-control-lg" placeholder=" " oninput="this.value = this.value.trimStart();" autocomplete="off" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fa-solid fa-lock text-success me-1"></i> Password / Jecha Darbii
                                </label>
                                <div class="input-group">
                                    <input type="password" name="password" id="passwordField" value="" class="form-control form-control-lg" placeholder=" " oninput="this.value = this.value.trimStart();" autocomplete="new-password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 fs-5 py-2 fw-bold shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> ግባ / Login / Seeni
                            </button>
                        </form>

                        <hr class="my-3">

                        <div class="text-center">
                            <p class="text-muted mb-2">አካውንት የለዎትም? / Account hin qabduu?</p>
                            <a href="register.php" class="btn btn-outline-primary btn-sm px-4 py-2 fw-semibold rounded-pill">
                                <i class="fa-solid fa-user-plus me-1"></i> አዲስ አካውንት ይፍጠሩ (Register)
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS & Custom JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ველዎቹ ሁልጊዜ ባዶ እንዲሆኑ ለማረጋገጥ
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('loginForm').reset();
            }
        });

        // JavaScript ለ Password ማሳያ/መደበቂያ (Show/Hide Password)
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#passwordField');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>