<?php
declare(strict_types=1);

function pastaBackups(): string {
    return __DIR__ . '/backups';
}

function arquivosDoBackup(): array {
    $arquivos = [];

    foreach (glob(__DIR__ . '/*.json') ?: [] as $arquivo) {
        if (basename($arquivo) !== 'composer.json' && is_file($arquivo)) {
            $arquivos[$arquivo] = 'dados/' . basename($arquivo);
        }
    }

    foreach (['uploads_documentos' => 'documentos', 'uploads_marcas' => 'imagens_marcas'] as $pastaOrigem => $destinoBackup) {
        $pastaDocumentos = __DIR__ . '/' . $pastaOrigem;
        if (!is_dir($pastaDocumentos)) continue;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pastaDocumentos, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $arquivo) {
            if ($arquivo->isFile()) {
                $caminho = $arquivo->getPathname();
                $relativo = str_replace('\\', '/', substr($caminho, strlen($pastaDocumentos) + 1));
                $arquivos[$caminho] = $destinoBackup . '/' . $relativo;
            }
        }
    }

    return $arquivos;
}

function listarBackups(): array {
    $arquivos = glob(pastaBackups() . '/marcafacil-*.tar.gz') ?: [];
    usort($arquivos, fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
    return $arquivos;
}

function limitarBackups(int $limite = 10): void {
    foreach (array_slice(listarBackups(), $limite) as $arquivo) {
        if (is_file($arquivo)) unlink($arquivo);
    }
}

function criarBackup(): string {
    if (!class_exists(PharData::class)) {
        throw new RuntimeException('O suporte Phar do PHP não está disponível.');
    }

    $pasta = pastaBackups();
    if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) {
        throw new RuntimeException('Não foi possível criar a pasta de backups.');
    }

    $base = 'marcafacil-' . date('Y-m-d_H-i-s');
    $tar = $pasta . '/' . $base . '.tar';
    $compactado = $tar . '.gz';

    try {
        $phar = new PharData($tar);
        foreach (arquivosDoBackup() as $origem => $destino) {
            $phar->addFile($origem, $destino);
        }
        $phar->compress(Phar::GZ);
        unset($phar);
    } catch (Throwable $erro) {
        if (is_file($tar)) unlink($tar);
        if (is_file($compactado)) unlink($compactado);
        throw new RuntimeException('Não foi possível criar o backup: ' . $erro->getMessage(), 0, $erro);
    }

    if (is_file($tar)) unlink($tar);
    limitarBackups(10);

    if (!is_file($compactado)) {
        throw new RuntimeException('O arquivo compactado não foi criado.');
    }

    return $compactado;
}

function tamanhoLegivelBackup(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return $bytes . ' B';
}
