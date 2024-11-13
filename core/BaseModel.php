<?php

namespace App\Core;


abstract class BaseModel
{
    private $db;


    /**
     * Retrieves the table name for the model based on the class name.
     *
     * This method uses the model class name, converts it to lowercase, and assumes it corresponds 
     * to a table name in the database. By convention, each model represents one database table.
     *
     * @return string The name of the database table corresponding to the model.
     *
     */
    protected function getTable(): string
    {
        // Use the plural form of the class name as the table name
        $className = basename(str_replace('\\', '/', get_class($this)));
        return strtolower($className); // e.g., 'User' => 'user', 'Post' => 'post'
    }

    /**
     * Initializes the database connection for the model.
     *
     * This method retrieves a PDO instance from a DatabaseManager singleton. It assigns this
     * PDO instance to `$this->db`, making it available for use in other database operations.
     *
     * @return void
     *
     */
    protected function getDb()
    {
        $databaseManager = DatabaseManager::getInstance();
        $this->db = $databaseManager->getDb();
    }

    /**
     * Fetches all records from the model's associated database table.
     *
     * Executes a `SELECT *` query on the table returned by `getTable()` and returns an array
     * of all records.
     *
     * @return array An array of associative arrays representing all records in the table.
     *
     */
    public function findAll()
    {
        $table = $this->getTable();
        $this->getDb();

        $stmt = $this->db->prepare("SELECT * FROM $table");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a single record by its ID from the model's associated database table.
     *
     * Executes a `SELECT * WHERE id = :id` query on the table returned by `getTable()` and 
     * retrieves a record based on the provided ID.
     *
     * @param int $id The ID of the record to fetch.
     * @return array|null An associative array representing the record, or null if not found.
     *
     */
    public function findById($id)
    {
        $table = $this->getTable();
        $this->getDb();

        $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new record into the model's associated database table.
     *
     * Reflects on the properties of the model instance and constructs an `INSERT INTO` SQL
     * statement with property names as columns. If the model has an `id` property, it updates 
     * the property with the last inserted ID.
     *
     * @return bool True if the insertion is successful, otherwise false.
     *
     */
    public function insert()
    {
        $reflect = new \ReflectionClass(get_class($this));
        $data = [];
        foreach ($reflect->getProperties() as $prop) {
            // Get property name and value
            $propertyName = $prop->getName();
            $propertyValue = $prop->getValue($this);
            $data[$propertyName] = $propertyValue;
        }


        $this->getDb();
        $table = $this->getTable();


        $keys = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        // get last inserted value
        $lastInsertId = $this->db->lastInsertId();

        if ($reflect->hasProperty('id')) {
            $idProperty = $reflect->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($this, $lastInsertId);
        }
        return $lastInsertId !== false;
    }

    /**
     * Updates an existing record in the model's associated database table.
     *
     * Reflects on the properties of the model instance and constructs an `UPDATE` SQL statement 
     * with property names as columns, excluding the `id` property. Sets fields according to 
     * property values and updates the record with the specified ID.
     *
     * @param int $id The ID of the record to update.
     * @return int The number of rows affected by the update.
     *
     */
    public function update($id)
    {
        $table = $this->getTable();
        $this->getDb();

        $reflect = new \ReflectionClass(get_class($this));
        $data = [];
        foreach ($reflect->getProperties() as $prop) {
            // Get property name and value
            $propertyName = $prop->getName();
            if ($propertyName === 'id') {
                continue;
            }
            $propertyValue = $prop->getValue($this);
            $data[$propertyName] = $propertyValue;
        }
        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= "$key = :$key, ";
        }
        $fields = rtrim($fields, ', ');
        $sql = "UPDATE $table SET $fields WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $stmt->rowCount(); // Returns number of affected rows
    }

    /**
     * Deletes a record by its ID from the model's associated database table.
     *
     * Executes a `DELETE FROM` SQL statement where the record's ID matches the provided value.
     *
     * @param int $id The ID of the record to delete.
     * @return bool True if the record was deleted, otherwise false.
     *
     */
    public function delete($id)
    {
        $table = $this->getTable();
        $this->getDb();

        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);

        // Check the number of affected rows to determine if delete was successful
        return $stmt->rowCount() > 0;
    }

    /**
     * Executes a custom SQL query on the database.
     *
     * This method allows the execution of a raw SQL query, typically used for custom, 
     * non-standard database operations. Returns a single associative array of the query result.
     *
     * @param string $sql The raw SQL query to execute.
     * @return array|null An associative array of the first result row or null if no result.
     *
     */
    public function customSQL($sql)
    {
        $this->getDb();
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // private function getPropertiesAndValues(\ReflectionClass $reflect)
    // {
    //     $data = [];
    //     foreach ($reflect->getProperties() as $prop) {
    //         // Get property name and value
    //         $propertyName = $prop->getName();
    //         $propertyValue = $prop->getValue($this);
    //         $data[$propertyName] = $propertyValue;
    //     }
    //     return $data;
    // }
};
