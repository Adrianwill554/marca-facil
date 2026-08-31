<?php
$paginaAtual = 'prazos';

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

function formatarData($data) {
    if (!$data) return '—';

    $timestamp = strtotime($data);

    if (!$timestamp) return $data;

    return date('d/m/Y', $timestamp);
}

function statusPrazo($data) {
    if (!$data) {
        return [
            'texto' => 'Sem data',
            'classe' => 'blue'
        ];
    }

    $hoje = strtotime(date('Y-m-d'));
    $prazo = strtotime($data);

    if (!$prazo) {
        return [
            'texto' => 'Sem data',
            'classe' => 'blue'
        ];
    }

    $dias = (int)(($prazo - $hoje) / 86400);

    if ($dias < 0) {
        return [
            'texto' => 'Atrasado',
            'classe' => 'red'
        ];
    }

    if ($dias <= 3) {
        return [
            'texto' => 'Urgente',
            'classe' => 'red'
        ];
    }

    if ($dias <= 10) {
        return [
            'texto' => 'Atenção',
            'classe' => 'amber'
        ];
    }

    return [
        'texto' => 'No prazo',
        'classe' => 'green'
    ];
}

$arquivoPrazos = __DIR__ . '/prazos.json';
$arquivoClientes = __DIR__ . '/clientes.json';
$arquivoDados = __DIR__ . '/dados_rpi.json';
$arquivoVinculos = __DIR__ . '/vinculos_processos.json';

