<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Manager', 'Admin'])) {
    header("Location: ../index.php");
    exit();
}

function renderTableRows($result) {
    ob_start();
    if($result->num_rows > 0):
        while($row = $result->fetch_assoc()):
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors group">
            <td class="px-8 py-5 border-b border-slate-50">
                <span class="font-bold text-slate-700"><?php echo date("M d, Y", strtotime($row['declaration_date'])); ?></span>
            </td>
            <td class="px-8 py-5 border-b border-slate-50">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['fullname']); ?></span>
                    <span class="text-[9px] font-black uppercase text-indigo-500"><?php echo htmlspecialchars($row['role']); ?></span>
                </div>
            </td>
            <td class="px-8 py-5 border-b border-slate-50">
                <div class="flex flex-col gap-1 text-[11px] font-bold">
                    <span class="text-emerald-600"><i class="fas fa-sign-in-alt w-4"></i> <?php echo date("h:i A", strtotime($row['time_in'])); ?></span>
                    <?php if($row['time_out']): ?>
                        <span class="text-rose-600"><i class="fas fa-sign-out-alt w-4"></i> <?php echo date("h:i A", strtotime($row['time_out'])); ?></span>
                    <?php else: ?>
                        <span class="text-slate-400"><i class="fas fa-sign-out-alt w-4"></i> Active</span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="px-8 py-5 border-b border-slate-50">
                <button 
                    onclick="viewItems(this)" 
                    data-items="<?php echo htmlspecialchars($row['items_json'] ?? '{}', ENT_QUOTES, 'UTF-8'); ?>"
                    class="text-blue-600 bg-blue-50 px-4 py-2 rounded-xl text-xs font-bold hover:bg-blue-100 transition-colors">
                    View Details
                </button>
            </td>
            <td class="px-8 py-5 border-b border-slate-50">
                <?php 
                    $status = strtolower($row['status'] ?? 'completed');
                    if ($status == 'active' || $status == 'in' || empty($row['time_out'])) {
                        echo '<span class="font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-100/50 text-[10px] uppercase tracking-wider">Active</span>';
                    } else {
                        echo '<span class="font-black text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200 text-[10px] uppercase tracking-wider">Completed</span>';
                    }
                ?>
            </td>
        </tr>
        <?php
        endwhile;
    else:
    ?>
        <tr>
            <td colspan="5" class="px-8 py-16 text-center">
                <div class="inline-flex flex-col items-center justify-center text-slate-400">
                    <i class="fas fa-folder-open text-4xl mb-4 text-slate-200"></i>
                    <p class="font-bold text-sm">No access records found.</p>
                </div>
            </td>
        </tr>
    <?php
    endif;
    return ob_get_clean();
}

function renderPagination($page, $total_pages) {
    ob_start();
    if($total_pages > 1) {
        echo '<div class="flex gap-2">';
        if($page > 1) {
            echo '<button onclick="loadData('.($page-1).')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"><i class="fas fa-chevron-left text-xs"></i></button>';
        }
        
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($i = $start; $i <= $end; $i++) {
            $activeClass = $i == $page ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
            echo '<button onclick="loadData('.$i.')" class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-bold transition-colors '.$activeClass.'">'.$i.'</button>';
        }

        if($page < $total_pages) {
            echo '<button onclick="loadData('.($page+1).')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"><i class="fas fa-chevron-right text-xs"></i></button>';
        }
        echo '</div>';
    }
    return ob_get_clean();
}

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

$stmt_count = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM item_declarations d 
    LEFT JOIN users u ON d.user_id = u.user_id 
    WHERE d.fullname LIKE ? OR d.user_id LIKE ? OR d.declaration_date LIKE ?
");
$stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$stmt_visitors = $conn->prepare("
    SELECT 
        d.*, 
        COALESCE(u.role, IF(d.user_id LIKE 'VIS-%', 'Visitor', 'OJT')) AS role 
    FROM item_declarations d 
    LEFT JOIN users u ON d.user_id = u.user_id 
    WHERE d.fullname LIKE ? OR d.user_id LIKE ? OR d.declaration_date LIKE ?
    ORDER BY d.declaration_date DESC, d.time_in DESC
    LIMIT ? OFFSET ?
");
$stmt_visitors->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
$stmt_visitors->execute();
$visitors_result = $stmt_visitors->get_result();

if (isset($_GET['ajax'])) {
    echo json_encode([
        'tbody' => renderTableRows($visitors_result),
        'pagination' => renderPagination($page, $total_pages),
        'total' => $total_records
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access History - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        
        <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0 relative">
            <div class="flex items-center gap-6">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 tracking-tight">Access History</h1>
                </div>
                
                <div class="h-8 w-px bg-slate-200 mx-2"></div>
                
                <div class="flex items-center relative">
                    <i class="fas fa-search absolute left-4 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" oninput="handleSearch()" placeholder="Search name, ID, or Date..." class="bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 w-80 transition-all shadow-sm">
                </div>
            </div>
            
            <div class="flex items-center gap-5">
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin User'); ?></p>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                </div>
            </div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto">
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full min-h-[600px]">
                <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center bg-white shrink-0 z-10">
                    <h2 class="text-lg font-black text-slate-800">Master Access Ledger</h2>
                    <span id="totalBadge" class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">Total Logs: <?php echo $total_records; ?></span>
                </div>
                
                <div class="overflow-auto flex-1">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-sm z-20">
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                <th class="px-8 py-4 border-b border-slate-100">Date</th>
                                <th class="px-8 py-4 border-b border-slate-100">Name & Role</th>
                                <th class="px-8 py-4 border-b border-slate-100">Time Duration</th>
                                <th class="px-8 py-4 border-b border-slate-100">Declared Items</th>
                                <th class="px-8 py-4 border-b border-slate-100">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="bg-white text-sm">
                            <?php echo renderTableRows($visitors_result); ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="px-8 py-5 border-t border-slate-50 flex justify-end items-center bg-slate-50/30 shrink-0 z-10" id="paginationContainer">
                    <?php echo renderPagination($page, $total_pages); ?>
                </div>
            </div>
        </div>
    </main>

    <div id="itemModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl transition-all">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-800">Declared Items</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors"><i class="fas fa-times-circle text-xl"></i></button>
            </div>
            <div id="itemList" class="p-6 space-y-3 max-h-[60vh] overflow-y-auto"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let debounceTimer;

        function handleSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1; 
                loadData(currentPage);
            }, 300); 
        }

        async function loadData(page) {
            currentPage = page;
            const searchVal = document.getElementById('searchInput').value;
            const tbody = document.getElementById('tableBody');
            
            tbody.style.opacity = '0.5';

            try {
                const response = await fetch(`visitors.php?ajax=1&search=${encodeURIComponent(searchVal)}&page=${page}`);
                const data = await response.json();
                
                tbody.innerHTML = data.tbody;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                document.getElementById('totalBadge').innerText = `Total Logs: ${data.total}`;
                
            } catch (error) {
                console.error("Error fetching data:", error);
            } finally {
                tbody.style.opacity = '1';
            }
        }

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

        function closeModal() { 
            document.getElementById('itemModal').classList.add('hidden'); 
        }
    </script>
</body>
</html>