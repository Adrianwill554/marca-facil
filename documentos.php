<?php
$paginaAtual = 'documentos';

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function dataParaTimestamp($data) {
    $data = trim((string)$data);

    if ($data === '') return 0;

    $partes = explode('/', $data);

    if (count($partes) === 3) {
        [$dia, $mes, $ano] = $partes;
        return mktime(0, 0, 0, (int)$mes, (int)$dia, (int)$ano);
    }

    $timestamp = strtotime($data);
    return $timestamp ?: 0;
}

function nomeArquivoSeguro($nome) {
    $nome = basename($nome);
    $nome = preg_replace('/[^A-Za-z0-9._-]/', '_', $nome);
    return $nome ?: 'arquivo';
}

$arquivoDocumentos = __DIR__ . '/documentos.json';
$arquivoClientes = __DIR__ . '/clientes.json';
$arquivoDados = __DIR__ . '/dados_rpi.json';
$arquivoVinculos = __DIR__ . '/vinculos_processos.json';
$pastaUploads = __DIR__ . '/uploads_documentos';

if (!is_dir($pastaUploads)) {
    mkdir($pastaUploads, 0775, true);
}

if (!file_exists($arquivoDocumentos)) {
    file_put_contents(
        $arquivoDocumentos,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function carregarJson($arquivo, $padrao = []) {
    if (!file_exists($arquivo)) return $padrao;

    $conteudo = file_get_contents($arquivo);

    if ($conteudo === false) return $padrao;

    $dados = json_decode($conteudo, true);

    return is_array($dados) ? $dados : $padrao;
}

$documentos = carregarJson($arquivoDocumentos);
$clientes = carregarJson($arquivoClientes);
$publicacoes = carregarJson($arquivoDados);
$vinculos = carregarJson($arquivoVinculos);

$clientesPorId = [];

foreach ($clientes as $cliente) {
    $id = (int)($cliente['id'] ?? 0);

    if ($id > 0) {
        $clientesPorId[$id] = $cliente;
    }
}

$processosAgrupados = [];

foreach ($publicacoes as $item) {

    $numero = trim((string)($item['processo'] ?? ''));

    if ($numero === '') continue;

    $timestamp = dataParaTimestamp($item['data'] ?? '');

    if (!isset($processosAgrupados[$numero])) {

        $processosAgrupados[$numero] = [
            'processo' => $numero,
            'marca' => $item['marca'] ?? 'Marca não informada',
            'titular' => $item['titular'] ?? 'Titular não informado',
            'rpi' => $item['rpi'] ?? '',
            'data' => $item['data'] ?? '',
            '_timestamp' => $timestamp
        ];

    } elseif ($timestamp >= $processosAgrupados[$numero]['_timestamp']) {

        $processosAgrupados[$numero]['marca'] =
            $item['marca'] ?? $processosAgrupados[$numero]['marca'];

        $processosAgrupados[$numero]['titular'] =
            $item['titular'] ?? $processosAgrupados[$numero]['titular'];

        $processosAgrupados[$numero]['rpi'] = $item['rpi'] ?? '';
        $processosAgrupados[$numero]['data'] = $item['data'] ?? '';
        $processosAgrupados[$numero]['_timestamp'] = $timestamp;
    }
}

foreach ($processosAgrupados as $numero => &$processo) {

    $clienteId = isset($vinculos[$numero])
        ? (int)$vinculos[$numero]
        : 0;

    $processo['cliente_id'] = $clienteId;

    $processo['cliente_nome'] =
        $clienteId > 0 && isset($clientesPorId[$clienteId])
        ? $clientesPorId[$clienteId]['nome']
        : '';
}
unset($processo);

$erro = '';
$sucesso = '';

$tipos = [
    'Procuração',
    'Contrato',
    'Comprovante',
    'Petição',
    'Protocolo',
    'Certificado',
    'Guia',
    'Manifestação',
    'Outros'
];

$extensoesPermitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
$tamanhoMaximo = 10 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {

        $processo = trim($_POST['processo'] ?? '');
        $tipoDocumento = trim($_POST['tipo_documento'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');

        if ($processo === '') {
            $erro = 'Selecione um processo.';
        } elseif ($tipoDocumento === '') {
            $erro = 'Selecione o tipo do documento.';
        } elseif ($titulo === '') {
            $erro = 'Informe o título do documento.';
        } elseif (!isset($processosAgrupados[$processo])) {
            $erro = 'O processo selecionado não foi encontrado.';
        } else {

            $arquivoNomeOriginal = '';
            $arquivoNomeSalvo = '';
            $arquivoCaminho = '';

            if (
                isset($_FILES['arquivo'])
                &&
                $_FILES['arquivo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                    $erro = 'Ocorreu um erro ao enviar o arquivo.';
                } elseif ($_FILES['arquivo']['size'] > $tamanhoMaximo) {
                    $erro = 'O arquivo deve ter no máximo 10 MB.';
                } else {

                    $arquivoNomeOriginal = $_FILES['arquivo']['name'];
                    $extensao = strtolower(
                        pathinfo($arquivoNomeOriginal, PATHINFO_EXTENSION)
                    );

                    if (!in_array($extensao, $extensoesPermitidas, true)) {
                        $erro = 'Formato não permitido. Use PDF, DOC, DOCX, JPG, PNG ou WEBP.';
                    } else {

                        $base = pathinfo($arquivoNomeOriginal, PATHINFO_FILENAME);
                        $base = nomeArquivoSeguro($base);

                        $arquivoNomeSalvo =
                            date('Ymd_His')
                            . '_'
                            . bin2hex(random_bytes(4))
                            . '_'
                            . $base
                            . '.'
                            . $extensao;

                        $destinoFisico =
                            $pastaUploads
                            . DIRECTORY_SEPARATOR
                            . $arquivoNomeSalvo;

                        if (!move_uploaded_file(
                            $_FILES['arquivo']['tmp_name'],
                            $destinoFisico
                        )) {
                            $erro = 'Não foi possível salvar o arquivo enviado.';
                        } else {
                            $arquivoCaminho =
                                'uploads_documentos/'
                                . $arquivoNomeSalvo;
                        }
                    }
                }
            }

            if ($erro === '') {

                $novoId = 1;

                if ($documentos) {
                    $ids = array_map(
                        fn($doc) => (int)($doc['id'] ?? 0),
                        $documentos
                    );

                    $novoId = max($ids) + 1;
                }

                $clienteId = isset($vinculos[$processo])
                    ? (int)$vinculos[$processo]
                    : 0;

                $documentos[] = [
                    'id' => $novoId,
                    'processo' => $processo,
                    'cliente_id' => $clienteId,
                    'tipo_documento' => $tipoDocumento,
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'referencia' => $referencia,
                    'arquivo_nome_original' => $arquivoNomeOriginal,
                    'arquivo_nome_salvo' => $arquivoNomeSalvo,
                    'arquivo_caminho' => $arquivoCaminho,
                    'criado_em' => date('Y-m-d H:i:s')
                ];

                $salvou = file_put_contents(
                    $arquivoDocumentos,
                    json_encode(
                        $documentos,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    ),
                    LOCK_EX
                );

                if ($salvou === false) {
                    $erro = 'Não foi possível salvar o documento.';
                } else {
                    $sucesso = 'Documento cadastrado com sucesso!';
                }
            }
        }
    }

    if ($acao === 'excluir') {

        $id = (int)($_POST['id'] ?? 0);

        $arquivoParaExcluir = '';

        foreach ($documentos as $doc) {
            if ((int)($doc['id'] ?? 0) === $id) {
                $arquivoParaExcluir = $doc['arquivo_caminho'] ?? '';
                break;
            }
        }

        $documentos = array_values(
            array_filter(
                $documentos,
                fn($doc) => (int)($doc['id'] ?? 0) !== $id
            )
        );

        if ($arquivoParaExcluir !== '') {
            $caminhoFisico = __DIR__ . '/' . $arquivoParaExcluir;

            if (file_exists($caminhoFisico)) {
                @unlink($caminhoFisico);
            }
        }

        $salvou = file_put_contents(
            $arquivoDocumentos,
            json_encode(
                $documentos,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );

        if ($salvou === false) {
            $erro = 'Não foi possível excluir o documento.';
        } else {
            $sucesso = 'Documento excluído com sucesso.';
        }
    }
}

foreach ($documentos as &$doc) {

    $numero = $doc['processo'] ?? '';
    $processo = $processosAgrupados[$numero] ?? [];

    $doc['marca'] =
        $processo['marca']
        ?? 'Marca não informada';

    $clienteId = (int)($doc['cliente_id'] ?? 0);

    if ($clienteId <= 0 && isset($vinculos[$numero])) {
        $clienteId = (int)$vinculos[$numero];
    }

    $doc['cliente_id'] = $clienteId;

    $doc['cliente_nome'] =
        $clienteId > 0 && isset($clientesPorId[$clienteId])
        ? $clientesPorId[$clienteId]['nome']
        : 'Sem cliente';
}
unset($doc);

usort(
    $documentos,
    fn($a, $b) =>
    strcmp(
        $b['criado_em'] ?? '',
        $a['criado_em'] ?? ''
    )
);

$busca = trim($_GET['busca'] ?? '');
$filtroTipo = $_GET['tipo'] ?? 'todos';

$documentosFiltrados = array_filter(
    $documentos,
    function ($doc) use ($busca, $filtroTipo) {

        $texto = mb_strtolower(
            ($doc['titulo'] ?? '')
            . ' '
            . ($doc['tipo_documento'] ?? '')
            . ' '
            . ($doc['processo'] ?? '')
            . ' '
            . ($doc['marca'] ?? '')
            . ' '
            . ($doc['cliente_nome'] ?? '')
            . ' '
            . ($doc['descricao'] ?? '')
            . ' '
            . ($doc['referencia'] ?? '')
            . ' '
            . ($doc['arquivo_nome_original'] ?? '')
        );

        $okBusca =
            $busca === ''
            ||
            str_contains(
                $texto,
                mb_strtolower($busca)
            );

        $okTipo =
            $filtroTipo === 'todos'
            ||
            mb_strtolower($doc['tipo_documento'] ?? '')
            ===
            mb_strtolower($filtroTipo);

        return $okBusca && $okTipo;
    }
);

$totalDocumentos = count($documentos);

$totalComArquivo = count(
    array_filter(
        $documentos,
        fn($doc) => !empty($doc['arquivo_caminho'])
    )
);

$totalProcessosComDocumento = count(
    array_unique(
        array_filter(
            array_map(
                fn($doc) => $doc['processo'] ?? '',
                $documentos
            )
        )
    )
);

$totalClientesComDocumento = count(
    array_unique(
        array_filter(
            array_map(
                fn($doc) => (int)($doc['cliente_id'] ?? 0),
                $documentos
            )
        )
    )
);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Documentos</title>

<style>
:root {
    --navy:#111827;
    --blue:#2563eb;
    --blue-dark:#1d4ed8;
    --bg:#f6f8fc;
    --text:#172033;
    --muted:#6b7280;
    --line:#e7eaf0;
    --green:#147a55;
    --red:#c73b4b;
}

* {
    box-sizing:border-box;
    margin:0;
    font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

body {
    min-height:100vh;
    background:var(--bg);
    color:var(--text);
}

.layout {
    display:grid;
    grid-template-columns:250px 1fr;
    min-height:100vh;
}

aside {
    background:var(--navy);
    color:#d7deeb;
    padding:26px 16px;
    display:flex;
    flex-direction:column;
}

.brand {
    padding:0 12px 30px;
    color:#fff;
    font-size:21px;
    font-weight:800;
    letter-spacing:-.7px;
}

.brand span {
    color:#78a5ff;
}

.menu-title {
    color:#8ea0bb;
    text-transform:uppercase;
    font-size:10px;
    letter-spacing:.8px;
    margin:8px 12px 10px;
    font-weight:800;
}

nav {
    display:grid;
    gap:5px;
}

nav a {
    text-decoration:none;
    color:inherit;
    padding:12px;
    border-radius:9px;
    font-size:14px;
}

nav a:hover,
nav a.active {
    background:#263757;
    color:#fff;
}

.side-note {
    margin-top:auto;
    padding:14px;
    border:1px solid #334155;
    border-radius:10px;
    font-size:12px;
    line-height:1.5;
}

main {
    width:100%;
    max-width:1500px;
    margin:auto;
    padding:34px 42px;
}

.header {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    margin-bottom:26px;
}

.header h1 {
    font-size:28px;
    letter-spacing:-.8px;
}

.header p {
    color:var(--muted);
    margin-top:6px;
    font-size:14px;
    line-height:1.5;
}

.badge {
    background:#eaf1ff;
    color:#2452aa;
    padding:8px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.message {
    margin-bottom:18px;
    padding:13px 15px;
    border-radius:10px;
    font-size:13px;
    font-weight:700;
}

.message.success {
    background:#dff7ed;
    color:#126149;
}

.message.error {
    background:#ffeaed;
    color:#a6283a;
}

.metrics {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:22px;
}

.metric,
.filters,
.panel {
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:0 2px 10px rgba(27,39,65,.03);
}

.metric {
    padding:18px;
}

.metric p {
    color:var(--muted);
    font-size:12px;
}

.metric h2 {
    margin-top:7px;
    font-size:27px;
}

.top-actions {
    margin-bottom:20px;
}

.primary {
    border:0;
    background:var(--blue);
    color:#fff;
    border-radius:9px;
    padding:11px 14px;
    font-weight:800;
    cursor:pointer;
    font-size:12px;
    text-decoration:none;
    display:inline-block;
}

.primary:hover {
    background:var(--blue-dark);
}

.secondary {
    border:1px solid #d9dfeb;
    background:#fff;
    color:#344054;
    border-radius:8px;
    padding:9px 11px;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.danger {
    border:1px solid #f3c5cc;
    background:#fff5f6;
    color:#a6283a;
    border-radius:8px;
    padding:9px 11px;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
}

.filters {
    padding:20px;
    margin-bottom:22px;
}

.filter-grid {
    display:grid;
    grid-template-columns:1.3fr .8fr auto;
    gap:12px;
    align-items:end;
}

label {
    display:grid;
    gap:6px;
    font-size:12px;
    font-weight:750;
    color:#344054;
}

input,
select,
textarea {
    width:100%;
    padding:11px 12px;
    border:1px solid #d5dae5;
    border-radius:9px;
    font-size:14px;
    background:#fff;
    outline:none;
}

input:focus,
select:focus,
textarea:focus {
    border-color:#8fb1f7;
    box-shadow:0 0 0 3px rgba(37,99,235,.08);
}

.panel {
    overflow:hidden;
}

.panel-head {
    padding:19px 21px 15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
}

.panel-head h2 {
    font-size:17px;
}

.panel-head span {
    color:var(--muted);
    font-size:13px;
}

.table-wrap {
    overflow:auto;
}

table {
    width:100%;
    border-collapse:collapse;
    min-width:1200px;
}

th,
td {
    padding:14px 16px;
    border-top:1px solid var(--line);
    text-align:left;
    font-size:13px;
    vertical-align:middle;
}

th {
    background:#fbfcfe;
    color:var(--muted);
    text-transform:uppercase;
    font-size:11px;
}

td strong {
    display:block;
    margin-bottom:3px;
}

td small {
    color:var(--muted);
}

.tag {
    display:inline-block;
    background:#eaf1ff;
    color:#2452aa;
    padding:5px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
}

.actions {
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

.empty {
    padding:40px 24px;
    text-align:center;
    color:var(--muted);
}

dialog {
    border:0;
    border-radius:16px;
    padding:0;
    width:min(720px,calc(100% - 30px));
    box-shadow:0 22px 65px rgba(0,0,0,.28);
}

dialog::backdrop {
    background:rgba(15,23,42,.48);
}

.modal {
    padding:24px;
}

.modal h2 {
    font-size:21px;
    margin-bottom:5px;
}

.modal > p {
    color:var(--muted);
    font-size:13px;
    margin-bottom:18px;
}

.form-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.full {
    grid-column:1 / -1;
}

.modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:18px;
}

.hint {
    display:block;
    color:var(--muted);
    font-size:11px;
    margin-top:4px;
}

.file-name {
    color:#2452aa;
    font-weight:700;
    font-size:12px;
}

@media(max-width:900px) {
    .layout {
        grid-template-columns:1fr;
    }

    aside {
        padding:15px;
    }

    nav {
        display:flex;
        overflow:auto;
    }

    nav a {
        white-space:nowrap;
    }

    .menu-title,
    .side-note {
        display:none;
    }

    main {
        padding:24px 16px;
    }

    .header {
        flex-direction:column;
    }

    .metrics {
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:600px) {
    .metrics,
    .filter-grid,
    .form-grid {
        grid-template-columns:1fr;
    }

    .full {
        grid-column:auto;
    }
}
</style>
</head>

<body>

<div class="layout">

<?php require __DIR__ . '/menu.php'; ?>

<main>

<div class="header">

    <div>
        <h1>Documentos</h1>
        <p>Cadastre documentos e anexe arquivos ligados aos processos e clientes.</p>
    </div>

    <span class="badge">
        <?= $totalDocumentos ?> documento(s)
    </span>

</div>

<?php if ($sucesso): ?>

<div class="message success">
    <?= e($sucesso) ?>
</div>

<?php endif; ?>

<?php if ($erro): ?>

<div class="message error">
    <?= e($erro) ?>
</div>

<?php endif; ?>

<section class="metrics">

    <div class="metric">
        <p>Total de documentos</p>
        <h2><?= $totalDocumentos ?></h2>
    </div>

    <div class="metric">
        <p>Com arquivo anexado</p>
        <h2><?= $totalComArquivo ?></h2>
    </div>

    <div class="metric">
        <p>Processos com documentos</p>
        <h2><?= $totalProcessosComDocumento ?></h2>
    </div>

    <div class="metric">
        <p>Clientes com documentos</p>
        <h2><?= $totalClientesComDocumento ?></h2>
    </div>

</section>

<div class="top-actions">

    <button
        type="button"
        class="primary"
        id="abrirNovoDocumento"
    >
        + Novo documento
    </button>

</div>

<section class="filters">

<form method="GET" class="filter-grid">

    <label>
        Buscar
        <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Documento, processo, marca ou cliente"
        >
    </label>

    <label>
        Tipo
        <select name="tipo">

            <option value="todos" <?= $filtroTipo === 'todos' ? 'selected' : '' ?>>
                Todos
            </option>

            <?php foreach ($tipos as $tipo): ?>

            <option
                value="<?= e($tipo) ?>"
                <?= $filtroTipo === $tipo ? 'selected' : '' ?>
            >
                <?= e($tipo) ?>
            </option>

            <?php endforeach; ?>

        </select>
    </label>

    <button
        type="submit"
        class="primary"
    >
        Filtrar
    </button>

</form>

</section>

<section class="panel">

<div class="panel-head">

    <h2>Documentos cadastrados</h2>

    <span>
        <?= count($documentosFiltrados) ?> resultado(s)
    </span>

</div>

<?php if (!$documentosFiltrados): ?>

<div class="empty">
    Nenhum documento encontrado.
</div>

<?php else: ?>

<div class="table-wrap">

<table>

<thead>

<tr>
    <th>Documento</th>
    <th>Tipo</th>
    <th>Processo / Marca</th>
    <th>Cliente</th>
    <th>Arquivo</th>
    <th>Ações</th>
</tr>

</thead>

<tbody>

<?php foreach ($documentosFiltrados as $doc): ?>

<tr>

<td>
    <strong>
        <?= e($doc['titulo'] ?? '') ?>
    </strong>

    <small>
        <?= e(
            ($doc['descricao'] ?? '') !== ''
            ? $doc['descricao']
            : 'Sem descrição'
        ) ?>
    </small>
</td>

<td>
    <span class="tag">
        <?= e($doc['tipo_documento'] ?? 'Outros') ?>
    </span>
</td>

<td>
    <strong>
        <?= e($doc['processo'] ?? '') ?>
    </strong>

    <small>
        <?= e($doc['marca'] ?? 'Marca não informada') ?>
    </small>
</td>

<td>
    <?= e($doc['cliente_nome'] ?? 'Sem cliente') ?>
</td>

<td>

<?php if (!empty($doc['arquivo_caminho'])): ?>

    <span class="file-name">
        <?= e(
            $doc['arquivo_nome_original']
            ?? basename($doc['arquivo_caminho'])
        ) ?>
    </span>

<?php else: ?>

    <small>Sem arquivo</small>

<?php endif; ?>

</td>

<td>

<div class="actions">

    <?php if (!empty($doc['arquivo_caminho'])): ?>

    <a
        class="primary"
        href="<?= e($doc['arquivo_caminho']) ?>"
        target="_blank"
        rel="noopener"
    >
        Abrir arquivo
    </a>

    <?php endif; ?>

    <a
        class="secondary"
        href="detalhes_processo.php?processo=<?= urlencode($doc['processo'] ?? '') ?>"
    >
        Ver processo
    </a>

    <form
        method="POST"
        onsubmit="return confirm('Tem certeza que deseja excluir este documento? O arquivo anexado também será removido.');"
    >

        <input
            type="hidden"
            name="acao"
            value="excluir"
        >

        <input
            type="hidden"
            name="id"
            value="<?= (int)($doc['id'] ?? 0) ?>"
        >

        <button
            type="submit"
            class="danger"
        >
            Excluir
        </button>

    </form>

</div>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</section>

</main>

</div>

<dialog id="novoDocumentoModal">

<div class="modal">

    <h2>Novo documento</h2>

    <p>
        Cadastre as informações e, se quiser, anexe o arquivo.
    </p>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="acao"
            value="salvar"
        >

        <div class="form-grid">

            <label class="full">

                Processo

                <select
                    name="processo"
                    id="processoSelect"
                    required
                >

                    <option value="">
                        Selecione um processo
                    </option>

                    <?php foreach ($processosAgrupados as $processo): ?>

                    <option
                        value="<?= e($processo['processo']) ?>"
                        data-cliente="<?= e($processo['cliente_nome'] ?? '') ?>"
                    >
                        <?= e($processo['processo']) ?>
                        —
                        <?= e($processo['marca']) ?>

                        <?php if (($processo['cliente_nome'] ?? '') !== ''): ?>
                            — <?= e($processo['cliente_nome']) ?>
                        <?php endif; ?>
                    </option>

                    <?php endforeach; ?>

                </select>

                <span
                    class="hint"
                    id="clienteHint"
                >
                    O cliente será identificado automaticamente pelo vínculo do processo.
                </span>

            </label>

            <label>

                Tipo do documento

                <select
                    name="tipo_documento"
                    required
                >

                    <option value="">
                        Selecione
                    </option>

                    <?php foreach ($tipos as $tipo): ?>

                    <option value="<?= e($tipo) ?>">
                        <?= e($tipo) ?>
                    </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <label>

                Referência / protocolo

                <input
                    type="text"
                    name="referencia"
                    placeholder="Opcional"
                >

            </label>

            <label class="full">

                Título

                <input
                    type="text"
                    name="titulo"
                    placeholder="Ex.: Procuração assinada"
                    required
                >

            </label>

            <label class="full">

                Arquivo

                <input
                    type="file"
                    name="arquivo"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                >

                <span class="hint">
                    PDF, DOC, DOCX, JPG, PNG ou WEBP. Máximo de 10 MB.
                </span>

            </label>

            <label class="full">

                Descrição

                <textarea
                    name="descricao"
                    rows="4"
                    placeholder="Observações sobre o documento"
                ></textarea>

            </label>

        </div>

        <div class="modal-actions">

            <button
                type="button"
                class="secondary"
                id="cancelarNovoDocumento"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="primary"
            >
                Salvar documento
            </button>

        </div>

    </form>

</div>

</dialog>

<script>
const modal =
    document.querySelector('#novoDocumentoModal');

document
.querySelector('#abrirNovoDocumento')
.addEventListener(
    'click',
    () => modal.showModal()
);

document
.querySelector('#cancelarNovoDocumento')
.addEventListener(
    'click',
    () => modal.close()
);

const processoSelect =
    document.querySelector('#processoSelect');

const clienteHint =
    document.querySelector('#clienteHint');

processoSelect
.addEventListener(
    'change',
    () => {

        const option =
            processoSelect.options[
                processoSelect.selectedIndex
            ];

        const cliente =
            option.dataset.cliente
            || '';

        clienteHint.textContent =
            cliente !== ''
            ? 'Cliente vinculado: ' + cliente
            : 'Este processo ainda não possui cliente vinculado.';
    }
);
</script>

</body>
</html>