if (!file_exists($arquivoPrazos)) {
    file_put_contents(
        $arquivoPrazos,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

$prazos = [];
$clientes = [];
$publicacoes = [];
$vinculos = [];

foreach ([
    [$arquivoPrazos, &$prazos],
    [$arquivoClientes, &$clientes],
    [$arquivoDados, &$publicacoes],
    [$arquivoVinculos, &$vinculos],
] as $item) {

    [$arquivo, &$destino] = $item;

    if (file_exists($arquivo)) {
        $conteudo = file_get_contents($arquivo);

        if ($conteudo !== false) {
            $dados = json_decode($conteudo, true);

            if (is_array($dados)) {
                $destino = $dados;
            }
        }
    }
}

$clientesPorId = [];

foreach ($clientes as $cliente) {
    $id = (int)($cliente['id'] ?? 0);

    if ($id > 0) {
        $clientesPorId[$id] = $cliente;
    }
}

/*
 * Monta um resumo dos processos importados.
 */
$processosAgrupados = [];

foreach ($publicacoes as $item) {
    $numero = trim((string)($item['processo'] ?? ''));

    if ($numero === '') {
        continue;
    }

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

        $processosAgrupados[$numero]['rpi'] =
            $item['rpi'] ?? '';

        $processosAgrupados[$numero]['data'] =
            $item['data'] ?? '';

        $processosAgrupados[$numero]['_timestamp'] =
            $timestamp;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {

        $processo = trim($_POST['processo'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $dataPrazo = trim($_POST['data_prazo'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($processo === '') {
            $erro = 'Selecione um processo.';
        } elseif ($titulo === '') {
            $erro = 'Informe o título do prazo.';
        } elseif ($dataPrazo === '') {
            $erro = 'Informe a data do prazo.';
        } elseif (!isset($processosAgrupados[$processo])) {
            $erro = 'O processo selecionado não foi encontrado.';
        } else {

            $novoId = 1;

            if ($prazos) {
                $ids = array_map(
                    fn($prazo) => (int)($prazo['id'] ?? 0),
                    $prazos
                );

                $novoId = max($ids) + 1;
            }

            $clienteId = isset($vinculos[$processo])
                ? (int)$vinculos[$processo]
                : 0;

            $prazos[] = [
                'id' => $novoId,
                'processo' => $processo,
                'cliente_id' => $clienteId,
                'titulo' => $titulo,
                'data_prazo' => $dataPrazo,
                'observacoes' => $observacoes,
                'concluido' => false,
                'criado_em' => date('Y-m-d H:i:s')
            ];

            $salvou = file_put_contents(
                $arquivoPrazos,
                json_encode(
                    $prazos,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                LOCK_EX
            );

            if ($salvou === false) {
                $erro = 'Não foi possível salvar o prazo.';
            } else {
                $sucesso = 'Prazo cadastrado com sucesso!';
            }
        }
    }

    if ($acao === 'concluir') {

        $id = (int)($_POST['id'] ?? 0);

        foreach ($prazos as &$prazo) {
            if ((int)($prazo['id'] ?? 0) === $id) {
                $prazo['concluido'] = true;
                $prazo['concluido_em'] = date('Y-m-d H:i:s');
                break;
            }
        }
        unset($prazo);

        file_put_contents(
            $arquivoPrazos,
            json_encode(
                $prazos,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );

        $sucesso = 'Prazo marcado como concluído.';
    }

    if ($acao === 'reabrir') {

        $id = (int)($_POST['id'] ?? 0);

        foreach ($prazos as &$prazo) {
            if ((int)($prazo['id'] ?? 0) === $id) {
                $prazo['concluido'] = false;
                unset($prazo['concluido_em']);
                break;
            }
        }
        unset($prazo);

        file_put_contents(
            $arquivoPrazos,
            json_encode(
                $prazos,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );

        $sucesso = 'Prazo reaberto.';
    }

    if ($acao === 'excluir') {

        $id = (int)($_POST['id'] ?? 0);

        $prazos = array_values(
            array_filter(
                $prazos,
                fn($prazo) => (int)($prazo['id'] ?? 0) !== $id
            )
        );

        file_put_contents(
            $arquivoPrazos,
            json_encode(
                $prazos,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );

        $sucesso = 'Prazo excluído.';
    }
}

$busca = trim($_GET['busca'] ?? '');
$filtroStatus = $_GET['status'] ?? 'todos';

foreach ($prazos as &$prazo) {

    $numero = $prazo['processo'] ?? '';

    $processo = $processosAgrupados[$numero] ?? [];

    $prazo['marca'] =
        $processo['marca']
        ?? 'Marca não informada';

    $clienteId = (int)($prazo['cliente_id'] ?? 0);

    if ($clienteId <= 0 && isset($vinculos[$numero])) {
        $clienteId = (int)$vinculos[$numero];
    }

    $prazo['cliente_id'] = $clienteId;

    $prazo['cliente_nome'] =
        $clienteId > 0 && isset($clientesPorId[$clienteId])
        ? $clientesPorId[$clienteId]['nome']
        : 'Sem cliente';

    $status = !empty($prazo['concluido'])
        ? ['texto' => 'Concluído', 'classe' => 'blue']
        : statusPrazo($prazo['data_prazo'] ?? '');

    $prazo['status_texto'] = $status['texto'];
    $prazo['status_classe'] = $status['classe'];
}
unset($prazo);

usort(
    $prazos,
    function ($a, $b) {

        $aConcluido = !empty($a['concluido']) ? 1 : 0;
        $bConcluido = !empty($b['concluido']) ? 1 : 0;

        if ($aConcluido !== $bConcluido) {
            return $aConcluido <=> $bConcluido;
        }

        return strtotime($a['data_prazo'] ?? '9999-12-31')
            <=>
            strtotime($b['data_prazo'] ?? '9999-12-31');
    }
);

$prazosFiltrados = array_filter(
    $prazos,
    function ($prazo) use ($busca, $filtroStatus) {

        $texto = mb_strtolower(
            ($prazo['titulo'] ?? '')
            . ' '
            . ($prazo['processo'] ?? '')
            . ' '
            . ($prazo['marca'] ?? '')
            . ' '
            . ($prazo['cliente_nome'] ?? '')
            . ' '
            . ($prazo['observacoes'] ?? '')
        );

        $okBusca =
            $busca === ''
            ||
            str_contains(
                $texto,
                mb_strtolower($busca)
            );

        $okStatus =
            $filtroStatus === 'todos'
            ||
            mb_strtolower($prazo['status_texto'] ?? '')
            ===
            mb_strtolower($filtroStatus);

        return $okBusca && $okStatus;
    }
);

$totalPrazos = count($prazos);

$totalUrgentes = count(
    array_filter(
        $prazos,
        fn($p) =>
        empty($p['concluido'])
        &&
        in_array(
            $p['status_texto'],
            ['Urgente', 'Atrasado'],
            true
        )
    )
);

$totalAtencao = count(
    array_filter(
        $prazos,
        fn($p) =>
        empty($p['concluido'])
        &&
        $p['status_texto'] === 'Atenção'
    )
);

$totalConcluidos = count(
    array_filter(
        $prazos,
        fn($p) => !empty($p['concluido'])
    )
);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Prazos</title>

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
    --amber:#b76a00;
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

.brand span { color:#78a5ff; }

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
    min-width:1100px;
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
    font-size:11px;
    font-weight:800;
    padding:5px 8px;
    border-radius:999px;
}

.tag.green {
    color:#126149;
    background:#dff7ed;
}

.tag.blue {
    color:#2452aa;
    background:#e8f0ff;
}

.tag.amber {
    color:#9a5a00;
    background:#fff1d8;
}

.tag.red {
    color:#a6283a;
    background:#ffeaed;
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
    width:min(650px,calc(100% - 30px));
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
        <h1>Prazos</h1>
        <p>Cadastre e acompanhe os prazos relacionados aos processos e clientes.</p>
    </div>

    <span class="badge">
        <?= $totalPrazos ?> prazo(s)
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
        <p>Total de prazos</p>
        <h2><?= $totalPrazos ?></h2>
    </div>

    <div class="metric">
        <p>Urgentes / atrasados</p>
        <h2><?= $totalUrgentes ?></h2>
    </div>

    <div class="metric">
        <p>Em atenção</p>
        <h2><?= $totalAtencao ?></h2>
    </div>

    <div class="metric">
        <p>Concluídos</p>
        <h2><?= $totalConcluidos ?></h2>
    </div>

</section>

<div class="top-actions">

    <button
        type="button"
        class="primary"
        id="abrirNovoPrazo"
    >
        + Novo prazo
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
            placeholder="Prazo, processo, marca ou cliente"
        >
    </label>

    <label>
        Status
        <select name="status">

            <option value="todos" <?= $filtroStatus === 'todos' ? 'selected' : '' ?>>
                Todos
            </option>

            <option value="No prazo" <?= $filtroStatus === 'No prazo' ? 'selected' : '' ?>>
                No prazo
            </option>

            <option value="Atenção" <?= $filtroStatus === 'Atenção' ? 'selected' : '' ?>>
                Atenção
            </option>

            <option value="Urgente" <?= $filtroStatus === 'Urgente' ? 'selected' : '' ?>>
                Urgente
            </option>

            <option value="Atrasado" <?= $filtroStatus === 'Atrasado' ? 'selected' : '' ?>>
                Atrasado
            </option>

            <option value="Concluído" <?= $filtroStatus === 'Concluído' ? 'selected' : '' ?>>
                Concluído
            </option>

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

    <h2>Prazos cadastrados</h2>

    <span>
        <?= count($prazosFiltrados) ?> resultado(s)
    </span>

</div>

<?php if (!$prazosFiltrados): ?>

<div class="empty">
    Nenhum prazo encontrado.
</div>

<?php else: ?>

<div class="table-wrap">

<table>

<thead>

<tr>
    <th>Prazo</th>
    <th>Processo / Marca</th>
    <th>Cliente</th>
    <th>Data</th>
    <th>Status</th>
    <th>Ações</th>
</tr>

</thead>

<tbody>

<?php foreach ($prazosFiltrados as $prazo): ?>

<tr>

<td>
    <strong>
        <?= e($prazo['titulo'] ?? '') ?>
    </strong>

    <small>
        <?= e(
            ($prazo['observacoes'] ?? '') !== ''
            ? $prazo['observacoes']
            : 'Sem observações'
        ) ?>
    </small>
</td>

<td>
    <strong>
        <?= e($prazo['processo'] ?? '') ?>
    </strong>

    <small>
        <?= e($prazo['marca'] ?? 'Marca não informada') ?>
    </small>
</td>

<td>
    <?= e($prazo['cliente_nome'] ?? 'Sem cliente') ?>
</td>

<td>
    <strong>
        <?= e(formatarData($prazo['data_prazo'] ?? '')) ?>
    </strong>
</td>

<td>

    <span class="tag <?= e($prazo['status_classe'] ?? 'blue') ?>">
        <?= e($prazo['status_texto'] ?? 'Sem data') ?>
    </span>

</td>

<td>

<div class="actions">

    <?php if (empty($prazo['concluido'])): ?>

    <form method="POST">

        <input
            type="hidden"
            name="acao"
            value="concluir"
        >

        <input
            type="hidden"
            name="id"
            value="<?= (int)($prazo['id'] ?? 0) ?>"
        >

        <button
            type="submit"
            class="secondary"
        >
            Concluir
        </button>

    </form>

    <?php else: ?>

    <form method="POST">

        <input
            type="hidden"
            name="acao"
            value="reabrir"
        >

        <input
            type="hidden"
            name="id"
            value="<?= (int)($prazo['id'] ?? 0) ?>"
        >

        <button
            type="submit"
            class="secondary"
        >
            Reabrir
        </button>

    </form>

    <?php endif; ?>

    <a
        class="primary"
        href="consulta_rpi.php?termo=<?= urlencode($prazo['processo'] ?? '') ?>&filtro=processo"
    >
        Ver processo
    </a>

    <form
        method="POST"
        onsubmit="return confirm('Tem certeza que deseja excluir este prazo?');"
    >

        <input
            type="hidden"
            name="acao"
            value="excluir"
        >

        <input
            type="hidden"
            name="id"
            value="<?= (int)($prazo['id'] ?? 0) ?>"
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

<dialog id="novoPrazoModal">

<div class="modal">

    <h2>Novo prazo</h2>

    <p>
        Escolha o processo e informe a data do prazo.
    </p>

    <form method="POST">

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

            <label class="full">

                Título do prazo

                <input
                    type="text"
                    name="titulo"
                    placeholder="Ex.: Responder exigência"
                    required
                >

            </label>

            <label>

                Data do prazo

                <input
                    type="date"
                    name="data_prazo"
                    required
                >

            </label>

            <label class="full">

                Observações

                <textarea
                    name="observacoes"
                    rows="4"
                    placeholder="Informações adicionais sobre o prazo"
                ></textarea>

            </label>

        </div>

        <div class="modal-actions">

            <button
                type="button"
                class="secondary"
                id="cancelarNovoPrazo"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="primary"
            >
                Salvar prazo
            </button>

        </div>

    </form>

</div>

</dialog>

<script>
const modal =
    document.querySelector('#novoPrazoModal');

document
.querySelector('#abrirNovoPrazo')
.addEventListener(
    'click',
    () => modal.showModal()
);

document
.querySelector('#cancelarNovoPrazo')
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
