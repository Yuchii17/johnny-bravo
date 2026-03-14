<?php
session_start();
require '../config.php';
require '../audit_logger.php';

$successMsg = '';
$errorMsg = '';

// Handle schedule creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_schedule'])) {
    $shiftName = $_POST['shift_name'];
    $timeFrom = $_POST['time_from'];
    $timeTo = $_POST['time_to'];
    $status = $_POST['status'];

    if (empty($shiftName) || empty($timeFrom) || empty($timeTo) || empty($status)) {
        $errorMsg = "All fields are required.";
    } else {
        $insertQuery = $conn->prepare("INSERT INTO schedules (shift_name, time_from, time_to, status) VALUES (?, ?, ?, ?)");
        $insertQuery->bind_param("ssss", $shiftName, $timeFrom, $timeTo, $status);

        if ($insertQuery->execute()) {
            $successMsg = "Schedule created successfully!";
            $userId = $_SESSION['user_id'] ?? null;
            $userRole = $_SESSION['role'] ?? 'Employee';
            log_audit($conn, $userId, $userRole, 'SCHEDULE_CREATED', "New schedule created: $shiftName");
        } else {
            $errorMsg = "Error creating schedule: " . $conn->error;
        }
        $insertQuery->close();
    }
}

$schedules = $conn->query("SELECT * FROM schedules ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Glassmorphism</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.5); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-slate-50 relative text-slate-800">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none bg-gradient-to-br from-blue-50 via-slate-100 to-white">
        <div class="absolute top-0 -left-10 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob"></div>
        <div class="absolute top-10 -right-10 w-96 h-96 bg-sky-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-10 left-1/3 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 flex w-full h-full">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 p-8 h-full overflow-hidden flex flex-col">
            <header class="mb-8 shrink-0">
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Schedule Management</h1>
                <p class="text-xs font-bold text-blue-600 mt-1 uppercase tracking-widest">Create and Manage Shifts</p>
            </header>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 flex-1 overflow-hidden">
                <div class="lg:col-span-1 bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 flex flex-col h-fit">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-3"><i class="fas fa-plus-circle text-blue-500"></i> New Schedule</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Shift Name</label>
                            <input type="text" name="shift_name" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Time From</label>
                                <input type="time" name="time_from" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Time To</label>
                                <input type="time" name="time_to" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Status</label>
                            <select name="status" required class="w-full mt-1 bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all text-slate-700">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" name="create_schedule" class="w-full mt-4 bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">Create Schedule</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white/40 backdrop-blur-xl p-8 rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 flex flex-col overflow-hidden">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-3 shrink-0"><i class="fas fa-list text-blue-500"></i> Existing Schedules</h2>
                    <div class="overflow-auto flex-1 pr-2 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white/60 backdrop-blur-md shadow-sm rounded-xl z-10">
                                <tr class="text-[11px] font-black text-slate-500 uppercase tracking-widest">
                                    <th class="p-4 rounded-l-xl">Shift Name</th>
                                    <th class="p-4">Time From</th>
                                    <th class="p-4">Time To</th>
                                    <th class="p-4 rounded-r-xl">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                <?php while($schedule = $schedules->fetch_assoc()): ?>
                                <tr class="hover:bg-white/30 transition-colors group">
                                    <td class="p-4 font-bold text-slate-800"><?php echo htmlspecialchars($schedule['shift_name']); ?></td>
                                    <td class="p-4 text-sm font-semibold text-slate-600"><?php echo date("g:i A", strtotime($schedule['time_from'])); ?></td>
                                    <td class="p-4 text-sm font-semibold text-slate-600"><?php echo date("g:i A", strtotime($schedule['time_to'])); ?></td>
                                    <td class="p-4">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?php echo $schedule['status'] == 'Active' ? 'bg-emerald-100/80 text-emerald-700 border border-emerald-200' : 'bg-slate-200/80 text-slate-600 border border-slate-300'; ?>">
                                            <?php echo $schedule['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const swalConfig = {
            confirmButtonColor: '#2563eb',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: 'rgba(0, 0, 0, 0.3)'
        };
        <?php if ($successMsg): ?>
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Success', text: '<?php echo $successMsg; ?>' });
        <?php endif; ?>
        <?php if ($errorMsg): ?>
        Swal.fire({ ...swalConfig, icon: 'error', title: 'Error', text: '<?php echo $errorMsg; ?>' });
        <?php endif; ?>
    </script>
</body>
</html>