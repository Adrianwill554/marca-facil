<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Este script deve ser executado apenas pelo agendador do servidor.'); }
require __DIR__ . '/rpi_service.php';
try { $edicao = rpiSincronizarEImportarMaisRecente(); echo 'RPI ' . $edicao['numero'] . ' pronta: ' . $edicao['arquivo'] . ' (' . $edicao['publicacoes_importadas'] . ' publicações novas).' . PHP_EOL; exit(0); }
catch (Throwable $erro) { fwrite(STDERR, 'Erro ao sincronizar RPI: ' . $erro->getMessage() . PHP_EOL); exit(1); }
