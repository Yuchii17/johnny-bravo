<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $shift_name = trim($_POST['shift_name']);
    $time_from = $_POST['time_from'];
    $time_to = $_POST['time_to'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO schedules (shift_name, time_from, time_to, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $shift_name, $time_from, $time_to, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Schedule added successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to add schedule.";
    }
    header("Location: schedules.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $id = $_POST['edit_id'];
    $shift_name = trim($_POST['edit_shift_name']);
    $time_from = $_POST['edit_time_from'];
    $time_to = $_POST['edit_time_to'];
    $status = $_POST['edit_status'];

    $stmt = $conn->prepare("UPDATE schedules SET shift_name = ?, time_from = ?, time_to = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $shift_name, $time_from, $time_to, $status, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Schedule updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to update schedule.";
    }
    header("Location: schedules.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Schedule deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Failed to delete schedule.";
    }
    header("Location: schedules.php");
    exit();
}

$query = "SELECT * FROM schedules ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - Camp John Hay</title>
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
        input[type="time"]::-webkit-calendar-picker-indicator { cursor: pointer; filter: opacity(0.5); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="h-20 bg-white border-b border-slate-100 flex items-center justify-between px-6 lg:px-10 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-slate-800">Schedules</h2>
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
                    <input type="text" id="searchInput" placeholder="Search schedules..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow shadow-sm">
                </div>
                
                <button type="button" id="openAddModalBtn" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-colors shadow-sm shadow-blue-600/20 text-sm">
                    <i class="fas fa-clock"></i> Add New Schedule
                </button>
            </div>

            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto table-container">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4">Shift Name</th>
                                <th class="px-6 py-4">Schedule Time</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="scheduleTableBody">
                            
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 transition-colors group schedule-row">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($row['shift_name']); ?></div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-slate-700">
                                                <i class="fas fa-stopwatch text-slate-400 mr-2"></i>
                                                <?php echo date('h:i A', strtotime($row['time_from'])); ?> - <?php echo date('h:i A', strtotime($row['time_to'])); ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php if ($row['status'] === 'Active'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="edit-btn w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center transition-all shadow-sm" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($row['shift_name']); ?>" 
                                                        data-from="<?php echo htmlspecialchars($row['time_from']); ?>" 
                                                        data-to="<?php echo htmlspecialchars($row['time_to']); ?>" 
                                                        data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                                    <i class="fas fa-edit text-sm pointer-events-none"></i>
                                                </button>
                                                <form method="POST" action="" class="inline-block" id="deleteForm_<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="delete_schedule" value="1">
                                                    <button type="button" class="delete-btn w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 flex items-center justify-center transition-all shadow-sm" data-id="<?php echo $row['id']; ?>">
                                                        <i class="fas fa-trash-alt text-sm pointer-events-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 text-2xl">
                                                <i class="fas fa-search-minus"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-700">No matching schedules found</p>
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 text-2xl">
                                                <i class="fas fa-calendar-times"></i>
                                            </div>
                                            <p class="text-base font-bold text-slate-700">No schedules found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay opacity-0 pointer-events-none transition-opacity duration-300 hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300" id="addModalContent">
            
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Add Schedule</h3>
                </div>
                <button type="button" id="closeAddModalBtn" class="text-slate-400 hover:text-slate-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="" class="p-8 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Shift Name</label>
                    <input type="text" name="shift_name" required placeholder="e.g. Morning Shift" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Time From</label>
                        <input type="time" name="time_from" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow text-slate-700 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Time To</label>
                        <input type="time" name="time_to" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow text-slate-700 font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                    <div class="relative">
                        <select name="status" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-8 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none cursor-pointer font-semibold text-slate-700">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" id="cancelAddModalBtn" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="add_schedule" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm shadow-blue-600/20">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay opacity-0 pointer-events-none transition-opacity duration-300 hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300" id="editModalContent">
            
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Edit Schedule</h3>
                </div>
                <button type="button" id="closeEditModalBtn" class="text-slate-400 hover:text-slate-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="" class="p-8 space-y-4">
                <input type="hidden" name="edit_id" id="editModalId">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Shift Name</label>
                    <input type="text" name="edit_shift_name" id="editModalName" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Time From</label>
                        <input type="time" name="edit_time_from" id="editModalFrom" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow text-slate-700 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Time To</label>
                        <input type="time" name="edit_time_to" id="editModalTo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow text-slate-700 font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                    <div class="relative">
                        <select name="edit_status" id="editModalStatus" required class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-8 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none cursor-pointer font-semibold text-slate-700">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="mt-8 flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" id="cancelEditModalBtn" class="flex-1 px-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="edit_schedule" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm shadow-blue-600/20">
                        Update
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
            const rows = document.querySelectorAll('.schedule-row');
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
                    document.getElementById('editModalName').value = this.getAttribute('data-name');
                    document.getElementById('editModalFrom').value = this.getAttribute('data-from');
                    document.getElementById('editModalTo').value = this.getAttribute('data-to');
                    document.getElementById('editModalStatus').value = this.getAttribute('data-status');
                    openModal('editModal', 'editModalContent');
                });
            });

            document.getElementById('closeEditModalBtn').addEventListener('click', () => closeModal('editModal', 'editModalContent'));
            document.getElementById('cancelEditModalBtn').addEventListener('click', () => closeModal('editModal', 'editModalContent'));

            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#94a3b8',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('deleteForm_' + id).submit();
                        }
                    })
                });
            });
        });
    </script>
</body>
</html>