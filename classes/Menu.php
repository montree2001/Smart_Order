<?php
class Menu {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAllItems() {
        return $this->db->fetchAll("SELECT * FROM menu_items ORDER BY category, name");
    }

    public function getItemById($id) {
        return $this->db->fetchOne("SELECT * FROM menu_items WHERE id = ?", [$id]);
    }

    public function addItem($data) {
        $sql = "INSERT INTO menu_items (name, price, category, description, image_url, available) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['name'], $data['price'], $data['category'], 
            $data['description'], $data['image_url'], $data['available']
        ]);
        return $this->db->lastInsertId();
    }

    public function updateItem($id, $data) {
        $sql = "UPDATE menu_items 
                SET name = ?, price = ?, category = ?, description = ?, image_url = ?, available = ?
                WHERE id = ?";
        return $this->db->query($sql, [
            $data['name'], $data['price'], $data['category'], 
            $data['description'], $data['image_url'], $data['available'], $id
        ]);
    }

    public function deleteItem($id) {
        return $this->db->query("DELETE FROM menu_items WHERE id = ?", [$id]);
    }

    public function getCategories() {
        return $this->db->fetchAll("SELECT DISTINCT category FROM menu_items ORDER BY category");
    }
}
