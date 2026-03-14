<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';
require '../audit_logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Security') {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['timeout_id'])) {
    $id = $_POST['timeout_id'];
    $date_out = date('Y-m-d');
    $time_out = date('H:i:s');
    
    $stmt = $conn->prepare("UPDATE item_declarations SET timeout_date = ?, time_out = ?, status = 'Logged Out' WHERE id = ?");
    $stmt->bind_param("ssi", $date_out, $time_out, $id);
    
    if ($stmt->execute()) {
        $log_action = "Time Out Processed";
        $log_details = "Security " . $_SESSION['fullname'] . " timed out entry ID: " . $id;
        $log_stmt = $conn->prepare("INSERT INTO security_logs (action, details) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $log_action, $log_details);
        $log_stmt->execute();
        $timeoutSuccess = true;
    }
}

$dates_query = $conn->query("SELECT DISTINCT declaration_date FROM item_declarations ORDER BY declaration_date DESC");
$available_dates = [];
while ($d = $dates_query->fetch_assoc()) {
    $available_dates[] = $d['declaration_date'];
}

$today = date('Y-m-d');
if (!in_array($today, $available_dates)) {
    array_unshift($available_dates, $today);
}

$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : $today;

$stmt_today = $conn->prepare("SELECT COUNT(*) as total FROM item_declarations WHERE declaration_date = ?");
$stmt_today->bind_param("s", $filter_date);
$stmt_today->execute();
$countToday = $stmt_today->get_result()->fetch_assoc()['total'];

$stmt_active = $conn->prepare("SELECT COUNT(*) as total FROM item_declarations WHERE declaration_date = ? AND status = 'Logged In'");
$stmt_active->bind_param("s", $filter_date);
$stmt_active->execute();
$countActive = $stmt_active->get_result()->fetch_assoc()['total'];

$stmt_out = $conn->prepare("SELECT COUNT(*) as total FROM item_declarations WHERE declaration_date = ? AND status = 'Logged Out'");
$stmt_out->bind_param("s", $filter_date);
$stmt_out->execute();
$countOut = $stmt_out->get_result()->fetch_assoc()['total'];

$stmt_logs = $conn->prepare("
    SELECT 
        d.*, 
        COALESCE(u.role, IF(d.user_id LIKE 'VIS-%', 'Visitor', 'OJT')) AS role, 
        COALESCE(s.shift_name, 'No Shift / Visitor') AS shift_name 
    FROM item_declarations d 
    LEFT JOIN users u ON d.user_id = u.user_id 
    LEFT JOIN schedules s ON d.shift_id = s.id 
    WHERE d.declaration_date = ?
    ORDER BY d.created_at DESC
");
$stmt_logs->bind_param("s", $filter_date);
$stmt_logs->execute();
$logs = $stmt_logs->get_result();

$stmt_archive = $conn->prepare("SELECT * FROM item_declarations WHERE declaration_date = ? ORDER BY created_at DESC");
$stmt_archive->bind_param("s", $filter_date);
$stmt_archive->execute();
$archive_logs = $stmt_archive->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center gap-6">
                <h1 class="text-lg font-bold text-slate-800">Security Command Center</h1>
                <div class="h-8 w-px bg-slate-200"></div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="filter_date" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <?php foreach($available_dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo ($filter_date == $date) ? 'selected' : ''; ?>>
                                <?php echo ($date == $today) ? 'Today (' . date('M d, Y', strtotime($date)) . ')' : date('M d, Y', strtotime($date)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($filter_date !== $today): ?>
                        <a href="?" class="bg-blue-50 text-blue-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-blue-100 transition-colors">View Today</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest">Security Personnel</p>
                </div>
            </div>
        </header>
        <div class="flex-1 p-8 overflow-y-auto space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl"><i class="fas fa-file-alt"></i></div>
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Total Entries</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countToday; ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl"><i class="fas fa-user-clock"></i></div>
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Logged In</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countActive; ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl"><i class="fas fa-door-open"></i></div>
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Logged Out</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countOut; ?></h3></div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col h-[600px]">
                    <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0">
                        <h2 class="text-lg font-black text-slate-800">Personnel Entry Ledger</h2>
                        <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-lg"><?php echo date('M d, Y', strtotime($filter_date)); ?></span>
                    </div>
                    <div class="overflow-auto flex-1">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-sm z-10">
                                <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    <th class="px-8 py-4 border-b border-slate-100">Name/Role</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Time In</th>
                                    <th class="px-8 py-4 border-b border-slate-100 text-center">Purpose/Items</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Status</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php if($logs->num_rows > 0): ?>
                                    <?php while($row = $logs->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[9px] font-black uppercase text-indigo-500"><?php echo htmlspecialchars($row['role']); ?></span>
                                                    <?php if($row['department']): ?>
                                                        <span class="text-[9px] font-bold text-slate-400">• <?php echo htmlspecialchars($row['department']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <p class="font-bold text-slate-700"><?php echo date("h:i A", strtotime($row['time_in'])); ?></p>
                                            <p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($row['shift_name']); ?></p>
                                        </td>
                                        <td class="px-8 py-5 text-center">
                                            <div class="flex flex-col gap-2 items-center">
                                                <?php if($row['role'] == 'Visitor'): ?>
                                                    <button 
                                                        onclick="viewPurpose(this)" 
                                                        data-purpose="<?php echo htmlspecialchars($row['purpose'] ?? 'No purpose stated', ENT_QUOTES, 'UTF-8'); ?>"
                                                        class="text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-emerald-100 transition-colors w-24">
                                                        Purpose
                                                    </button>
                                                <?php endif; ?>
                                                <button 
                                                    onclick="viewItems(this)" 
                                                    data-items="<?php echo htmlspecialchars($row['items_json'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-blue-100 transition-colors w-24">
                                                    Items
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo ($row['status'] == 'Logged In') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <?php if($row['status'] == 'Logged In'): ?>
                                                <form method="POST"><input type="hidden" name="timeout_id" value="<?php echo $row['id']; ?>"><button type="submit" class="bg-rose-500 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-rose-600 transition-colors">TIMEOUT</button></form>
                                            <?php else: ?>
                                                <p class="text-[10px] font-bold text-slate-400">OUT: <?php echo date("h:i A", strtotime($row['time_out'])); ?></p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="px-8 py-10 text-center text-slate-400 font-bold text-sm">No entry records found for this date.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8 flex flex-col h-[600px]">
                    <div class="flex justify-between items-center mb-6 shrink-0">
                        <h2 class="text-lg font-black text-slate-800">Security Log Archive</h2>
                    </div>
                    <div class="space-y-6 overflow-y-auto flex-1 pr-2">
                        <?php if($archive_logs->num_rows > 0): ?>
                            <?php while($arch = $archive_logs->fetch_assoc()): ?>
                            <div class="relative pl-6 border-l-2 border-slate-100">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-2 border-blue-500"></div>
                                <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($arch['fullname']); ?></p>
                                <div class="text-[10px] text-slate-500 mt-2 space-y-1">
                                    <p><span class="font-bold text-slate-600">Time In:</span> <?php echo date("h:i A", strtotime($arch['time_in'])); ?></p>
                                    <p><span class="font-bold text-slate-600">Time Out:</span> <?php echo $arch['time_out'] ? date("h:i A", strtotime($arch['time_out'])) : '<span class="text-emerald-500 font-bold">Still Inside</span>'; ?></p>
                                    <p class="leading-relaxed">
                                        <span class="font-bold text-slate-600">Items:</span> 
                                        <?php 
                                            $items_arr = json_decode($arch['items_json'], true);
                                            $item_strings = [];
                                            if(!empty($items_arr)) {
                                                foreach($items_arr as $key => $val) {
                                                    $name = isset($val['name']) ? $val['name'] : $key;
                                                    $item_strings[] = $name . ' (x' . $val['qty'] . ')';
                                                }
                                                echo htmlspecialchars(implode(', ', $item_strings));
                                            } else {
                                                echo "No items declared";
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-slate-400 font-bold text-sm mt-10">No logs found for this date.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- AUDIT LOGS SECTION -->
            <div class="max-w-6xl mx-auto mt-12">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Your Activity Logs</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Personal Audit Trail</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="filter_date" value="<?php echo $filter_date; ?>">
                            <select name="log_status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="0" <?php echo (!isset($_GET['log_status']) || $_GET['log_status'] == '0') ? 'selected' : ''; ?>>Active Logs (Last 24h)</option>
                                <option value="1" <?php echo (isset($_GET['log_status']) && $_GET['log_status'] == '1') ? 'selected' : ''; ?>>Archived Logs</option>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                    <th class="px-8 py-4">Time</th>
                                    <th class="px-8 py-4">Action</th>
                                    <th class="px-8 py-4">Details</th>
                                    <th class="px-8 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php
                                $log_status = isset($_GET['log_status']) ? (int)$_GET['log_status'] : 0;
                                $user_id = $_SESSION['user_id'];
                                $logs_stmt = $conn->prepare("SELECT * FROM audit_logs WHERE user_id = ? AND is_archived = ? ORDER BY created_at DESC LIMIT 10");
                                $logs_stmt->bind_param("si", $user_id, $log_status);
                                $logs_stmt->execute();
                                $logs_res = $logs_stmt->get_result();

                                if ($logs_res->num_rows > 0):
                                    while ($log = $logs_res->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-8 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-slate-700"><?php echo date("M d, Y", strtotime($log['created_at'])); ?></span>
                                                <span class="text-[10px] font-bold text-slate-400"><?php echo date("h:i A", strtotime($log['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-4">
                                            <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-black uppercase tracking-wider border border-blue-100">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-4">
                                            <p class="text-xs text-slate-500 font-medium truncate max-w-xs" title="<?php echo htmlspecialchars($log['details']); ?>">
                                                <?php echo htmlspecialchars($log['details']); ?>
                                            </p>
                                        </td>
                                        <td class="px-8 py-4">
                                            <?php if ($log['is_archived']): ?>
                                                <span class="flex items-center gap-1.5 text-slate-400 font-bold text-[10px] uppercase">
                                                    <i class="fas fa-archive"></i> Archived
                                                </span>
                                            <?php else: ?>
                                                <span class="flex items-center gap-1.5 text-emerald-500 font-bold text-[10px] uppercase">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Recent
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-12 text-center text-slate-400 text-xs font-bold italic">
                                            No <?php echo $log_status ? 'archived' : 'recent'; ?> activity logs found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div id="itemModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-800">Declared Items</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors"><i class="fas fa-times-circle text-xl"></i></button>
            </div>
            <div id="itemList" class="p-6 space-y-3 max-h-[60vh] overflow-y-auto"></div>
        </div>
    </div>

    <div id="purposeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-800">Purpose of Visit</h3>
                <button onclick="closePurposeModal()" class="text-slate-400 hover:text-slate-600 transition-colors"><i class="fas fa-times-circle text-xl"></i></button>
            </div>
            <div class="p-8">
                <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl">
                    <p id="purposeText" class="text-sm font-bold text-emerald-800 leading-relaxed italic text-center"></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function viewPurpose(btn) {
            const purpose = btn.getAttribute('data-purpose');
            document.getElementById('purposeText').innerText = '"' + purpose + '"';
            document.getElementById('purposeModal').classList.remove('hidden');
        }
        function closePurposeModal() { document.getElementById('purposeModal').classList.add('hidden'); }

        function viewItems(btn) {
            const items = JSON.parse(btn.getAttribute('data-items'));
            const list = document.getElementById('itemList');
            list.innerHTML = '';
            
            if(Object.keys(items).length === 0) {
                list.innerHTML = '<p class="text-center text-slate-400 font-bold text-sm py-4">No items declared.</p>';
            } else {
                for (const [key, details] of Object.entries(items)) {
                    let amountHtml = details.amount ? ` | ₱${details.amount}` : '';
                    list.innerHTML += `<div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center"><div><p class="text-xs font-black text-slate-700 uppercase">${details.name || key}</p><p class="text-[10px] text-slate-400">${details.brand || 'N/A'} | ${details.color || 'N/A'}${amountHtml}</p></div><div class="text-xs font-black text-blue-600 bg-blue-100 px-3 py-1 rounded-lg">x${details.qty}</div></div>`;
                }
            }
            document.getElementById('itemModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('itemModal').classList.add('hidden'); }
        
        <?php if(isset($timeoutSuccess)): ?>
        Swal.fire({ icon: 'success', title: 'Personnel Timed Out', showConfirmButton: false, timer: 1500 });
        <?php endif; ?>
    </script>
</body>
</html>