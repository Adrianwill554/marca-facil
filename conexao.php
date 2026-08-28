<?php

$host = "localhost";
$banco = "marcafacil";
$usuario = "root";
$senha = "Comvc@255";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}