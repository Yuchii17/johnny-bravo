<?php
session_start();
date_default_timezone_set('Asia/Manila'); 
require '../config.php';
require '../audit_logger.php';

$successTrigger = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_declaration'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $department = trim($_POST['department'] ?? ''); // Added department
    $purpose = trim($_POST['purpose'] ?? '');
    $user_id = 'VIS-' . time(); 
    $shift_id = 0;              
    $declaration_date = date('Y-m-d'); 
    $time_now = date('H:i:s');        
    
    $declared_items = [];
    if (isset($_POST['items'])) {
        foreach ($_POST['items'] as $item_key) {
            $declared_items[$item_key] = [
                'qty'    => $_POST[$item_key . '_qty'] ?? 1,
                'brand'  => $_POST[$item_key . '_brand'] ?? '',
                'color'  => $_POST[$item_key . '_color'] ?? '',
                'amount' => $_POST['wallet_amt'] ?? '' 
            ];
        }
    }
    
    if (isset($_POST['items']) && in_array('others', $_POST['items'])) {
        $declared_items['others']['name'] = $_POST['others_name'] ?? 'Others';
    }

    $items_json = json_encode($declared_items);

    // Updated INSERT statement with department and purpose for Visitor
    $stmt = $conn->prepare("INSERT INTO item_declarations (user_id, fullname, department, purpose, shift_id, declaration_date, time_in, items_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssisss", $user_id, $fullname, $department, $purpose, $shift_id, $declaration_date, $time_now, $items_json);
    
    if ($stmt->execute()) {
        $successTrigger = true;
        // Log the Visitor entry action
        log_audit($conn, $user_id, $fullname, 'Visitor', 'ITEM_DECLARATION', "Visitor submitted declaration. Dept: $department, Purpose: $purpose");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Entry & Item Declaration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        /* Custom Scrollbar for the inner form area */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.6); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(37, 99, 235, 0.5); }
    </style>
