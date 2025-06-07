<?php

namespace App\Core;


class QueryBuilder
{

    private $modelClassTable;
    private $db;
    private $sql;
    private $bindings;
    private BaseModel $model;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
        $this->modelClassTable = $this->model->getTable();
        $this->db = DataBaseComponent::getInstance()->getDB();
    }

    private function buildAndExecuteSTMT($sql)
    {
        $stmt = $this->db->prepare($sql);
        foreach ($this->bindings as $param => $bind) {
            $stmt->bindValue($param, $bind['value'], $bind['type']);
        }
        $stmt->execute();
        return $stmt;
    }
    public function all(): array
    {
        $sql = $this->sql ?: "SELECT * FROM {$this->modelClassTable}";
        $stmt = $this->buildAndExecuteSTMT($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function first(): ?BaseModel
    {
        $sql = $this->sql ?: "SELECT * FROM {$this->modelClassTable} LIMIT 1";
        $stmt = $this->buildAndExecuteSTMT($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            return null; // Nothing found
        }

        $reflect = new \ReflectionClass($this->model);
        foreach ($result as $property => $value) {
            if ($reflect->hasProperty($property)) {
                $prop = $reflect->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($this->model, $value);
            }
        }

        return $this->model;
    }

    public function where(array $input)
    {
        [$key, $operation, $value] = $input;

        $allowedOps = ['=', '!=', '<', '>', '<=', '>=', 'LIKE'];
        if (!in_array($operation, $allowedOps)) {
            throw new \InvalidArgumentException("Invalid operation: $operation");
        }
        $this->sql = "SELECT * FROM {$this->modelClassTable} WHERE {$key} {$operation} :value";
        $this->bindings = [
            ':value' => [
                'value' => $value,
                'type' => is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR
            ]
        ];
        return $this;
    }

    public function order(array ...$inputs)
    {
        $default_value = "ASC";
        $orders = [];
        foreach ($inputs as $col) {
            if (count($col) > 2 || count($col) < 1) {
                throw new \InvalidArgumentException("Invalid inputs arguments: Expected min 1 or max 2 arguments, actual " . count($col));
            }
            $key = $col[0];
            $value = isset($col[1]) ? $col[1] : $default_value;

            if (!in_array($value, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException("Invalid sort direction: $value");
            }
            $orders[] = "$key $value";
        }

        if (empty($this->sql)) {
            $this->sql = "SELECT * FROM {$this->modelClassTable}";
        }

        $this->sql .= " ORDER BY " . implode(", ", $orders);
        return $this;
    }
};
