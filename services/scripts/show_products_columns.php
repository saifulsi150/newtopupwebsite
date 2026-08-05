<?php
$env = parse_ini_file(__DIR__ . '/../.env');
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'], $env['DB_PORT'], $env['DB_DATABASE']);
$db = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach ($db->query('SHOW COLUMNS FROM products') as $row) {
    echo $row['Field'] . '|' . $row['Null'] . '|' . ($row['Default'] ?? 'NULL') . PHP_EOL;
}
