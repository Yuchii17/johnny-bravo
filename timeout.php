<?php
session_start();
date_default_timezone_set('Asia/Manila');
require 'config.php';
require 'audit_logger.php';

$successTrigger = false;
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_timeout'])) {
    $search_name = trim($_POST['fullname'] ?? '');
    
    // Find active session for this user
    $stmt = $conn->prepare("SELECT id, user_id, fullname, role FROM (
        SELECT id, user_id, fullname, 'OJT' as role FROM item_declarations WHERE fullname = ? AND status = 'Logged In'
        UNION
        SELECT id, user_id, fullname, 'Visitor' as role FROM item_declarations WHERE fullname = ? AND status = 'Logged In'
    ) as active_sessions ORDER BY id DESC LIMIT 1");
    
    $stmt->bind_param("ss", $search_name, $search_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $session_id = $row['id'];
        $user_id = $row['user_id'];
        $fullname = $row['fullname'];
        $role = $row['role'];
        $timeout_date = date('Y-m-d');
        $time_now = date('H:i:s');
        
        $update = $conn->prepare("UPDATE item_declarations SET status = 'Logged Out', time_out = ?, timeout_date = ? WHERE id = ?");
        $update->bind_param("ssi", $time_now, $timeout_date, $session_id);
        
        if ($update->execute()) {
            $successTrigger = true;
            // Log the time out action
            log_audit($conn, $user_id, $fullname, $role, 'TIME_OUT', "User timed out from the system");
        } else {
            $errorMsg = "Failed to update record.";
        }
    } else {
        $errorMsg = "No active session found for '$search_name'.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Out - John Hay Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 text-slate-800">

    <div class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-rose-600 text-white">
            <div>
                <h3 class="text-2xl font-black">Time Out</h3>
                <p class="text-sm font-medium text-rose-100">Current Local Time: <?php echo date('h:i A'); ?></p>
            </div>
            <a href="index.php" class="text-white hover:text-rose-100 flex items-center gap-2 font-bold text-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <form action="" method="POST" class="p-8 space-y-6">
            <?php if ($errorMsg): ?>
                <div class="bg-rose-50 text-rose-600 p-4 rounded-2xl border border-rose-100 text-sm font-bold flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Enter Full Name</label>
                <input type="text" name="fullname" required placeholder="As registered during entry" class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-rose-500">
            </div>

            <button type="submit" name="submit_timeout" class="w-full bg-rose-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-rose-500/30 hover:bg-rose-700 transition-colors">
                Submit Time Out
            </button>
        </form>
    </div>

    <script>
        <?php if($successTrigger): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'Timed Out Successfully!', 
            text: 'Your session has been closed. PH Time: <?php echo date('h:i A'); ?>', 
            confirmButtonColor: '#E11D48' 
        }).then(() => { 
            window.location.href = 'index.php'; 
        });
        <?php endif; ?>
    </script>
</body>
</html>