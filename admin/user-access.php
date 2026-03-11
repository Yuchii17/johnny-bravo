<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

// --- HANDLE ADD USER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dept = trim($_POST['department']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Determine the prefix based on the selected role
    $prefix = "USR"; // Default fallback
    switch($role) {
        case 'Employee':   $prefix = "EMP"; break;
        case 'Visitor':    $prefix = "VST"; break;
        case 'OJT':        $prefix = "OJT"; break;
        case 'Manager':    $prefix = "MNG"; break;
        case 'Supervisor': $prefix = "SPV"; break;
        case 'Security':   $prefix = "SEC"; break;
        case 'Front Desk': $prefix = "FDK"; break;
    }

    // Generate a random 6-digit user ID with the role prefix
    $new_user_id = $prefix . "-" . rand(100000, 999999);

    $stmt = $conn->prepare("INSERT INTO users (user_id, fullname, email, password, department, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $new_user_id, $fullname, $email, $password, $dept, $role);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "New user added successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to add user. Email might already exist.";
    }
    
    header("Location: user-access.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $update_id = $_POST['edit_user_id'];
    $fullname = trim($_POST['edit_fullname']);
    $email = trim($_POST['edit_email']);
    $dept = trim($_POST['edit_department']);
    $role = $_POST['edit_role'];

    $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, department = ?, role = ? WHERE user_id = ?");
    $stmt->bind_param("sssss", $fullname, $email, $dept, $role, $update_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "User credentials updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to update user credentials.";
    }
    
    header("Location: user-access.php");
    exit();
}

