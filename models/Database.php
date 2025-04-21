<?php
/**
 * Classe Database
 * Gère la connexion à la base de données avec PDO
 */
class Database {
    private static $instance = null;
    private $conn;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir l'instance unique de Database (Singleton)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir la connexion PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Exécuter une requête préparée
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtenir une seule ligne
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|false
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtenir toutes les lignes
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insérer des données et retourner l'ID inséré
     * @param string $table Nom de la table
     * @param array $data Données à insérer (clé => valeur)
     * @return int|false
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($query, array_values($data));
        return $this->conn->lastInsertId();
    }
    
    /**
     * Mettre à jour des données
     * @param string $table Nom de la table
     * @param array $data Données à mettre à jour (clé => valeur)
     * @param string $where Condition WHERE (ex: "id = ?")
     * @param array $whereParams Paramètres de la condition WHERE
     * @return int
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClauses = [];
        
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }
        
        $setClause = implode(', ', $setClauses);
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($query, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Supprimer des données
     * @param string $table Nom de la table
     * @param string $where Condition WHERE (ex: "id = ?")
     * @param array $params Paramètres de la condition WHERE
     * @return int
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($query, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollback() {
        $this->conn->rollBack();
    }
}