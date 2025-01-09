<?php
require_once __DIR__ . '/../database/bdd.php';

class User {
    protected $conn;
    protected $table_name = "user_";  

    public $id;  
    public $prenom;
    public $nom;
    public $email;
    public $tel;
    public $password;
    public $matricule;
    public $post;
    public $supprime;
    public $image;
    public $age;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserByMatricule($matricule, $password) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE matricule = :matricule 
                      AND supprime = 0";  
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":matricule", $matricule);
            
            if($stmt->execute()) {
                if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if(password_verify($password, $row['password'])) {
                        return array(
                            'id_user' => $row['id'],
                            'nom' => $row['nom'],
                            'prenom' => $row['prenom'],
                            'email' => $row['email'],
                            'tel' => $row['tel'],
                            'post' => $row['post'],
                            'matricule' => $row['matricule'],
                            'image' => $row['image'],
                            'age' => $row['age']
                        );
                    }
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function register($prenom, $nom, $email, $tel, $password, $post, $age, $image = null) {
        try {
            $upload_dir = __DIR__ . '/../assets/profil_image/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $query = "INSERT INTO " . $this->table_name . " 
                      (prenom, nom, email, tel, password, matricule, post, supprime, age, image) 
                      VALUES (:prenom, :nom, :email, :tel, :password, :matricule, :post, 0, :age, :image)";

            $stmt = $this->conn->prepare($query);

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $matricule = $this->generateMatricule($post);

            $image_filename = null;
            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $image_info = getimagesize($image['tmp_name']);
                if ($image_info !== false) {
                    $image_filename = uniqid() . '_' . basename($image['name']);
                    $upload_path = $upload_dir . $image_filename;
                    move_uploaded_file($image['tmp_name'], $upload_path);
                }
            }

            $stmt->bindParam(":prenom", $prenom);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":tel", $tel);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":matricule", $matricule);
            $stmt->bindParam(":post", $post);
            $stmt->bindParam(":age", $age);
            $stmt->bindParam(":image", $image_filename);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Erreur lors de l'inscription: " . $e->getMessage());
            return false;
        }
    }

    private function generateMatricule($post) {
        $prefix = ($post == 'auteur') ? 'AUT' : 'LEC';
        $unique = uniqid();
        return $prefix . substr($unique, -5);
    }

    public function updateProfile($id, $prenom, $nom, $email, $tel, $age, $image = null) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET prenom = :prenom, nom = :nom, email = :email, tel = :tel, age = :age";
            
            $params = [
                ":prenom" => $prenom,
                ":nom" => $nom,
                ":email" => $email,
                ":tel" => $tel,
                ":age" => $age,
                ":id" => $id
            ];

            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../assets/profil_image/';
                $image_filename = uniqid() . '_' . basename($image['name']);
                $upload_path = $upload_dir . $image_filename;
                
                if (move_uploaded_file($image['tmp_name'], $upload_path)) {
                    $query .= ", image = :image";
                    $params[":image"] = $image_filename;
                }
            }

            $query .= " WHERE id = :id AND supprime = 0";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Erreur lors de la mise à jour du profil: " . $e->getMessage());
            return false;
        }
    }

    public function changePassword($id, $new_password) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET password = :password 
                      WHERE id = :id AND supprime = 0";

            $stmt = $this->conn->prepare($query);

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":id", $id);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Erreur lors du changement de mot de passe: " . $e->getMessage());
            return false;
        }
    }
}
?>