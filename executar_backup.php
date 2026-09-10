<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script deve ser executado apenas pelo Agendador do Windows.');
}

require __DIR__ . '/backup_service.php';

try {
    $arquivo = criarBackup();
    echo 'Backup criado: ' . basename($arquivo) . PHP_EOL;
    exit(0);
} catch (Throwable $erro) {
    fwrite(STDERR, 'Erro no backup: ' . $erro->getMessage() . PHP_EOL);
    exit(1);
}
