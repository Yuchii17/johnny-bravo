<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_logged_in_user = $_SESSION['fullname'];

// --- FETCH STATS FOR CARDS ---
$today = date('Y-m-d');

// 1. Total Entries Today
$total_stmt = $conn->query("SELECT COUNT(*) as total FROM item_declarations WHERE declaration_date = '$today'");
$total_today = $total_stmt->fetch_assoc()['total'] ?? 0;

// 2. OJT Entries Today (Joining with users table to check role)
$ojt_stmt = $conn->query("SELECT COUNT(*) as total FROM item_declarations id JOIN users u ON id.user_id = u.user_id WHERE id.declaration_date = '$today' AND u.role = 'OJT'");
$ojt_today = $ojt_stmt->fetch_assoc()['total'] ?? 0;

// 3. Visitor Entries Today
$vst_stmt = $conn->query("SELECT COUNT(*) as total FROM item_declarations id JOIN users u ON id.user_id = u.user_id WHERE id.declaration_date = '$today' AND u.role = 'Visitor'");
$vst_today = $vst_stmt->fetch_assoc()['total'] ?? 0;


// ==========================================
// FORM PROCESSING: CREATE NEW USER (Auto-ID)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $new_fullname = trim($_POST['new_fullname']);
    $new_email = trim($_POST['new_email']);
    $new_role = trim($_POST['new_role']);
    $new_dept = trim($_POST['new_department']);
    $new_pass = password_hash('password123', PASSWORD_DEFAULT);

    $prefix = "USR";
    switch ($new_role) {
        case 'Employee':   $prefix = "EMP"; break;
        case 'Visitor':    $prefix = "VST"; break;
        case 'OJT':        $prefix = "OJT"; break;
    }

    $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
    $countQuery->bind_param("s", $new_role);
    $countQuery->execute();
    $row = $countQuery->get_result()->fetch_assoc();
    
    $nextNumber = $row['total'] + 1;
    $generatedId = $prefix . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO users (user_id, fullname, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $generatedId, $new_fullname, $new_email, $new_pass, $new_role, $new_dept);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "User Created! ID: <br><strong style='font-size: 24px; color: #3B82F6;'>$generatedId</strong>";
    } else {
        $_SESSION['error_msg'] = "Failed to create user. Email might already exist.";
    }
    header("Location: dashboard.php");
    exit();
}

// ==========================================
// FORM PROCESSING: ENTRY & DECLARATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_declaration'])) {
    $target_user_id = $_POST['target_user_id'];
    $stmt_u = $conn->prepare("SELECT fullname FROM users WHERE user_id = ?");
    $stmt_u->bind_param("s", $target_user_id);
    $stmt_u->execute();
    $u_res = $stmt_u->get_result()->fetch_assoc();
    $target_fullname = $u_res['fullname'] ?? 'Unknown';

    $shift_id = $_POST['shift_id'];
    $declaration_date = date('Y-m-d'); 
    $time_now = date('H:i:s');        
    
    $declared_items = [];
    if (isset($_POST['items'])) {
        foreach ($_POST['items'] as $item_key) {
            if ($item_key === 'others') continue; 
            $declared_items[$item_key] = [
                'qty'    => $_POST[$item_key . '_qty'] ?? 1,
                'brand'  => $_POST[$item_key . '_brand'] ?? '',
                'color'  => $_POST[$item_key . '_color'] ?? '',
                'amount' => ($item_key === 'wallet') ? ($_POST['wallet_amt'] ?? '') : ''
            ];
        }
    }
    $items_json = json_encode($declared_items);

    $stmt = $conn->prepare("INSERT INTO item_declarations (user_id, fullname, shift_id, declaration_date, time_in, items_json, status) VALUES (?, ?, ?, ?, ?, ?, 'Logged In')");
    $stmt->bind_param("ssisss", $target_user_id, $target_fullname, $shift_id, $declaration_date, $time_now, $items_json);
    
    if ($stmt->execute()) {
        $_SESSION['entry_success'] = "Entry successfully recorded for $target_fullname!";
    }
    header("Location: dashboard.php");
    exit();
}

