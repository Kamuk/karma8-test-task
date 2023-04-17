<?php
//методы для работы с таблицей tasks

require_once("mysqliFunctions.php");

const TASK_STATUS_NEW = 0;
const TASK_STATUS_IN_PROGRESS = 1;
const TASK_STATUS_ERROR = 2;
const TASK_STATUS_SUCCESS = 3;
const TASK_MAX_RETRY = 5;
const TASK_TYPE_CHECK = 'check';
const TASK_TYPE_SEND= 'send';

// Вставка строк в таблицу tasks
function createTasks($connection, $users, $typeTask) {
    $tasks = [];
    foreach ($users as $user) {
        $tasks[] = [
            'type' => $typeTask,
            'email' => $user['email'],
            'retry' => 0,
            'status' => 0
        ];
    }

    preparedInsert($connection, 'tasks', $tasks, "ssii");
}

function updateTask($connection, $task) {
    preparedUpdate($connection, 'tasks', $task);
}

function preparedUpdate($connection, $table, $task) {
    $table = escapeMysqlIdentifier($table);
    $sql = "UPDATE $table SET ";

    foreach (array_keys($task) as $i => $field) {
        $field = escapeMysqlIdentifier($field);
        $sql .= ($i) ? ", " : "";
        $sql .= "$field = ?";
    }
    $sql .= " WHERE email = '".  $task['email'] . "' AND type = '". $task['type']."';";

    preparedQuery($connection, $sql, array_values($task));
}

function markedTaskAsInProgress($connection, $tasks, $type)
{
    $emails = "";
    foreach ($tasks as $task) {
        $emails .= ($emails=="" ? "" : ",") . "'".mysqli_real_escape_string($connection, $task['email'])."'";
    }

    if (!$emails) {
        return;
    }

    $sql = "UPDATE tasks SET status = ".TASK_STATUS_IN_PROGRESS." WHERE type = '".$type."' AND email IN (".$emails.");";
    $connection->query($sql);
}
