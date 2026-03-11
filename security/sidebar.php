<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function getLinkStyle($pageName, $currentPage) {
    $baseStyle = "flex items-center gap-3 px-4 py-3 mx-4 my-1 rounded-xl transition-all duration-300 group ";
    if ($currentPage == $pageName) {
        return $baseStyle . "bg-blue-600 text-white shadow-md shadow-blue-500/20 font-semibold";
    } else {
        return $baseStyle . "text-slate-500 hover:bg-slate-50 hover:text-blue-600 font-medium hover:translate-x-1";
    }
}

function getIconStyle($pageName, $currentPage) {
    if ($currentPage == $pageName) {
        return "text-white";
    } else {
        return "text-slate-400 group-hover:text-blue-600 transition-colors duration-300";
    }
}
?>

<aside class="w-72 bg-white border-r border-slate-100 flex flex-col h-screen sticky top-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-20 shrink-0">
    
    <div class="h-24 flex items-center px-6 border-b border-slate-100 bg-gradient-to-b from-slate-50/50 to-white">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-sm flex-shrink-0 bg-white flex items-center justify-center ring-2 ring-slate-100">
                <img src="../asset/john-logo.jpg" alt="Logo" class="w-full h-full object-cover" onerror="this.outerHTML='<div class=\'w-full h-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg\'>JH</div>'">
            </div>
            <div class="flex flex-col">
                <h1 class="text-[15px] font-extrabold text-slate-800 tracking-tight leading-tight">Camp John Hay</h1>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-[0.15em] mt-0.5">Security Portal</p>
            </div>
        </div>
    </div>

    <div class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-2">Main Menu</div>
    
    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden">
        <a href="dashboard.php" class="<?php echo getLinkStyle('dashboard.php', $currentPage); ?>">
            <i class="fas fa-chart-pie w-5 text-center <?php echo getIconStyle('dashboard.php', $currentPage); ?>"></i> Dashboard
        </a>
        
        <a href="ledger.php" class="<?php echo getLinkStyle('ledger.php', $currentPage); ?>">
            <i class="fas fa-book w-5 text-center <?php echo getIconStyle('ledger.php', $currentPage); ?>"></i> Ledger
        </a>
        
        <a href="visitor-list.php" class="<?php echo getLinkStyle('visitor-list.php', $currentPage); ?>">
            <i class="fas fa-users w-5 text-center <?php echo getIconStyle('visitor-list.php', $currentPage); ?>"></i> Visitor List
        </a>

        <div class="px-8 py-4 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-4">Account</div>
        
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 mx-4 my-1 rounded-xl transition-all duration-300 group text-red-500 hover:bg-red-50 hover:text-red-600 font-medium hover:translate-x-1">
            <i class="fas fa-sign-out-alt w-5 text-center text-red-400 group-hover:text-red-600 transition-colors duration-300"></i> Sign Out
        </a>
    </nav>

    <div class="p-6 border-t border-slate-100 mt-auto">
        <div class="bg-slate-50 rounded-2xl p-4 text-center border border-slate-100 shadow-sm relative overflow-hidden group hover:bg-slate-100 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-100 rounded-full opacity-50 transition-transform group-hover:scale-150"></div>
            
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">System Status</p>
            
            <div class="flex items-center justify-center gap-2 relative z-10">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <p class="text-[13px] font-bold text-slate-700">Online & Secure</p>
            </div>
        </div>
    </div>
</aside>