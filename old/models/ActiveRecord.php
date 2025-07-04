<?php

namespace Model;

class ActiveRecord{
    //Data Base
    protected static $db;
    protected static $columnsDB = [];
    protected static $table = '';

    //Errors - Validation
    protected static $alerts = [];

    // Eager loading
    protected static $with = [];

    // Query Builder
    protected array $builderWheres = [];
    protected ?string $builderOrderBy = null;
    protected string $builderDirection = 'ASC';
    protected ?int $builderLimit = null;
    protected bool $builderFirst = false;
     
    
     //Define the connection to the DB
    public static function setDB($database){
        self::$db = $database;
    }

     // Set an alert messagea and type
    public static function setAlert($type, $message) {
        static::$alerts[$type][] = $message;
    }

    // Get the alerts
    public static function getAlerts() {
        return static::$alerts;
    }
    
    // Validations inherited on models
    public function validate() {
        static::$alerts = [];
        return static::$alerts;
    }    

    // SQL fetch to create objects in memory
    public static function consultSQL($query) {
        // If the table has soft deletes and the query is not already filtering deleted_at
        if (in_array('deleted_at', static::$columnsDB) && !str_contains(strtolower($query), 'deleted_at')) {
            // Add filter only if no WHERE clause exists
            if (str_contains(strtolower($query), 'where')) {
                $query .= " AND deleted_at IS NULL";
            } else {
                $query .= " WHERE deleted_at IS NULL";
            }
        }
    
        $result = self::$db->query($query);
        $array = [];
    
        while ($registry = $result->fetch_assoc()) {
            $array[] = static::createObject($registry);
        }
    
        $result->free();
        return $array;
    }

    public static function SQLWithTrashed() {
        // bypasses consultSQL filter
        $query = "SELECT * FROM " . static::$table;
        $result = self::$db->query($query);
        $array = [];
    
        while ($registry = $result->fetch_assoc()) {
            $array[] = static::createObject($registry);
        }
    
        $result->free();
        return $array;
    }
    
    

    public static function rawQuery($query) {
        return self::consultSQL($query);
    }

    public static function query() {
        return new static;
    }
    
    // use with() to eager load relationships
    public static function with(...$relations) {
        static::$with = $relations;
        return new static;
    }

    // Creates an object in memory from the DB
    protected static function createObject($registry) {
        $object = new static;
    
        foreach ($registry as $key => $value) {
            if (property_exists($object, $key)) {
                $object->$key = $value;
            }
        }
    
        // Handle eager-loaded relationships
        foreach (static::$with as $relation) {
            if (method_exists($object, $relation)) {
                $object->$relation = $object->$relation();
            }
        }
    
        return $object;
    }
    

    public function attributes() {
        $attributes = [];
        foreach(static::$columnsDB as $column) {
            if($column === 'id') continue;
            $attributes[$column] = $this->$column;
        }
        return $attributes;
    }

