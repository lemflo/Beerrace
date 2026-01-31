<?php
$db = new SQLite3(__DIR__ . '/../data/database.sqlite');
$db->exec("PRAGMA foreign_keys = ON;");
