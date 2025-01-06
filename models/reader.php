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

    public function addComment($article_id, $user_id, $comment) {
        try {
            $query = "INSERT INTO commentaires (id_article, id_utilisateur, commentaire, date_creation) 
                      VALUES (:article_id, :user_id, :comment, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in addComment: " . $e->getMessage());
            return false;
        }
    }

    public function updateComment($commentId, $newComment) {
        $query = "UPDATE commentaires SET commentaire = :commentaire WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':commentaire', $newComment);
        $stmt->bindParam(':id', $commentId);
        return $stmt->execute();
    }

    public function deleteComment($commentId) {
        $query = "DELETE FROM commentaires WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $commentId);
        return $stmt->execute();
    }

    public function getComments($article_id) {
        try {
            $query = "SELECT c.*, u.nom, u.prenom 
                      FROM commentaires c
                      LEFT JOIN user_ u ON c.id_utilisateur = u.id
                      WHERE c.id_article = :article_id
                      ORDER BY c.date_creation DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getComments: " . $e->getMessage());
            return [];
        }
    }

    public function handleLikeDislike($articleId, $userId, $action) {
        $query = "SELECT * FROM adore WHERE id_article = :article_id AND id_reader = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $articleId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $existingLike = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingLike) {
            if ($action === 'like') {
                $adore = $existingLike['adore'] == 1 ? 0 : 1;
                $deslike = 0;
            } else {
                $adore = 0;
                $deslike = $existingLike['deslike'] == 1 ? 0 : 1;
            }
            $query = "UPDATE adore SET adore = :adore, deslike = :deslike WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':adore', $adore);
            $stmt->bindParam(':deslike', $deslike);
            $stmt->bindParam(':id', $existingLike['id']);
        } else {
            $adore = $action === 'like' ? 1 : 0;
            $deslike = $action === 'dislike' ? 1 : 0;
            $query = "INSERT INTO adore (id_article, id_reader, adore, deslike) VALUES (:article_id, :user_id, :adore, :deslike)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':article_id', $articleId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':adore', $adore);
            $stmt->bindParam(':deslike', $deslike);
        }

        return $stmt->execute();
    }

    public function getArticleLikes($articleId) {
        $query = "SELECT 
                    SUM(adore) as likes,
                    SUM(deslike) as dislikes
                  FROM adore 
                  WHERE id_article = :article_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $articleId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserLikeStatus($articleId, $userId) {
        $query = "SELECT adore, deslike FROM adore WHERE id_article = :article_id AND id_reader = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $articleId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            if ($result['adore'] == 1) return 'like';
            if ($result['deslike'] == 1) return 'dislike';
        }
        return null;
    }

    public function toggleFavorite($articleId, $userId) {
        try {
            $this->conn->beginTransaction();

            $query = "SELECT * FROM favoris WHERE id_article = :article_id AND id_reader = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':article_id', $articleId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $existingFavorite = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingFavorite) {
                $query = "DELETE FROM favoris WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $existingFavorite['id']);
            } else {
                $query = "INSERT INTO favoris (id_article, id_reader) VALUES (:article_id, :user_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':article_id', $articleId);
                $stmt->bindParam(':user_id', $userId);
            }

            $result = $stmt->execute();
            $this->conn->commit();
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in toggleFavorite: " . $e->getMessage());
            return false;
        }
    }

    public function isFavorite($articleId, $userId) {
        $query = "SELECT COUNT(*) FROM favoris WHERE id_article = :article_id AND id_reader = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $articleId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function getFavorites($userId) {
        $query = "SELECT a.*, f.id as favorite_id
                  FROM favoris f
                  JOIN article a ON f.id_article = a.id
                  WHERE f.id_reader = :user_id
                  ORDER BY f.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeFavorite($favoriteId, $userId) {
        try {
            $this->conn->beginTransaction();

            $query = "DELETE FROM favoris WHERE id = :id AND id_reader = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $favoriteId);
            $stmt->bindParam(':user_id', $userId);
            $result = $stmt->execute();

            $this->conn->commit();
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in removeFavorite: " . $e->getMessage());
            return false;
        }
    }
}
?>

