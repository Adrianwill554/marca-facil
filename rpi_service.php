<?php
declare(strict_types=1);

const RPI_LISTAGEM_URL = 'https://revistas.inpi.gov.br/rpi/';

function rpiDiretorio(): string { return __DIR__ . DIRECTORY_SEPARATOR . 'rpis'; }
function rpiArquivoIndice(): string { return __DIR__ . DIRECTORY_SEPARATOR . 'rpis.json'; }
function rpiCarregarIndice(): array {
    $dados = is_file(rpiArquivoIndice()) ? json_decode((string) file_get_contents(rpiArquivoIndice()), true) : [];
    return is_array($dados) ? $dados : [];
}
function rpiSalvarIndice(array $edicoes): void {
    file_put_contents(rpiArquivoIndice(), json_encode($edicoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function rpiRequisitar(string $url, bool $binario = false): string {
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Não foi possível iniciar a conexão com o INPI.');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => $binario ? 600 : 45, CURLOPT_USERAGENT => 'MarcaFacil-RPI/1.0', CURLOPT_HTTPHEADER => ['Accept: text/html,application/pdf;q=0.9,*/*;q=0.8']]);
    $conteudo = curl_exec($curl); $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $erro = curl_error($curl); curl_close($curl);
    if ($conteudo === false || $codigo < 200 || $codigo >= 300) throw new RuntimeException('O INPI não respondeu à solicitação' . ($erro !== '' ? ': ' . $erro : ' (HTTP ' . $codigo . ').'));
    return $conteudo;
}
function rpiBuscarEdicoesDisponiveis(): array {
    $dom = new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML(rpiRequisitar(RPI_LISTAGEM_URL)); libxml_clear_errors();
    $xpath = new DOMXPath($dom); $links = $xpath->query('//a[contains(@href, "/pdf/Marcas")]'); $edicoes = [];
    foreach ($links as $link) {
        $href = html_entity_decode((string) $link->getAttribute('href'));
        if (!preg_match('~(?:(?:https?:)?//[^/]+)?/pdf/Marcas(\d+)\.pdf$~i', $href, $partes)) continue;
        $linha = $link; while ($linha->parentNode !== null && strtolower($linha->nodeName) !== 'tr') $linha = $linha->parentNode;
        preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', trim((string) $linha->textContent), $data);
        $arquivoDados = '';
        foreach (['.//a[contains(@href, "/txt/RM")]', './/a[contains(@href, "/xml/RM")]'] as $consulta) {
            $linkDados = $xpath->query($consulta, $linha)->item(0);
            if ($linkDados !== null) { $arquivoDados = html_entity_decode((string) $linkDados->getAttribute('href')); break; }
        }
        $base = 'https://revistas.inpi.gov.br';
        $edicoes[(int) $partes[1]] = ['numero' => (int) $partes[1], 'data' => $data[1] ?? '', 'url_pdf_marcas' => str_starts_with($href, 'http') ? $href : $base . (str_starts_with($href, '/') ? '' : '/') . $href, 'url_dados_marcas' => $arquivoDados === '' ? '' : (str_starts_with($arquivoDados, 'http') ? $arquivoDados : $base . (str_starts_with($arquivoDados, '/') ? '' : '/') . $arquivoDados)];
    }
    krsort($edicoes, SORT_NUMERIC); return array_values($edicoes);
}
function rpiBaixarEdicao(array $edicao): array {
    $numero = (int) ($edicao['numero'] ?? 0);
    if ($numero <= 0 || empty($edicao['url_pdf_marcas'])) throw new InvalidArgumentException('Edição da RPI inválida.');
    $indice = rpiCarregarIndice();
    foreach ($indice as $salva) if ((int) ($salva['numero'] ?? 0) === $numero && !empty($salva['arquivo']) && is_file(rpiDiretorio() . DIRECTORY_SEPARATOR . $salva['arquivo'])) return $salva;
    $pdf = rpiRequisitar((string) $edicao['url_pdf_marcas'], true);
    if (!str_starts_with($pdf, '%PDF')) throw new RuntimeException('O arquivo retornado pelo INPI não é um PDF válido.');
    if (!is_dir(rpiDiretorio()) && !mkdir(rpiDiretorio(), 0755, true) && !is_dir(rpiDiretorio())) throw new RuntimeException('Não foi possível criar o diretório das revistas.');
    $arquivo = 'rpi-' . $numero . '-marcas.pdf'; $caminho = rpiDiretorio() . DIRECTORY_SEPARATOR . $arquivo; file_put_contents($caminho, $pdf, LOCK_EX);
    $registro = ['numero' => $numero, 'data' => (string) ($edicao['data'] ?? ''), 'arquivo' => $arquivo, 'url_origem' => (string) $edicao['url_pdf_marcas'], 'baixada_em' => date('c'), 'tamanho' => filesize($caminho)];
    array_unshift($indice, $registro); usort($indice, fn(array $a, array $b): int => ((int) $b['numero']) <=> ((int) $a['numero'])); rpiSalvarIndice($indice); return $registro;
}
function rpiSincronizarMaisRecente(): array {
    $edicoes = rpiBuscarEdicoesDisponiveis(); if ($edicoes === []) throw new RuntimeException('Nenhuma edição de Marcas foi encontrada no portal do INPI.'); return rpiBaixarEdicao($edicoes[0]);
}

function rpiImportarPublicacoes(array $edicao): int {
    $numero = (int) ($edicao['numero'] ?? 0); if ($numero <= 0) throw new InvalidArgumentException('Edição da RPI inválida para importação.');
    $url = (string) ($edicao['url_dados_marcas'] ?? ''); if ($url === '') $url = 'https://revistas.inpi.gov.br/txt/RM' . $numero . '.zip';
    $temporario = tempnam(sys_get_temp_dir(), 'rpi-'); if ($temporario === false) throw new RuntimeException('Não foi possível preparar o arquivo temporário da RPI.');
    try {
        file_put_contents($temporario, rpiRequisitar($url, true), LOCK_EX); $zip = new ZipArchive();
        if ($zip->open($temporario) !== true) throw new RuntimeException('O arquivo estruturado da RPI não pôde ser aberto.');
        $xml = ''; for ($i = 0; $i < $zip->numFiles; $i++) { $nome = $zip->getNameIndex($i); if (is_string($nome) && str_ends_with(strtolower($nome), '.xml')) { $xml = (string) $zip->getFromIndex($i); break; } } $zip->close();
        if ($xml === '') throw new RuntimeException('A RPI não contém um XML de Marcas reconhecido.');
        libxml_use_internal_errors(true); $revista = simplexml_load_string($xml); libxml_clear_errors(); if ($revista === false) throw new RuntimeException('O XML da RPI é inválido.');
        $data = (string) ($revista['data'] ?? ($edicao['data'] ?? '')); $arquivoDados = __DIR__ . '/dados_rpi.json'; $existentes = is_file($arquivoDados) ? json_decode((string) file_get_contents($arquivoDados), true) : []; if (!is_array($existentes)) $existentes = [];
        $chaves = []; foreach ($existentes as $indice => $item) $chaves[(string) ($item['processo'] ?? '') . '|' . (string) ($item['rpi'] ?? '') . '|' . (string) ($item['despacho'] ?? '')] = $indice;
        $novas = 0;
        foreach ($revista->processo as $processo) {
            $titulares = []; foreach ($processo->titulares->titular ?? [] as $titular) $titulares[] = trim((string) $titular['nome-razao-social']); $marca = trim((string) ($processo->marca->nome ?? '')); $procurador = trim((string) ($processo->procurador ?? ''));
            foreach ($processo->despachos->despacho ?? [] as $despacho) {
                $registro = ['processo' => trim((string) $processo['numero']), 'marca' => $marca, 'titular' => implode('; ', array_filter($titulares)), 'procurador' => $procurador, 'rpi' => (string) $numero, 'data' => $data, 'despacho' => trim((string) $despacho['nome']), 'pagina' => '', 'codigo_despacho' => trim((string) $despacho['codigo']), 'importado_em' => date('Y-m-d H:i:s')];
                $chave = $registro['processo'] . '|' . $registro['rpi'] . '|' . $registro['despacho'];
                if ($registro['processo'] !== '' && !array_key_exists($chave, $chaves)) { $existentes[] = $registro; $chaves[$chave] = array_key_last($existentes); $novas++; }
                elseif ($registro['procurador'] !== '') { $existentes[$chaves[$chave]]['procurador'] = $registro['procurador']; }
            }
        }
        file_put_contents($arquivoDados, json_encode($existentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX); return $novas;
    } finally { if (is_file($temporario)) unlink($temporario); }
}
function rpiSincronizarEImportarMaisRecente(): array {
    $edicoes = rpiBuscarEdicoesDisponiveis(); if ($edicoes === []) throw new RuntimeException('Nenhuma edição de Marcas foi encontrada no portal do INPI.');
    $registro = rpiBaixarEdicao($edicoes[0]); $registro['url_dados_marcas'] = $edicoes[0]['url_dados_marcas'] ?? ''; $registro['publicacoes_importadas'] = rpiImportarPublicacoes($registro);
    $registro['processos_cadastrados'] = rpiAtualizarCarteirasMonitoradas();
    $indice = rpiCarregarIndice(); foreach ($indice as &$salva) if ((int) ($salva['numero'] ?? 0) === (int) $registro['numero']) { $salva['ultima_importacao_em'] = date('c'); $salva['publicacoes_importadas'] = $registro['publicacoes_importadas']; break; } unset($salva); rpiSalvarIndice($indice); return $registro;
}

function rpiAtualizarCarteirasMonitoradas(): int {
    $arquivoConfiguracao = __DIR__ . '/monitoramentos.json';
    $arquivoCarteira = __DIR__ . '/processos_monitorados.json';
    $monitoramentos = is_file($arquivoConfiguracao) ? json_decode((string) file_get_contents($arquivoConfiguracao), true) : [];
    $publicacoes = is_file(__DIR__ . '/dados_rpi.json') ? json_decode((string) file_get_contents(__DIR__ . '/dados_rpi.json'), true) : [];
    $carteira = is_file($arquivoCarteira) ? json_decode((string) file_get_contents($arquivoCarteira), true) : [];
    if (!is_array($monitoramentos) || !is_array($publicacoes)) return 0;
    if (!is_array($carteira)) $carteira = [];
    $existentes = []; foreach ($carteira as $item) $existentes[(string) ($item['processo'] ?? '')] = true;
    $novos = 0;
    foreach ($publicacoes as $publicacao) {
        $procurador = (string) ($publicacao['procurador'] ?? '');
        $processo = trim((string) ($publicacao['processo'] ?? ''));
        if ($processo === '' || isset($existentes[$processo])) continue;
        foreach ($monitoramentos as $monitoramento) {
            $alvo = trim((string) ($monitoramento['procurador'] ?? ''));
            if ($alvo !== '' && stripos($procurador, $alvo) !== false) {
                $carteira[] = ['processo' => $processo, 'carteira' => (string) ($monitoramento['nome'] ?? 'Carteira monitorada'), 'procurador' => $procurador, 'cadastrado_em' => date('c')];
                $existentes[$processo] = true; $novos++; break;
            }
        }
    }
    usort($carteira, fn(array $a, array $b): int => strcmp((string) $a['processo'], (string) $b['processo']));
    file_put_contents($arquivoCarteira, json_encode($carteira, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $novos;
}
