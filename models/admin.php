<?php
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getArticles($status = null) {
        try {
            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.image, 
                             a.id_categorie, a.id_auteur, a.statut,
                             u.nom as nom_auteur, c.nom as nom_categorie
                      FROM article a
                      LEFT JOIN user_ u ON a.id_auteur = u.id
                      LEFT JOIN categories c ON a.id_categorie = c.id
                      WHERE 1=1";
            
            if ($status) {
                $query .= " AND a.statut = :status";
            }
            
            $stmt = $this->conn->prepare($query);
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                error_log("Error fetching articles: " . implode(", ", $stmt->errorInfo()));
                return [];
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Error in getArticles: " . $e->getMessage());
            return [];
        }
    }

    public function getCategories() {
        try {
            $query = "SELECT id, nom, description FROM categories ORDER BY nom ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    public function consulterProfils() {
        try {
            $query = "SELECT id, nom, email, post FROM user_ ORDER BY nom ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching user profiles: " . $e->getMessage());
            return [];
        }
    }

    public function confirmerArticle($articleId) {
        try {
            $query = "UPDATE article SET statut = 'confirme' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error confirming article: " . $e->getMessage());
            return false;
        }
    }

    public function bloquerProfil($userId, $motif) {
        try {
            $query = "UPDATE user_ SET statut = 'bloque', motif_blocage = :motif WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':motif', $motif);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error blocking user profile: " . $e->getMessage());
            return false;
        }
    }

    public function ajouterCategorie($nom, $description) {
        try {
            $query = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error adding category: " . $e->getMessage());
            return false;
        }
    }
}