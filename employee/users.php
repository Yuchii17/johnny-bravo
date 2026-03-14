<?php
session_start();
require '../config.php';
require '../audit_logger.php';

$successMsg = '';
$errorMsg = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $fullName = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department = $_POST['department'];

    if (empty($fullName) || empty($email) || empty($password) || empty($role) || empty($department)) {
        $errorMsg = "All fields are required.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $generatedId = strtoupper(substr(str_replace(' ', '', $fullName), 0, 4)) . rand(1000, 9999);

        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            $errorMsg = "Email address already exists.";
        } else {
            $insertQuery = $conn->prepare("INSERT INTO users (user_id, fullname, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
            $insertQuery->bind_param("ssssss", $generatedId, $fullName, $email, $hashedPassword, $role, $department);
            if ($insertQuery->execute()) {
                $successMsg = "User created successfully!";
                log_audit($conn, $generatedId, $fullName, $role, 'USER_CREATED', "New user account created.");
            } else {
                $errorMsg = "Failed to create user.";
            }
        }
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Glassmorphism</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.5); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-slate-50 relative text-slate-800">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none bg-gradient-to-br from-blue-50 via-slate-100 to-white">
        <div class="absolute top-0 -left-10 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob"></div>
        <div class="absolute top-10 -right-10 w-96 h-96 bg-sky-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-10 left-1/3 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 flex w-full h-full">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-8 h-full overflow-hidden flex flex-col">
            <header class="mb-8 shrink-0">
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">User Management</h1>
                <p class="text-xs font-bold text-blue-600 mt-1 uppercase tracking-widest">Create and Manage Accounts</p>
            </header>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 flex-1 overflow-hidden">
                <div class="lg:col-span-1 bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 flex flex-col overflow-y-auto">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-3"><i class="fas fa-user-plus text-blue-500"></i> Create User</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Full Name</label>
                            <input type="text" name="fullname" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Email</label>
                            <input type="email" name="email" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Password</label>
                            <input type="password" name="password" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Role</label>
                            <select name="role" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all text-slate-700">
                                <option value="Employee">Employee</option>
                                <option value="OJT">OJT</option>
                                <option value="Visitor">Visitor</option>
                                <option value="Security">Security</option>
                                <option value="Manager">Manager</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Department</label>
                            <input type="text" name="department" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                        </div>
                        <button type="submit" name="create_user" class="w-full mt-4 bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Create Account</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 flex flex-col overflow-hidden">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-3 shrink-0"><i class="fas fa-users-cog text-blue-500"></i> Directory</h2>
                    <div class="overflow-auto flex-1 pr-2 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white/60 backdrop-blur-md shadow-sm rounded-xl z-10">
                                <tr class="text-[11px] font-black text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 rounded-l-xl">Name</th>
                                    <th class="p-4">Email</th>
                                    <th class="p-4">Role</th>
                                    <th class="p-4 rounded-r-xl">Department</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr class="hover:bg-white/30 transition-colors group">
                                    <td class="p-4 font-bold text-slate-800"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td class="p-4 text-sm font-semibold text-slate-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-4 text-sm font-semibold text-slate-600">
                                        <span class="px-3 py-1 bg-white/50 border border-white/80 rounded-lg text-xs font-bold text-blue-800">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm font-semibold text-slate-600"><?php echo htmlspecialchars($user['department']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const swalConfig = {
            confirmButtonColor: '#2563eb',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: 'rgba(0, 0, 0, 0.3)'
        };
        <?php if ($successMsg): ?>
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Success', text: '<?php echo $successMsg; ?>' });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
        Swal.fire({ ...swalConfig, icon: 'error', title: 'Error', text: '<?php echo $errorMsg; ?>' });
        <?php endif; ?>
    </script>
</body>
</html>