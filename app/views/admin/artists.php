<?php
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized access');
}

require_once __DIR__ . '/../../models/ArtistModel.php';
$artistModel = new ArtistModel();
$message = '';

// Xử lý thêm/sửa/xóa nghệ sĩ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    if (!isset($_FILES['image'])) {
                        throw new Exception("Vui lòng chọn ảnh nghệ sĩ");
                    }

                    $imageFile = $_FILES['image'];

                    if ($imageFile['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Lỗi khi upload ảnh");
                    }

                    $uploadDir = 'uploads/artists/';
                    $fullUploadPath = __DIR__ . '/../../../public/' . $uploadDir;
                    if (!file_exists($fullUploadPath)) {
                        mkdir($fullUploadPath, 0777, true);
                    }

                    $imageFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $imageFile['name']);

                    if (!move_uploaded_file($imageFile['tmp_name'], $fullUploadPath . $imageFileName)) {
                        throw new Exception("Không thể lưu ảnh");
                    }

                    $result = $artistModel->addArtist([
                        'name' => trim($_POST['name']),
                        'image' => $uploadDir . $imageFileName,
                        'followers' => intval($_POST['followers'] ?? 0)
                    ]);

                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        Thêm nghệ sĩ thành công!</div>';

                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;

            case 'edit':
                try {
                    if (empty($_POST['artist_id'])) {
                        throw new Exception("ID nghệ sĩ không hợp lệ");
                    }

                    $data = [
                        'id' => $_POST['artist_id'],
                        'name' => trim($_POST['name']),
                        'followers' => intval($_POST['followers'] ?? 0)
                    ];

                    if (!empty($_FILES['image']['name'])) {
                        $imageFile = $_FILES['image'];
                        if ($imageFile['error'] === UPLOAD_ERR_OK) {
                            $imageFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $imageFile['name']);
                            if (move_uploaded_file($imageFile['tmp_name'], $fullUploadPath . $imageFileName)) {
                                $data['image'] = $uploadDir . $imageFileName;
                            }
                        }
                    }

                    if ($artistModel->updateArtist($data)) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Cập nhật nghệ sĩ thành công!</div>';
                    } else {
                        throw new Exception("Không thể cập nhật nghệ sĩ");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;

            case 'delete':
                try {
                    if (empty($_POST['artist_id'])) {
                        throw new Exception("ID nghệ sĩ không hợp lệ");
                    }

                    if ($artistModel->deleteArtist($_POST['artist_id'])) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Xóa nghệ sĩ thành công!</div>';
                    } else {
                        throw new Exception("Không thể xóa nghệ sĩ");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;
        }
    }
}

$artists = $artistModel->getAllArtists();
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <?= $message ?>
    
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Danh sách nghệ sĩ</h3>
        <button onclick="openModal('addArtistModal')" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
            <i class="fas fa-plus mr-2"></i>Thêm nghệ sĩ
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left">ID</th>
                    <th class="py-3 px-4 text-left">Ảnh</th>
                    <th class="py-3 px-4 text-left">Tên nghệ sĩ</th>
                    <th class="py-3 px-4 text-left">Lượt theo dõi</th>
                    <th class="py-3 px-4 text-left">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artists as $artist): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= htmlspecialchars($artist['id']) ?></td>
                    <td class="py-3 px-4">
                        <img src="<?= htmlspecialchars($artist['image']) ?>" 
                             alt="<?= htmlspecialchars($artist['name']) ?>"
                             class="w-12 h-12 object-cover rounded-full">
                    </td>
                    <td class="py-3 px-4"><?= htmlspecialchars($artist['name']) ?></td>
                    <td class="py-3 px-4"><?= number_format($artist['followers']) ?></td>
                    <td class="py-3 px-4">
                        <button onclick="editArtist(<?= $artist['id'] ?>)" 
                                class="text-blue-500 hover:text-blue-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteArtist(<?= $artist['id'] ?>)" 
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Nghệ Sĩ -->
<div id="addArtistModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold">Thêm nghệ sĩ mới</h4>
            <button onclick="closeModal('addArtistModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tên nghệ sĩ</label>
                    <input type="text" name="name" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Lượt theo dõi</label>
                    <input type="number" name="followers" value="0" 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ảnh nghệ sĩ</label>
                    <input type="file" name="image" accept="image/*" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addArtistModal')"
                        class="px-4 py-2 border rounded hover:bg-gray-100">
                    Hủy
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Nghệ Sĩ -->
<div id="editArtistModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold">Sửa thông tin nghệ sĩ</h4>
            <button onclick="closeModal('editArtistModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="editArtistForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="artist_id" id="edit_artist_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tên nghệ sĩ</label>
                    <input type="text" name="name" id="edit_name" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Lượt theo dõi</label>
                    <input type="number" name="followers" id="edit_followers"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ảnh mới (không bắt buộc)</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('editArtistModal')"
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

async function editArtist(artistId) {
    try {
        const response = await fetch(`?page=admin/artists/get/${artistId}`);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Không thể lấy thông tin nghệ sĩ');
        }
        
        const artist = await response.json();
        
        document.getElementById('edit_artist_id').value = artist.id;
        document.getElementById('edit_name').value = artist.name;
        document.getElementById('edit_followers').value = artist.followers;
        
        openModal('editArtistModal');
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}

function deleteArtist(artistId) {
    if (confirm('Bạn có chắc muốn xóa nghệ sĩ này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="artist_id" value="${artistId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 