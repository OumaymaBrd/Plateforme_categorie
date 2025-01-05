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
            
            $articles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Traitement de l'image
                if (isset($row['image']) && $row['image'] !== null) {
                    // Vérifier si c'est un chemin de fichier
                    if (is_string($row['image']) && !preg_match('/[^\x20-\x7E]/', $row['image'])) {
                        // C'est probablement un chemin de fichier
                        $row['image_type'] = 'path';
                        // Nettoyer le chemin de fichier
                        $row['image'] = basename($row['image']); // Ne garder que le nom du fichier
                    } else {
                        // C'est probablement un BLOB
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

    public function confirmerArticle($articleId) {
        try {
            $query = "UPDATE article SET statut = 'confirme' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error confirming article: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error confirming article: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function annulerConfirmationArticle($articleId) {
        try {
            $query = "UPDATE article SET statut = 'nom confirme' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error canceling article confirmation: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error canceling article confirmation: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function supprimerArticle($articleId) {
        try {
            $query = "UPDATE article SET supprimer = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error deleting article: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error deleting article: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCategories() {
        try {
            $query = "SELECT id, nom, description FROM categories WHERE supprime = 0 ORDER BY nom ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $categories];
        } catch(PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function ajouterCategorie($nom, $description) {
        try {
            $query = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error adding category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
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
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error modifying category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
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
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error deleting category: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
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
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $users];
        } catch(PDOException $e) {
            error_log("Error fetching user profiles: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function consulterProfilsBloques($type = null) {
        try {
            $query = "SELECT id, nom, email, post, motif_supprime FROM user_ WHERE supprime = 1";
            if ($type !== null) {
                $query .= " AND post = :type";
            }
            $query .= " ORDER BY nom ASC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($type !== null) {
                $stmt->bindParam(':type', $type);
            }
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $users];
        } catch(PDOException $e) {
            error_log("Error fetching blocked user profiles: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function bloquerProfil($userId, $motif) {
        try {
            $query = "UPDATE user_ SET supprime = 1, motif_supprime = :motif WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':motif', $motif);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error blocking user profile: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error blocking user profile: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function debloquerProfil($userId) {
        try {
            $query = "UPDATE user_ SET supprime = 0,  motif_supprime = 'Avertissement' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error unblocking user profile: " . implode(", ", $errorInfo));
                return ['success' => false, 'error' => $errorInfo[2]];
            }
        } catch(PDOException $e) {
            error_log("Error unblocking user profile: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getArticleById($articleId) {
        try {
            $query = "SELECT a.*, u.nom as auteur_nom, u.prenom as auteur_prenom 
                     FROM article a 
                     LEFT JOIN user_ u ON a.id_auteur = u.id 
                     WHERE a.id = :id AND a.supprimer = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $articleId);
            $stmt->execute();
            
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article) {
                // Traitement de l'image comme dans getArticles()
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
                
                return ['success' => true, 'data' => $article];
            } else {
                return ['success' => false, 'error' => 'Article non trouvé'];
            }
        } catch(PDOException $e) {
            error_log("Error fetching article by ID: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCategorieById($categorieId) {
        try {
            $query = "SELECT * FROM categories WHERE id = :id AND supprime = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $categorieId);
            $stmt->execute();
            
            $categorie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categorie) {
                return ['success' => true, 'data' => $categorie];
            } else {
                return ['success' => false, 'error' => 'Catégorie non trouvée'];
            }
        } catch(PDOException $e) {
            error_log("Error fetching category by ID: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getProfilById($userId) {
        try {
            $query = "SELECT * FROM user_ WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return ['success' => true, 'data' => $user];
            } else {
                return ['success' => false, 'error' => 'Utilisateur non trouvé'];
            }
        } catch(PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>