$query = "SELECT * FROM users WHERE role != 'Super Admin' ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Access - Camp John Hay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
        
        .table-container::-webkit-scrollbar { height: 8px; }
        .table-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .modal-overlay { background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-6 lg:px-10 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-slate-800">User Access Management</h2>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-xs font-medium text-slate-500"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                </div>
                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg font-semibold transition-colors text-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 lg:p-10 relative">
            
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <div class="relative w-full sm:w-96">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="searchInput" placeholder="Search users to manage access..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow shadow-sm">
                </div>
                
                <button type="button" id="openAddModalBtn" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-colors shadow-sm shadow-blue-600/20 text-sm">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>

            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto table-container">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4">User Details</th>
                                <th class="px-6 py-4">System ID</th>
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Current Role</th>
                                <th class="px-6 py-4 text-center">Manage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="accessTableBody">
                            
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 transition-colors group user-row">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-sm border border-slate-200">
                                                    <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm user-name"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                                    <div class="text-xs text-slate-500 font-medium user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600 font-mono user-id">
                                                <?php echo htmlspecialchars($row['user_id']); ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-slate-700">
                                                <?php echo htmlspecialchars($row['department']); ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php 
                                                $role = htmlspecialchars($row['role']);
                                                $badgeClass = "bg-slate-100 text-slate-700 border border-slate-200";
                                                
                                                if ($role === 'Manager') $badgeClass = "bg-blue-100 text-blue-700 border border-blue-200";
                                                elseif ($role === 'Security') $badgeClass = "bg-amber-100 text-amber-700 border border-amber-200";
                                                elseif ($role === 'Supervisor') $badgeClass = "bg-purple-100 text-purple-700 border border-purple-200";
                                                elseif ($role === 'Employee') $badgeClass = "bg-cyan-100 text-cyan-700 border border-cyan-200";
                                                elseif ($role === 'OJT') $badgeClass = "bg-pink-100 text-pink-700 border border-pink-200";
                                                elseif ($role === 'Visitor') $badgeClass = "bg-gray-100 text-gray-700 border border-gray-200";
                                                elseif ($role === 'Front Desk') $badgeClass = "bg-emerald-100 text-emerald-700 border border-emerald-200";
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?php echo $badgeClass; ?>">
                                                <i class="fas fa-shield-alt mr-1.5 text-[10px] opacity-70"></i> <?php echo $role; ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center">
                                                <button type="button" 
                                                        class="edit-btn w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center transition-all shadow-sm group-hover:shadow" 
                                                        title="Edit User"
                                                        data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                        data-name="<?php echo htmlspecialchars($row['fullname']); ?>"
                                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                                        data-role="<?php echo htmlspecialchars($row['role']); ?>">
                                                    <i class="fas fa-user-edit text-sm pointer-events-none"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 text-2xl">
                                                <i class="fas fa-search-minus"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-700">No matching users found</p>
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 text-2xl">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-700">No manageable users found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
                
                <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500" id="tableStatus">Showing all manageable users</p>
                </div>
            </div>

        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay opacity-0 pointer-events-none transition-opacity duration-300 hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300" id="addModalContent">
            
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Add New User</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1">Create a new system account</p>
                </div>
                <button type="button" id="closeAddModalBtn" class="text-slate-400 hover:text-slate-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="" class="p-8 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Full Name</label>
                        <input type="text" name="fullname" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                        <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Department</label>
                        <input type="text" name="department" required placeholder="e.g. IT, HR" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">System Role</label>
                        <div class="relative">
                            <select name="role" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-8 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none cursor-pointer font-semibold text-slate-700">
                                <option value="Employee">Employee</option>
                                <option value="Manager">Manager</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Security">Security</option>
                                <option value="Front Desk">Front Desk</option>
                                <option value="OJT">OJT</option>
                                <option value="Visitor">Visitor</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Initial Password</label>
                        <input type="password" name="password" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" id="cancelAddModalBtn" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_user" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm shadow-blue-600/20">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay opacity-0 pointer-events-none transition-opacity duration-300 hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300" id="editModalContent">
            
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Edit User Profile</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1" id="editModalSubtitle">System ID: 000000</p>
                </div>
                <button type="button" id="closeEditModalBtn" class="text-slate-400 hover:text-slate-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="" class="p-8 space-y-4">
                <input type="hidden" name="edit_user_id" id="editModalId">

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Full Name</label>
                        <input type="text" name="edit_fullname" id="editModalName" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address</label>
                        <input type="email" name="edit_email" id="editModalEmail" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Department</label>
                        <input type="text" name="edit_department" id="editModalDept" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">System Role</label>
                        <div class="relative">
                            <select name="edit_role" id="editModalRole" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-8 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none cursor-pointer font-semibold text-slate-700">
                                <option value="Employee">Employee</option>
                                <option value="Manager">Manager</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Security">Security</option>
                                <option value="Front Desk">Front Desk</option>
                                <option value="OJT">OJT</option>
                                <option value="Visitor">Visitor</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" id="cancelEditModalBtn" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="edit_user" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm shadow-blue-600/20">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo $_SESSION['success_msg']; ?>', showConfirmButton: false, timer: 2000 });
    </script>
    <?php unset($_SESSION['success_msg']); endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
    <script>
        Swal.fire({ icon: 'error', title: 'Oops...', text: '<?php echo $_SESSION['error_msg']; ?>', confirmButtonColor: '#3B82F6' });
    </script>
    <?php unset($_SESSION['error_msg']); endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll('.user-row');
            const tableStatus = document.getElementById('tableStatus');
            const noResultsRow = document.getElementById('noResultsRow');

            searchInput.addEventListener('input', function() {
                const searchTerm = searchInput.value.toLowerCase();
                let visibleCount = 0;

                rows.forEach(row => {
                    if (row.textContent.toLowerCase().includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noResultsRow) {
                    noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
                }
                tableStatus.textContent = searchTerm === '' ? 'Showing all manageable users' : `Showing ${visibleCount} filtered user(s)`;
            });

            function openModal(modalId, contentId) {
                const modal = document.getElementById(modalId);
                const content = document.getElementById(contentId);
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.remove('opacity-0', 'pointer-events-none');
                    content.classList.remove('scale-95');
                    content.classList.add('scale-100');
                }, 10);
            }

            function closeModal(modalId, contentId) {
                const modal = document.getElementById(modalId);
                const content = document.getElementById(contentId);
                modal.classList.add('opacity-0', 'pointer-events-none');
                content.classList.remove('scale-100');
                content.classList.add('scale-95');
                setTimeout(() => { modal.classList.add('hidden'); }, 300);
            }

            document.getElementById('openAddModalBtn').addEventListener('click', () => openModal('addModal', 'addModalContent'));
            document.getElementById('closeAddModalBtn').addEventListener('click', () => closeModal('addModal', 'addModalContent'));
            document.getElementById('cancelAddModalBtn').addEventListener('click', () => closeModal('addModal', 'addModalContent'));

            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('editModalId').value = this.getAttribute('data-id');
                    document.getElementById('editModalSubtitle').textContent = 'System ID: ' + this.getAttribute('data-id');
                    document.getElementById('editModalName').value = this.getAttribute('data-name');
                    document.getElementById('editModalEmail').value = this.getAttribute('data-email');
                    document.getElementById('editModalDept').value = this.getAttribute('data-dept');
                    
                    const roleSelect = document.getElementById('editModalRole');
                    const currentRole = this.getAttribute('data-role');
                    for(let i = 0; i < roleSelect.options.length; i++) {
                        if(roleSelect.options[i].value === currentRole) {
                            roleSelect.selectedIndex = i; break;
                        }
                    }
                    openModal('editModal', 'editModalContent');
                });
            });

            document.getElementById('closeEditModalBtn').addEventListener('click', () => closeModal('editModal', 'editModalContent'));
            document.getElementById('cancelEditModalBtn').addEventListener('click', () => closeModal('editModal', 'editModalContent'));

            document.getElementById('addModal').addEventListener('click', function(e) { if (e.target === this) closeModal('addModal', 'addModalContent'); });
            document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeModal('editModal', 'editModalContent'); });
        });
    </script>
</body>
</html>