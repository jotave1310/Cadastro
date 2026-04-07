<?php
declare(strict_types=1);

const APP_DB_HOST = '127.0.0.1';
const APP_DB_PORT = '3306';
const APP_DB_NAME = 'secure_contact_system';
const APP_DB_USER = 'root';
const APP_DB_PASS = '&tec77@info!';
const APP_DB_CHARSET = 'utf8mb4';

// Alterar para uma chave longa e aleatória antes de colocar em produção.
const APP_SECRET_KEY = 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_KEY_32_PLUS_CHARS';

function app_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        APP_DB_HOST,
        APP_DB_PORT,
        APP_DB_NAME,
        APP_DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, APP_DB_USER, APP_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Falha na conexão com o banco de dados: ' . $e->getMessage(), 0, $e);
    }

    return $pdo;
}
