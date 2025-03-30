<?php
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized access');
}

require_once __DIR__ . '/../../models/UserModel.php';
$userModel = new UserModel();
$message = '';

// Xử lý sửa/xóa user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit':
                try {
                    if (empty($_POST['user_id'])) {
                        throw new Exception("ID người dùng không hợp lệ");
                    }

                    $data = [
                        'id' => $_POST['user_id'],
                        'name' => trim($_POST['name']),
                        'email' => trim($_POST['email']),
                        'role' => $_POST['role']
                    ];

                    // Chỉ cập nhật mật khẩu nếu có nhập mới
                    if (!empty($_POST['password'])) {
                        $data['password'] = $_POST['password'];
                    }

                    if ($userModel->updateUser($data)) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Cập nhật người dùng thành công!</div>';
                    } else {
                        throw new Exception("Không thể cập nhật người dùng");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;

            case 'delete':
                try {
                    if (empty($_POST['user_id'])) {
                        throw new Exception("ID người dùng không hợp lệ");
                    }

                    if ($userModel->deleteUser($_POST['user_id'])) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Xóa người dùng thành công!</div>';
                    } else {
                        throw new Exception("Không thể xóa người dùng");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;
        }
    }
}

$users = $userModel->getAllUsers();
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <?= $message ?>
    
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Danh sách người dùng</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên hiển thị</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai trò</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($user['id']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($user['name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($user['email']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($user['role']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($user['created_at']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="editUser(<?= $user['id'] ?>)" 
                                class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteUser(<?= $user['id'] ?>)"
                                class="ml-3 text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal chỉnh sửa người dùng -->
<div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Chỉnh sửa người dùng</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div>
                <label class="block text-sm font-medium mb-1">Tên hiển thị</label>
                <input type="text" name="name" id="edit_name"
                       class="w-full p-2 border rounded focus:outline-none focus:border-blue-500"
                       required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" id="edit_email"
                       class="w-full p-2 border rounded focus:outline-none focus:border-blue-500"
                       required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Vai trò</label>
                <select name="role" id="edit_role"
                        class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Mật khẩu mới (để trống nếu không đổi)</label>
                <input type="password" name="password" id="edit_password"
                       class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('editUserModal')"
                        class="px-4 py-2 border rounded hover:bg-gray-100">
                    Hủy
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
}

async function editUser(userId) {
    try {
        const response = await fetch(`?page=admin/users/get/${userId}`);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Không thể lấy thông tin người dùng');
        }
        
        const user = await response.json();
        
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_password').value = '';
        
        openModal('editUserModal');
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}

function deleteUser(userId) {
    if (confirm('Bạn có chắc muốn xóa người dùng này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 