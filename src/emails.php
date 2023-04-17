<?php
//методы для работы с таблицей emails

require_once("mysqliFunctions.php");

// Вставка строк в таблицу emails
function createEmails($connection, $emails) {
    preparedInsert($connection, 'emails', $emails, "sii");
}
