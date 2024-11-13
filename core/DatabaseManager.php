<?php

namespace App\Core;

class DatabaseManager
{
    private $db;
    private $entities = [];
    private static $instance;

    /**
     * DatabaseManager constructor.
     *
     * Instantiates a new DatabaseManager object, initializes the database connection by calling 
     * `setDB()`, and populates the `$entities` property with metadata by scanning for available
     * entities in the project with `scanEntitys()`.
     */
    public function __construct()
    {
        $this->setDB();
        $this->entities = $this->scanEntitys();
    }

    /**
     * Retrieves the current database connection.
     *
     * @return \PDO The PDO instance representing the database connection.
     *
     */
    public function getDB()
    {
        return $this->db;
    }

    /**
     * Provides a singleton instance of DatabaseManager.
     *
     * Ensures that only one instance of DatabaseManager is created and reused throughout the application.
     *
     * @return DatabaseManager The singleton instance of DatabaseManager.
     *
     */
    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the PDO database connection using configuration from an environment file.
     *
     * Parses the `.env` file for database connection details, attempts to connect, creates
     * the database if it does not exist, and sets the database context to the specified database.
     * On connection errors, it outputs an error message.
     *
     * @return void
     *
     */
    private function setDB()
    {
        $envFile = parse_ini_file('.env');
        $dbInfo = $envFile['DATABASE_URL'];
        $parts = parse_url($dbInfo);
        $schema = $parts['scheme'];
        $host = $parts['host'];
        $port = $parts['port'];
        $user = $parts['user'];
        $pass = $parts['pass'];
        $dbName = ltrim($parts['path'], "/");
        try {
            $dsn = "{$schema}:host={$host};port={$port}";

            $pdo = new \PDO($dsn, $user, $pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            $pdo->exec("USE `{$dbName}`");

            $this->db = $pdo;
        } catch (\Exception $e) {
            echo "<h1>" . ($e->getMessage()) . "</h1>";
        }
    }

    /**
     * Scans model classes in the application to identify their properties and types.
     *
     * Finds all model files in the `/model` directory, creates a ReflectionClass for each model
     * class, and retrieves property names and types. This data is used to map table columns for 
     * each model. If a table does not exist for the model, `createTable()` is called to generate it.
     *
     * @return array An associative array of entities with property names and types.
     *
     */
    private function scanEntitys()
    {
        $entites = [];
        $models = glob('model/*.php'); // Scan controller files and get every file.

        foreach ($models as $modelFile) {

            $modelClass = 'App\\Model\\' . basename($modelFile, '.php');

            $reflectionClass = new \ReflectionClass($modelClass);

            foreach ($reflectionClass->getProperties() as $properties) {
                $type = $properties->getType()->getName();
                $entites[$modelClass][$properties->name] = $type;
            }
            $tableName = $reflectionClass->getShortName();
            $this->createTable($tableName, $entites[$modelClass]);
        }
        return $entites;
    }

    /**
     * Creates a new table in the database based on entity properties if the table does not already exist.
     *
     * Takes an entity's property names and types, constructs a SQL `CREATE TABLE` statement,
     * and sets column types based on property types. Executes the SQL statement to create the table.
     * - `int` properties are mapped to `INT`.
     * - `string` properties are mapped to `VARCHAR(255)`.
     * - `bool` properties are mapped to `BOOLEAN`.
     * - Other property types default to `TEXT`.
     *
     * @param string $tableName The name of the table to create.
     * @param array $entity An associative array of column names and their data types.
     * @return int|false The number of affected rows or false on failure.
     *
     */
    private function createTable($tableName, $entity)
    {
        // should add autoincrement
        $columns = [];
        foreach ($entity as $name => $type) {
            if ($name === 'id') {
                $columns[] = "$name INT AUTO_INCREMENT PRIMARY KEY";
                continue;
            }
            $columnType = match ($type) {
                'int' => 'INT',
                'string' => 'VARCHAR(255)',
                'bool' => 'BOOLEAN',
                default => 'TEXT'
            };
            $columns[] = "{$name} $columnType";
        }
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(', ', $columns) . ")";
        return $this->db->exec($sql);
    }
};
