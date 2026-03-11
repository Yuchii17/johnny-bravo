<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="h-20 bg-white border-b border-blue-100 flex items-center justify-between px-6 lg:px-10">
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-slate-800">Dashboard Overview</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-xs font-medium text-slate-500"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-semibold transition-colors text-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 lg:p-10">
            <h1 class="text-xl font-bold text-slate-700">Welcome to the Dashboard!</h1>
        </main>
    </div>
</body>
</html>