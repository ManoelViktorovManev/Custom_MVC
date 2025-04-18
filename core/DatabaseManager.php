<?php

namespace App\Core;

use App\Core\DataBaseComponent;
use App\Core\EntityManipulation;

class DatabaseManager
{
    private DataBaseComponent $DBComponent;
    private EntityManipulation $entity;
    private static $instance;

    /**
     * DatabaseManager constructor.
     *
     * Instantiates a new DatabaseManager object, initializes the database connection by calling 
     * `setDB()`, and populates the `$entities` property with metadata by scanning for available
     * entities in the project with `scanEntitys()`.
     */
    private function __construct()
    {
        $this->DBComponent = DataBaseComponent::getInstance();
        $this->entity = EntityManipulation::getInstance($this->DBComponent);
    }

    /**
     * Retrieves the current database connection.
     *
     * @return \PDO The PDO instance representing the database connection.
     *
     */
    public function getDB()
    {
        return $this->DBComponent->getDb();
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
};
