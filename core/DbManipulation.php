<?php

namespace App\Core;

class DbManipulation
{
    private $queue;
    private $deletequeue;
    private $db;


    public function __construct()
    {
        $this->db = DataBaseComponent::getInstance()->getDB();
        $this->deletequeue = [];
        $this->queue = [];
    }

    public function add(BaseModel $entity)
    {
        // $table = $entity->getTable();
        $this->queue[] = $entity;
    }
    public function commit()
    {
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
            $stmt->execute($data);

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
    }

    public function delete(BaseModel $entity)
    {
        $this->deletequeue[] = $entity;
    }
};
