<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super Admin', 'Manager'])) {
    header("Location: ../index.php");
    exit();
}

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM tip_ledger WHERE recipient_name LIKE ? OR processed_by LIKE ?");
$stmt_count->bind_param("ss", $search_param, $search_param);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$stmt_tips = $conn->prepare("SELECT * FROM tip_ledger WHERE recipient_name LIKE ? OR processed_by LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt_tips->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt_tips->execute();
$tips_result = $stmt_tips->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tip History - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gradient-to-br from-slate-100 via-sky-50 to-indigo-100 relative z-0">
    
    <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-blue-400/30 rounded-full blur-[100px] pointer-events-none z-[-1]"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-purple-400/30 rounded-full blur-[100px] pointer-events-none z-[-1]"></div>

    <?php include 'sidebar.php'; ?>
    
    <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10">
        
        <header class="bg-white/40 backdrop-blur-md border-b border-white/60 h-24 flex items-center justify-between px-10 shrink-0 shadow-sm z-10 relative">
            <div class="flex items-center gap-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Tip History</h1>
                    <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">Financial Ledger Overview</p>
                </div>
                
                <div class="h-10 w-px bg-white/60 mx-4"></div>
                
                <form method="GET" id="searchForm" class="flex items-center relative">
                    <i class="fas fa-search absolute left-4 text-slate-400 text-sm"></i>
                    <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search recipient or staff..." class="bg-white/50 backdrop-blur-sm border border-white/60 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/80 w-72 transition-all shadow-sm placeholder-slate-400">
                    
                    <button type="submit" class="ml-2 bg-slate-800/90 backdrop-blur-md text-white border border-slate-700/50 px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-700 transition-colors shadow-lg">Search</button>
                    <a href="tip-history.php" id="clearBtn" class="ml-2 bg-rose-500/10 backdrop-blur-sm border border-rose-500/20 text-rose-600 px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-rose-500/20 transition-colors <?php echo empty($search) ? 'hidden' : ''; ?>">Clear</a>
                </form>
            </div>
            
            <div class="flex items-center gap-5">
                <div class="text-right">
                    <p class="text-sm font-black text-slate-900"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
            </div>
        </header>

        <div class="flex-1 p-10 overflow-y-auto">
            <div class="bg-white/40 backdrop-blur-xl rounded-[2rem] shadow-xl border border-white/60 flex flex-col h-full min-h-[600px] overflow-hidden">
                
                <div class="px-8 py-6 border-b border-white/50 flex justify-between items-center bg-white/30 shrink-0 z-10">
                    <h2 class="text-lg font-black text-slate-800">Master Ledger</h2>
                    <span id="total-records" class="text-xs font-bold text-slate-600 bg-white/50 backdrop-blur-md px-4 py-1.5 rounded-xl border border-white/60 shadow-sm">Total Records: <?php echo $total_records; ?></span>
                </div>
                
                <div class="overflow-auto flex-1 relative">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-white/60 backdrop-blur-md z-20 shadow-sm">
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-white/50">
                                <th class="px-8 py-5">Transaction Date</th>
                                <th class="px-8 py-5">Recipient</th>
                                <th class="px-8 py-5">Remarks</th>
                                <th class="px-8 py-5">Amount</th>
                                <th class="px-8 py-5">Processed By</th>
                            </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-white/40 text-sm">
                            <?php if($tips_result->num_rows > 0): ?>
                                <?php while($row = $tips_result->fetch_assoc()): ?>
                                <tr class="hover:bg-white/50 transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-700"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span>
                                            <span class="text-[10px] font-black text-slate-400"><?php echo date("h:i A", strtotime($row['created_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['recipient_name']); ?></span>
                                    </td>
                                    <td class="px-8 py-5 max-w-xs truncate text-slate-500 font-medium">
                                        <?php echo !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : '<span class="text-slate-400 italic">No remarks</span>'; ?>
                                    </td>
                                    <td class="px-8 py-5">
                                        <span class="font-black text-emerald-700 bg-emerald-500/10 backdrop-blur-sm px-3 py-1.5 rounded-xl border border-emerald-500/20 shadow-sm">₱<?php echo number_format($row['amount'], 2); ?></span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-white/60 border border-white/80 shadow-sm flex items-center justify-center text-[10px] text-slate-500 font-bold">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600"><?php echo htmlspecialchars($row['processed_by']); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-16 text-center">
                                        <div class="inline-flex flex-col items-center justify-center text-slate-500">
                                            <div class="w-16 h-16 bg-white/50 border border-white/60 backdrop-blur-sm rounded-full flex items-center justify-center mb-4 text-2xl shadow-sm">
                                                <i class="fas fa-folder-open text-slate-400"></i>
                                            </div>
                                            <p class="font-bold text-sm text-slate-600">No tip records found.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="pagination-wrapper">
                    <?php if($total_pages > 1): ?>
                    <div class="px-8 py-5 border-t border-white/50 flex justify-end items-center bg-white/30 shrink-0 z-10">
                        <div class="flex gap-2">
                            <?php if($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-white/60 bg-white/50 backdrop-blur-sm text-slate-600 hover:bg-white/80 transition-all font-bold shadow-sm"><i class="fas fa-chevron-left text-xs"></i></a>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for($i = $start; $i <= $end; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border backdrop-blur-sm text-sm font-black transition-all shadow-sm <?php echo $i == $page ? 'bg-blue-600/90 text-white border-blue-500/50 shadow-blue-500/30' : 'bg-white/50 text-slate-600 border-white/60 hover:bg-white/80'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-white/60 bg-white/50 backdrop-blur-sm text-slate-600 hover:bg-white/80 transition-all font-bold shadow-sm"><i class="fas fa-chevron-right text-xs"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            const tableBody = document.getElementById('table-body');
            const paginationWrapper = document.getElementById('pagination-wrapper');
            const totalRecordsText = document.getElementById('total-records');
            const clearBtn = document.getElementById('clearBtn');
            let debounceTimer;

            function fetchData(query, page) {
                const url = `tip-history.php?search=${encodeURIComponent(query)}&page=${page}`;
                
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        tableBody.innerHTML = doc.getElementById('table-body').innerHTML;
                        paginationWrapper.innerHTML = doc.getElementById('pagination-wrapper').innerHTML;
                        totalRecordsText.innerHTML = doc.getElementById('total-records').innerHTML;
                        
                        if (query.trim() !== '') {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }

                        window.history.pushState({ path: url }, '', url);
                    });
            }

            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchData(e.target.value, 1);
                }, 300);
            });

            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchData(searchInput.value, 1);
            });

            document.addEventListener('click', (e) => {
                const paginationLink = e.target.closest('#pagination-wrapper a');
                if (paginationLink) {
                    e.preventDefault();
                    const url = new URL(paginationLink.href);
                    const page = url.searchParams.get('page') || 1;
                    const search = url.searchParams.get('search') || '';
                    fetchData(search, page);
                }
            });

            window.addEventListener('popstate', () => {
                const url = new URL(window.location.href);
                const page = url.searchParams.get('page') || 1;
                const search = url.searchParams.get('search') || '';
                searchInput.value = search;
                fetchData(search, page);
            });
        });
    </script>
</body>
</html>