<?php
declare(strict_types=1);

return [
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=student_fees_management;charset=utf8mb4',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_password' => getenv('DB_PASSWORD') ?: '',
];
