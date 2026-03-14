<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .table-container::-webkit-scrollbar { height: 8px; }
        .table-container::-webkit-scrollbar-track { background: transparent; border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb { background: rgba(203, 213, 225, 0.5); border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.8); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gradient-to-br from-indigo-50 via-slate-100 to-cyan-50">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="h-20 bg-white/60 backdrop-blur-xl border-b border-white/50 flex items-center justify-between px-6 lg:px-10 z-10 shadow-sm relative">
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-slate-800">Employee Directory</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-xs font-medium text-slate-600"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 bg-red-50/80 backdrop-blur-md border border-red-100/50 text-red-600 hover:bg-red-100/80 rounded-lg font-semibold transition-colors text-sm shadow-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 lg:p-10">
            
            <div class="flex flex-col sm:flex-row justify-start items-center mb-6 gap-3">
                <div class="relative w-full sm:w-80">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or ID..." class="w-full bg-white/50 backdrop-blur-md border border-white/60 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-shadow shadow-sm placeholder-slate-500 text-slate-800">
                </div>
                
                <div class="relative w-full sm:w-48">
                    <i class="fas fa-filter absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                    <select id="roleFilter" class="w-full bg-white/50 backdrop-blur-md border border-white/60 rounded-xl pl-10 pr-8 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none shadow-sm cursor-pointer text-slate-700 font-medium">
                        <option value="all">All Roles</option>
                        <option value="Super Admin">Super Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Security">Security</option>
                        <option value="Front Desk">Front Desk</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-500 text-xs pointer-events-none"></i>
                </div>
            </div>

            <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="overflow-x-auto table-container">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="bg-white/40 border-b border-white/60 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                <th class="px-6 py-4">Employee</th>
                                <th class="px-6 py-4">System ID</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4">Date Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/50" id="employeeTableBody">
                            
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-white/50 transition-colors group employee-row" data-role="<?php echo htmlspecialchars($row['role']); ?>">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-blue-100/80 backdrop-blur-sm border border-blue-200/50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                                    <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm employee-name"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                                    <div class="text-xs text-slate-600 font-medium employee-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white/50 backdrop-blur-sm border border-white/60 text-slate-600 font-mono employee-id">
                                                <?php echo htmlspecialchars($row['user_id']); ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-slate-700 employee-dept">
                                                <?php echo htmlspecialchars($row['department']); ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php 
                                                $role = htmlspecialchars($row['role']);
                                                $badgeClass = "bg-white/60 text-slate-700 border-slate-200/50";
                                                
                                                if ($role === 'Super Admin') $badgeClass = "bg-purple-100/80 text-purple-700 border-purple-200/50";
                                                elseif ($role === 'Manager') $badgeClass = "bg-blue-100/80 text-blue-700 border-blue-200/50";
                                                elseif ($role === 'Security') $badgeClass = "bg-amber-100/80 text-amber-700 border-amber-200/50";
                                                elseif ($role === 'Front Desk') $badgeClass = "bg-emerald-100/80 text-emerald-700 border-emerald-200/50";
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border backdrop-blur-sm <?php echo $badgeClass; ?>">
                                                <?php echo $role; ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-600">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-white/50 backdrop-blur-sm border border-white/60 rounded-full flex items-center justify-center text-slate-500 mb-4 text-2xl">
                                                <i class="fas fa-search-minus"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-800">No matching employees found</p>
                                            <p class="text-sm">Try adjusting your search or filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-600">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-white/50 backdrop-blur-sm border border-white/60 rounded-full flex items-center justify-center text-slate-500 mb-4 text-2xl">
                                                <i class="fas fa-users-slash"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-800">No employees found</p>
                                            <p class="text-sm">There are currently no users registered in the database.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
                
                <div class="bg-white/30 backdrop-blur-md border-t border-white/60 px-6 py-4 flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-600" id="tableStatus">Showing all registered employees</p>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const rows = document.querySelectorAll('.employee-row');
            const tableStatus = document.getElementById('tableStatus');
            const noResultsRow = document.getElementById('noResultsRow');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedRole = roleFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const rowRole = row.getAttribute('data-role');

                    const matchesSearch = rowText.includes(searchTerm);
                    const matchesRole = (selectedRole === 'all') || (rowRole === selectedRole);

                    if (matchesSearch && matchesRole) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noResultsRow) {
                    if (visibleCount === 0 && rows.length > 0) {
                        noResultsRow.style.display = '';
                    } else {
                        noResultsRow.style.display = 'none';
                    }
                }

                if (searchTerm === '' && selectedRole === 'all') {
                    tableStatus.textContent = 'Showing all registered employees';
                } else {
                    tableStatus.textContent = `Showing ${visibleCount} filtered result(s)`;
                }
            }

            searchInput.addEventListener('input', filterTable);
            roleFilter.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>