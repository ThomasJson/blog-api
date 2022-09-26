<?php

abstract class DatabaseController
{
    public function __construct($params)
    {
        $id = array_shift($params);
        $this->action = null;

        // Vérifier que l'id est bien un entier 
        if (isset($id) && !ctype_digit($id)) {
            return $this;
        }

        // On récupère le Json body côté PHP
        $request_body = file_get_contents('php://input');
        $this->body = $request_body ? json_decode($request_body, true) : null;
        // nous obtenons ainsi un tableau associatif (clé/valeur)

        // Connaître la table correspondant au contrôleur utilisé
        $this->table = lcfirst(str_replace("Controller", "", get_called_class()));

        if ($_SERVER['REQUEST_METHOD'] == "GET" && !isset($id)) {
            $this->action = $this->getAll();
        }
        if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($id)) {
            $this->action = $this->getOne($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $this->action = $this->create();
        }
        if ($_SERVER['REQUEST_METHOD'] == "PUT" && isset($id)) {
            $this->action = $this->update($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "PATCH" && isset($id)) {
            $this->action = $this->softDelete($id);
        }
        if ($_SERVER['REQUEST_METHOD'] == "DELETE" && isset($id)) {
            $this->action = $this->hardDelete($id);
        }

        // Routes avec les relations
        // Ces routes vont permettre de spécifier pour quelles relations 
        // nous souhaitons récupérer les données, cette information 
        // sera passée dans le body de la requête.
        if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($id)) {
            if ($id == 0) {
                $this->action = $this->getAllWith($this->body["with"]);
            }
            if ($id > 0) {
                $this->action = $this->getOneWith($id, $this->body["with"]);
            }
        }
    }

    // Cette méthode n’a pas de corps, elle va devoir être définie dans chaque classe fille
    // Le & présent devant la variable $row indique que cette variable est passée
    // par référence et donc qu’elle sera modifiée par la méthode.
    public abstract function affectDataToRow(&$row, $sub_rows);

    public function getAll()
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->selectAll();
        return $rows; // "Select all rows from table tag";
    }

    // Récupérer les données provenant d'autres tables
    function getAllWith($with)
    {
        $rows = $this->getAll();
        foreach ($with as $table) {
            // si $table est un tableau, il s’agit d’une relation ManyToMany
            if (is_array($table)) {
                $final_table = key($table);
                $through_table = $table[$final_table];
                $dbs = new DatabaseService($through_table);
                $through_table_rows = $dbs->selectWhere();
                $dbs = new DatabaseService($final_table);
                $final_table_rows = $dbs->selectAll();
                foreach ($through_table_rows as $through_table_row) {
                    $row_to_add = array_filter(
                        $final_table_rows,
                        function ($item) use ($through_table_row, $final_table) {
                            $prop = 'Id_' . $final_table;
                            return $item->{$prop} == $through_table_row->{$prop};
                        }
                    );
                    $through_table_row->$final_table = count($row_to_add) == 1 ? array_pop($row_to_add) : null;
                }
                $sub_rows[$final_table] = $through_table_rows;
                continue;
            }
            $dbs = new DatabaseService($table);
            $table_rows = $dbs->selectAll();
            $sub_rows[$table] = $table_rows;
        }
        foreach ($rows as $row) {
            $this->affectDataToRow($row, $sub_rows);
        }
        return $rows;
    }

    public function getOne($id)
    {
        $dbs = new DatabaseService($this->table);
        $row = $dbs->selectOne($id);
        return $row; // "Select row with id = $id from table tag";
    }

    // Récupérer les données provenant d'autres tables
    function getOneWith($id, $with)
    {
        $row = $this->getOne($id);

        foreach ($with as $table) {
            if (is_array($table)) {
                $final_table = key($table);
                $through_table = $table[$final_table];
                $dbs = new DatabaseService($through_table);
                $through_table_rows = $dbs->selectWhere();
                $dbs = new DatabaseService($final_table);
                $final_table_rows = $dbs->selectAll();
                foreach ($through_table_rows as $through_table_row) {
                    $row_to_add = array_filter(
                        $final_table_rows,
                        function ($item) use ($through_table_row, $final_table) {
                            $prop = 'Id_' . $final_table;
                            return $item->{$prop} == $through_table_row->{$prop};
                        }
                    );
                    $through_table_row->$final_table = count($row_to_add) == 1 ? array_pop($row_to_add) : null;
                }
                $sub_rows[$final_table] = $through_table_rows;
                continue;
            }
            $dbs = new DatabaseService($table);
            $table_rows = $dbs->selectAll();
            $sub_rows[$table] = $table_rows;
        }
        $this->affectDataToRow($row, $sub_rows);
        return $row;
    }

    //TODO Insert ($this->table) somewhere
    public function create()
    {
        return "Insert a new row in table theme with values : " .
            urldecode(http_build_query($this->body, '', ', '));
    }
    public function update($id)
    {
        return "Update row with id = $id in table theme with values : " .
            urldecode(http_build_query($this->body, '', ', '));
        // Permet de récupérer ce qu’il y a dans le tableau 
        // et de le convertir en string au format 
        // key = value séparé par des virgules
    }

    public function softDelete($id)
    {
        return "Delete (soft) row with id = $id in table tag";
    }
    public function hardDelete($id)
    {
        return "Delete (hard) row with id = $id in table tag";
    }
}
