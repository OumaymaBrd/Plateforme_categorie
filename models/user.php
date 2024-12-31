<?php
require_once __DIR__ . '/../database/bdd.php';

class User {
    protected $id_user;
    protected $prenom;
    protected $nom;
    protected $email;
    protected $tel;
    protected $password;
    protected $matricule;
    protected $post;

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generateMatricule() {
        return 'AV' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function register($prenom, $nom, $email, $tel, $password, $post) {
        try {
            $this->prenom = $prenom;
            $this->nom = $nom;
            $this->email = $email;
            $this->tel = $tel;
            $this->password = password_hash($password, PASSWORD_DEFAULT);
            $this->post = $post;
            $this->matricule = $this->generateMatricule();

            $query = "INSERT INTO user_ (prenom, nom, email, tel, password, matricule, post) 
                      VALUES (:prenom, :nom, :email, :tel, :password, :matricule, :post)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':prenom', $this->prenom);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':tel', $this->tel);
            $stmt->bindParam(':password', $this->password);
            $stmt->bindParam(':matricule', $this->matricule);
            $stmt->bindParam(':post', $this->post);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login($matricule, $password) {
        try {
            $query = "SELECT * FROM user_ WHERE matricule = :matricule";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();
    
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $row['password'])) {
                    return $row;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    
}
?>