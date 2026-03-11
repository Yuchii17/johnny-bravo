<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'Manager') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Employee') {
        header("Location: employee/dashboard.php");
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
</head>
<body class="bg-blue-50 font-sans flex flex-col min-h-screen">

    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <span class="text-2xl font-extrabold text-blue-800 tracking-tight">The Manor</span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-blue-600 font-medium">Hello, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                        <a href="logout.php" class="px-5 py-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 font-semibold transition">Log Out</a>
                    <?php else: ?>
                        <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold transition">Login</a>
                        <a href="register.php" class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold transition shadow-md">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center text-center px-4">
        <div class="max-w-3xl">
            <h1 class="text-5xl md:text-6xl font-extrabold text-blue-900 mb-6 drop-shadow-sm">
                Experience Elegance at <br><span class="text-blue-600">Camp John Hay</span>
            </h1>
            <p class="text-xl text-blue-700 mb-10 leading-relaxed">
                Discover a haven of tranquility and world-class service. Whether you are a valued guest, a dedicated employee, or part of our management team, excellence awaits you.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-10">
                <a href="ojt/selection.php" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold text-lg hover:bg-blue-700 transition shadow-lg flex items-center justify-center gap-3">
                    <i class="fas fa-user-graduate"></i> OJT Entry
                </a>
                <a href="visitor/selection.php" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl font-bold text-lg hover:bg-emerald-700 transition shadow-lg flex items-center justify-center gap-3">
                    <i class="fas fa-user-friends"></i> Visitor Entry
                </a>
            </div>

            <?php if (!isset($_SESSION['user_id'])): ?>
            <?php else: ?>
                <div class="bg-white inline-block p-6 rounded-xl border border-blue-100 shadow-md">
                    <h3 class="text-2xl font-bold text-blue-800 mb-2">Welcome to your Portal</h3>
                    <p class="text-blue-600">Your Role: <span class="font-bold bg-blue-100 px-3 py-1 rounded-md ml-1"><?php echo htmlspecialchars($_SESSION['role']); ?></span></p>
                    <p class="text-blue-600 mt-2">Your ID: <span class="font-bold"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white py-6 border-t border-blue-100">
        <div class="text-center text-blue-500 font-medium">
            &copy; <?php echo date("Y"); ?> The Manor at Camp John Hay. All rights reserved.
        </div>
    </footer>

</body>
</html>