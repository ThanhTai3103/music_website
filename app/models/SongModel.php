<?php
require_once __DIR__ . '/../core/Database.php';

class SongModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addSong($data) {
        try {
            // Validate dữ liệu
            if (empty($data['title']) || empty($data['artist']) || 
                empty($data['file_path']) || empty($data['cover_image'])) {
                throw new Exception("Thiếu thông tin bắt buộc");
            }

            $sql = "INSERT INTO songs (title, artist, album, file_path, cover_image) 
                    VALUES (:title, :artist, :album, :file_path, :cover_image)";
            
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':title' => $data['title'],
                ':artist' => $data['artist'],
                ':album' => $data['album'] ?? '',
                ':file_path' => $data['file_path'],
                ':cover_image' => $data['cover_image']
            ];

            // Debug thông tin
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));

            if (!$stmt->execute($params)) {
                error_log("Database error: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Lỗi khi thêm bài hát vào database");
            }

            return true;

        } catch (Exception $e) {
            error_log("Error in addSong: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllSongs() {
        try {
            $sql = "SELECT * FROM songs ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllSongs: " . $e->getMessage());
            return [];
        }
    }

    public function getSongById($id) {
        try {
            $sql = "SELECT * FROM songs WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSongById: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSong($id) {
        try {
            // Lấy thông tin bài hát để xóa file
            $song = $this->getSongById($id);
            if ($song) {
                // Xóa files
                @unlink(__DIR__ . '/../../public/' . $song['file_path']);
                @unlink(__DIR__ . '/../../public/' . $song['cover_image']);
                
                // Xóa record trong database
                $sql = "DELETE FROM songs WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id]);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error in deleteSong: " . $e->getMessage());
            return false;
        }
    }

    public function updateSong($data) {
        try {
            if (empty($data['id'])) {
                throw new Exception("ID bài hát không hợp lệ");
            }

            $updates = [];
            $params = [];

            // Cập nhật các trường cơ bản
            foreach (['title', 'artist', 'album'] as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            // Cập nhật file_path nếu có
            if (!empty($data['file_path'])) {
                $updates[] = "file_path = :file_path";
                $params[':file_path'] = $data['file_path'];
            }

            // Cập nhật cover_image nếu có
            if (!empty($data['cover_image'])) {
                $updates[] = "cover_image = :cover_image";
                $params[':cover_image'] = $data['cover_image'];
            }

            if (empty($updates)) {
                return false;
            }

            $params[':id'] = $data['id'];
            $sql = "UPDATE songs SET " . implode(', ', $updates) . " WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("Error in updateSong: " . $e->getMessage());
            return false;
        }
    }
} 