<?php

namespace Model\Services;

use Exception;
use PDO;
use Model\Utilities\Log;

class DataAccess
{
    public static $pdo;

    private static function Catch($e)
    {
        Log::WriteLog('error.txt', $e->getMessage());
        return false;
    }

    /////////////////////////////////////////////////////////////
    #region - - - [ BASIC FUNCTIONS ]
    public static function Select(string $table, string $column = null)
    {
        try
        {
            if(!$column)
            {
                $column = '*';
            }
            $statement = self::$pdo->prepare("SELECT $column FROM $table");
            $statement->execute();            
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function SelectLast(string $table, string $column)
    {
        try
        {
            $statement = self::$pdo->prepare("SELECT $column
            FROM $table
            ORDER BY $column DESC
            LIMIT 1;");
            $statement->execute();
            return $statement->fetchColumn();
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    /**
     *
        $query =    "SELECT {$queryColumn}
            FROM {$table1}
            JOIN {$table2} ON {$table1}.{$join1} = {$table2}.{$join2}
            WHERE {$table2}.{$whereColumn} = '{$whereValue}'
            ";
     */
    public static function SelectWithJoin($table1, $table2, $join1, $join2, $whereColumn, $whereValue, $column = null)
    {        
        try
        {
            if (                    
                    ($join1 == null || $join2 == null)
                )
            {
                throw new Exception("Invalid input: Columns and values must be arrays, and be of the same length.");
            }         

            if(!$column)
            {
                $queryColumn = "{$table1}.*";
            }
            else
            {
                $queryColumn = '';
                $lastElement = end($column);
                foreach($column as $col)
                {
                    $queryColumn .= "{$col}";
                    if($col != $lastElement)
                    {
                        $queryColumn .= ', ';
                    }
                    else
                    {
                        $queryColumn .= ' ';
                    }
                }                
            }
    
            $query =    "SELECT {$queryColumn}
                        FROM {$table1}
                        JOIN {$table2} ON {$table1}.{$join1} = {$table2}.{$join2}
                        WHERE {$table2}.{$whereColumn} = '{$whereValue}'
                        ";    
            $statement = self::$pdo->prepare($query);
            $statement->execute();            
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
            return $result;
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }

    public static function Insert(string $table, array $columns, array $values)
    {
        try
        {
            $queryColumns = '';
            $lastColumn = end($columns);
            foreach($columns as $col)
            {
                $queryColumns .= "`{$col}`";
                if($col != $lastColumn)
                {
                    $queryColumns .= ', ';
                }
            }
    
            $queryValues = '';
            $lastValue = end($values);
            foreach($values as $value)
            {
                $queryValues .= "'{$value}'";
                if($value != $lastValue)
                {
                    $queryValues .= ', ';
                }
            }    
            $query = "INSERT INTO `{$table}` ({$queryColumns}) VALUES ({$queryValues})";            
            $statement = self::$pdo->prepare($query);            
    
            return $statement->execute();;
        }
        catch(Exception $e)
        {
            self::Catch($e);
        }
    }
    #endregion    
}