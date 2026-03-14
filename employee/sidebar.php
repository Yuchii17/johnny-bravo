<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function getLinkStyle($page, $currentPage) {
    return $page == $currentPage 
        ? 'bg-white/70 shadow-sm text-blue-700 border border-white/60 backdrop-blur-md' 
        : 'text-slate-600 hover:bg-white/40 hover:text-blue-600 border border-transparent transition-all';
}

function getIconStyle($page, $currentPage) {
    return $page == $currentPage 
        ? 'text-blue-600 drop-shadow-sm' 
        : 'text-slate-400 group-hover:text-blue-500 transition-colors';
}
?>
<aside class="w-64 bg-white/40 backdrop-blur-xl border-r border-white/60 flex flex-col shrink-0 z-20 shadow-[4px_0_24px_rgba(0,0,0,0.02)] relative">
    <div class="h-24 flex items-center justify-center border-b border-white/50 mx-6">
        <a href="dashboard.php" class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-600 tracking-tight drop-shadow-sm">Employee Portal</a>
    </div>
    <nav class="flex-1 p-6 space-y-3">
        <a href="dashboard.php" class="<?php echo getLinkStyle('dashboard.php', $currentPage); ?> flex items-center gap-4 px-4 py-3.5 rounded-xl font-bold group">
            <i class="fas fa-tachometer-alt w-5 text-center <?php echo getIconStyle('dashboard.php', $currentPage); ?>"></i> Dashboard
        </a>
        <a href="users.php" class="<?php echo getLinkStyle('users.php', $currentPage); ?> flex items-center gap-4 px-4 py-3.5 rounded-xl font-bold group">
            <i class="fas fa-users w-5 text-center <?php echo getIconStyle('users.php', $currentPage); ?>"></i> Users
        </a>
        <a href="schedules.php" class="<?php echo getLinkStyle('schedules.php', $currentPage); ?> flex items-center gap-4 px-4 py-3.5 rounded-xl font-bold group">
            <i class="fas fa-calendar-alt w-5 text-center <?php echo getIconStyle('schedules.php', $currentPage); ?>"></i> Schedules
        </a>
    </nav>
    <div class="p-6 border-t border-white/50">
        <a href="../index.php" class="flex items-center justify-center gap-3 px-4 py-3.5 rounded-xl font-bold text-rose-600 bg-white/40 border border-white/50 backdrop-blur-md hover:bg-rose-50 hover:border-rose-200 hover:shadow-sm transition-all">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</aside>