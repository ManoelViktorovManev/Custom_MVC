<?php

namespace App\Core;

/**
 * Base model class for database-backed entities.
 *
 * This abstract class serves as the foundation for all models in the application.
 * It provides shared functionality related to database interaction and table
 * resolution, allowing concrete models to represent database tables by convention.
 *
 * By default, the database table name is inferred from the model's class name.
 * Each model is expected to correspond to a single database table, following
 * a simple naming convention.
 *
 * This class also provides access to a query builder, enabling fluent and
 * expressive database queries directly from model instances.
 *
 * This class is intended to be extended and should not be instantiated directly.
 *
 * @since 1.0
 */
abstract class BaseModel
{

    /**
     * Retrieves the table name for the model based on the class name.
     *
     * This method uses the model class name, converts it to lowercase, and assumes it corresponds 
     * to a table name in the database. By convention, each model represents one database table.
     *
     * @return string The name of the database table corresponding to the model.
     * @since 1.0
     */
    public function getTable(): string
    {
        // Use the plural form of the class name as the table name
        $className = basename(str_replace('\\', '/', get_class($this)));
        return strtolower($className); // e.g., 'User' => 'user', 'Post' => 'post'
    }

    /**
     * Creates and returns a new query builder instance for the current model.
     *
     * This method initializes a {@see QueryBuilder} configured for the model’s
     * associated database table. It allows building and executing database
     * queries in a fluent and expressive manner.
     *
     *
     * @return QueryBuilder A query builder instance bound to the model's table.
     * @since 2.0
     */
    public function query()
    {
        return new QueryBuilder($this, $this->getTable());
    }
};