</head>
<body class="flex items-center justify-center h-screen overflow-hidden bg-slate-50 relative text-slate-800 p-4">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none bg-gradient-to-br from-blue-50 via-slate-100 to-white">
        <div class="absolute top-0 -left-10 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob"></div>
        <div class="absolute top-10 -right-10 w-96 h-96 bg-sky-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-50 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-10 left-1/3 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-[80px] opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 bg-white/40 backdrop-blur-xl border border-white/60 rounded-[2.5rem] w-full max-w-2xl shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] flex flex-col max-h-[95vh] overflow-hidden">
        
        <div class="p-6 md:p-8 border-b border-white/50 flex justify-between items-center bg-blue-600 text-white shrink-0">
            <div>
                <h3 class="text-2xl font-black tracking-tight shadow-sm">Visitor Entry</h3>
                <p class="text-sm font-semibold text-blue-100 mt-1 drop-shadow-sm">Local Time: <?php echo date('h:i A'); ?></p>
            </div>
            <a href="../index.php" class="text-white hover:text-blue-100 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl backdrop-blur-md border border-white/30 transition-all font-bold text-sm flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <form action="" method="POST" class="flex flex-col flex-1 min-h-0">
            
            <div class="p-6 md:p-8 space-y-6 flex-1 overflow-y-auto custom-scrollbar">
                
                <div class="bg-blue-50/60 backdrop-blur-md p-4 rounded-2xl border border-blue-100 shadow-sm">
                    <p class="text-xs font-bold text-blue-800 leading-relaxed flex items-center">
                        <i class="fas fa-info-circle mr-3 text-lg text-blue-500"></i>
                        Please declare any items you are bringing into the premises.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Full Name</label>
                        <input type="text" name="fullname" required placeholder="Enter your full name" class="w-full bg-white/50 backdrop-blur-md border border-white/60 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Dept / Office</label>
                        <input type="text" name="department" required placeholder="e.g. Guest, Service Provider" class="w-full bg-white/50 backdrop-blur-md border border-white/60 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all shadow-sm">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Purpose of Visit</label>
                    <input type="text" name="purpose" required placeholder="e.g. Visit Guest, Delivery, Service" class="w-full bg-white/50 backdrop-blur-md border border-white/60 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 transition-all shadow-sm">
                </div>

                <div class="space-y-6 mt-6">
                    <label class="text-xs font-black text-blue-600 uppercase tracking-widest block mb-4 border-b border-white/50 pb-2">Item Declaration List</label>
                    
                    <?php
                    $categories = [
                        'Apparel' => [
                            'shirt' => 'Shirt/Blouse', 'pants' => 'Pants/Jeans', 
                            'jacket' => 'Jacket', 'cap' => 'Cap', 'shoes' => 'Shoes', 'belt' => 'Belt'
                        ],
                        'Equipments & Electronics' => [
                            'laptop' => 'Laptop', 'phone' => 'Mobile Phone', 
                            'tumbler' => 'Tumbler', 'charger' => 'Charger/Powerbank'
                        ],
                        'Personal Items' => [
                            'bag' => 'Bag', 'wallet' => 'Wallet', 
                            'cosmetics' => 'Cosmetics', 'jewelries' => 'Jewelries'
                        ]
                    ];

                    foreach ($categories as $catName => $items): ?>
                        <div class="space-y-3 bg-white/30 backdrop-blur-sm p-5 rounded-2xl border border-white/50 shadow-sm">
                            <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-widest"><?php echo $catName; ?></h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($items as $key => $label): ?>
                                    <div class="bg-white/50 border border-white/60 rounded-2xl p-3 hover:bg-white/70 transition-colors shadow-sm">
                                        <label class="flex items-center cursor-pointer px-1">
                                            <input type="checkbox" name="items[]" value="<?php echo $key; ?>" id="chk_<?php echo $key; ?>" onchange="toggleItemFields('<?php echo $key; ?>')" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-3 font-bold text-slate-700 text-sm"><?php echo $label; ?></span>
                                        </label>
                                        <div id="fields_<?php echo $key; ?>" class="hidden mt-3 pt-3 border-t border-white/60 grid grid-cols-1 gap-2">
                                            <?php if($key == 'wallet'): ?>
                                                <input type="number" name="wallet_amt" placeholder="Amount (₱)" class="w-full bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                            <?php endif; ?>
                                            <div class="grid grid-cols-3 gap-2">
                                                <input type="number" name="<?php echo $key; ?>_qty" placeholder="QTY" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                                <input type="text" name="<?php echo $key; ?>_brand" placeholder="Brand" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                                <input type="text" name="<?php echo $key; ?>_color" placeholder="Color" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="bg-white/30 backdrop-blur-sm border border-white/50 rounded-2xl p-5 shadow-sm">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="items[]" value="others" id="chk_others" onchange="toggleItemFields('others')" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-3 font-black text-blue-800 text-sm">OTHER ITEMS</span>
                        </label>
                        <div id="fields_others" class="hidden mt-4 pt-4 border-t border-white/60 space-y-2">
                            <input type="text" name="others_name" placeholder="Item Name" class="w-full bg-white/70 border border-white/80 rounded-xl p-3 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <div class="grid grid-cols-3 gap-2">
                                <input type="number" name="others_qty" placeholder="QTY" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                <input type="text" name="others_brand" placeholder="Brand" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                                <input type="text" name="others_color" placeholder="Color" class="bg-white/70 border border-white/80 rounded-xl p-2.5 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-400">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 md:p-8 border-t border-white/50 bg-white/40 backdrop-blur-md flex justify-end gap-3 shrink-0">
                <button type="submit" name="submit_declaration" class="w-full bg-blue-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                    Submit Entry & Declaration
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleItemFields(key) {
            document.getElementById('fields_' + key).classList.toggle('hidden', !document.getElementById('chk_' + key).checked);
        }

        <?php if($successTrigger): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'Entry Submitted!', 
            text: 'Your entry and item declaration have been recorded. Local Time: <?php echo date('h:i A'); ?>', 
            confirmButtonColor: '#2563eb',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.4)'
        }).then(() => { 
            window.location.href = '../index.php'; 
        });
        <?php endif; ?>
    </script>
</body>
</html>