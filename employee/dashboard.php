<?php
session_start();
date_default_timezone_set('Asia/Manila'); 
require '../config.php';
require '../audit_logger.php';

$successTrigger = false;
$errorMsg = '';

// 2. Handle Item Declaration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_declaration'])) {
    $user_id = 'UNKNOWN-EMP'; // Default for non-logged-in employees
    $fullname = trim($_POST['fullname'] ?? '');
    $department = trim($_POST['department'] ?? ''); // Added department
    $shift_id = $_POST['shift_id'];
    $declaration_date = date('Y-m-d'); 
    $time_now = date('H:i:s');        
    
    $declared_items = [];
    if (isset($_POST['items'])) {
        foreach ($_POST['items'] as $item_key) {
            $item_details = [
                'qty'    => $_POST[$item_key . '_qty'] ?? 1,
                'brand'  => $_POST[$item_key . '_brand'] ?? '',
                'color'  => $_POST[$item_key . '_color'] ?? ''
            ];

            if ($item_key === 'wallet') {
                $item_details['amount'] = $_POST['wallet_amt'] ?? '';
            }

            $declared_items[$item_key] = $item_details;
        }
    }
    
    if (isset($_POST['items']) && in_array('others', $_POST['items'])) {
        $declared_items['others']['name'] = $_POST['others_name'] ?? 'Others';
    }

    $items_json = json_encode($declared_items);

    // Updated INSERT statement with department and removing purpose for employee
    $stmt = $conn->prepare("INSERT INTO item_declarations (user_id, fullname, department, shift_id, declaration_date, time_in, items_json) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $user_id, $fullname, $department, $shift_id, $declaration_date, $time_now, $items_json);
    
    if ($stmt->execute()) {
        $successTrigger = true;
        log_audit($conn, $user_id, $fullname, 'Employee', 'ITEM_DECLARATION', "Employee submitted declaration. Dept: $department");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Glassmorphism</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Glassmorphism Blob Animations */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }

        /* Minimal Glass Scrollbar just in case items expand too much */
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
        
        <main class="flex-1 flex flex-col h-full overflow-hidden">
            <header class="bg-white/40 backdrop-blur-xl border-b border-white/60 h-24 flex items-center justify-between px-10 shrink-0 shadow-[0_4px_30px_rgba(0,0,0,0.03)] z-10">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Employee Entry</h1>
                    <p class="text-xs font-bold text-blue-600 mt-1 uppercase tracking-widest drop-shadow-sm">Item Declaration</p>
                </div>
            </header>

            <div class="flex-1 p-6 h-full overflow-hidden">
                <form action="" method="POST" class="h-full flex gap-6">
                    
                    <div class="w-1/3 bg-white/40 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 p-8 flex flex-col h-full">
                        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3">
                            <i class="fas fa-user-circle text-blue-500"></i> Identity Details
                        </h2>
                        
                        <div class="space-y-5 flex-1">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Full Name</label>
                                <select name="fullname" required class="w-full bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all text-slate-700">
                                    <option value="" disabled selected>Select your name</option>
                                    <?php
                                    $users = $conn->query("SELECT fullname FROM users ORDER BY fullname ASC");
                                    while($user = $users->fetch_assoc()) {
                                        echo "<option value='{$user['fullname']}'>{$user['fullname']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Department</label>
                                <input type="text" name="department" required placeholder="e.g. Front Office, F&B" class="w-full bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all text-slate-700 placeholder-slate-400">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Assigned Shift</label>
                                <select name="shift_id" required class="w-full bg-white/50 backdrop-blur-md border border-white/60 shadow-sm rounded-xl p-3.5 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all text-slate-700">
                                    <option value="" disabled selected>Select active schedule...</option>
                                    <?php
                                    $s = $conn->query("SELECT * FROM schedules WHERE status = 'Active'");
                                    while($row = $s->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['shift_name']} (".date("g:i A", strtotime($row['time_from']))." - ".date("g:i A", strtotime($row['time_to'])).")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-white/50">
                            <button type="submit" name="submit_declaration" class="w-full bg-blue-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-3">
                                <span>Submit Entry</span> <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="w-2/3 bg-white/40 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] border border-white/60 p-8 flex flex-col h-full overflow-y-auto">
                        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3 shrink-0">
                            <i class="fas fa-box-open text-blue-500"></i> Gate Pass Declarations
                        </h2>
                        
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <?php
                            $categories = [
                                'Apparel' => [
                                    'shirt' => 'Shirt/Blouse', 'pants' => 'Pants/Skirt', 
                                    'jacket' => 'Jacket', 'cap' => 'Cap', 'shoes' => 'Shoes', 'belt' => 'Belt'
                                ],
                                'Equipments & Electronics' => [
                                    'laptop' => 'Laptop', 'phone' => 'Mobile Phone', 
                                    'tumbler' => 'Tumbler', 'charger' => 'Charger'
                                ],
                                'Personal Items' => [
                                    'bag' => 'Bag', 'wallet' => 'Wallet', 
                                    'cosmetics' => 'Cosmetics', 'jewelries' => 'Jewelries'
                                ]
                            ];

                            foreach ($categories as $catName => $items): ?>
                                <div class="bg-white/20 backdrop-blur-sm border border-white/50 rounded-2xl p-5 shadow-sm">
                                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-widest mb-4 border-b border-white/40 pb-2"><?php echo $catName; ?></h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <?php foreach ($items as $key => $label): ?>
                                            <div class="col-span-1">
                                                <label class="flex items-center cursor-pointer group hover:bg-white/40 p-2 rounded-xl transition-colors">
                                                    <input type="checkbox" name="items[]" value="<?php echo $key; ?>" id="chk_<?php echo $key; ?>" onchange="toggleItemFields('<?php echo $key; ?>')" class="w-4 h-4 rounded border-white/60 text-blue-600 focus:ring-blue-500/50 bg-white/50 cursor-pointer">
                                                    <span class="ml-3 font-bold text-sm text-slate-700 group-hover:text-blue-700 transition-colors"><?php echo $label; ?></span>
                                                </label>
                                                <div id="fields_<?php echo $key; ?>" class="hidden mt-2 p-3 bg-white/40 border border-white/50 rounded-xl space-y-2 backdrop-blur-sm shadow-inner">
                                                    <?php if($key == 'wallet'): ?>
                                                        <input type="number" name="wallet_amt" placeholder="Amount (₱)" class="w-full bg-white/60 border border-white/60 rounded-lg p-2 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                                    <?php endif; ?>
                                                    <div class="flex gap-2">
                                                        <input type="number" name="<?php echo $key; ?>_qty" placeholder="Qty" class="w-1/3 bg-white/60 border border-white/60 rounded-lg p-2 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                                        <input type="text" name="<?php echo $key; ?>_brand" placeholder="Brand" class="w-2/3 bg-white/60 border border-white/60 rounded-lg p-2 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                                    </div>
                                                    <input type="text" name="<?php echo $key; ?>_color" placeholder="Color" class="w-full bg-white/60 border border-white/60 rounded-lg p-2 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="bg-white/20 backdrop-blur-sm border border-white/50 rounded-2xl p-5 shadow-sm xl:col-span-2">
                                <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-widest mb-4 border-b border-white/40 pb-2">Miscellaneous</h4>
                                <label class="flex items-center cursor-pointer group hover:bg-white/40 p-2 rounded-xl transition-colors w-fit">
                                    <input type="checkbox" name="items[]" value="others" id="chk_others" onchange="toggleItemFields('others')" class="w-4 h-4 rounded border-white/60 text-blue-600 focus:ring-blue-500/50 bg-white/50 cursor-pointer">
                                    <span class="ml-3 font-bold text-sm text-slate-700 group-hover:text-blue-700 transition-colors">OTHER ITEMS</span>
                                </label>
                                <div id="fields_others" class="hidden mt-3 p-4 bg-white/40 border border-white/50 rounded-xl space-y-3 backdrop-blur-sm shadow-inner">
                                    <input type="text" name="others_name" placeholder="Item Name / Description" class="w-full bg-white/60 border border-white/60 rounded-lg p-3 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                    <div class="grid grid-cols-3 gap-3">
                                        <input type="number" name="others_qty" placeholder="QTY" class="w-full bg-white/60 border border-white/60 rounded-lg p-3 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                        <input type="text" name="others_brand" placeholder="Brand" class="w-full bg-white/60 border border-white/60 rounded-lg p-3 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                        <input type="text" name="others_color" placeholder="Color" class="w-full bg-white/60 border border-white/60 rounded-lg p-3 text-xs font-semibold focus:ring-2 focus:ring-blue-400 outline-none">
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        function toggleItemFields(key) {
            document.getElementById('fields_' + key).classList.toggle('hidden', !document.getElementById('chk_' + key).checked);
        }
        <?php if($successTrigger): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'Entry Submitted!', 
            text: 'Your shift entry and item declaration have been recorded.', 
            confirmButtonColor: '#2563eb',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: 'rgba(0, 0, 0, 0.3)'
        });
        <?php endif; ?>
    </script>
</body>
</html>