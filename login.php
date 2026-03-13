<?php
session_start();
require 'config.php';
require 'audit_logger.php';

$loginSuccess = false;
$errorMessage = "";
$redirectUrl = "index.php"; // Default redirection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $loginSuccess = true;
            
            // Log the login action
            log_audit($conn, $user['user_id'], $user['fullname'], $user['role'], 'LOGIN', 'User logged into the system');
            
            if ($user['role'] === 'Super Admin' || $user['role'] === 'Manager') {
                $redirectUrl = 'admin/dashboard.php';
            } elseif ($user['role'] === 'Employee') {
                $redirectUrl = 'employee/dashboard.php';
            } elseif ($user['role'] === 'Security') {
                $redirectUrl = 'security/dashboard.php';
            } else {
                $redirectUrl = 'index.php'; 
            }

        } else {
            $errorMessage = "Invalid password.";
        }
    } else {
        $errorMessage = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - John Hay Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        body, html { height: 100%; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F3F4F6; overflow-x: hidden; }
        .main-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-card { background: white; border-radius: 2rem; width: 100%; max-width: 1100px; display: flex; overflow: hidden; box-shadow: 0 40px 80px -20px rgba(53, 88, 114, 0.25); border: 1px solid #e8eef5; position: relative; }
        .form-section { flex: 1.2; padding: 4rem 3.5rem; z-index: 10; background: white; }
        .image-section { flex: 1; position: relative; background-image: url('https://johnhayhotels.com/wp-content/uploads/2025/09/garding-wing-xmas-09-20251.jpg'); background-size: cover; background-repeat: no-repeat; background-position: center; }
        .image-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(53,88,114,0.35), rgba(0,0,0,0.15)); box-shadow: inset 0 0 60px rgba(0,0,0,0.25); }
        .input-group { position: relative; margin-bottom: 1.25rem; }
        .input-field { width: 100%; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 0.75rem; padding: 0.875rem 1rem 0.875rem 3rem; font-size: 0.9rem; transition: all 0.2s ease; color: #1E293B; }
        .input-field:focus { background-color: white; border-color: #3B82F6; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1rem; }
        .btn-primary { background-color: #3B82F6; color: white; padding: 1rem 2rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.95rem; transition: all 0.2s ease; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3); width: 100%; margin-top: 1rem; }
        .btn-primary:hover { background-color: #2563EB; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4); }
        @media (max-width: 768px) { .auth-card { flex-direction: column; } .image-section { display: none; } .form-section { padding: 2.5rem 1.5rem; } }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="auth-card">
            <div class="form-section">
                <div class="flex items-center justify-center">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center">
                            <div class="logo">
                                <img src="asset/john-logo.jpg" alt="Logo" style="width: 150px;" class="circle-logo">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="max-w-md text-center mx-auto mt-4">
                    <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-[0.2em] mb-3">Welcome Back</p>
                    <h1 class="text-4xl font-extrabold text-slate-900 mb-8">Sign In<span class="text-blue-600">.</span></h1>

                    <form method="POST" action="" class="space-y-4">
                        <div class="input-group">
                            <i class="far fa-envelope input-icon"></i>
                            <input type="email" name="email" required placeholder="Email Address" class="input-field">
                        </div>

                        <div class="input-group">
                            <i class="far fa-lock-alt input-icon"></i>
                            <input type="password" name="password" required placeholder="Password" class="input-field">
                        </div>

                        <button type="submit" class="btn-primary">Sign In</button>
                        <p class="text-slate-500 mb-8 mt-4 text-sm font-medium">Don't have an account? <a href="register.php" class="text-blue-600 hover:underline">Register</a></p><br>
                        <span class="font-bold text-slate-800 tracking-tight">Camp John Hay</span>
                    </form>
                </div>
            </div>

            <div class="image-section">
                <div class="image-overlay"></div>
                
                <div class="absolute bottom-10 right-10 text-right">
                    <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 inline-block">
                        <h3 class="text-white font-black text-2xl tracking-tighter mb-1 uppercase">CAMP JOHN HAY</h3>
                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-[0.4em]">Secure Access Point</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($loginSuccess): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Login Successfully!',
            text: 'Welcome back, <?php echo $_SESSION['fullname']; ?>',
            showConfirmButton: false,
            timer: 1000
        }).then(() => {
            window.location.href = '<?php echo $redirectUrl; ?>';
        });
    </script>
    <?php elseif ($errorMessage != ""): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Login Failed', text: '<?php echo $errorMessage; ?>', confirmButtonColor: '#3B82F6' });
    </script>
    <?php endif; ?>

</body>
</html>