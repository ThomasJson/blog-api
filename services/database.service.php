<?php
class DatabaseService

// Cette classe va nous permettre de se connecter à la base de donnée 
// et d’exécuter des requêtes SQL

{
    public function __construct($table)
    {
        $this->table = $table;
    }

    private static $connection = null;

    private function connect()
    {
        if (self::$connection == null) {

            //Connexion à la DB
            $db_config = $_ENV['config']->db;
            $host = $db_config->host;
            $port = $db_config->port;
            $dbName = $db_config->dbName;
            $dsn = "mysql:host=$host;port=$port;dbname=$dbName";
            $user = $db_config->user;
            $pass = $db_config->pass;
            try {
                $db_connection = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    )
                );
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données : $e->getMessage()");
            }
            self::$connection = $db_connection;
        }
        return self::$connection;
    }

    // La méthode query() pour exécuter des requêtes préparées.
    // Méthode qui nous permet de faire des requêtes préparées 
    // et qui nous renverra un objet avec le result (true/false) 
    // de l’exécution de la requête 
    // ainsi que le statment 
    // pour accéder aux info renvoyées par la requête SQL
    public function query($sql, $params = [])
    {
        $statment = $this->connect()->prepare($sql);
        $result = $statment->execute($params);
        return (object)['result' => $result, 'statment' => $statment];
    }
    public function selectAll($is_deleted = 0)
    {
        $sql = "SELECT * FROM $this->table WHERE is_deleted = ?";
        $resp = $this->query($sql, [$is_deleted]);
        $rows = $resp->statment->fetchAll(PDO::FETCH_CLASS);
        return $rows;
    }

    public function selectWhere($where = "1", $params = [])
    {
        $sql = "SELECT * FROM $this->table WHERE $where;";
        $resp = $this->query($sql, $params);
        $rows = $resp->statment->fetchAll(PDO::FETCH_CLASS);
        return $rows;
    }

    public function selectOne($id)
    {
        $sql = "SELECT * FROM $this->table WHERE is_deleted = ? AND Id_$this->table = ?";
        $resp = $this->query($sql, [0, $id]);
        $rows = $resp->statment->fetchAll(PDO::FETCH_CLASS);
        $row = $resp->result && count($rows) == 1 ? $rows[0] : null;
        return $row;
    }

    public function insertOne($body = []){ 
        if(isset($body["Id_$this->table"])){
            unset($body["Id_$this->table"]);
        }
        $columns = implode(",", array_keys($body));
        $values = implode(",", array_map(function (){ return "?"; },$body));
        $valuesToBind = array_values($body);
        $sql = "INSERT INTO $this->table ($columns) VALUES ($values)";
        $resp = $this->query($sql, $valuesToBind);
        if($resp->result && $resp->statment->rowCount() == 1){
            $insertedId = self::$connection->lastInsertId();
            $row = $this->selectOne($insertedId);
            return $row;
        }
        return false;
    }
}
