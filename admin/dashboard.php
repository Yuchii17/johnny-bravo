<?php
session_start();
require '../config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 1. STAT CARDS DATA
$res_users = $conn->query("SELECT COUNT(*) as count FROM users");
$total_users = $res_users ? ($res_users->fetch_assoc()['count'] ?? 0) : 0;

$res_active = $conn->query("SELECT COUNT(*) as count FROM item_declarations WHERE status = 'Logged In'");
$active_personnel = $res_active ? ($res_active->fetch_assoc()['count'] ?? 0) : 0;

$res_tips = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM tip_ledger");
$total_tips = $res_tips ? ($res_tips->fetch_assoc()['total'] ?? 0.00) : 0.00;

$res_schedules = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE status = 'Active'");
$active_schedules = $res_schedules ? ($res_schedules->fetch_assoc()['count'] ?? 0) : 0;

// 2. CHART DATA (3D PIE: Users by Department)
$dept_data = [];
$res_dept = $conn->query("SELECT department, COUNT(*) as count FROM users GROUP BY department");
if ($res_dept) {
    while($row = $res_dept->fetch_assoc()) {
        $dept_data[] = [$row['department'] ?: 'Unassigned', (int)$row['count']];
    }
}

// 3. CHART DATA (3D COLUMN: Declarations Last 7 Days)
$dates = [];
$declarations = [];
$res_decl = $conn->query("
    SELECT declaration_date, COUNT(*) as count 
    FROM item_declarations 
    WHERE declaration_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY declaration_date 
    ORDER BY declaration_date ASC
");
if ($res_decl) {
    while($row = $res_decl->fetch_assoc()) {
        $dates[] = date("M d", strtotime($row['declaration_date']));
        $declarations[] = (int)$row['count'];
    }
}

// 4. CHART DATA (3D DONUT: Top 5 Tip Recipients)
$tip_data = [];
$res_top_tips = $conn->query("
    SELECT recipient_name, SUM(amount) as total 
    FROM tip_ledger 
    GROUP BY recipient_name 
    ORDER BY total DESC 
    LIMIT 5
");
if ($res_top_tips) {
    while($row = $res_top_tips->fetch_assoc()) {
        $tip_data[] = [$row['recipient_name'], (float)$row['total']];
    }
}

// 5. CHART DATA (3D COLUMN: Visitors by Month)
$vis_months = [];
$vis_counts = [];
$res_vis = $conn->query("
    SELECT DATE_FORMAT(declaration_date, '%b %Y') as month, COUNT(*) as count 
    FROM item_declarations 
    WHERE user_id LIKE 'VIS-%'
    GROUP BY DATE_FORMAT(declaration_date, '%Y-%m'), month
    ORDER BY DATE_FORMAT(declaration_date, '%Y-%m') ASC
    LIMIT 6
");
if ($res_vis) {
    while($row = $res_vis->fetch_assoc()) {
        $vis_months[] = $row['month'];
        $vis_counts[] = (int)$row['count'];
    }
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.3.0/highcharts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.3.0/highcharts-3d.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
        .highcharts-background { fill: transparent; }
        .highcharts-title { font-family: 'Plus Jakarta Sans', sans-serif !important; font-weight: 800 !important; color: #1e293b !important; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-8 z-20 shrink-0">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-black text-slate-800 tracking-tight">System Overview</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin User'); ?></p>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                </div>
                <div class="h-8 w-px bg-slate-200"></div>
                <a href="../logout.php" class="w-10 h-10 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition-colors shadow-sm" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8 space-y-8">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center gap-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out z-0"></div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-lg shadow-blue-500/30 z-10 shrink-0">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Registered Users</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1"><?php echo number_format($total_users); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center gap-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out z-0"></div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl shadow-lg shadow-emerald-500/30 z-10 shrink-0">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Currently Inside</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1"><?php echo number_format($active_personnel); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center gap-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out z-0"></div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-lg shadow-amber-500/30 z-10 shrink-0">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Total Tips Processed</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1"><span class="text-lg text-slate-400">₱</span><?php echo number_format($total_tips, 2); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center gap-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out z-0"></div>
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500 text-white flex items-center justify-center text-xl shadow-lg shadow-indigo-500/30 z-10 shrink-0">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="z-10">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Active Schedules</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-1"><?php echo number_format($active_schedules); ?></h3>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center justify-center">
                    <div id="columnChart3D" class="h-80 w-full"></div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center justify-center">
                    <div id="pieChart3D" class="h-80 w-full"></div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center justify-center">
                    <div id="donutChart3D" class="h-80 w-full"></div>
                </div>

                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex items-center justify-center">
                    <div id="visitorMonthChart3D" class="h-80 w-full"></div>
                </div>
                
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // Check if internet connection blocked the library from loading
            if (typeof Highcharts === 'undefined') {
                const errorHtml = '<div class="text-center text-slate-400"><i class="fas fa-wifi text-3xl mb-3"></i><p class="font-bold text-sm">Failed to load charts.</p><p class="text-xs mt-1">Check your network connection or console.</p></div>';
                document.getElementById('columnChart3D').innerHTML = errorHtml;
                document.getElementById('pieChart3D').innerHTML = errorHtml;
                document.getElementById('donutChart3D').innerHTML = errorHtml;
                document.getElementById('visitorMonthChart3D').innerHTML = errorHtml;
                return;
            }

            try {
                const chartColors = ['#2563eb', '#10b981', '#f59e0b', '#6366f1', '#ec4899', '#8b5cf6', '#14b8a6'];

                // 1. 3D Column Chart (Last 7 Days)
                Highcharts.chart('columnChart3D', {
                    chart: { type: 'column', options3d: { enabled: true, alpha: 15, beta: 15, depth: 50, viewDistance: 25 }, style: { fontFamily: 'Plus Jakarta Sans' } },
                    colors: ['#3b82f6'],
                    title: { text: 'Access Declarations (Last 7 Days)', align: 'left' },
                    xAxis: { categories: <?php echo json_encode($dates) ?: '[]'; ?>, labels: { skew3d: true, style: { fontSize: '12px', fontWeight: 'bold' } } },
                    yAxis: { title: { text: 'Total Persons' } },
                    plotOptions: { column: { depth: 25, borderRadius: 4 } },
                    series: [{ name: 'Entries', data: <?php echo json_encode($declarations) ?: '[]'; ?> }],
                    credits: { enabled: false }
                });

                // 2. 3D Pie Chart (Dept)
                Highcharts.chart('pieChart3D', {
                    chart: { type: 'pie', options3d: { enabled: true, alpha: 45, beta: 0 } },
                    colors: chartColors,
                    title: { text: 'Personnel by Department', align: 'left' },
                    plotOptions: { pie: { allowPointSelect: true, cursor: 'pointer', depth: 35, dataLabels: { enabled: true, format: '{point.name}: {point.y}' } } },
                    series: [{ type: 'pie', name: 'Total Users', data: <?php echo json_encode($dept_data) ?: '[]'; ?> }],
                    credits: { enabled: false }
                });

                // 3. 3D Donut Chart (Tips)
                Highcharts.chart('donutChart3D', {
                    chart: { type: 'pie', options3d: { enabled: true, alpha: 45 } },
                    colors: ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'],
                    title: { text: 'Top 5 Tip Recipients', align: 'left' },
                    subtitle: { text: 'Highest accumulated amounts distributed', align: 'left' },
                    plotOptions: { pie: { innerSize: '40%', depth: 45, dataLabels: { enabled: true, format: '<b>{point.name}</b><br>₱{point.y:.2f}' } } },
                    series: [{ name: 'Total Amount Received (₱)', data: <?php echo json_encode($tip_data) ?: '[]'; ?> }],
                    credits: { enabled: false }
                });

                // 4. NEW: 3D Column Chart (Visitors by Month)
                Highcharts.chart('visitorMonthChart3D', {
                    chart: { type: 'column', options3d: { enabled: true, alpha: 10, beta: 20, depth: 40, viewDistance: 25 }, style: { fontFamily: 'Plus Jakarta Sans' } },
                    colors: ['#8b5cf6'], // Purple tone for differentiation
                    title: { text: 'Monthly Visitor Traffic', align: 'left' },
                    subtitle: { text: 'Total entries registered as visitors', align: 'left' },
                    xAxis: { categories: <?php echo json_encode($vis_months) ?: '[]'; ?>, labels: { skew3d: true, style: { fontSize: '12px', fontWeight: 'bold' } } },
                    yAxis: { title: { text: 'Visitor Count' } },
                    plotOptions: { column: { depth: 25, borderRadius: 4, colorByPoint: true } },
                    colors: ['#8b5cf6', '#a855f7', '#c084fc', '#d8b4fe', '#e9d5ff', '#f3e8ff'], // Gradient effect
                    series: [{ name: 'Visitors', data: <?php echo json_encode($vis_counts) ?: '[]'; ?> }],
                    credits: { enabled: false },
                    legend: { enabled: false }
                });

            } catch (error) {
                console.error("Highcharts rendering error: ", error);
            }
        });
    </script>
</body>
</html>