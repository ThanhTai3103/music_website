<?php
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized access');
}

require_once __DIR__ . '/../../models/SongModel.php';
$songModel = new SongModel();
$message = '';

// Thêm đoạn code này vào đầu file songs.php
$uploadDir = 'public/uploads/songs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Xử lý thêm/sửa/xóa bài hát
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Kiểm tra file upload
                    if (!isset($_FILES['song_file'], $_FILES['cover_image'])) {
                        throw new Exception("Vui lòng chọn đầy đủ file nhạc và ảnh bìa");
                    }

                    $songFile = $_FILES['song_file'];
                    $coverFile = $_FILES['cover_image'];

                    // Kiểm tra lỗi upload
                    if ($songFile['error'] !== UPLOAD_ERR_OK || $coverFile['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Lỗi khi upload file");
                    }

                    // Tạo thư mục upload nếu chưa tồn tại
                    $uploadDir = 'uploads/songs/';
                    $fullUploadPath = __DIR__ . '/../../../public/' . $uploadDir;
                    if (!file_exists($fullUploadPath)) {
                        mkdir($fullUploadPath, 0777, true);
                    }

                    // Tạo tên file an toàn
                    $songFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $songFile['name']);
                    $coverFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $coverFile['name']);

                    // Upload files
                    if (!move_uploaded_file($songFile['tmp_name'], $fullUploadPath . $songFileName)) {
                        throw new Exception("Không thể lưu file nhạc");
                    }

                    if (!move_uploaded_file($coverFile['tmp_name'], $fullUploadPath . $coverFileName)) {
                        // Xóa file nhạc nếu upload ảnh thất bại
                        unlink($fullUploadPath . $songFileName);
                        throw new Exception("Không thể lưu ảnh bìa");
                    }

                    // Thêm vào database
                    $result = $songModel->addSong([
                        'title' => trim($_POST['title']),
                        'artist' => trim($_POST['artist']),
                        'album' => trim($_POST['album'] ?? ''),
                        'file_path' => $uploadDir . $songFileName,
                        'cover_image' => $uploadDir . $coverFileName
                    ]);

                    if ($result) {
                        $_SESSION['success_message'] = 'Thêm bài hát thành công!';
                        header('Location: ?page=home#songs');
                        exit;
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;
            case 'edit':
                try {
                    if (empty($_POST['song_id'])) {
                        throw new Exception("ID bài hát không hợp lệ");
                    }

                    $data = [
                        'id' => $_POST['song_id'],
                        'title' => trim($_POST['title']),
                        'artist' => trim($_POST['artist']),
                        'album' => trim($_POST['album'] ?? '')
                    ];

                    // Xử lý upload file mới nếu có
                    if (!empty($_FILES['song_file']['name'])) {
                        $songFile = $_FILES['song_file'];
                        if ($songFile['error'] === UPLOAD_ERR_OK) {
                            $songFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $songFile['name']);
                            if (move_uploaded_file($songFile['tmp_name'], $fullUploadPath . $songFileName)) {
                                $data['file_path'] = $uploadDir . $songFileName;
                            }
                        }
                    }

                    if (!empty($_FILES['cover_image']['name'])) {
                        $coverFile = $_FILES['cover_image'];
                        if ($coverFile['error'] === UPLOAD_ERR_OK) {
                            $coverFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $coverFile['name']);
                            if (move_uploaded_file($coverFile['tmp_name'], $fullUploadPath . $coverFileName)) {
                                $data['cover_image'] = $uploadDir . $coverFileName;
                            }
                        }
                    }

                    if ($songModel->updateSong($data)) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Cập nhật bài hát thành công!</div>';
                    } else {
                        throw new Exception("Không thể cập nhật bài hát");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;
            case 'delete':
                try {
                    if (empty($_POST['song_id'])) {
                        throw new Exception("ID bài hát không hợp lệ");
                    }

                    if ($songModel->deleteSong($_POST['song_id'])) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            Xóa bài hát thành công!</div>';
                    } else {
                        throw new Exception("Không thể xóa bài hát");
                    }
                } catch (Exception $e) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                break;
        }
    }
}

// Lấy danh sách bài hát
$songs = $songModel->getAllSongs();
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <?= $message ?>
    
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold">Danh sách bài hát</h3>
        <button onclick="openModal('addSongModal')" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
            <i class="fas fa-plus mr-2"></i>Thêm bài hát
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left">ID</th>
                    <th class="py-3 px-4 text-left">Ảnh</th>
                    <th class="py-3 px-4 text-left">Tên bài hát</th>
                    <th class="py-3 px-4 text-left">Nghệ sĩ</th>
                    <th class="py-3 px-4 text-left">Album</th>
                    <th class="py-3 px-4 text-left">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4"><?= htmlspecialchars($song['id']) ?></td>
                    <td class="py-3 px-4">
                        <img src="<?= htmlspecialchars($song['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($song['title']) ?>"
                             class="w-12 h-12 object-cover rounded">
                    </td>
                    <td class="py-3 px-4"><?= htmlspecialchars($song['title']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($song['artist']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($song['album']) ?></td>
                    <td class="py-3 px-4">
                        <button onclick="editSong(<?= $song['id'] ?>)" 
                                class="text-blue-500 hover:text-blue-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteSong(<?= $song['id'] ?>)" 
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

<!-- Modal Thêm Bài Hát -->
<div id="addSongModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold">Thêm bài hát mới</h4>
            <button onclick="closeModal('addSongModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tên bài hát</label>
                    <input type="text" name="title" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nghệ sĩ</label>
                    <input type="text" name="artist" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500"
                           placeholder="Nhập tên nghệ sĩ">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Album</label>
                    <input type="text" name="album"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500"
                           placeholder="Nhập tên album (không bắt buộc)">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">File nhạc</label>
                    <input type="file" name="song_file" accept="audio/*" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ảnh bìa</label>
                    <input type="file" name="cover_image" accept="image/*" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('addSongModal')"
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

<!-- Modal Sửa Bài Hát -->
<div id="editSongModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="flex justify-between items-center mb-6">
            <h4 class="text-xl font-semibold">Sửa bài hát</h4>
            <button onclick="closeModal('editSongModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" id="editSongForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="song_id" id="edit_song_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tên bài hát</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nghệ sĩ</label>
                    <input type="text" name="artist" id="edit_artist" required 
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Album</label>
                    <input type="text" name="album" id="edit_album"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">File nhạc mới (không bắt buộc)</label>
                    <input type="file" name="song_file" accept="audio/*"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ảnh bìa mới (không bắt buộc)</label>
                    <input type="file" name="cover_image" accept="image/*"
                           class="w-full p-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('editSongModal')"
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

async function editSong(songId) {
    try {
        const response = await fetch(`?page=admin/songs/get/${songId}`);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Không thể lấy thông tin bài hát');
        }
        
        const song = await response.json();
        
        // Điền thông tin vào form
        document.getElementById('edit_song_id').value = song.id;
        document.getElementById('edit_title').value = song.title;
        document.getElementById('edit_artist').value = song.artist;
        document.getElementById('edit_album').value = song.album || '';
        
        // Mở modal
        openModal('editSongModal');
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}

function deleteSong(songId) {
    if (confirm('Bạn có chắc muốn xóa bài hát này?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="song_id" value="${songId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 