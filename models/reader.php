<?php
require_once __DIR__ . '/user.php';

class Reader extends User {
    public function __construct($db) {
        parent::__construct($db);
    }

    public function getArticles($page = 1, $limit = 10, $status = 'confirme', $category = null) {
        try {
            $offset = ($page - 1) * $limit;

            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.statut, 
                         a.image, a.nom_categorie, u.nom, u.prenom
                  FROM article a
                  LEFT JOIN user_ u ON a.id_auteur = u.id
                  WHERE a.supprimer = 0";
            
            if ($status !== null) {
                $query .= " AND a.statut = :status";
            }
            
            if ($category !== null && $category !== '') {
                $query .= " AND a.nom_categorie = :category";
            }
            
            $query .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            if ($status !== null) {
                $stmt->bindParam(':status', $status);
            }
            
            if ($category !== null && $category !== '') {
                $stmt->bindParam(':category', $category);
            }

            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $articles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['image']) && $row['image'] !== null) {
                    if (is_string($row['image']) && !preg_match('/[^\x20-\x7E]/', $row['image'])) {
                        $row['image_type'] = 'path';
                        $row['image'] = basename($row['image']);
                    } else {
                        $row['image_type'] = 'blob';
                        if (is_resource($row['image'])) {
                            $row['image'] = stream_get_contents($row['image']);
                        }
                    }
                } else {
                    $row['image_type'] = 'none';
                    $row['image'] = null;
                }
                $articles[] = $row;
            }
            
            return ['success' => true, 'data' => $articles];
        } catch(PDOException $e) {
            error_log("Error in getArticles: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCategories() {
        try {
            $query = "SELECT DISTINCT nom_categorie FROM article WHERE supprimer = 0 AND nom_categorie IS NOT NULL AND nom_categorie != ''";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) {
            error_log("Error in getCategories: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalArticles($category = null) {
        try {
            $query = "SELECT COUNT(*) as total 
                     FROM article 
                     WHERE statut = 'confirme' 
                     AND supprimer = 0";
            
            if ($category !== null && $category !== '') {
                $query .= " AND nom_categorie = :category";
            }

            $stmt = $this->conn->prepare($query);
            
            if ($category !== null && $category !== '') {
                $stmt->bindParam(':category', $category);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch(PDOException $e) {
            error_log("Erreur dans getTotalArticles: " . $e->getMessage());
            return 0;
        }
    }

    public function getArticleById($id) {
        try {
            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.statut, 
                         a.image, a.nom_categorie, u.nom, u.prenom
                     FROM article a
                     LEFT JOIN user_ u ON a.id_auteur = u.id
                     WHERE a.id = :id AND a.supprimer = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article) {
                if (isset($article['image']) && $article['image'] !== null) {
                    if (is_string($article['image']) && !preg_match('/[^\x20-\x7E]/', $article['image'])) {
                        $article['image_type'] = 'path';
                        $article['image'] = basename($article['image']);
                    } else {
                        $article['image_type'] = 'blob';
                        if (is_resource($article['image'])) {
                            $article['image'] = stream_get_contents($article['image']);
                        }
                    }
                } else {
                    $article['image_type'] = 'none';
                    $article['image'] = null;
                }
            }

            return $article;
        } catch(PDOException $e) {
            error_log("Erreur dans getArticleById: " . $e->getMessage());
            return false;
        }
    }
}
?>