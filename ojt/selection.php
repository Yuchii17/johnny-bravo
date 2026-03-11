<?php
session_start();
// Set timezone to Philippine Time
date_default_timezone_set('Asia/Manila'); 
require '../config.php';

// 1. Security Check - Removed for public access
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'OJT') {
//     header("Location: ../login.php");
//     exit();
// }

$user_id = $_SESSION['user_id'] ?? null;
$successTrigger = false;

// 2. Handle Item Declaration & Time In
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_declaration'])) {
    $shift_id = $_POST['shift_id'];
    $fullname = trim($_POST['fullname'] ?? '');
    $declaration_date = date('Y-m-d'); // Current PH Date
    $time_now = date('H:i:s');        // Current PH Time
    
    // Process items into an array to store as JSON
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

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO item_declarations (user_id, fullname, shift_id, declaration_date, time_in, items_json) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $user_id, $fullname, $shift_id, $declaration_date, $time_now, $items_json);
    
    if ($stmt->execute()) {
        $successTrigger = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Shift Entry & Item Declaration - John Hay Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 text-slate-800">

    <div class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden">
        
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-blue-600 text-white">
            <div>
                <h3 class="text-2xl font-black">OJT Entry Declaration</h3>
                <p class="text-sm font-medium text-blue-100">Current Local Time: <?php echo date('h:i A'); ?></p>
            </div>
            <a href="../index.php" class="text-white hover:text-blue-100 flex items-center gap-2 font-bold text-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <form action="" method="POST" class="flex flex-col">
            <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto">
                
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Full Name</label>
                    <input type="text" name="fullname" required placeholder="Enter your full name" class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Select Assigned Shift</label>
                    <select name="shift_id" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled selected>Select from active schedules...</option>
                        <?php
                        $s = $conn->query("SELECT * FROM schedules WHERE status = 'Active'");
                        while($row = $s->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['shift_name']} (".date("g:i A", strtotime($row['time_from']))." - ".date("g:i A", strtotime($row['time_to'])).")</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Item Declaration (Matching Gate Pass)</label>
                    <?php
                    $gateItems = [
                        'shirt' => 'Shirt/Blouse', 'pants' => 'Pants/Jeans/Skirt', 'bag' => 'Bag', 
                        'wallet' => 'Wallet', 'belt' => 'Belt', 'jacket' => 'Jacket', 
                        'cap' => 'Cap', 'cosmetics' => 'Cosmetics', 'jewelries' => 'Jewelries', 
                        'shoes' => 'Shoes', 'tumbler' => 'Tumbler', 'charger' => 'Charger'
                    ];
                    foreach ($gateItems as $key => $label): ?>
                        <div class="border border-slate-100 rounded-2xl p-4 hover:bg-slate-50 transition-colors">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="items[]" value="<?php echo $key; ?>" id="chk_<?php echo $key; ?>" onchange="toggleItemFields('<?php echo $key; ?>')" class="w-5 h-5 rounded border-slate-300">
                                <span class="ml-4 font-bold text-slate-700"><?php echo $label; ?></span>
                            </label>
                            <div id="fields_<?php echo $key; ?>" class="hidden mt-4 pl-9 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <?php if($key == 'wallet'): ?>
                                    <input type="number" name="wallet_amt" placeholder="Amount (₱)" class="col-span-full bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                <?php endif; ?>
                                <input type="number" name="<?php echo $key; ?>_qty" placeholder="QTY" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                <input type="text" name="<?php echo $key; ?>_brand" placeholder="Brand" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                <input type="text" name="<?php echo $key; ?>_color" placeholder="Color" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="border border-slate-100 rounded-2xl p-4 bg-slate-50">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="items[]" value="others" id="chk_others" onchange="toggleItemFields('others')" class="w-5 h-5 rounded border-slate-300">
                            <span class="ml-4 font-black text-slate-800">OTHERS</span>
                        </label>
                        <div id="fields_others" class="hidden mt-4 pl-9 space-y-3">
                            <input type="text" name="others_name" placeholder="Item Name" class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs">
                            <div class="grid grid-cols-3 gap-3">
                                <input type="number" name="others_qty" placeholder="QTY" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                <input type="text" name="others_brand" placeholder="Brand" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                <input type="text" name="others_color" placeholder="Color" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 border-t border-slate-100 bg-white flex justify-end gap-3">
                <button type="submit" name="submit_declaration" class="w-full bg-blue-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-colors">
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
            text: 'Your shift entry and item declaration have been recorded. PH Time: <?php echo date('h:i A'); ?>', 
            confirmButtonColor: '#2563EB' 
        }).then(() => { 
            window.location.href = '../index.php'; 
        });
        <?php endif; ?>
    </script>
</body>
</html>