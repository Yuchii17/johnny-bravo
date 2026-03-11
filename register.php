<?php
require 'config.php';

$registerSuccess = false;
$errorMessage = "";
$generatedId = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName   = $_POST['full_name'];
    $email      = $_POST['email'];
    $password   = $_POST['password']; 
    $role       = $_POST['role'];
    $department = $_POST['department'];

    // Ensure only permitted roles can be registered through this form
    $allowedRoles = ['Employee', 'OJT', 'Visitor'];
    if (!in_array($role, $allowedRoles)) {
        $errorMessage = "Invalid role selected.";
    } else {
        $prefix = "";
        switch ($role) {
            case 'Employee':   $prefix = "EMP"; break;
            case 'Visitor':    $prefix = "VST"; break;
            case 'OJT':        $prefix = "OJT"; break;
            default:           $prefix = "USR"; break; 
        }

        $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
        $countQuery->bind_param("s", $role);
        $countQuery->execute();
        $result = $countQuery->get_result();
        $row = $result->fetch_assoc();
        
        $nextNumber = $row['total'] + 1;
        $generatedId = $prefix . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertQuery = $conn->prepare("INSERT INTO users (user_id, fullname, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $insertQuery->bind_param("ssssss", $generatedId, $fullName, $email, $hashedPassword, $role, $department);

        if ($insertQuery->execute()) {
            $registerSuccess = true;
        } else {
            $errorMessage = $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Register - John Hay Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        /* Set strict height limits and prevent body scrolling */
        body, html { height: 100vh; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F3F4F6; overflow: hidden; }
        
        .main-container { height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; box-sizing: border-box; }
        
        /* Limit max height of the card */
        .auth-card { background: white; border-radius: 2rem; width: 100%; max-width: 1100px; height: 90vh; max-height: 680px; display: flex; overflow: hidden; box-shadow: 0 40px 80px -20px rgba(53, 88, 114, 0.25); border: 1px solid #e8eef5; position: relative; }
        
        /* Adjusted padding and flex alignment */
        .form-section { flex: 1.2; padding: 2rem 3rem; z-index: 10; background: white; display: flex; flex-direction: column; justify-content: center; }
        .image-section { flex: 1; position: relative; background-image: url('https://johnhayhotels.com/wp-content/uploads/2025/09/garding-wing-xmas-09-20251.jpg'); background-size: cover; background-repeat: no-repeat; background-position: center; }
        .image-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(53,88,114,0.35), rgba(0,0,0,0.15)); box-shadow: inset 0 0 60px rgba(0,0,0,0.25); }
        
        /* Tighter input spacing */
        .input-group { position: relative; margin-bottom: 0.8rem; }
        .input-field { width: 100%; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 0.75rem; padding: 0.75rem 1rem 0.75rem 2.8rem; font-size: 0.85rem; transition: all 0.2s ease; color: #1E293B; }
        .input-field:focus { background-color: white; border-color: #3B82F6; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.95rem; }
        
        .btn-primary { background-color: #3B82F6; color: white; padding: 0.875rem 2rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.95rem; transition: all 0.2s ease; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3); width: 100%; margin-top: 0.5rem; }
        .btn-primary:hover { background-color: #2563EB; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4); }
        
        @media (max-width: 768px) { 
            body, html { overflow-y: auto; }
            .auth-card { flex-direction: column; height: auto; max-height: none; } 
            .image-section { display: none; } 
            .form-section { padding: 2rem 1.5rem; justify-content: flex-start; } 
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="auth-card">
            <div class="form-section">
                <div class="flex items-center justify-center mb-2">
                    <img src="asset/john-logo.jpg" alt="Logo" style="width: 110px;" class="circle-logo">
                </div>

                <div class="max-w-2xl text-center mx-auto">
                    <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[0.2em] mb-1">Join the Platform</p>
                    <h1 class="text-3xl font-extrabold text-slate-900 mb-5">Create Account<span class="text-blue-600">.</span></h1>

                    <form method="POST" action="" class="space-y-3">
                        <div class="input-group">
                            <i class="far fa-user input-icon"></i>
                            <input type="text" name="full_name" required placeholder="Full Name" class="input-field">
                        </div>

                        <div class="input-group">
                            <i class="far fa-envelope input-icon"></i>
                            <input type="email" name="email" required placeholder="Email Address" class="input-field">
                        </div>

                        <div class="input-group">
                            <i class="far fa-lock-alt input-icon"></i>
                            <input type="password" name="password" required placeholder="Password" class="input-field">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                            <div class="input-group">
                                <i class="far fa-briefcase input-icon"></i>
                                <select name="role" required class="input-field appearance-none">
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="Employee">Employee</option>
                                    <option value="OJT">OJT</option>
                                    <option value="Visitor">Visitor</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <i class="far fa-building input-icon"></i>
                                <select name="department" required class="input-field appearance-none">
                                    <option value="" disabled selected>Select Department</option>
                                    <option value="MIS Department">MIS Department</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Human Capital">Human Capital</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Front Office">Front Office</option>
                                    <option value="House Keeping">House Keeping</option>
                                    <option value="Security Dept">Security Dept</option>
                                    <option value="Training Coordinator">Training Coordinator</option>
                                    <option value="Medical Clinic">Medical Clinic</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Telephone Operator">Telephone Operator</option>
                                    <option value="Business Visit">Business Visit</option>
                                    <option value="Personal Visit">Personal Visit</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">Create Account</button>
                        
                        <div class="mt-4">
                            <p class="text-slate-500 text-[13px] font-medium mb-1">Already a member? <a href="login.php" class="text-blue-600 hover:underline">Sign In</a></p>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Camp John Hay</span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="image-section">
                <div class="image-overlay"></div>
                <div class="absolute bottom-8 right-8 text-right">
                    <div class="bg-white/10 backdrop-blur-md p-5 rounded-3xl border border-white/20 inline-block">
                        <h3 class="text-white font-black text-xl tracking-tighter mb-1 uppercase">CAMP JOHN HAY</h3>
                        <p class="text-white/60 text-[9px] font-bold uppercase tracking-[0.4em]">Secure Access Point</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($registerSuccess): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Created Successfully!',
            html: 'Your generated System ID is: <br><strong style="font-size: 24px; color: #3B82F6;"><?php echo $generatedId; ?></strong>',
            showConfirmButton: false, // Hides the OK button
            timer: 1500, // 1.5 seconds
            timerProgressBar: true // Shows the depleting progress bar
        }).then(() => {
            // Automatically fires when the timer runs out
            window.location.href = 'login.php';
        });
    </script>
    <?php elseif ($errorMessage != ""): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Registration Failed', text: '<?php echo $errorMessage; ?>', confirmButtonColor: '#3B82F6' });
    </script>
    <?php endif; ?>

</body>
</html>