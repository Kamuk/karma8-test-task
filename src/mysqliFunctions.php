<?php
//Общие методы для работы c mysqli

function escapeMysqlIdentifier($field): string
{
    return "`".str_replace("`", "``", $field)."`";
}

// Вставка в таблицу массива данных
function preparedInsert($connection, $table, $data, $types = "") {

    $table = escapeMysqlIdentifier($table);
    $keys = array_keys($data[0]);
    $keys = array_map('escapeMysqlIdentifier', $keys);
    $fields = implode(" = ?,", $keys);
    $sql = "INSERT IGNORE INTO $table SET $fields = ?";
    $stmt = $connection->prepare($sql);

    $connection->begin_transaction();
    foreach ($data as $row) {
        $stmt->bind_param($types, ...array_values($row));
        $stmt->execute();
    }
    $connection->commit();
}

function preparedQuery($mysqli, $sql, $params, $types = "")
{
    $types = $types ?: str_repeat("s", count($params));
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt;
}
