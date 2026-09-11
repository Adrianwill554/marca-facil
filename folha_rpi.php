<?php
declare(strict_types=1);

require_once __DIR__ . '/carteira_service.php';
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$processo = normalizarProcesso((string)($_GET['processo'] ?? ''));
$numeroRpi = trim((string)($_GET['rpi'] ?? ''));
$modo = ($_GET['modo'] ?? 'ver') === 'baixar' ? 'D' : 'I';

if ($processo === '' || $numeroRpi === '' || !processoPertenceACarteira($processo)) {
    http_response_code(404);
    exit('Processo não encontrado na carteira monitorada.');
}

$dados = is_file(__DIR__ . '/dados_rpi.json') ? json_decode((string)file_get_contents(__DIR__ . '/dados_rpi.json'), true) : [];
$publicacao = null;
foreach (is_array($dados) ? $dados : [] as $item) {
    if (normalizarProcesso((string)($item['processo'] ?? '')) === $processo && (string)($item['rpi'] ?? '') === $numeroRpi) {
        $publicacao = $item;
        break;
    }
}

$pagina = max(0, (int)($publicacao['pagina'] ?? 0));
if (!$publicacao || $pagina < 1) {
    http_response_code(422);
    exit('A publicação não informa o número da página no PDF da RPI.');
}

$edicoes = is_file(__DIR__ . '/rpis.json') ? json_decode((string)file_get_contents(__DIR__ . '/rpis.json'), true) : [];
$arquivoPdf = '';
foreach (is_array($edicoes) ? $edicoes : [] as $edicao) {
    if ((string)($edicao['numero'] ?? '') === $numeroRpi) {
        $nome = basename((string)($edicao['arquivo'] ?? ''));
        $candidato = __DIR__ . '/rpis/' . $nome;
        if ($nome !== '' && is_file($candidato)) $arquivoPdf = $candidato;
        break;
    }
}
if ($arquivoPdf === '') {
    http_response_code(404);
    exit('O PDF completo desta RPI ainda não está salvo no sistema.');
}

try {
    $pdf = new Fpdi();
    $total = $pdf->setSourceFile($arquivoPdf);
    if ($pagina > $total) throw new RuntimeException('Página fora do intervalo do PDF.');
    $modelo = $pdf->importPage($pagina);
    $tamanho = $pdf->getTemplateSize($modelo);
    $orientacao = $tamanho['width'] > $tamanho['height'] ? 'L' : 'P';
    $pdf->AddPage($orientacao, [$tamanho['width'], $tamanho['height']]);
    $pdf->useTemplate($modelo);
    $pdf->SetTitle('RPI ' . $numeroRpi . ' - Processo ' . $processo);
    $pdf->Output($modo, 'rpi-' . $numeroRpi . '-processo-' . $processo . '-pagina-' . $pagina . '.pdf');
} catch (Throwable $erro) {
    http_response_code(500);
    exit('Não foi possível extrair a página da RPI: ' . $erro->getMessage());
}
