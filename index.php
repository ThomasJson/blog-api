<?php

// Autoriser l'accès
header("Access-Control-Allow-Origin: http://localhost:3000");

// Dev Mode
// On récup les paramètres du fichier 
// config dans une variable globale $_ENV
$_ENV["current"] = "dev";
$config = file_get_contents("configs/" . $_ENV["current"] . ".config.json");
$_ENV['config'] = json_decode($config);
//
// Import de la classe DatabaseService, DatabaseController //
require_once 'services/database.service.php';
require_once 'controllers/database.controller.php';
//

// 
// Tester la connexion en éxecutant la méthode connect() en public
// $dbs = new DatabaseService("test");
// $db_connection = $dbs->connect();
//
//
// TEST DE LA METHODE QUERY()
$dbs = new DatabaseService("test");
// $query_resp = $dbs->query("SELECT table_name FROM information_schema.tables
// WHERE table_schema = ?", ['db_blog']);
// $rows = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
// En utilisant PDO::FETCH_COLUMN on obtient un tableau indicé.
// echo var_export($rows) . "<br/>";
// la fonction var_export() permet d’afficher le contenu d’une variable
// Cette requête permet de lister toutes les tables de la base de données.
//


//
$route = trim($_SERVER["REQUEST_URI"], '/');
// La variable $_SERVER["REQUEST_URI"] contient la route saisie.
$route = filter_var($route, FILTER_SANITIZE_URL);
$route = explode('/', $route);
$controllerName = array_shift($route);

// Création d'une route GET /init qui permet d'initialiser l'api rest
// Uniquement si nous sommes en dev. 
if ($_ENV["current"] == "dev" && $controllerName == "init") {
    // Permet de lister toutes les tables de notre bdd
    $dbs = new DatabaseService(null);
    $query_resp = $dbs->query("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", ['db_blog']);
    $rows = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
    //
    // Pour chaque nom de table contenu dans $rows
    // Création d'un fichier avec son contenu 
    foreach ($rows as $tableName) {
        $controllerFile = "controllers/$tableName.controller.php";
        if(!file_exists($controllerFile)){
            $fileContent = "<?php class ".ucfirst($tableName)."Controller extends DatabaseController {\r\n\r\n}?>";
            file_put_contents($controllerFile, $fileContent);
            echo ucfirst($tableName)."Controller created\r\n";
        }
    }
    echo 'api initialized';
}


// Créer une instance du controleur correspondant à la route
$controllerFilePath = "controllers/$controllerName.controller.php";
if (!file_exists($controllerFilePath)) {
    header('HTTP/1.0 404 Not Found');
    die;
}
// Affiche le résultat de l’action du controller
require_once $controllerFilePath;
$controllerClassName = ucfirst($controllerName) . "Controller";
$controller = new $controllerClassName($route);

$response = $controller->action;

// Si la deuxième partie de la route, l’id, est un entier, la requête fonctionne, 
// sinon erreur 404
if (!isset($response)) {
    header('HTTP/1.0 404 Not Found');
    die;
}

echo json_encode($response);
