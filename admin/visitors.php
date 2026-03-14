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
        <tr class="hover:bg-white/40 transition-colors group border-b border-white/20">
            <td class="px-8 py-5">
                <span class="font-bold text-slate-700"><?php echo date("M d, Y", strtotime($row['declaration_date'])); ?></span>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['fullname']); ?></span>
                    <span class="text-[9px] font-black uppercase text-indigo-600"><?php echo htmlspecialchars($row['role']); ?></span>
                </div>
            </td>
            <td class="px-8 py-5">
                <div class="flex flex-col gap-1 text-[11px] font-bold">
                    <span class="text-emerald-700"><i class="fas fa-sign-in-alt w-4"></i> <?php echo date("h:i A", strtotime($row['time_in'])); ?></span>
                    <?php if($row['time_out']): ?>
                        <span class="text-rose-600"><i class="fas fa-sign-out-alt w-4"></i> <?php echo date("h:i A", strtotime($row['time_out'])); ?></span>
                    <?php else: ?>
                        <span class="text-slate-500"><i class="fas fa-sign-out-alt w-4"></i> Active</span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="px-8 py-5">
                <button 
                    onclick="viewItems(this)" 
                    data-items="<?php echo htmlspecialchars($row['items_json'] ?? '{}', ENT_QUOTES, 'UTF-8'); ?>"
                    class="text-blue-700 bg-white/50 border border-white/60 shadow-sm backdrop-blur-sm px-4 py-2 rounded-xl text-xs font-bold hover:bg-white/70 transition-all">
                    View Details
                </button>
            </td>
            <td class="px-8 py-5">
                <?php 
                    $status = strtolower($row['status'] ?? 'completed');
                    if ($status == 'active' || $status == 'in' || empty($row['time_out'])) {
                        echo '<span class="font-black text-blue-700 bg-blue-100/50 backdrop-blur-sm px-3 py-1.5 rounded-xl border border-blue-200/50 text-[10px] uppercase tracking-wider shadow-sm">Active</span>';
                    } else {
                        echo '<span class="font-black text-slate-600 bg-white/50 backdrop-blur-sm px-3 py-1.5 rounded-xl border border-white/60 text-[10px] uppercase tracking-wider shadow-sm">Completed</span>';
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
                <div class="inline-flex flex-col items-center justify-center text-slate-500">
                    <i class="fas fa-folder-open text-4xl mb-4 text-white/80 drop-shadow-md"></i>
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
            echo '<button onclick="loadData('.($page-1).')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/50 bg-white/30 backdrop-blur-sm shadow-sm text-slate-700 hover:bg-white/60 transition-all"><i class="fas fa-chevron-left text-xs"></i></button>';
        }
        
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for($i = $start; $i <= $end; $i++) {
            $activeClass = $i == $page ? 'bg-blue-600/90 text-white border-blue-500/50 shadow-md' : 'bg-white/30 text-slate-700 border-white/50 hover:bg-white/60 shadow-sm';
            echo '<button onclick="loadData('.$i.')" class="w-8 h-8 flex items-center justify-center rounded-lg border backdrop-blur-sm text-xs font-bold transition-all '.$activeClass.'">'.$i.'</button>';
        }

        if($page < $total_pages) {
            echo '<button onclick="loadData('.($page+1).')" class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/50 bg-white/30 backdrop-blur-sm shadow-sm text-slate-700 hover:bg-white/60 transition-all"><i class="fas fa-chevron-right text-xs"></i></button>';
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Custom animations for the background blobs to make glassmorphism pop */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 7s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
        
        /* Custom scrollbar to match glass look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.5); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.8); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-slate-100 relative text-slate-800">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50">
        <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-2xl opacity-40 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 flex w-full h-full">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 flex flex-col h-full overflow-hidden">
            
            <header class="bg-white/40 backdrop-blur-xl border-b border-white/60 h-20 flex items-center justify-between px-8 shrink-0 shadow-[0_4px_30px_rgba(0,0,0,0.03)]">
                <div class="flex items-center gap-6">
                    <div>
                        <h1 class="text-lg font-bold text-slate-800 tracking-tight">Access History</h1>
                    </div>
                    
                    <div class="h-8 w-px bg-white/60 mx-2"></div>
                    
                    <div class="flex items-center relative group">
                        <i class="fas fa-search absolute left-4 text-slate-500 text-xs transition-colors group-focus-within:text-blue-600"></i>
                        <input type="text" id="searchInput" oninput="handleSearch()" placeholder="Search name, ID, or Date..." class="bg-white/50 backdrop-blur-md border border-white/60 rounded-xl pl-10 pr-4 py-2 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/50 w-80 transition-all shadow-sm focus:bg-white/80 placeholder-slate-400">
                    </div>
                </div>
                
                <div class="flex items-center gap-5">
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin User'); ?></p>
                        <p class="text-[10px] text-blue-700 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                    </div>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <div class="bg-white/40 backdrop-blur-xl rounded-[2rem] border border-white/60 shadow-[0_8px_32px_0_rgba(31,38,135,0.05)] overflow-hidden flex flex-col h-full min-h-[600px]">
                    
                    <div class="px-8 py-6 border-b border-white/50 flex justify-between items-center bg-white/20 shrink-0 z-10">
                        <h2 class="text-lg font-black text-slate-800">Master Access Ledger</h2>
                        <span id="totalBadge" class="text-xs font-bold text-slate-600 bg-white/60 backdrop-blur-sm px-3 py-1 rounded-lg border border-white/80 shadow-sm">Total Logs: <?php echo $total_records; ?></span>
                    </div>
                    
                    <div class="overflow-auto flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white/60 backdrop-blur-md z-20 border-b border-white/50 shadow-sm">
                                <tr class="text-[11px] font-black text-slate-500 uppercase tracking-widest">
                                    <th class="px-8 py-4">Date</th>
                                    <th class="px-8 py-4">Name & Role</th>
                                    <th class="px-8 py-4">Time Duration</th>
                                    <th class="px-8 py-4">Declared Items</th>
                                    <th class="px-8 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="text-sm transition-opacity duration-300">
                                <?php echo renderTableRows($visitors_result); ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-8 py-5 border-t border-white/50 flex justify-end items-center bg-white/30 shrink-0 z-10" id="paginationContainer">
                        <?php echo renderPagination($page, $total_pages); ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="itemModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white/60 backdrop-blur-2xl rounded-[2rem] w-full max-w-md border border-white/50 shadow-[0_8px_32px_0_rgba(31,38,135,0.15)] transition-all transform scale-95 opacity-0 duration-300" id="modalContent">
            <div class="p-6 border-b border-white/40 flex justify-between items-center bg-white/20 rounded-t-[2rem]">
                <h3 class="text-xl font-black text-slate-800">Declared Items</h3>
                <button onclick="closeModal()" class="text-slate-500 hover:text-rose-500 transition-colors drop-shadow-sm"><i class="fas fa-times-circle text-xl"></i></button>
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
            
            tbody.style.opacity = '0.4';

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
                list.innerHTML = '<p class="text-center text-slate-500 font-bold text-sm py-4">No items declared.</p>';
            } else {
                for (const [key, details] of Object.entries(items)) {
                    let amountHtml = details.amount ? ` | ₱${details.amount}` : '';
                    list.innerHTML += `<div class="p-4 bg-white/50 backdrop-blur-sm rounded-2xl border border-white/60 shadow-sm flex justify-between items-center transition-transform hover:-translate-y-0.5"><div><p class="text-xs font-black text-slate-800 uppercase">${details.name || key}</p><p class="text-[10px] text-slate-500 font-medium">${details.brand || 'N/A'} | ${details.color || 'N/A'}${amountHtml}</p></div><div class="text-xs font-black text-blue-700 bg-blue-100/60 border border-blue-200/50 backdrop-blur-sm px-3 py-1 rounded-lg">x${details.qty}</div></div>`;
                }
            }
            
            const modal = document.getElementById('itemModal');
            const modalContent = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            
            // Trigger animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() { 
            const modal = document.getElementById('itemModal');
            const modalContent = document.getElementById('modalContent');
            
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden'); 
            }, 300);
        }
    </script>
</body>
</html>