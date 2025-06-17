<?php

namespace App\Core;


class QueryBuilder
{

    private $modelClassTable;
    private $db;
    private $sql;
    private $bindings;
    private BaseModel $model;
    private bool $firstTimeWhere;
    private bool $firstTimeOrder;
    private int $countWhereStatements;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
        $this->modelClassTable = $this->model->getTable();
        $this->db = DataBaseComponent::getInstance()->getDB();
        $this->sql = "SELECT * FROM {$this->modelClassTable}";
        $this->firstTimeWhere = true;
        $this->firstTimeOrder = true;
        $this->countWhereStatements = 0;
        $this->bindings = [];
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
    public function all($wantingInstances = false): array
    {
        $stmt = $this->buildAndExecuteSTMT($this->sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($wantingInstances) {
            $instances = [];
            foreach ($results as $result) {
                $reflect = new \ReflectionClass($this->model);
                $modelInstance = $reflect->newInstance(); // create a new model instance

                foreach ($result as $property => $value) {
                    if ($reflect->hasProperty($property)) {
                        $prop = $reflect->getProperty($property);
                        $prop->setAccessible(true);
                        $prop->setValue($modelInstance, $value);
                    }
                }

                $instances[] = $modelInstance;
            }

            return $instances;
        } else {
            return $results;
        }
    }

    public function first(): ?BaseModel
    {
        $this->sql .= " LIMIT 1";

        $stmt = $this->buildAndExecuteSTMT($this->sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
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
        if ($this->firstTimeWhere) {
            $this->firstTimeWhere = false;
            $this->sql .= " WHERE {$key} {$operation} :value{$this->countWhereStatements}";
        } else {
            $this->sql .= " {$key} {$operation} :value{$this->countWhereStatements}";
        }

        $this->bindings[":value{$this->countWhereStatements}"] = [
            'value' => $value,
            'type' => is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR
        ];
        $this->countWhereStatements++;
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

        if ($this->firstTimeOrder) {
            $this->firstTimeOrder = false;
            $this->sql .= " ORDER BY " . implode(", ", $orders);
        } else {
            $this->sql .= " " . implode(", ", $orders);
        }

        return $this;
    }
    public function and()
    {
        $this->sql .= " AND ";
        return $this;
    }
    public function or()
    {
        $this->sql .= " OR ";
        return $this;
    }
};
