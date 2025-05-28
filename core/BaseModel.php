<?php

namespace App\Core;

use App\Core\QueryBuilder;

abstract class BaseModel
{

    /**
     * Retrieves the table name for the model based on the class name.
     *
     * This method uses the model class name, converts it to lowercase, and assumes it corresponds 
     * to a table name in the database. By convention, each model represents one database table.
     *
     * @return string The name of the database table corresponding to the model.
     *
     */
    public function getTable(): string
    {
        // Use the plural form of the class name as the table name
        $className = basename(str_replace('\\', '/', get_class($this)));
        return strtolower($className); // e.g., 'User' => 'user', 'Post' => 'post'
    }

    public function query()
    {
        return new QueryBuilder($this, $this->getTable());
    }
};
