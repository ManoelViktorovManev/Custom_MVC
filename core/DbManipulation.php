<?php

namespace App\Core;

/**
 * Entity persistence manager for database operations.
 *
 * This class is responsible for handling the persistence lifecycle of
 * {@see BaseModel} entities. It queues entities for insertion, update,
 * or deletion and applies all pending operations in a single commit.
 *
 * Features:
 * - Automatic INSERT or UPDATE based on entity ID presence
 * - Batched database operations
 * - Reflection-based entity property mapping
 * - Type-safe parameter binding
 *
 * This class acts as a lightweight Unit of Work implementation.
 *
 * @since 2.0
 */
class DbManipulation
{
    private $queue;
    private $deletequeue;
    private $db;


    /**
     * Initializes the database manipulation manager.
     *
     * Retrieves the shared PDO connection and initializes
     * internal queues for pending database operations.
     *
     * @since 2.0
     */
    public function __construct()
    {
        $this->db = DataBaseComponent::getInstance()->getDB();
        $this->deletequeue = [];
        $this->queue = [];
    }

    /**
     * Schedules an entity for insertion or update.
     *
     * The entity is added to the internal queue and will be persisted
     * when {@see DbManipulation::commit()} is called.
     *
     * If the entity contains an `id` property with a value, an UPDATE
     * operation will be performed; otherwise, an INSERT will occur.
     *
     * @param BaseModel $entity The entity to persist.
     * @return void
     * @since 2.0
     */

    public function add(BaseModel $entity)
    {
        $this->queue[] = $entity;
    }

    /**
     * Executes all queued database operations.
     *
     * Persists all queued entities using INSERT or UPDATE statements
     * and removes all entities queued for deletion. Reflection is used
     * to map entity properties to database columns dynamically.
     *
     * After execution, both the insert/update and delete queues
     * are cleared.
     *
     * @return void
     * @since 2.0
     */
    public function commit()
    {
        try {
            foreach ($this->queue as $entity) {
                $reflect = new \ReflectionClass(get_class($entity));
                $data = [];
                $id = null;
                foreach ($reflect->getProperties() as $prop) {
                    $prop->setAccessible(true); // Ensure private/protected are accessible
                    // Get property name and value
                    $propertyName = $prop->getName();
                    $propertyValue = $prop->getValue($entity);
                    $data[$propertyName] = $propertyValue;
                    if ($propertyName === 'id') {
                        $id = $propertyValue;
                    }
                }

                $table = $entity->getTable();

                if ($id === null) {
                    // INSERT
                    $keys = implode(', ', array_keys($data));
                    $placeholders = ':' . implode(', :', array_keys($data));
                    $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
                } else {
                    // UPDATE
                    $set = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
                    $sql = "UPDATE $table SET $set WHERE id = :id";
                }

                $stmt = $this->db->prepare($sql);

                // we have to bind types!
                foreach ($data as $key => $value) {
                    if (is_bool($value)) {
                        $stmt->bindValue(":$key", (int)$value, \PDO::PARAM_INT);
                    } elseif (is_int($value)) {
                        $stmt->bindValue(":$key", $value, \PDO::PARAM_INT);
                    } elseif (is_null($value)) {
                        $stmt->bindValue(":$key", null, \PDO::PARAM_NULL);
                    } elseif (is_float($value)) {
                        $stmt->bindValue(":$key", $value, \PDO::PARAM_STR); // PDO has no float, use STR
                    } else {
                        $stmt->bindValue(":$key", $value, \PDO::PARAM_STR);
                    }
                }
                $stmt->execute();

                // If insert, update the ID back on the object
                if ($id === null && $reflect->hasProperty('id')) {
                    $lastInsertId = $this->db->lastInsertId();
                    $idProperty = $reflect->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($entity, $lastInsertId);
                }
            }
            foreach ($this->deletequeue as $entity) {

                $reflect = new \ReflectionClass(get_class($entity));
                if (!$reflect->hasProperty('id')) {
                    throw new \Exception("Cannot delete entity without an 'id' property.");
                }

                $idProperty = $reflect->getProperty('id');
                $idProperty->setAccessible(true);
                $id = $idProperty->getValue($entity);
                $table = $entity->getTable();

                $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
                $stmt->execute(['id' => $id]);
            }
            $this->queue = [];
            $this->deletequeue = [];
        } catch (\Exception $e) {
            echo "<h1>" . ($e->getMessage()) . "</h1>";
        }
    }

    /**
     * Schedules an entity for deletion.
     *
     * The entity will be removed from the database when
     * {@see DbManipulation::commit()} is called.
     *
     * @param BaseModel $entity The entity to delete.
     * @return void
     * @since 2.0
     */
    public function delete(BaseModel $entity)
    {
        $this->deletequeue[] = $entity;
    }
};
