<?php

namespace app\core;

abstract class Model
{
    public \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Application::$app->db->pdo;
    }

    public function loadData($data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    abstract public static function tableName();

    public function attributes(): array
    {
        return [];
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function insert(): bool
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();

        $params = array_map(fn ($attr) => ":$attr", $attributes);

        $statement = $this->pdo->prepare("INSERT INTO $tableName (" . implode(", ", $attributes) . ") 
                VALUES (" . implode(", ", $params) . ")");

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        $statement->execute();

        return true;
    }

    public function update($fields, $where): bool
    {
        $tableName = $this->tableName();

        $set = array();
        $wheres = array();

        function isJson($string) {
            json_decode($string);
            return json_last_error() === JSON_ERROR_NONE;
         }

        foreach ($fields as $key => $value) {
            if(is_numeric($value) || isJson($value))
                $set[] = $key . " = " . "$value";
            else
                $set[] = $key . " = " . "'$value'";
        }

        foreach ($where as $key => $value) {
            if(is_numeric($value) || isJson($value))
                $wheres[] = $key . " = " . "$value";
            else
                $wheres[] = $key . " = " . "'$value'";
        }

        $statement = $this->pdo->prepare("UPDATE $tableName " . "SET " . implode(", ", $set) . " WHERE ". implode(", ", $wheres) . ";");

        return $statement->execute();
    }

    public function delete($where): bool
    {
        $tableName = $this->tableName();

        $wheres = array();

        foreach ($where as $key => $value) {
            $wheres[] = $key . " = " . "$value";
        }

        $statement = $this->pdo->prepare("DELETE FROM $tableName " . "WHERE ". implode(", ", $wheres) . ";");

        return $statement->execute();
    }

    public function selectAll()
    {
        $tableName = static::tableName();

        $sql = "SELECT * From $tableName";
        $stm = $this->pdo->query($sql);

        $result = $stm->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function count(): int
    {
        $tableName = $this->tableName();

        $sql = "SELECT COUNT(*) FROM $tableName;";
        $stm = $this->pdo->query($sql);

        return intval($stm->fetchColumn());
    }

    public function selectAllWhere($where)
    {
        $tableName = static::tableName();
        $attributes = array_keys($where);

        $sql = implode(" AND ", array_map(fn ($attr) => "$attr = :$attr", $attributes));
        $statement = $this->pdo->prepare("SELECT * FROM $tableName WHERE $sql");

        foreach ($where as $key => $item)
            $statement->bindValue(":$key", $item);

        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?? false;
    }

    public function lastInsertID()
    {
        return $this->pdo->lastInsertId();
    }
}