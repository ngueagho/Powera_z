<?php
/**
 * Classe User
 * Gère les opérations liées aux utilisateurs
 */
class User {
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Créer un nouvel utilisateur
     * @param array $userData Données de l'utilisateur
     * @return int|false ID de l'utilisateur ou false si échec
     */
    public function create($userData) {
        // Hasher le mot de passe
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Générer un token de vérification
        $userData['verification_token'] = bin2hex(random_bytes(32));
        
        return $this->db->insert('users', $userData);
    }
    
    /**
     * Mettre à jour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param array $userData Données à mettre à jour
     * @return int Nombre de lignes affectées
     */
    public function update($userId, $userData) {
        // Si un nouveau mot de passe est fourni, le hasher
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        return $this->db->update('users', $userData, 'id = ?', [$userId]);
    }
    
    /**
     * Supprimer un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de lignes affectées
     */
    public function delete($userId) {
        return $this->db->delete('users', 'id = ?', [$userId]);
    }
    
    /**
     * Obtenir un utilisateur par son ID
     * @param int $userId ID de l'utilisateur
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getById($userId) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Obtenir un utilisateur par son email
     * @param string $email Email de l'utilisateur
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getByEmail($email) {
        return $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    /**
     * Obtenir un utilisateur par son token de vérification
     * @param string $token Token de vérification
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getByVerificationToken($token) {
        return $this->db->fetchOne("SELECT * FROM users WHERE verification_token = ?", [$token]);
    }
    
    /**
     * Obtenir un utilisateur par son token de réinitialisation
     * @param string $token Token de réinitialisation
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getByResetToken($token) {
        return $this->db->fetchOne("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()", [$token]);
    }
    
    /**
     * Vérifier l'email d'un utilisateur
     * @param string $token Token de vérification
     * @return bool Succès ou échec
     */
    public function verifyEmail($token) {
        $user = $this->getByVerificationToken($token);
        
        if ($user) {
            return $this->db->update('users', 
                ['verified' => true, 'verification_token' => null],
                'id = ?', 
                [$user['id']]
            ) > 0;
        }
        
        return false;
    }
    
    /**
     * Générer un token de réinitialisation de mot de passe
     * @param string $email Email de l'utilisateur
     * @return string|false Token généré ou false si l'utilisateur n'existe pas
     */
    public function generateResetToken($email) {
        $user = $this->getByEmail($email);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 heure
            
            $updated = $this->db->update('users', 
                ['reset_token' => $token, 'reset_token_expiry' => $expiry],
                'id = ?', 
                [$user['id']]
            );
            
            return $updated ? $token : false;
        }
        
        return false;
    }
    
    /**
     * Réinitialiser le mot de passe
     * @param string $token Token de réinitialisation
     * @param string $newPassword Nouveau mot de passe
     * @return bool Succès ou échec
     */
    public function resetPassword($token, $newPassword) {
        $user = $this->getByResetToken($token);
        
        if ($user) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            return $this->db->update('users', 
                ['password' => $hashedPassword, 'reset_token' => null, 'reset_token_expiry' => null],
                'id = ?', 
                [$user['id']]
            ) > 0;
        }
        
        return false;
    }
    
    /**
     * Authentifier un utilisateur
     * @param string $email Email
     * @param string $password Mot de passe
     * @return array|false Données de l'utilisateur ou false si échec
     */
    public function authenticate($email, $password) {
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // Vérifier si l'utilisateur est verrouillé pour tentatives échouées
        if ($user['lockout_time'] && strtotime($user['lockout_time']) > time()) {
            return false;
        }
        
        // Vérifier le statut du compte
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            // Réinitialiser les tentatives de connexion
            $this->db->update('users', 
                ['login_attempts' => 0, 'lockout_time' => null, 'last_login' => date('Y-m-d H:i:s')],
                'id = ?', 
                [$user['id']]
            );
            
            return $user;
        }
        
        // Incrémenter les tentatives de connexion
        $attempts = $user['login_attempts'] + 1;
        $lockout = null;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockout = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
        }
        
        $this->db->update('users', 
            ['login_attempts' => $attempts, 'lockout_time' => $lockout],
            'id = ?', 
            [$user['id']]
        );
        
        return false;
    }
    
    /**
     * Obtenir tous les utilisateurs
     * @param string $role Filtrer par rôle (optionnel)
     * @param string $status Filtrer par statut (optionnel)
     * @return array Liste des utilisateurs
     */
    public function getAll($role = null, $status = null) {
        $query = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if ($role) {
            $query .= " AND role = ?";
            $params[] = $role;
        }
        
        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Changer le statut d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function changeStatus($userId, $status) {
        return $this->db->update('users', ['status' => $status], 'id = ?', [$userId]) > 0;
    }
    
    /**
     * Télécharger une photo de profil
     * @param int $userId ID de l'utilisateur
     * @param array $file Données du fichier ($_FILES)
     * @return string|false Chemin de l'image ou false si échec
     */
    public function uploadProfilePic($userId, $file) {
        // Vérifier si le fichier est une image
        $fileType = exif_imagetype($file['tmp_name']);
        if (!$fileType || !in_array($fileType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            return false;
        }
        
        // Créer le dossier si nécessaire
        if (!file_exists(USER_UPLOADS)) {
            mkdir(USER_UPLOADS, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '_' . uniqid() . '.' . $extension;
        $filePath = USER_UPLOADS . $fileName;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Mettre à jour l'utilisateur
            $this->db->update('users', ['profile_pic' => $fileName], 'id = ?', [$userId]);
            return $fileName;
        }
        
        return false;
    }
}