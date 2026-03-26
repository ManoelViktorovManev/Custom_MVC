<?php

namespace App\Core;

/**
 * Automatic entity-to-database schema manager.
 *
 * This class is responsible for scanning application model classes
 * and synchronizing their structure with the database schema.
 * It uses reflection to analyze model properties and dynamically
 * creates database tables when they do not exist.
 *
 * Responsibilities:
 * - Discover model classes
 * - Analyze entity properties and types
 * - Automatically generate database tables
 * - Maintain a single shared instance (Singleton pattern)
 *
 * This class is initialized during application bootstrap and should
 * not be instantiated directly.
 *
 * @since 2.0
 */
class EntityManipulation
{

    private static $instance;
    private $db;

    /**
     * Private constructor.
     *
     * Initializes the entity manager and triggers entity scanning
     * and database schema synchronization.
     *
     * @param DataBaseComponent $dbcomp Database component instance.
     * @since 2.0 
     */
    private function __construct(DataBaseComponent $dbcomp)
    {
        $this->db = $dbcomp->getDB();
        $this->scanEntitys();
    }

    /**
     * Provides a singleton instance of EntityManipulation.
     *
     * Ensures that only one instance of EntityManipulation is created and reused throughout the application.
     *
     * @return EntityManipulation The singleton instance of EntityManipulation.
     * @since 2.0 
     */
    public static function getInstance(DataBaseComponent $dbComponent): EntityManipulation
    {
        if (self::$instance === null) {
            self::$instance = new self($dbComponent);
        }
        return self::$instance;
    }

    /**
     * Scans model classes in the application to identify their properties and types.
     *
     * Finds all model files in the `/model` directory, creates a ReflectionClass for each model
     * class, and retrieves property names and types. This data is used to map table columns for 
     * each model. If a table does not exist for the model, `createTable()` is called to generate it.
     *
     * @return array An associative array of entities with property names and types.
     * @since 2.0 
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
     * @since 2.0 
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
                'float' => 'DECIMAL(16,8)',
                default => 'TEXT'
            };
            $columns[] = "{$name} $columnType";
        }
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(', ', $columns) . ")";
        return $this->db->exec($sql);
    }
}
