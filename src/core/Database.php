<?php

class Database
{
    private static ?PDO $instance = null;
    private static ?PDOStatement $stmt = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                Response::json([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ], 500);
            }
        }
        return self::$instance;
    }

    // Prepare statement with query
    public static function query(string $sql): void
    {
        self::$stmt = self::getInstance()->prepare($sql);
    }

    // Bind parameters to the statement
    public static function bind(string $param, mixed $value, int $type = PDO::PARAM_STR): void
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        self::$stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public static function execute(): bool
    {
        return self::$stmt->execute();
    }

    // Get result set as array of objects
    public static function resultAll(): array
    {
        self::execute();
        return self::$stmt->fetchAll();
    }

    // Get single record as object
    public static function resultOne(): mixed
    {
        self::execute();
        return self::$stmt->fetch();
    }
}