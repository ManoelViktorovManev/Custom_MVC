<?php

namespace App\Core;

/**
 * Fluent SQL query builder for model-based database queries.
 *
 * This class provides a lightweight, chainable API for building and executing
 * SQL queries tied to a specific {@see BaseModel}. It supports common query
 * operations such as SELECT, WHERE, JOIN, ORDER BY, LIMIT, and raw SQL execution.
 *
 * Features:
 * - Model-aware table resolution
 * - Prepared statements with safe parameter binding
 * - Fluent method chaining
 * - Optional hydration of results into model instances
 *
 * This class is not intended to be used directly; it is typically accessed
 * via {@see BaseModel::query()}.
 *
 * @since 2.0
 */
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

    /**
     * Initializes the query builder for a specific model.
     *
     * Sets up the base SELECT query and prepares internal state
     * for building SQL clauses.
     *
     * @param BaseModel $model The model instance associated with the query.
     * @since 2.0
     */
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

    /**
     * Prepares and executes a PDO statement with bound parameters.
     *
     * @param string $sql Final SQL query string.
     * @return \PDOStatement Executed PDO statement.
     * @since 2.0
     */
    private function buildAndExecuteSTMT($sql)
    {
        $stmt = $this->db->prepare($sql);
        foreach ($this->bindings as $param => $bind) {
            $stmt->bindValue($param, $bind['value'], $bind['type']);
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * Specifies the columns to select.
     *
     * If no columns are provided, all columns (*) are selected.
     *
     * @param string ...$columns Column names to select.
     * @return QueryBuilder Fluent query builder instance.
     * @since 3.0
     */
    public function select(string ...$columns)
    {
        $columnList = empty($columns) ? '*' : implode(', ', $columns);
        $this->sql = "SELECT {$columnList} FROM {$this->modelClassTable}";
        return $this;
    }

    /**
     * Executes the query and returns all results.
     *
     * Results can be returned either as associative arrays or
     * hydrated model instances.
     *
     * @param bool $wantingInstances Whether to return model instances.
     * @return array Result set.
     * @since 2.0
     */
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

    /**
     * Executes the query and returns the first result as a model instance.
     *
     * Adds a LIMIT 1 clause to the query. Returns null if
     * no result is found.
     *
     * @return BaseModel|null The hydrated model or null.
     * @since 2.0
     */
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

    /**
     * Adds a WHERE condition to the query.
     *
     * Expected input format:
     * ('column', 'operator', 'value')
     *
     * Supported operators include:
     * =, !=, <, >, <=, >=, LIKE, IN, NOT IN, IS, IS NOT
     *
     * @param string $key looking field.
     * @param string $operation for filter search.
     * @param string $value looking value.
     * @return QueryBuilder Fluent query builder instance.
     * @throws \InvalidArgumentException On invalid operator or input.
     * @since 2.0
     */
    public function where(string $key, string $operation, string $value)
    {
        $allowedOps = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT'];
        if (!in_array($operation, $allowedOps)) {
            throw new \InvalidArgumentException("Invalid operation: $operation");
        }
        if ($this->firstTimeWhere) {
            $this->firstTimeWhere = false;
            $this->sql .= " WHERE ";
        } else {
            $this->sql .= " ";
        }

        if (in_array($operation, ['IN', 'NOT IN'])) {
            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for IN/NOT IN must be an array.");
            }
            // Build placeholders
            $placeholders = [];
            foreach ($value as $i => $val) {
                $placeholder = ":value{$this->countWhereStatements}_{$i}";
                $placeholders[] = $placeholder;

                $this->bindings[$placeholder] = [
                    'value' => $val,
                    'type' => is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR
                ];
            }

            $placeholderList = implode(', ', $placeholders);
            $this->sql .= "{$key} {$operation} ({$placeholderList})";
        } else {
            $placeholder = ":value{$this->countWhereStatements}";
            $this->sql .= "{$key} {$operation} {$placeholder}";
            $this->bindings[$placeholder] = [
                'value' => $value,
                'type' => is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR
            ];
        }
        $this->countWhereStatements++;
        return $this;
    }

    /**
     * Adds a JOIN clause to the query.
     *
     * @param string $typeJoin JOIN type (INNER, LEFT, RIGHT).
     * @param string $table Table to join.
     * @param string $sql Join condition.
     * @return QueryBuilder Fluent query builder instance.
     * @since 3.0
     */
    public function join(string $typeJoin, string $table, string $sql)
    {
        $this->sql .= " {$typeJoin} JOIN {$table} ON {$sql} ";
        return $this;
    }

    /**
     * Adds ORDER BY clauses to the query.
     *
     * Each input should be an array of:
     * [column, direction]
     *
     * Direction defaults to ASC.
     *
     * @param array ...$inputs Sorting definitions.
     * @return QueryBuilder Fluent query builder instance.
     * @throws \InvalidArgumentException On invalid sort direction.
     * @since 2.0
     */
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

    /**
     * Adds an AND logical operator to the WHERE clause.
     *
     * @return QueryBuilder Fluent query builder instance.
     * @since 3.0
     */
    public function and()
    {
        $this->sql .= " AND ";
        return $this;
    }

    /**
     * Adds an OR logical operator to the WHERE clause.
     *
     * @return QueryBuilder Fluent query builder instance.
     * @since 3.0
     */
    public function or()
    {
        $this->sql .= " OR ";
        return $this;
    }

    /**
     * Executes a raw SQL query.
     *
     * @param string $sql Raw SQL string.
     * @param array $bindings Optional bound parameters.
     * @return array Query results as associative arrays.
     * @since 3.0
     */
    public function raw(string $sql, array $bindings = []): array
    {
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Executes multiple SQL queries in a single call.
     *
     * @param array $queries List of SQL queries.
     * @return array Results for each query.
     * @since 3.0
     */
    public function multiQuery(array $queries): array
    {
        $sql = implode('; ', $queries) . ';';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $results = [];
        do {
            $results[] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } while ($stmt->nextRowset());

        return $results;
    }

    /**
     * Adds pagination limits to the query.
     *
     * @param int $numberOfElements Number of records per page.
     * @param int $pageNumber Page index (zero-based).
     * @return QueryBuilder Fluent query builder instance.
     * @since 3.0
     */
    public function limit(int $numberOfElements, int $pageNumber): QueryBuilder
    {
        $calculateOffset = $pageNumber * $numberOfElements;
        $this->sql .= " LIMIT {$numberOfElements} OFFSET {$calculateOffset}; ";
        return $this;
    }
};
