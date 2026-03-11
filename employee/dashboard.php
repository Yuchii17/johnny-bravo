<?php
session_start();
// Redirect to login if not logged in or not an Employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="sidebar w-64 bg-slate-900 text-white flex flex-col h-full">
        <div class="p-6 flex items-center justify-center border-b border-slate-800">
            <div class="text-center">
                <h2 class="text-xl font-black tracking-tight uppercase">Camp John Hay</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">Employee Portal</p>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-xl font-medium transition-colors">
                <i class="fas fa-home w-5"></i>
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors">
                <i class="fas fa-tasks w-5"></i>
                My Tasks
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors">
                <i class="fas fa-user w-5"></i>
                Profile
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-xl font-medium transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i>
                Sign Out
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8">
            <h1 class="text-lg font-bold text-slate-800">Employee Dashboard</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-xs text-slate-500 font-medium">Staff Member</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 border border-slate-300">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl p-8 text-white shadow-lg mb-8">
                <h2 class="text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars(explode(' ', trim($_SESSION['fullname']))[0]); ?>! 👋</h2>
                <p class="text-blue-100">Here is what's happening at Camp John Hay today.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Announcements</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="text-blue-500 mt-1"><i class="fas fa-bullhorn"></i></div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">Quarterly Meeting Update</p>
                                <p class="text-xs text-slate-500">Scheduled for Friday at 10:00 AM.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Quick Links</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button class="p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition text-sm font-semibold text-slate-700 flex flex-col items-center gap-2">
                            <i class="fas fa-calendar-alt text-xl text-blue-500"></i>
                            Leave Request
                        </button>
                        <button class="p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition text-sm font-semibold text-slate-700 flex flex-col items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-xl text-green-500"></i>
                            Payslips
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>