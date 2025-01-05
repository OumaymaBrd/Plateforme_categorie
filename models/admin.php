<?php
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getArticles($status = null) {
        try {
            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.statut, 
                         a.image, a.nom_categorie, u.nom, u.prenom
                  FROM article a
                  LEFT JOIN user_ u ON a.id_auteur = u.id
                  WHERE a.supprimer = 0";
            
            if ($status !== null) {
                $query .= " AND a.statut = :status";
            }
        
            $stmt = $this->conn->prepare($query);
            
            if ($status !== null) {
                $stmt->bindParam(':status', $status);
            }
            
            $stmt->execute();
        
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            if ($result === false) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error fetching articles: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        
            return ['success' => true, 'data' => $result];
        } catch(PDOException $e) {
            error_log("Error in getArticles: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function confirmerArticle($articleId) {
        return $this->updateArticleStatus($articleId, 'confirme');
    }

    public function annulerConfirmationArticle($articleId) {
        return $this->updateArticleStatus($articleId, 'nom confirme');
    }

    private function updateArticleStatus($articleId, $status) {
        try {
            $query = "UPDATE article SET statut = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $articleId);
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error updating article status: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error updating article status: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCategories() {
        try {
            $query = "SELECT id, nom, description FROM categories WHERE supprime = 0 ORDER BY nom ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $result];
        } catch(PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function consulterProfils($type = null) {
        try {
            $query = "SELECT id, nom, email, post FROM user_ WHERE supprime = 0";
            if ($type !== null) {
                $query .= " AND post = :type";
            }
            $query .= " ORDER BY nom ASC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($type !== null) {
                $stmt->bindParam(':type', $type);
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $result];
        } catch(PDOException $e) {
            error_log("Error fetching user profiles: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function bloquerProfil($userId, $motif) {
        try {
            $query = "UPDATE user_ SET supprime = 1, motif_supprime = :motif WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':motif', $motif);
            $result = $stmt->execute();
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error blocking user profile: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error blocking user profile: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function ajouterCategorie($nom, $description) {
        try {
            $query = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error adding category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error adding category: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function modifierCategorie($id, $nom, $description) {
        try {
            $query = "UPDATE categories SET nom = :nom, description = :description WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error modifying category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error modifying category: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function supprimerCategorie($id) {
        try {
            $query = "UPDATE categories SET supprime = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error deleting category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function supprimerArticle($articleId) {
        try {
            $query = "UPDATE article SET supprimer = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error deleting article: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Error deleting article: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>

