<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle Tip Processing
$processSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_tip'])) {
    $recipient_name = trim($_POST['recipient_name']);
    $amount = floatval($_POST['amount']);
    $remarks = trim($_POST['remarks']);
    $processed_by = $_SESSION['fullname']; // Tracking who processed it

    if (!empty($recipient_name) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO tip_ledger (recipient_name, amount, remarks, processed_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $recipient_name, $amount, $remarks, $processed_by);
        
        if ($stmt->execute()) {
            $processSuccess = true;
        }
        $stmt->close();
    }
}

// Fetch Ledger History
$ledger_logs = $conn->query("SELECT * FROM tip_ledger ORDER BY created_at DESC");

// Calculate Total Tips Processed Today
$today = date('Y-m-d');
$stmt_total = $conn->prepare("SELECT SUM(amount) as daily_total FROM tip_ledger WHERE DATE(created_at) = ?");
$stmt_total->bind_param("s", $today);
$stmt_total->execute();
$daily_total = $stmt_total->get_result()->fetch_assoc()['daily_total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tip Ledger</title>
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
                <h1 class="text-lg font-bold text-slate-800">Financial Tip Ledger</h1>
                <div class="h-8 w-px bg-slate-200"></div>
                <div class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                    <i class="fas fa-coins"></i> Today's Total: ₱<?php echo number_format($daily_total, 2); ?>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
            </div>
        </header>
        
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full min-h-[600px]">
                
                <div class="lg:col-span-1 bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8 flex flex-col h-fit">
                    <div class="mb-8">
                        <h2 class="text-lg font-black text-slate-800">Process New Tip</h2>
                        <p class="text-xs font-bold text-slate-400 mt-1">Record a financial tip transaction.</p>
                    </div>
                    
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="process_tip" value="1">
                        
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Recipient Name</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" name="recipient_name" required placeholder="Juan Dela Cruz" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Amount (₱)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">₱</span>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00" min="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm font-black text-emerald-600 outline-none focus:ring-2 focus:ring-emerald-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Remarks / Purpose (Optional)</label>
                            <textarea name="remarks" rows="3" placeholder="e.g., Delivery fee tip, Excellent service..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-xl text-sm font-black uppercase tracking-wider hover:bg-slate-700 transition-colors shadow-lg shadow-slate-200 mt-4 flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i> Submit Record
                        </button>
                    </form>
                </div>
                
                <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col h-[600px] lg:h-auto">
                    <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center shrink-0">
                        <h2 class="text-lg font-black text-slate-800">Transaction History</h2>
                        <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-lg">Total Records: <?php echo $ledger_logs->num_rows; ?></span>
                    </div>
                    
                    <div class="overflow-auto flex-1">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-sm z-10">
                                <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    <th class="px-8 py-4 border-b border-slate-100">Date & Time</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Recipient</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Amount</th>
                                    <th class="px-8 py-4 border-b border-slate-100">Processed By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php if($ledger_logs->num_rows > 0): ?>
                                    <?php while($row = $ledger_logs->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-700"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span>
                                                <span class="text-[10px] text-slate-400 font-bold"><?php echo date("h:i A", strtotime($row['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['recipient_name']); ?></span>
                                                <?php if(!empty($row['remarks'])): ?>
                                                    <span class="text-[10px] font-bold text-slate-400 italic">"<?php echo htmlspecialchars($row['remarks']); ?>"</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg">₱<?php echo number_format($row['amount'], 2); ?></span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-xs font-bold text-slate-500"><i class="fas fa-user-shield mr-1 text-slate-300"></i> <?php echo htmlspecialchars($row['processed_by']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="px-8 py-10 text-center text-slate-400 font-bold text-sm">No tip transactions recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </main>
    
    <script>
        <?php if($processSuccess): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'Tip Recorded!', 
            text: 'The transaction has been successfully logged.',
            showConfirmButton: false, 
            timer: 2000,
            customClass: { popup: 'rounded-3xl' }
        });
        // Prevent form resubmission on page refresh
        setTimeout(() => { window.location.href = 'ledger.php'; }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>