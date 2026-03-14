<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'Manager') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Security') {
        header("Location: security/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Manor at Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Custom animations for the background blobs */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen relative overflow-x-hidden selection:bg-blue-500/30">

    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 -left-10 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob"></div>
        <div class="absolute top-1/4 -right-10 w-96 h-96 bg-sky-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-10 left-1/3 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <nav class="sticky top-0 z-50 bg-white/40 backdrop-blur-xl border-b border-white/60 shadow-[0_4px_30px_rgba(31,38,135,0.05)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center gap-3">
                    <img src="asset/john-logo.jpg" alt="John Hay Logo" style="border-radius: 50%;" class="h-10 w-10 object-cover shadow-sm border border-white/50">
                    <span class="text-xl md:text-2xl font-extrabold text-blue-900 tracking-tight shadow-sm">The Manor</span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-slate-600 font-bold text-sm hidden md:block">Hello, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                        <a href="logout.php" class="px-5 py-2 rounded-xl bg-white/50 border border-white/60 text-slate-700 hover:bg-white/80 hover:text-blue-600 shadow-sm font-bold transition-all duration-300">Log Out</a>
                    <?php else: ?>
                        <a href="login.php" class="text-slate-600 hover:text-blue-600 font-bold transition text-sm md:text-base">Login</a>
                        <a href="register.php" class="px-5 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 hover:-translate-y-0.5 shadow-md shadow-blue-500/30 font-bold transition-all duration-300 text-sm md:text-base">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center text-center px-4 relative z-10 py-12 md:py-20">
        <div class="max-w-5xl w-full">
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-black text-slate-800 mb-6 tracking-tight leading-tight drop-shadow-sm">
                Experience Elegance at <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-sky-500">Camp John Hay</span>
            </h1>
            <p class="text-base md:text-lg text-slate-600 font-medium mb-12 leading-relaxed max-w-2xl mx-auto">
                Discover a haven of tranquility and world-class service. Whether you are a valued guest, a dedicated employee, or part of our management team, excellence awaits you.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mb-12">
                
                <div class="group bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] border border-white/60 shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] hover:shadow-xl hover:bg-white/60 hover:-translate-y-2 transition-all duration-500 flex flex-col items-center text-center relative overflow-hidden">
                    <div class="w-16 h-16 bg-blue-100/80 border border-blue-200 text-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm transform group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 mb-3">Employee</h3>
                    <p class="text-sm font-medium text-slate-500 mb-8">Log your daily shift and declare items for a seamless entry.</p>
                    <a href="employee/dashboard.php" class="w-full mt-auto px-6 py-3.5 bg-white/70 border border-white/80 text-blue-700 rounded-xl font-black text-sm hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-colors shadow-sm relative z-10">
                        Proceed to Entry
                    </a>
                </div>

                <div class="group bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] border border-white/60 shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] hover:shadow-xl hover:bg-white/60 hover:-translate-y-2 transition-all duration-500 flex flex-col items-center text-center relative overflow-hidden">
                    <div class="w-16 h-16 bg-sky-100/80 border border-sky-200 text-sky-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm transform group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-user-graduate text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 mb-3">OJT</h3>
                    <p class="text-sm font-medium text-slate-500 mb-8">On-the-job trainees can log their attendance and declare items here.</p>
                    <a href="ojt/selection.php" class="w-full mt-auto px-6 py-3.5 bg-white/70 border border-white/80 text-sky-700 rounded-xl font-black text-sm hover:bg-sky-600 hover:text-white hover:border-sky-600 transition-colors shadow-sm relative z-10">
                        Proceed to Entry
                    </a>
                </div>

                <div class="group bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] border border-white/60 shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] hover:shadow-xl hover:bg-white/60 hover:-translate-y-2 transition-all duration-500 flex flex-col items-center text-center relative overflow-hidden">
                    <div class="w-16 h-16 bg-indigo-100/80 border border-indigo-200 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm transform group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-user-friends text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 mb-3">Visitor</h3>
                    <p class="text-sm font-medium text-slate-500 mb-8">All visitors must declare their items before entering the premises.</p>
                    <a href="visitor/selection.php" class="w-full mt-auto px-6 py-3.5 bg-white/70 border border-white/80 text-indigo-700 rounded-xl font-black text-sm hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-colors shadow-sm relative z-10">
                        Proceed to Entry
                    </a>
                </div>
            </div>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="text-center mt-4 bg-white/40 backdrop-blur-md border border-white/60 py-4 px-6 rounded-2xl inline-block shadow-sm">
                    <p class="text-sm font-bold text-slate-600">Are you an Admin or Security? <a href="login.php" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors">Login here</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white/40 backdrop-blur-md border-t border-white/60 py-6 relative z-10 mt-auto">
        <div class="text-center text-slate-500 font-bold text-xs tracking-wide">
            &copy; <?php echo date("Y"); ?> The Manor at Camp John Hay. All rights reserved.
        </div>
    </footer>

</body>
</html>