<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';

// Security Check - Only Super Admin and Manager can view audit logs
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Manager'])) {
    header("Location: ../index.php");
    exit();
}

$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';

$query_base = "SELECT * FROM audit_logs WHERE (fullname LIKE ? OR action LIKE ? OR details LIKE ?)";
$params = ["%$search%", "%$search%", "%$search%"];
$types = "sss";

if ($role_filter) {
    $query_base .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($action_filter) {
    $query_base .= " AND action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

// Count total for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query_base);
$stmt_count = $conn->prepare($count_query);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch logs
$query_base .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt_logs = $conn->prepare($query_base);
$stmt_logs->bind_param($types, ...$params);
$stmt_logs->execute();
$logs_result = $stmt_logs->get_result();

// Get unique actions for filter
$actions_res = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
$roles_res = $conn->query("SELECT DISTINCT role FROM audit_logs WHERE role IS NOT NULL ORDER BY role ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gradient-to-br from-indigo-50 via-slate-100 to-cyan-50">
    
    <?php include 'sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        
        <header class="bg-white/60 backdrop-blur-xl border-b border-white/50 h-24 flex items-center justify-between px-10 shrink-0 shadow-sm z-10 relative">
            <div class="flex items-center gap-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Audit Logs</h1>
                    <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">System Activity Monitoring</p>
                </div>
            </div>
            
            <form method="GET" class="flex items-center gap-3">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." class="bg-white/50 backdrop-blur-md border border-white/60 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 w-64 transition-all shadow-sm">
                </div>
                
                <select name="role" class="bg-white/50 backdrop-blur-md border border-white/60 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <option value="">All Roles</option>
                    <?php while($r = $roles_res->fetch_assoc()): ?>
                        <option value="<?php echo $r['role']; ?>" <?php echo $role_filter == $r['role'] ? 'selected' : ''; ?>><?php echo $r['role']; ?></option>
                    <?php endwhile; ?>
                </select>

                <select name="action" class="bg-white/50 backdrop-blur-md border border-white/60 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <option value="">All Actions</option>
                    <?php while($a = $actions_res->fetch_assoc()): ?>
                        <option value="<?php echo $a['action']; ?>" <?php echo $action_filter == $a['action'] ? 'selected' : ''; ?>><?php echo $a['action']; ?></option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="bg-slate-800/90 backdrop-blur-md text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-700 transition-colors shadow-sm border border-slate-700/50">Filter</button>
                <?php if($search || $role_filter || $action_filter): ?>
                    <a href="audit-logs.php" class="bg-rose-50/80 backdrop-blur-md text-rose-600 border border-rose-100 px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-rose-100 shadow-sm">Clear</a>
                <?php endif; ?>
            </form>
        </header>

        <div class="flex-1 p-8 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-white shadow-xl shadow-slate-200/40 flex items-center gap-5">
                    <div class="w-14 h-14 bg-blue-50/80 backdrop-blur-sm text-blue-600 rounded-2xl flex items-center justify-center text-2xl border border-blue-100/50">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Logs</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo number_format($total_records); ?></h3>
                    </div>
                </div>
                <div class="bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-white shadow-xl shadow-slate-200/40 flex items-center gap-5">
                    <div class="w-14 h-14 bg-emerald-50/80 backdrop-blur-sm text-emerald-600 rounded-2xl flex items-center justify-center text-2xl border border-emerald-100/50">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div>
                        <?php
                        $login_count = $conn->query("SELECT COUNT(*) as total FROM audit_logs WHERE action = 'LOGIN'")->fetch_assoc()['total'];
                        ?>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Logins Recorded</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo number_format($login_count); ?></h3>
                    </div>
                </div>
                <div class="bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-white shadow-xl shadow-slate-200/40 flex items-center gap-5">
                    <div class="w-14 h-14 bg-amber-50/80 backdrop-blur-sm text-amber-600 rounded-2xl flex items-center justify-center text-2xl border border-amber-100/50">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <?php
                        $decl_count = $conn->query("SELECT COUNT(*) as total FROM audit_logs WHERE action = 'ITEM_DECLARATION'")->fetch_assoc()['total'];
                        ?>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Declarations</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo number_format($decl_count); ?></h3>
                    </div>
                </div>
            </div>

            <div class="bg-white/60 backdrop-blur-xl rounded-3xl shadow-xl shadow-slate-200/40 border border-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/40 text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-white/60">
                                <th class="px-8 py-5">Timestamp</th>
                                <th class="px-8 py-5">User / Fullname</th>
                                <th class="px-8 py-5">Action</th>
                                <th class="px-8 py-5">Details</th>
                                <th class="px-8 py-5">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/50">
                            <?php if($logs_result->num_rows > 0): ?>
                                <?php while($row = $logs_result->fetch_assoc()): ?>
                                <tr class="hover:bg-white/50 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-700"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span>
                                            <span class="text-[10px] font-bold text-slate-500"><?php echo date("h:i A", strtotime($row['created_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest"><?php echo htmlspecialchars($row['role'] ?? 'Public'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <?php
                                        $action_class = "bg-slate-100/80 text-slate-600 border border-slate-200/50";
                                        if($row['action'] == 'LOGIN') $action_class = "bg-emerald-50/80 text-emerald-600 border border-emerald-100/50";
                                        if($row['action'] == 'REGISTER') $action_class = "bg-purple-50/80 text-purple-600 border border-purple-100/50";
                                        if($row['action'] == 'ITEM_DECLARATION') $action_class = "bg-blue-50/80 text-blue-600 border border-blue-100/50";
                                        if($row['action'] == 'TIME_OUT') $action_class = "bg-rose-50/80 text-rose-600 border border-rose-100/50";
                                        ?>
                                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider backdrop-blur-sm <?php echo $action_class; ?>">
                                            <?php echo htmlspecialchars($row['action']); ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="text-sm text-slate-600 font-medium max-w-xs truncate" title="<?php echo htmlspecialchars($row['details']); ?>">
                                            <?php echo htmlspecialchars($row['details']); ?>
                                        </p>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="text-xs font-mono font-bold text-slate-500 bg-white/50 border border-white/60 px-2 py-1 rounded-lg backdrop-blur-sm"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-500">
                                            <i class="fas fa-search text-4xl mb-4 opacity-40"></i>
                                            <p class="font-bold">No activity logs found matching your criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="px-8 py-5 border-t border-white/60 flex justify-between items-center bg-white/30 backdrop-blur-md">
                    <p class="text-xs font-bold text-slate-600">Showing page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                    <div class="flex gap-2">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&action=<?php echo urlencode($action_filter); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/50 border border-white/60 text-slate-600 hover:bg-white/80 transition-all shadow-sm"><i class="fas fa-chevron-left text-xs"></i></a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for($i = $start; $i <= $end; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&action=<?php echo urlencode($action_filter); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-white/60 text-sm font-black transition-all shadow-sm <?php echo $i == $page ? 'bg-blue-600/90 text-white backdrop-blur-md shadow-blue-500/30' : 'bg-white/50 text-slate-600 hover:bg-white/80 backdrop-blur-sm'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&action=<?php echo urlencode($action_filter); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/50 border border-white/60 text-slate-600 hover:bg-white/80 transition-all shadow-sm"><i class="fas fa-chevron-right text-xs"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
</body>
</html>