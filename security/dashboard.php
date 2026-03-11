<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';

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

$countToday = $conn->query("SELECT COUNT(*) as total FROM item_declarations WHERE declaration_date = CURDATE()")->fetch_assoc()['total'];
$countActive = $conn->query("SELECT COUNT(*) as total FROM item_declarations WHERE status = 'Logged In'")->fetch_assoc()['total'];
$countOut = $conn->query("SELECT COUNT(*) as total FROM item_declarations WHERE status = 'Logged Out'")->fetch_assoc()['total'];

$logs = $conn->query("SELECT d.*, u.fullname, u.role, s.shift_name FROM item_declarations d JOIN users u ON d.user_id = u.user_id JOIN schedules s ON d.shift_id = s.id ORDER BY d.created_at DESC");
$history = $conn->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 5");
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
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0">
            <h1 class="text-lg font-bold text-slate-800">Security Command Center</h1>
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
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Today</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countToday; ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl"><i class="fas fa-user-clock"></i></div>
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Active</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countActive; ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl"><i class="fas fa-door-open"></i></div>
                    <div><p class="text-xs text-slate-400 font-bold uppercase">Out</p><h3 class="text-3xl font-black text-slate-800"><?php echo $countOut; ?></h3></div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-50"><h2 class="text-lg font-black text-slate-800">Personnel Entry Ledger</h2></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">
                                    <th class="px-8 py-4">Name/Role</th>
                                    <th class="px-8 py-4">Time In</th>
                                    <th class="px-8 py-4 text-center">Items</th>
                                    <th class="px-8 py-4">Status</th>
                                    <th class="px-8 py-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php while($row = $logs->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                            <span class="text-[9px] font-black uppercase text-indigo-500"><?php echo $row['role']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="font-bold text-slate-700"><?php echo date("h:i A", strtotime($row['time_in'])); ?></p>
                                        <p class="text-[10px] text-slate-400"><?php echo $row['shift_name']; ?></p>
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <button onclick='viewItems(<?php echo $row["items_json"]; ?>)' class="text-blue-600 bg-blue-50 px-4 py-2 rounded-xl text-xs font-bold">View</button>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo ($row['status'] == 'Logged In') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <?php if($row['status'] == 'Logged In'): ?>
                                            <form method="POST"><input type="hidden" name="timeout_id" value="<?php echo $row['id']; ?>"><button type="submit" class="bg-rose-500 text-white px-4 py-2 rounded-xl text-xs font-bold">TIMEOUT</button></form>
                                        <?php else: ?>
                                            <p class="text-[10px] font-bold text-slate-400">OUT: <?php echo date("h:i A", strtotime($row['time_out'])); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8">
                    <h2 class="text-lg font-black text-slate-800 mb-6">Security History</h2>
                    <div class="space-y-6">
                        <?php while($h = $history->fetch_assoc()): ?>
                        <div class="relative pl-6 border-l-2 border-slate-100">
                            <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-2 border-blue-500"></div>
                            <p class="text-xs font-black text-slate-800"><?php echo $h['action']; ?></p>
                            <p class="text-[10px] text-slate-500 mt-1"><?php echo htmlspecialchars($h['details']); ?></p>
                            <p class="text-[9px] font-bold text-blue-500 mt-1"><?php echo date("h:i A", strtotime($h['created_at'])); ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div id="itemModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-800">Declared Items</h3>
                <button onclick="closeModal()" class="text-slate-400"><i class="fas fa-times-circle"></i></button>
            </div>
            <div id="itemList" class="p-6 space-y-3"></div>
        </div>
    </div>
    <script>
        function viewItems(items) {
            const list = document.getElementById('itemList');
            list.innerHTML = '';
            for (const [key, details] of Object.entries(items)) {
                list.innerHTML += `<div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center"><div><p class="text-xs font-black text-slate-700 uppercase">${key}</p><p class="text-[10px] text-slate-400">${details.brand || 'N/A'} | ${details.color || 'N/A'}</p></div><div class="text-xs font-black text-blue-600">x${details.qty}</div></div>`;
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