$users_list = $conn->query("SELECT user_id, fullname, role FROM users ORDER BY fullname ASC");
$schedules_list = $conn->query("SELECT * FROM schedules WHERE status = 'Active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
            <div>
                <h2 class="text-xl font-black text-slate-800 tracking-tight">Gate Entry Portal</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Duty: <?php echo htmlspecialchars($current_logged_in_user); ?></p>
            </div>
            <button onclick="toggleUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-black transition shadow-lg shadow-blue-500/20">
                <i class="fas fa-plus mr-2"></i> Register New User
            </button>
        </header>

        <main class="flex-1 overflow-y-auto p-6 space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Entries Today</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo $total_today; ?></h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">OJT Logged</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo $ojt_today; ?></h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Visitors Logged</p>
                        <h3 class="text-2xl font-black text-slate-800"><?php echo $vst_today; ?></h3>
                    </div>
                </div>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                    <div class="bg-slate-800 p-8 text-white flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-black">Gate Pass Declaration</h3>
                            <p class="text-slate-400 text-sm">Log entry and declare property items</p>
                        </div>
                        <i class="fas fa-shield-alt text-4xl text-slate-700"></i>
                    </div>

                    <form action="" method="POST" class="p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">User Search</label>
                                <select name="target_user_id" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                                    <option value="" disabled selected>Select Personnel...</option>
                                    <?php while($u = $users_list->fetch_assoc()): ?>
                                        <option value="<?php echo $u['user_id']; ?>">
                                            <?php echo htmlspecialchars($u['fullname']); ?> (<?php echo $u['user_id']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Shift Assigned</label>
                                <select name="shift_id" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                                    <option value="" disabled selected>Select Shift...</option>
                                    <?php while($row = $schedules_list->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['shift_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block border-b pb-2">Item Checklist</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[400px] overflow-y-auto pr-2">
                                <?php
                                $gateItems = [
                                    'shirt' => 'Shirt/Blouse', 'pants' => 'Pants/Jeans/Skirt', 'bag' => 'Bag', 
                                    'wallet' => 'Wallet', 'belt' => 'Belt', 'jacket' => 'Jacket', 
                                    'cap' => 'Cap', 'cosmetics' => 'Cosmetics', 'jewelries' => 'Jewelries', 
                                    'shoes' => 'Shoes', 'tumbler' => 'Tumbler', 'charger' => 'Charger'
                                ];
                                foreach ($gateItems as $key => $label): ?>
                                    <div class="border border-slate-100 bg-slate-50/50 rounded-2xl p-4 hover:bg-white hover:shadow-md transition-all">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" name="items[]" value="<?php echo $key; ?>" id="chk_<?php echo $key; ?>" onchange="toggleItemFields('<?php echo $key; ?>')" class="w-5 h-5 rounded-lg border-slate-300 text-blue-600">
                                            <span class="ml-4 font-bold text-slate-700"><?php echo $label; ?></span>
                                        </label>
                                        <div id="fields_<?php echo $key; ?>" class="hidden mt-4 pl-9 space-y-3">
                                            <?php if($key == 'wallet'): ?>
                                                <input type="number" name="wallet_amt" placeholder="Amount (₱)" class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                            <?php endif; ?>
                                            <div class="grid grid-cols-3 gap-2">
                                                <input type="number" name="<?php echo $key; ?>_qty" placeholder="QTY" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                                <input type="text" name="<?php echo $key; ?>_brand" placeholder="Brand" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                                <input type="text" name="<?php echo $key; ?>_color" placeholder="Color" class="bg-white border border-slate-200 rounded-xl p-3 text-xs">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" name="submit_declaration" class="w-full bg-blue-600 text-white py-5 rounded-[1.5rem] font-black text-lg shadow-xl shadow-blue-500/30 hover:bg-blue-700 hover:-translate-y-1 transition-all">
                            Complete Entry Log <i class="fas fa-check-circle ml-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <div id="createUserModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-xl text-slate-800">New Personnel Registration</h3>
                <button onclick="toggleUserModal()" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <form action="" method="POST" class="p-8 space-y-5">
                <input type="hidden" name="create_user" value="1">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Full Name</label>
                    <input type="text" name="new_fullname" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm mt-1 outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Email</label>
                    <input type="email" name="new_email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm mt-1 outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</label>
                        <select name="new_role" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm mt-1 outline-none">
                            <option value="Employee">Employee</option>
                            <option value="OJT">OJT</option>
                            <option value="Visitor">Visitor</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dept</label>
                        <input type="text" name="new_department" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm mt-1">
                    </div>
                </div>
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black mt-4 hover:bg-black transition shadow-lg">Save & Generate ID</button>
            </form>
        </div>
    </div>

    <script>
        function toggleItemFields(key) {
            const fields = document.getElementById('fields_' + key);
            fields.classList.toggle('hidden', !document.getElementById('chk_' + key).checked);
        }
        function toggleUserModal() {
            document.getElementById('createUserModal').classList.toggle('hidden');
        }
    </script>
</body>
</html>