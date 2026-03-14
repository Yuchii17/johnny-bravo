<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function getLinkStyle($pageName, $currentPage) {
    // Adding glassmorphism touches to the links
    $baseStyle = "flex items-center gap-3 px-4 py-3 mx-4 my-1 rounded-xl transition-all duration-300 group ";
    if ($currentPage == $pageName) {
        // Active state: Semi-transparent solid brand color with blur
        return $baseStyle . "bg-blue-600/90 backdrop-blur-sm text-white shadow-[0_4px_15px_rgba(37,99,235,0.2)] border border-blue-400/30 font-semibold";
    } else {
        // Inactive state: Hover glass effect
        return $baseStyle . "text-slate-600 hover:bg-white/60 hover:shadow-sm hover:border-white/50 border border-transparent hover:text-blue-700 font-medium hover:translate-x-1";
    }
}

function getIconStyle($pageName, $currentPage) {
    if ($currentPage == $pageName) {
        return "text-white drop-shadow-sm";
    } else {
        return "text-slate-400 group-hover:text-blue-600 transition-colors duration-300";
    }
}
?>

<aside class="w-72 bg-white/30 backdrop-blur-xl border-r border-white/50 flex flex-col h-screen sticky top-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-20">
    
    <div class="h-24 flex items-center px-6 border-b border-white/50 bg-white/10">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border border-white/80 shadow-sm flex-shrink-0 bg-white/60 backdrop-blur-sm flex items-center justify-center ring-2 ring-white/40">
                <img src="../asset/john-logo.jpg" alt="Logo" class="w-full h-full object-cover mix-blend-multiply" onerror="this.outerHTML='<div class=\'w-full h-full bg-blue-100/50 text-blue-700 flex items-center justify-center font-bold text-lg backdrop-blur-sm\'>JH</div>'">
            </div>
            <div class="flex flex-col">
                <h1 class="text-[15px] font-extrabold text-slate-800 tracking-tight leading-tight">Camp John Hay</h1>
                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-[0.15em] mt-0.5 drop-shadow-sm">Admin Portal</p>
            </div>
        </div>
    </div>

    <div class="px-8 py-4 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mt-2 drop-shadow-sm">Main Menu</div>
    
    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden">
        <a href="dashboard.php" class="<?php echo getLinkStyle('dashboard.php', $currentPage); ?>">
            <i class="fas fa-chart-pie w-5 text-center <?php echo getIconStyle('dashboard.php', $currentPage); ?>"></i> Dashboard
        </a>
        
        <a href="employees.php" class="<?php echo getLinkStyle('employees.php', $currentPage); ?>">
            <i class="fas fa-users w-5 text-center <?php echo getIconStyle('employees.php', $currentPage); ?>"></i> Employees
        </a>
        
        <a href="schedules.php" class="<?php echo getLinkStyle('schedules.php', $currentPage); ?>">
            <i class="fas fa-calendar-alt w-5 text-center <?php echo getIconStyle('schedules.php', $currentPage); ?>"></i> Schedules
        </a>
        
        <a href="user-access.php" class="<?php echo getLinkStyle('user-access.php', $currentPage); ?>">
            <i class="fas fa-shield-alt w-5 text-center <?php echo getIconStyle('user-access.php', $currentPage); ?>"></i> User Access
        </a>
        
        <a href="visitors.php" class="<?php echo getLinkStyle('visitors.php', $currentPage); ?>">
            <i class="fas fa-id-badge w-5 text-center <?php echo getIconStyle('visitors.php', $currentPage); ?>"></i> Visitors
        </a>

        <a href="tip-history.php" class="<?php echo getLinkStyle('tip-history.php', $currentPage); ?>">
            <i class="fas fa-coins w-5 text-center <?php echo getIconStyle('tip-history.php', $currentPage); ?>"></i> Tip History
        </a>

        <a href="audit-logs.php" class="<?php echo getLinkStyle('audit-logs.php', $currentPage); ?>">
            <i class="fas fa-clipboard-list w-5 text-center <?php echo getIconStyle('audit-logs.php', $currentPage); ?>"></i> Audit Logs
        </a>
    </nav>

    <div class="p-6 border-t border-white/50 mt-auto bg-white/10">
        <div class="bg-white/40 backdrop-blur-md rounded-2xl p-4 text-center border border-white/60 shadow-[0_4px_15px_rgba(0,0,0,0.03)] relative overflow-hidden group hover:bg-white/60 transition-colors duration-300">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-300 rounded-full mix-blend-multiply opacity-30 filter blur-md transition-transform group-hover:scale-150 duration-500"></div>
            <div class="absolute -left-4 -bottom-4 w-16 h-16 bg-emerald-300 rounded-full mix-blend-multiply opacity-20 filter blur-md transition-transform group-hover:scale-150 duration-500"></div>
            
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 relative z-10">System Status</p>
            
            <div class="flex items-center justify-center gap-2 relative z-10">
                <span class="relative flex h-3 w-3 drop-shadow-md">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border border-white/50"></span>
                </span>
                <p class="text-[13px] font-bold text-slate-800 drop-shadow-sm">Online & Secure</p>
            </div>
        </div>
    </div>
</aside>