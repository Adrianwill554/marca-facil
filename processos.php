<?php
$paginaAtual = 'processos';

$busca = trim($_GET['busca'] ?? '');
$tipo = $_GET['tipo'] ?? 'todos';

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function detectarTipo($despacho) {
    $texto = mb_strtolower((string)$despacho);

    if (str_contains($texto, 'prorroga')) return 'Prorrogação';
    if (str_contains($texto, 'defer')) return 'Deferimento';
    if (str_contains($texto, 'oposição') || str_contains($texto, 'oposicao')) return 'Oposição';
    if (str_contains($texto, 'registro') || str_contains($texto, 'concessão') || str_contains($texto, 'concessao')) return 'Registro';
    if (str_contains($texto, 'exigência') || str_contains($texto, 'exigencia')) return 'Exigência';
    if (str_contains($texto, 'indefer')) return 'Indeferimento';

    return 'Outros';
}

function classeStatus($tipo) {
    return match ($tipo) {
        'Deferimento', 'Registro' => 'green',
        'Oposição', 'Prorrogação' => 'amber',
        'Exigência', 'Indeferimento' => 'red',
        default => 'blue'
    };
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

$arquivoDados = __DIR__ . '/dados_rpi.json';
$arquivoClientes = __DIR__ . '/clientes.json';
$arquivoVinculos = __DIR__ . '/vinculos_processos.json';
$arquivoMonitorados = __DIR__ . '/processos_monitorados.json';

$publicacoes = [];
$clientes = [];
$vinculos = [];
$monitorados = [];
$mensagem = '';

if (file_exists($arquivoDados)) {
    $conteudo = file_get_contents($arquivoDados);

    if ($conteudo !== false) {
        $publicacoes = json_decode($conteudo, true);
        if (!is_array($publicacoes)) $publicacoes = [];
    }
}

if (file_exists($arquivoClientes)) {
    $conteudo = file_get_contents($arquivoClientes);

    if ($conteudo !== false) {
        $clientes = json_decode($conteudo, true);
        if (!is_array($clientes)) $clientes = [];
    }
}

if (file_exists($arquivoVinculos)) {
    $conteudo = file_get_contents($arquivoVinculos);

    if ($conteudo !== false) {
        $vinculos = json_decode($conteudo, true);
        if (!is_array($vinculos)) $vinculos = [];
    }
}

if (file_exists($arquivoMonitorados)) {
    $conteudo = file_get_contents($arquivoMonitorados);
    if ($conteudo !== false) {
        $itensMonitorados = json_decode($conteudo, true);
        if (is_array($itensMonitorados)) {
            foreach ($itensMonitorados as $itemMonitorado) {
                $numeroMonitorado = trim((string) ($itemMonitorado['processo'] ?? ''));
                if ($numeroMonitorado !== '') $monitorados[$numeroMonitorado] = $itemMonitorado;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'vincular') {
        $processo = trim($_POST['processo'] ?? '');
        $clienteId = trim($_POST['cliente_id'] ?? '');

        if ($processo !== '') {

            if ($clienteId === '') {
                unset($vinculos[$processo]);
                $mensagem = 'Vínculo removido com sucesso.';
            } else {
                $vinculos[$processo] = (int)$clienteId;
                $mensagem = 'Cliente vinculado ao processo com sucesso.';
            }

            file_put_contents(
                $arquivoVinculos,
                json_encode($vinculos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
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

$processosAgrupados = [];

foreach ($publicacoes as $item) {
    $numero = trim((string)($item['processo'] ?? ''));

    if ($numero === '') continue;

    $item['tipo'] = detectarTipo($item['despacho'] ?? '');
    $item['_timestamp'] = dataParaTimestamp($item['data'] ?? '');

    if (!isset($processosAgrupados[$numero])) {
        $processosAgrupados[$numero] = [
            'processo' => $numero,
            'marca' => $item['marca'] ?? 'Marca não informada',
            'titular' => $item['titular'] ?? 'Titular não informado',
            'procurador' => $item['procurador'] ?? '',
            'ultima_rpi' => $item['rpi'] ?? '',
            'ultima_data' => $item['data'] ?? '',
            'ultimo_despacho' => $item['despacho'] ?? '',
            'tipo' => $item['tipo'],
            'pagina' => $item['pagina'] ?? '',
            'total_publicacoes' => 1,
            '_timestamp' => $item['_timestamp']
        ];
    } else {
        $processosAgrupados[$numero]['total_publicacoes']++;

        if ($item['_timestamp'] >= $processosAgrupados[$numero]['_timestamp']) {
            $processosAgrupados[$numero]['marca'] = $item['marca'] ?? $processosAgrupados[$numero]['marca'];
            $processosAgrupados[$numero]['titular'] = $item['titular'] ?? $processosAgrupados[$numero]['titular'];
            $processosAgrupados[$numero]['procurador'] = $item['procurador'] ?? $processosAgrupados[$numero]['procurador'];
            $processosAgrupados[$numero]['ultima_rpi'] = $item['rpi'] ?? '';
            $processosAgrupados[$numero]['ultima_data'] = $item['data'] ?? '';
            $processosAgrupados[$numero]['ultimo_despacho'] = $item['despacho'] ?? '';
            $processosAgrupados[$numero]['tipo'] = $item['tipo'];
            $processosAgrupados[$numero]['pagina'] = $item['pagina'] ?? '';
            $processosAgrupados[$numero]['_timestamp'] = $item['_timestamp'];
        }
    }
}

$processos = array_values($processosAgrupados);

$processos = array_values(array_filter(
    $processos,
    fn(array $processo): bool => isset($monitorados[$processo['processo']])
));

foreach ($processos as &$processo) {
    $numero = $processo['processo'];
    $clienteId = $vinculos[$numero] ?? null;
    $processo['cliente_id'] = $clienteId;
    $processo['cliente_nome'] = $clienteId && isset($clientesPorId[$clienteId])
        ? $clientesPorId[$clienteId]['nome']
        : '';
}
unset($processo);

usort($processos, fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']);

$filtrados = array_filter($processos, function ($item) use ($busca, $tipo) {
    $texto = mb_strtolower(
        ($item['processo'] ?? '') . ' ' .
        ($item['marca'] ?? '') . ' ' .
        ($item['titular'] ?? '') . ' ' .
        ($item['procurador'] ?? '') . ' ' .
        ($item['cliente_nome'] ?? '') . ' ' .
        ($item['ultimo_despacho'] ?? '')
    );

    $okBusca = $busca === '' || str_contains($texto, mb_strtolower($busca));
    $okTipo = $tipo === 'todos' || mb_strtolower($item['tipo'] ?? '') === mb_strtolower($tipo);

    return $okBusca && $okTipo;
});

$totalProcessos = count($processos);
$totalPublicacoes = array_sum(array_column($processos, 'total_publicacoes'));
$totalVinculados = count(array_filter($processos, fn($p) => !empty($p['cliente_id'])));
$totalSemCliente = $totalProcessos - $totalVinculados;
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Processos</title>

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
    background:#dff7ed;
    color:#126149;
    font-size:13px;
    font-weight:700;
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

.filters {
    padding:20px;
    margin-bottom:22px;
}

.filter-grid {
    display:grid;
    grid-template-columns:1.4fr .8fr auto;
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
select {
    width:100%;
    padding:11px 12px;
    border:1px solid #d5dae5;
    border-radius:9px;
    font-size:14px;
    background:#fff;
    outline:none;
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

.tag.green { color:#126149; background:#dff7ed; }
.tag.blue { color:#2452aa; background:#e8f0ff; }
.tag.amber { color:#9a5a00; background:#fff1d8; }
.tag.red { color:#a6283a; background:#ffeaed; }

.client-form {
    display:flex;
    gap:7px;
    min-width:260px;
}

.client-form select {
    padding:8px 9px;
    font-size:12px;
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

@media(max-width:850px) {
    .layout { grid-template-columns:1fr; }
    aside { padding:15px; }
    nav { display:flex; overflow:auto; }
    nav a { white-space:nowrap; }
    .menu-title,.side-note { display:none; }
    main { padding:24px 16px; }
    .header { flex-direction:column; }
    .metrics { grid-template-columns:1fr 1fr; }
}

@media(max-width:560px) {
    .metrics,
    .filter-grid {
        grid-template-columns:1fr;
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
        <h1>Processos</h1>
        <p>Vincule cada processo ao cliente correspondente.</p>
    </div>

    <span class="badge"><?= $totalProcessos ?> processo(s)</span>
</div>

<?php if ($mensagem): ?>
<div class="message">
    <?= e($mensagem) ?>
</div>
<?php endif; ?>

<section class="metrics">

    <div class="metric">
        <p>Total de processos</p>
        <h2><?= $totalProcessos ?></h2>
    </div>

    <div class="metric">
        <p>Publicações</p>
        <h2><?= $totalPublicacoes ?></h2>
    </div>

    <div class="metric">
        <p>Com cliente</p>
        <h2><?= $totalVinculados ?></h2>
    </div>

    <div class="metric">
        <p>Sem cliente</p>
        <h2><?= $totalSemCliente ?></h2>
    </div>

</section>

<section class="filters">

<form method="GET" class="filter-grid">

    <label>
        Buscar
        <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Processo, marca, titular, procurador ou cliente"
        >
    </label>

    <label>
        Tipo
        <select name="tipo">
            <option value="todos" <?= $tipo === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="Deferimento" <?= $tipo === 'Deferimento' ? 'selected' : '' ?>>Deferimento</option>
            <option value="Registro" <?= $tipo === 'Registro' ? 'selected' : '' ?>>Registro</option>
            <option value="Prorrogação" <?= $tipo === 'Prorrogação' ? 'selected' : '' ?>>Prorrogação</option>
            <option value="Oposição" <?= $tipo === 'Oposição' ? 'selected' : '' ?>>Oposição</option>
            <option value="Exigência" <?= $tipo === 'Exigência' ? 'selected' : '' ?>>Exigência</option>
            <option value="Indeferimento" <?= $tipo === 'Indeferimento' ? 'selected' : '' ?>>Indeferimento</option>
            <option value="Outros" <?= $tipo === 'Outros' ? 'selected' : '' ?>>Outros</option>
        </select>
    </label>

    <button type="submit" class="primary">
        Filtrar
    </button>

</form>

</section>

<section class="panel">

<div class="panel-head">
    <h2>Processos encontrados</h2>
    <span><?= count($filtrados) ?> resultado(s)</span>
</div>

<?php if (!$filtrados): ?>

<div class="empty">
    Nenhum processo encontrado.
</div>

<?php else: ?>

<div class="table-wrap">

<table>

<thead>
<tr>
    <th>Marca / Titular</th>
    <th>Processo</th>
    <th>Última publicação</th>
    <th>Cliente vinculado</th>
    <th>Ações</th>
</tr>
</thead>

<tbody>

<?php foreach ($filtrados as $item): ?>

<tr>

<td>
    <strong><?= e($item['marca']) ?></strong>
    <small><?= e($item['titular']) ?></small>
    <?php if (!empty($item['procurador'])): ?>
    <small style="display:block;margin-top:4px;">Procurador: <?= e($item['procurador']) ?></small>
    <?php endif; ?>
</td>

<td>
    <strong><?= e($item['processo']) ?></strong>
    <small>
        RPI <?= e($item['ultima_rpi'] ?: '—') ?>
        · <?= e($item['ultima_data'] ?: 'Sem data') ?>
    </small>
</td>

<td>
    <span class="tag <?= e(classeStatus($item['tipo'])) ?>">
        <?= e($item['tipo']) ?>
    </span>

    <small style="display:block;margin-top:5px;">
        <?= e($item['ultimo_despacho']) ?>
    </small>
</td>

<td>

<form method="POST" class="client-form">

    <input type="hidden" name="acao" value="vincular">

    <input
        type="hidden"
        name="processo"
        value="<?= e($item['processo']) ?>"
    >

    <select name="cliente_id">

        <option value="">
            Sem cliente
        </option>

        <?php foreach ($clientes as $cliente): ?>

            <option
                value="<?= (int)($cliente['id'] ?? 0) ?>"
                <?= (int)($item['cliente_id'] ?? 0) === (int)($cliente['id'] ?? 0)
                    ? 'selected'
                    : ''
                ?>
            >
                <?= e($cliente['nome'] ?? '') ?>
            </option>

        <?php endforeach; ?>

    </select>

    <button
        type="submit"
        class="secondary"
    >
        Salvar
    </button>

</form>

</td>

<td>

<div class="actions">

    <a
        class="secondary"
        href="gerar_extrato_rpi.php?processo=<?= urlencode($item['processo']) ?>"
    >
        Baixar PDF
    </a>

    <?php if (!empty($item['ultima_rpi'])): ?>
    <a
        class="secondary"
        target="_blank"
        rel="noopener"
        href="https://revistas.inpi.gov.br/pdf/Marcas<?= urlencode((string)$item['ultima_rpi']) ?>.pdf"
    >
        RPI oficial
    </a>
    <?php endif; ?>

    <a
        class="secondary"
        href="consulta_rpi.php?termo=<?= urlencode($item['processo']) ?>&filtro=processo"
    >
        Ver histórico
    </a>

    <a
        class="primary"
        href="publicacoes.php?busca=<?= urlencode($item['processo']) ?>"
    >
        Publicações
    </a>

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

</body>
</html>
