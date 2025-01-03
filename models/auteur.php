<?php
require_once __DIR__ . '/user.php';

class Auteur extends User {
    private $articles_table = "article";
    private $categories_table = "categories";
    protected $conn;

    public function __construct($db) {
        $this->conn = $db;
        parent::__construct($db);
    }

    public function getAllCategories() {
        try {
            $query = "SELECT id, nom, description FROM `" . $this->categories_table . "` WHERE supprime = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
            throw $e;
        }
    }

    public function createArticle($auteur_id, $titre, $description, $contenu, $image, $categorie_id) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO `" . $this->articles_table . "` 
                     (titre, description, contenu, statut, image, id_auteur, id_categorie, supprimer) 
                     VALUES (:titre, :description, :contenu, :statut, :image, :id_auteur, :id_categorie, 0)";

            $stmt = $this->conn->prepare($query);

            $statut = "nom confirme"; // Updated line

            // Bind all parameters before handling the image
            $stmt->bindParam(":titre", $titre);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":contenu", $contenu);
            $stmt->bindParam(":statut", $statut);
            $stmt->bindParam(":id_auteur", $auteur_id);
            $stmt->bindParam(":id_categorie", $categorie_id); // Updated parameter binding
            $stmt->bindParam(":image", $image_path);

            // Handle image upload if present
            if ($image && $image['size'] > 0) {
                $upload_dir = __DIR__ . '/../assets/images/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        throw new Exception("Impossible de créer le répertoire d'upload.");
                    }
                }
                $image_name = uniqid() . '_' . basename($image["name"]);
                $upload_file = $upload_dir . $image_name;

                if (move_uploaded_file($image['tmp_name'], $upload_file)) {
                    $image_path = 'assets/images/' . $image_name;
                } else {
                    throw new Exception("Échec du téléchargement de l'image.");
                }
            }

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'exécution de la requête: " . implode(", ", $stmt->errorInfo()));
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur de création d'article: " . $e->getMessage());
            throw $e;
        }
    }

    public function getArticlesByAuteur($auteur_id) {
        try {
            if (!$this->conn) {
                throw new Exception("La connexion à la base de données n'est pas établie.");
            }

            // Simplified query that only uses known columns
            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.statut, a.image, a.id_auteur, 
                            c.nom as nom_categorie 
                     FROM `" . $this->articles_table . "` a
                     LEFT JOIN `" . $this->categories_table . "` c 
                     ON a.`id_categorie` = c.`id`
                     WHERE a.`id_auteur` = :id_auteur 
                     AND a.`supprimer` = 0";

            $stmt = $this->conn->prepare($query);

            if (!$stmt) {
                throw new Exception("Erreur lors de la préparation de la requête.");
            }

            $stmt->bindParam(":id_auteur", $auteur_id);

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'exécution de la requête: " . implode(", ", $stmt->errorInfo()));
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur de récupération des articles: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateArticle($id, $auteur_id, $titre, $description, $contenu, $image, $categorie_id) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE `" . $this->articles_table . "` 
                     SET titre = :titre, 
                         description = :description, 
                         contenu = :contenu, 
                         id_categorie = :id_categorie";

            if ($image && $image['size'] > 0) {
                $query .= ", image = :image";
            }

            $query .= " WHERE id = :id AND id_auteur = :id_auteur AND supprimer = 0";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":titre", $titre);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":contenu", $contenu);
            $stmt->bindParam(":id_auteur", $auteur_id);
            $stmt->bindParam(":id_categorie", $categorie_id);

            if ($image && $image['size'] > 0) {
                $upload_dir = __DIR__ . '/../assets/images/';
                $image_name = uniqid() . '_' . basename($image["name"]);
                $upload_file = $upload_dir . $image_name;

                if (move_uploaded_file($image['tmp_name'], $upload_file)) {
                    $image_path = 'assets/images/' . $image_name;
                    $stmt->bindParam(":image", $image_path);
                } else {
                    throw new Exception("Échec du téléchargement de l'image lors de la mise à jour.");
                }
            }

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'exécution de la requête de mise à jour");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erreur de mise à jour d'article: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteArticle($id, $auteur_id) {
        try {
            $query = "UPDATE `" . $this->articles_table . "` 
                     SET supprimer = 1 
                     WHERE id = :id AND id_auteur = :id_auteur";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":id_auteur", $auteur_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la suppression de l'article");
            }
            return true;
        } catch (Exception $e) {
            error_log("Erreur de suppression d'article: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAuteurInfo($auteur_id) {
        try {
            $query = "SELECT prenom, nom FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $auteur_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des informations de l'auteur: " . $e->getMessage());
            throw $e;
        }
    }

    public function getArticlesByAuteurAndCategory($auteur_id, $category_id = null) {
        try {
            $query = "SELECT a.id, a.titre, a.description, a.contenu, a.statut, a.image, a.id_auteur, a.id_categorie, 
                         c.nom as nom_categorie 
                  FROM `" . $this->articles_table . "` a
                  LEFT JOIN `" . $this->categories_table . "` c ON a.`id_categorie` = c.`id`
                  WHERE a.`id_auteur` = :id_auteur 
                  AND a.`supprimer` = 0";

            if ($category_id) {
                $query .= " AND a.`id_categorie` = :category_id";
            }

            $query .= " ORDER BY a.id DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_auteur", $auteur_id);

            if ($category_id) {
                $stmt->bindParam(":category_id", $category_id);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur de récupération des articles: " . $e->getMessage());
            throw $e;
        }
    }

    public function getArticleStatusStats($auteur_id) {
        try {
            $query = "SELECT 
                        SUM(CASE WHEN statut = 'confirme' THEN 1 ELSE 0 END) as confirme,
                        SUM(CASE WHEN statut = 'non confirme' THEN 1 ELSE 0 END) as non_confirme
                      FROM `" . $this->articles_table . "`
                      WHERE id_auteur = :id_auteur AND supprimer = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_auteur", $auteur_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des statistiques de statut: " . $e->getMessage());
            throw $e;
        }
    }

    public function getArticlesByCategoryStats($auteur_id) {
        try {
            $query = "SELECT c.nom as category_name, COUNT(a.id) as article_count
                      FROM `" . $this->categories_table . "` c
                      LEFT JOIN `" . $this->articles_table . "` a ON c.id = a.id_categorie AND a.id_auteur = :id_auteur AND a.supprimer = 0
                      GROUP BY c.id
                      ORDER BY article_count DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_auteur", $auteur_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des statistiques par catégorie: " . $e->getMessage());
            throw $e;
        }
    }

    public function getDeletedArticlesCount($auteur_id) {
        try {
            $query = "SELECT COUNT(*) as deleted_count
                      FROM `" . $this->articles_table . "`
                      WHERE id_auteur = :id_auteur AND supprimer = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_auteur", $auteur_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['deleted_count'];
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du nombre d'articles supprimés: " . $e->getMessage());
            throw $e;
        }
    }
}
?>