    // Sanitize the attributes before saving to DB
    public function sanitizeAttributes() {
        return $this->attributes(); // leave escaping to the `create()` method now
    }
    
  
    // Sincronize the object with the DB
    public function synchronize($args = []) {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                // Trim and normalize input
                $cleaned = trim($value);
    
                // Optionally convert ' to ´ if you like your old sText()
                if (str_contains($cleaned, "'")) {
                    $cleaned = str_replace("'", "´", $cleaned);
                }
    
                $this->$key = $cleaned;
            }
        }
    }
    
    public function where(string $column, $value) {
        $this->builderWheres[] = [
            'column' => self::$db->escape_string($column),
            'value' => self::$db->escape_string($value)
        ];
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'ASC') {
        $this->builderOrderBy = self::$db->escape_string($column);
        $this->builderDirection = strtoupper($direction);
        return $this;
    }
    
    public function limit(int $limit) {
        $this->builderLimit = $limit;
        return $this;
    }
    
    public function first() {
        $this->builderFirst = true;
        $this->builderLimit = 1;
        return $this->get();
    }

    public function get() {
        $query = "SELECT * FROM " . static::$table;
    
        $clauses = [];
    
        foreach ($this->builderWheres as $where) {
            $clauses[] = "{$where['column']} = '{$where['value']}'";
        }
    
        if (!empty($clauses)) {
            $query .= " WHERE " . implode(" AND ", $clauses);
        }
    
        if ($this->builderOrderBy) {
            $query .= " ORDER BY {$this->builderOrderBy} {$this->builderDirection}";
        }
    
        if ($this->builderLimit !== null) {
            $query .= " LIMIT {$this->builderLimit}";
        }
    
        $result = self::consultSQL($query);
    
        return $this->builderFirst ? array_shift($result) : $result;
    }
    

    // Records - CRUD
    public function save() {
        $result = '';
        if(!is_null($this->id)) {
            // actualizar
            $result = $this->update();
        } else {
            // Creando un nuevo registro
            $result = $this->create();
        }
        return $result;
    }

    public static function exists($column, $value, $exceptId = null): bool {
        $value = self::$db->escape_string($value);
        $query = "SELECT COUNT(*) as total FROM " . static::$table .
                 " WHERE {$column} = '{$value}'";
    
        if ($exceptId) {
            $query .= " AND id != " . intval($exceptId);
        }
    
        if (in_array('deleted_at', static::$columnsDB)) {
            $query .= " AND deleted_at IS NULL";
        }
    
        $result = self::$db->query($query);
        $row = $result->fetch_assoc();
    
        return isset($row['total']) && (int)$row['total'] > 0;
    }
    
    
    
    public static function pluck($column, $id) {
        $column = self::$db->escape_string($column);
        $id = intval($id);
    
        $query = "SELECT $column FROM " . static::$table . " WHERE id = $id";
    
        if (in_array('deleted_at', static::$columnsDB)) {
            $query .= " AND deleted_at IS NULL";
        }
    
        $query .= " LIMIT 1";
    
        $result = self::$db->query($query);
        $row = $result->fetch_assoc();
    
        return $row[$column] ?? null;
    }
    
    

    
    public static function pluckAll($column) {
        $column = self::$db->escape_string($column);
    
        $query = "SELECT $column FROM " . static::$table;
    
        if (in_array('deleted_at', static::$columnsDB)) {
            $query .= " WHERE deleted_at IS NULL";
        }
    
        $result = self::$db->query($query);
        $items = [];
    
        while ($row = $result->fetch_assoc()) {
            $items[] = $row[$column];
        }
    
        return $items;
    }
    

    // Get all the records from the table
    public static function all($columns = ['*'], $orderBy = 'id', $direction = 'DESC', $limit = null) {
        $columnsList = implode(', ', $columns);
        $query = "SELECT $columnsList FROM " . static::$table;
    
        if ($orderBy) {
            $query .= " ORDER BY $orderBy " . strtoupper($direction);
        }
    
        if ($limit) {
            $query .= " LIMIT $limit";
        }
    
        return self::consultSQL($query);
    }

    //Get all the records from the table including soft deleted ones
    public static function allWithTrashed() {
        $query = "SELECT * FROM " . static::$table;
        return self::SQLWithTrashed($query);
    }

    public static function onlyTrashed() {
        $query = "SELECT * FROM " . static::$table . " WHERE deleted_at IS NOT NULL";
        return self::SQLWithTrashed($query);
    }
    
    
    // Search by ID
    public static function find($id) {
        $id = intval($id);
        $query = "SELECT * FROM " . static::$table . " WHERE id = $id LIMIT 1";
        $result = self::consultSQL($query);
        return array_shift($result);
    }

    // Find by column and value returning the first match
    public static function findWhere(array $conditions = [], $orderBy = null, $direction = 'ASC') {
        $query = "SELECT * FROM " . static::$table . " WHERE ";
        $clauses = [];
    
        foreach ($conditions as $column => $value) {
            $value = self::$db->escape_string($value);
            $clauses[] = "$column = '$value'";
        }
    
        if (!empty($clauses)) {
            $query .= " WHERE " . implode(" AND ", $clauses);
        }

        $query .= " AND deleted_at IS NULL";
    
        if ($orderBy) {
            $orderBy = self::$db->escape_string($orderBy);
            $query .= " ORDER BY $orderBy " . strtoupper($direction);
        }
    
        $query .= " LIMIT 1";
    
        $result = self::consultSQL($query);
        return array_shift($result);
    }

    // Find by column and value returning all matches
    public static function findAllWhere(array $conditions = [], $orderBy = null, $direction = 'ASC', $limit = null) {
        $query = "SELECT * FROM " . static::$table;
    
        $clauses = [];
    
        // Apply all where conditions
        foreach ($conditions as $column => $value) {
            $value = self::$db->escape_string($value);
            $clauses[] = "$column = '$value'";
        }
    
        // Append WHERE if needed
        if (!empty($clauses)) {
            $query .= " WHERE " . implode(" AND ", $clauses);
        }
    
        if ($orderBy) {
            $orderBy = self::$db->escape_string($orderBy);
            $query .= " ORDER BY $orderBy " . strtoupper($direction);
        }
    
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
    
        return self::consultSQL($query);
    }
    
    
    
    public static function unionTables($table1, $table2){
        $query = "SELECT * FROM " . $table1 . " UNION SELECT * FROM " . $table2;
        $result = self::consultSQL($query);
        return $result;
    }

    public static function joinTables(array $options) {
        $type = strtoupper($options['type'] ?? 'INNER');
        $table1 = self::$db->escape_string($options['table1']);
        $table2 = self::$db->escape_string($options['table2']);
        $col1 = self::$db->escape_string($options['col1']);
        $col2 = self::$db->escape_string($options['col2']);
        $select = $options['select'] ?? "$table1.*";
        $where = $options['where'] ?? null;
        $orderBy = isset($options['orderBy']) ? self::$db->escape_string($options['orderBy']) : null;
        $direction = strtoupper($options['direction'] ?? 'ASC');
        $limit = $options['limit'] ?? null;
    
        $query = "SELECT $select FROM $table1 $type JOIN $table2 ON $table1.$col1 = $table2.$col2";
    
        $clauses = [];
    
        if ($where && is_array($where)) {
            foreach ($where as $key => $value) {
                $value = self::$db->escape_string($value);
                $clauses[] = "$key = '$value'";
            }
        }
    
        if (!empty($clauses)) {
            $query .= " WHERE " . implode(' AND ', $clauses);
        }
    
        if ($orderBy) {
            $query .= " ORDER BY $orderBy $direction";
        }
    
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
    
        return self::consultSQL($query);
    }
    
    // Set the timestamps for created_at, updated_at, deleted_at fields
    protected function setTimestamp($field) {
        if (in_array($field, static::$columnsDB)) {
            $this->$field = date('Y-m-d H:i:s');
        }
    }
    
 
    //Create a new record in the DB
    public function create() {
        $this->setTimestamp('created_at');
        $this->setTimestamp('updated_at');
    
        $attributes = $this->sanitizeAttributes();
    
        $columns = implode(', ', array_keys($attributes));
        $values = implode(', ', array_map(function ($val) {
            if (is_null($val)) return "NULL";
            if (is_numeric($val) && (int)$val == $val) return $val;
            return "'" . self::$db->real_escape_string($val) . "'";
        }, array_values($attributes)));
    
        $query = "INSERT INTO " . static::$table . " ($columns) VALUES ($values)";
    
        $result = self::$db->query($query);
        $this->id = self::$db->insert_id; // ✅ set it here!
    
        return [
            'result' => $result,
            'id' => $this->id
        ];
    }

    // Update a record in the DB
    public function update() {
        $this->setTimestamp('updated_at');
    
        $attributes = $this->sanitizeAttributes();
        $values = [];
    
        foreach ($attributes as $key => $value) {
            $values[] = "{$key}='{$value}'";
        }
    
        $query = "UPDATE " . static::$table . " SET " . join(', ', $values) .
                 " WHERE id = '" . self::$db->escape_string($this->id) . "' LIMIT 1";
    
        return self::$db->query($query);
    }
    
 
     //Delete a record from the DB
    public function delete() {
        $this->setTimestamp('deleted_at');
        $attributes = $this->sanitizeAttributes();
    
        $query = "UPDATE " . static::$table . 
                 " SET deleted_at = '" . $attributes['deleted_at'] . "'" .
                 " WHERE id = '" . self::$db->escape_string($this->id) . "' LIMIT 1";
    
        return self::$db->query($query);
    }
    

    public function restore() {
        $this->deleted_at = null;
        $query = "UPDATE " . static::$table . " SET deleted_at = NULL WHERE id = '" . self::$db->escape_string($this->id) . "' LIMIT 1";
        return self::$db->query($query);
    }

    // Permanently delete a record from the DB
    public function forceDelete() {
        $query = "DELETE FROM " . static::$table . 
                 " WHERE id = '" . self::$db->escape_string($this->id) . "' LIMIT 1";
    
        return self::$db->query($query);
    }
    

     // Upload files or images
     protected function handleFileUpload($field, $filename, $folder) {
        if (!is_null($this->id)) {
            $this->deleteFile($field, $folder);
        }
    
        if ($filename) {
            $this->$field = $filename;
        }
    }

    // Delete files or images
    protected function handleFileDelete($field, $folder) {
        $filepath = $folder . $this->$field;
        if (!empty($this->$field) && file_exists($filepath)) {
            unlink($filepath);
        }
    }

    public function setFile($file, $field, $folder) {
        $this->handleFileUpload($field, $file, $folder);
    }
    
    public function deleteFile($field, $folder) {
        $this->handleFileDelete($field, $folder);
    }

    public function toArray(array $only = [], array $except = []): array {
        $array = [];
    
        foreach (static::$columnsDB as $column) {
            if (!empty($only) && !in_array($column, $only)) continue;
            if (in_array($column, $except)) continue;
    
            $array[$column] = $this->$column ?? null;
        }
    
        return $array;
    }

    public static function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = 'id', $direction = 'DESC') {
        $offset = ($page - 1) * $perPage;
    
        $clauses = [];
        foreach ($conditions as $key => $value) {
            $value = self::$db->escape_string($value);
            $clauses[] = "$key = '$value'";
        }
    
        $where = '';
        if (!empty($clauses)) {
            $where = " WHERE " . implode(" AND ", $clauses);
        }
    
        $query = "SELECT * FROM " . static::$table . $where .
                 " ORDER BY $orderBy " . strtoupper($direction) .
                 " LIMIT $perPage OFFSET $offset";
    
        $items = self::consultSQL($query);
        $total = self::countAll($conditions);
        $lastPage = ceil($total / $perPage);
    
        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ]
        ];
    }

    public static function countAll($conditions = []) {
        $clauses = [];
    
        foreach ($conditions as $key => $value) {
            $value = self::$db->escape_string($value);
            $clauses[] = "$key = '$value'";
        }
    
        $where = '';
        if (!empty($clauses)) {
            $where = " WHERE " . implode(" AND ", $clauses);
        }
    
        $query = "SELECT COUNT(*) as total FROM " . static::$table . $where;
        $result = self::$db->query($query);
        $row = $result->fetch_assoc();
    
        return (int) ($row['total'] ?? 0);
    }
    

    // ORM superpowers
    public function hasMany($relatedClass, $foreignKey, $localKey = 'id') {
        $relatedTable = new $relatedClass;
        return $relatedClass::findAllWhere([
            $foreignKey => $this->$localKey
        ]);
    }

    public function belongsTo($relatedClass, $foreignKey, $ownerKey = 'id') {
        $relatedTable = new $relatedClass;
        return $relatedClass::findWhere([
            $ownerKey => $this->$foreignKey
        ]);
    }

    public function hasOne($relatedClass, $foreignKey, $localKey = 'id') {
        return $relatedClass::findWhere([
            $foreignKey => $this->$localKey
        ]);
    }

    
    public function hasManyThrough(
        $relatedClass,
        $throughClass,
        $firstKey,    // foreign key on through table pointing to this model
        $secondKey,   // foreign key on related table pointing to through table
        $localKey = 'id',     // this model's PK
        $relatedLocalKey = 'id'  // through table PK
    ) {
        $throughInstances = $throughClass::findAllWhere([
            $firstKey => $this->$localKey
        ]);
    
        $relatedItems = [];
    
        foreach ($throughInstances as $through) {
            $items = $relatedClass::findAllWhere([
                $secondKey => $through->$relatedLocalKey
            ]);
            $relatedItems = array_merge($relatedItems, $items);
        }
    
        return $relatedItems;
    }

    public function morphTo($typeField = 'commentable_type', $idField = 'commentable_id') {
        $modelClass = $this->$typeField;
        $modelId = $this->$idField;
    
        if (class_exists($modelClass)) {
            return $modelClass::find($modelId);
        }
    
        return null;
    }    
    
}