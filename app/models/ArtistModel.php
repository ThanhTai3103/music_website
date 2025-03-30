<?php
require_once __DIR__ . '/../core/Database.php';

class ArtistModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addArtist($data) {
        try {
            if (empty($data['name']) || empty($data['image'])) {
                throw new Exception("Thiếu thông tin bắt buộc");
            }

            $sql = "INSERT INTO artists (name, image, followers) 
                    VALUES (:name, :image, :followers)";
            
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':name' => $data['name'],
                ':image' => $data['image'],
                ':followers' => $data['followers'] ?? 0
            ];

            if (!$stmt->execute($params)) {
                throw new Exception("Lỗi khi thêm nghệ sĩ");
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in addArtist: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllArtists() {
        try {
            $sql = "SELECT * FROM artists ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllArtists: " . $e->getMessage());
            return [];
        }
    }

    public function getArtistById($id) {
        try {
            $sql = "SELECT * FROM artists WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getArtistById: " . $e->getMessage());
            return false;
        }
    }

    public function updateArtist($data) {
        try {
            if (empty($data['id']) || empty($data['name'])) {
                throw new Exception("Thiếu thông tin bắt buộc");
            }

            $updates = [];
            $params = [':id' => $data['id']];

            if (isset($data['name'])) {
                $updates[] = "name = :name";
                $params[':name'] = $data['name'];
            }

            if (isset($data['image'])) {
                $updates[] = "image = :image";
                $params[':image'] = $data['image'];
            }

            if (isset($data['followers'])) {
                $updates[] = "followers = :followers";
                $params[':followers'] = $data['followers'];
            }

            $sql = "UPDATE artists SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("Error in updateArtist: " . $e->getMessage());
            return false;
        }
    }

    public function deleteArtist($id) {
        try {
            $artist = $this->getArtistById($id);
            if ($artist) {
                @unlink(__DIR__ . '/../../public/' . $artist['image']);
                
                $sql = "DELETE FROM artists WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id]);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error in deleteArtist: " . $e->getMessage());
            return false;
        }
    }
} 