<?php
$paginaAtual = 'dashboard';

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

function formatarData($data) {
    if (!$data) return '—';

    $timestamp = strtotime($data);

    if (!$timestamp) return $data;

    return date('d/m/Y', $timestamp);
}

function statusPrazo($data, $concluido = false) {
    if ($concluido) {
        return ['texto' => 'Concluído', 'classe' => 'blue'];
    }

    if (!$data) {
        return ['texto' => 'Sem data', 'classe' => 'blue'];
    }

    $hoje = strtotime(date('Y-m-d'));
    $prazo = strtotime($data);

    if (!$prazo) {
        return ['texto' => 'Sem data', 'classe' => 'blue'];
    }

    $dias = (int)(($prazo - $hoje) / 86400);

    if ($dias < 0) {
        return ['texto' => 'Atrasado', 'classe' => 'red'];
    }

    if ($dias <= 3) {
        return ['texto' => 'Urgente', 'classe' => 'red'];
    }

    if ($dias <= 10) {
        return ['texto' => 'Atenção', 'classe' => 'amber'];
    }

    return ['texto' => 'No prazo', 'classe' => 'green'];
}

function carregarJson($arquivo, $padrao = []) {
    if (!file_exists($arquivo)) {
        return $padrao;
    }

    $conteudo = file_get_contents($arquivo);

    if ($conteudo === false) {
        return $padrao;
    }

    $dados = json_decode($conteudo, true);

    return is_array($dados) ? $dados : $padrao;
}

$publicacoes = carregarJson(__DIR__ . '/dados_rpi.json');
$clientes = carregarJson(__DIR__ . '/clientes.json');
$prazos = carregarJson(__DIR__ . '/prazos.json');
$documentos = carregarJson(__DIR__ . '/documentos.json');
$vinculos = carregarJson(__DIR__ . '/vinculos_processos.json');

foreach ($publicacoes as &$item) {
    $item['tipo'] = detectarTipo($item['despacho'] ?? '');
    $item['_timestamp'] = dataParaTimestamp($item['data'] ?? '');
}
unset($item);

usort(
    $publicacoes,
    fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']
);

$processosUnicos = [];

foreach ($publicacoes as $item) {
    $numero = trim((string)($item['processo'] ?? ''));

    if ($numero === '') {
        continue;
    }

    if (!isset($processosUnicos[$numero])) {
        $processosUnicos[$numero] = $item;
    }
}

$totalProcessos = count($processosUnicos);
$totalPublicacoes = count($publicacoes);
$totalClientes = count($clientes);
$totalDocumentos = count($documentos);

$totalDeferimentos = count(
    array_filter(
        $publicacoes,
        fn($item) =>
        in_array(
            $item['tipo'] ?? '',
            ['Deferimento', 'Registro'],
            true
        )
    )
);

foreach ($prazos as &$prazo) {
    $status = statusPrazo(
        $prazo['data_prazo'] ?? '',
        !empty($prazo['concluido'])
    );

    $prazo['status_texto'] = $status['texto'];
    $prazo['status_classe'] = $status['classe'];
}
unset($prazo);

$totalPrazos = count($prazos);

$totalPrazosUrgentes = count(
    array_filter(
        $prazos,
        fn($p) =>
        empty($p['concluido'])
        &&
        in_array(
            $p['status_texto'] ?? '',
            ['Urgente', 'Atrasado'],
            true
        )
    )
);

$totalPrazosConcluidos = count(
    array_filter(
        $prazos,
        fn($p) => !empty($p['concluido'])
    )
);

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

usort(
    $documentos,
    fn($a, $b) =>
    strcmp(
        $b['criado_em'] ?? '',
        $a['criado_em'] ?? ''
    )
);

$processosRecentes = array_slice(
    array_values($processosUnicos),
    0,
    5
);

$publicacoesRecentes = array_slice(
    $publicacoes,
    0,
    5
);

$prazosRecentes = array_slice(
    $prazos,
    0,
    5
);

$documentosRecentes = array_slice(
    $documentos,
    0,
    5
);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Dashboard</title>

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
    white-space:nowrap;
}

.metrics {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:22px;
}

.metric,
.panel,
.quick-card {
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

.metric small {
    display:block;
    margin-top:5px;
    color:var(--muted);
    font-size:11px;
}

.quick-actions {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
    margin-bottom:22px;
}

.quick-card {
    padding:18px;
    text-decoration:none;
    color:inherit;
    transition:.15s ease;
}

.quick-card:hover {
    transform:translateY(-2px);
    border-color:#cad7f4;
    box-shadow:0 8px 20px rgba(27,39,65,.06);
}

.quick-icon {
    width:38px;
    height:38px;
    border-radius:10px;
    background:#eef4ff;
    color:#2452aa;
    display:grid;
    place-items:center;
    font-weight:800;
    margin-bottom:12px;
}

.quick-card strong {
    display:block;
    font-size:14px;
    margin-bottom:4px;
}

.quick-card span {
    color:var(--muted);
    font-size:12px;
    line-height:1.45;
}

.grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
    margin-bottom:22px;
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

.panel-head a {
    color:var(--blue);
    text-decoration:none;
    font-size:12px;
    font-weight:750;
}

.list {
    padding:0 20px 10px;
}

.list-item {
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 0;
    border-top:1px solid var(--line);
}

.list-main {
    flex:1;
}

.list-main strong {
    display:block;
    font-size:13px;
    margin-bottom:3px;
}

.list-main span {
    color:var(--muted);
    font-size:12px;
    line-height:1.45;
}

.tag {
    display:inline-block;
    font-size:11px;
    font-weight:800;
    padding:5px 8px;
    border-radius:999px;
    white-space:nowrap;
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

.empty {
    padding:32px 20px;
    text-align:center;
    color:var(--muted);
    font-size:13px;
}

@media(max-width:1100px) {
    .metrics,
    .quick-actions {
        grid-template-columns:repeat(2,1fr);
    }
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

    .grid {
        grid-template-columns:1fr;
    }
}

@media(max-width:600px) {
    .metrics,
    .quick-actions {
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
        <h1>Dashboard</h1>
        <p>Visão geral do escritório com dados de processos, clientes, prazos e documentos.</p>
    </div>

    <span class="badge">
        <?= $totalPublicacoes ?> publicação(ões)
    </span>

</div>

<section class="metrics">

    <div class="metric">
        <p>Processos</p>
        <h2><?= $totalProcessos ?></h2>
        <small><?= $totalDeferimentos ?> registro(s) / deferimento(s)</small>
    </div>

    <div class="metric">
        <p>Clientes</p>
        <h2><?= $totalClientes ?></h2>
        <small>Clientes cadastrados</small>
    </div>

    <div class="metric">
        <p>Prazos</p>
        <h2><?= $totalPrazos ?></h2>
        <small><?= $totalPrazosUrgentes ?> urgente(s) ou atrasado(s)</small>
    </div>

    <div class="metric">
        <p>Documentos</p>
        <h2><?= $totalDocumentos ?></h2>
        <small><?= $totalPrazosConcluidos ?> prazo(s) concluído(s)</small>
    </div>

</section>

<section class="quick-actions">

    <a class="quick-card" href="consulta_rpi.php">
        <div class="quick-icon">⌕</div>
        <strong>Consultar RPI</strong>
        <span>Pesquisar processo, marca, titular ou despacho.</span>
    </a>

    <a class="quick-card" href="clientes.php">
        <div class="quick-icon">♙</div>
        <strong>Clientes</strong>
        <span>Consultar clientes e processos vinculados.</span>
    </a>

    <a class="quick-card" href="prazos.php">
        <div class="quick-icon">◷</div>
        <strong>Prazos</strong>
        <span>Acompanhar tarefas urgentes e vencimentos.</span>
    </a>

    <a class="quick-card" href="documentos.php">
        <div class="quick-icon">▤</div>
        <strong>Documentos</strong>
        <span>Acessar documentos ligados aos processos.</span>
    </a>

</section>

<section class="grid">

    <div class="panel">

        <div class="panel-head">
            <h2>Últimas publicações</h2>
            <a href="publicacoes.php">Ver todas</a>
        </div>

        <?php if (!$publicacoesRecentes): ?>

        <div class="empty">
            Nenhuma publicação importada.
        </div>

        <?php else: ?>

        <div class="list">

        <?php foreach ($publicacoesRecentes as $item): ?>

            <div class="list-item">

                <div class="list-main">

                    <strong>
                        <?= e($item['marca'] ?? 'Marca não informada') ?>
                    </strong>

                    <span>
                        Processo <?= e($item['processo'] ?? '—') ?>
                        · RPI <?= e($item['rpi'] ?? '—') ?>
                        · <?= e($item['data'] ?? 'Sem data') ?>
                    </span>

                </div>

                <span class="tag <?= e(classeStatus($item['tipo'] ?? 'Outros')) ?>">
                    <?= e($item['tipo'] ?? 'Outros') ?>
                </span>

            </div>

        <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </div>

    <div class="panel">

        <div class="panel-head">
            <h2>Próximos prazos</h2>
            <a href="prazos.php">Ver todos</a>
        </div>

        <?php if (!$prazosRecentes): ?>

        <div class="empty">
            Nenhum prazo cadastrado.
        </div>

        <?php else: ?>

        <div class="list">

        <?php foreach ($prazosRecentes as $prazo): ?>

            <div class="list-item">

                <div class="list-main">

                    <strong>
                        <?= e($prazo['titulo'] ?? 'Prazo') ?>
                    </strong>

                    <span>
                        Processo <?= e($prazo['processo'] ?? '—') ?>
                        · <?= e(formatarData($prazo['data_prazo'] ?? '')) ?>
                    </span>

                </div>

                <span class="tag <?= e($prazo['status_classe'] ?? 'blue') ?>">
                    <?= e($prazo['status_texto'] ?? 'Sem data') ?>
                </span>

            </div>

        <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </div>

</section>

<section class="grid">

    <div class="panel">

        <div class="panel-head">
            <h2>Processos recentes</h2>
            <a href="processos.php">Ver todos</a>
        </div>

        <?php if (!$processosRecentes): ?>

        <div class="empty">
            Nenhum processo disponível.
        </div>

        <?php else: ?>

        <div class="list">

        <?php foreach ($processosRecentes as $item): ?>

            <div class="list-item">

                <div class="list-main">

                    <strong>
                        <?= e($item['marca'] ?? 'Marca não informada') ?>
                    </strong>

                    <span>
                        <?= e($item['processo'] ?? '—') ?>
                        · <?= e($item['titular'] ?? 'Titular não informado') ?>
                    </span>

                </div>

                <a
                    class="tag blue"
                    style="text-decoration:none;"
                    href="consulta_rpi.php?termo=<?= urlencode($item['processo'] ?? '') ?>&filtro=processo"
                >
                    Abrir
                </a>

            </div>

        <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </div>

    <div class="panel">

        <div class="panel-head">
            <h2>Documentos recentes</h2>
            <a href="documentos.php">Ver todos</a>
        </div>

        <?php if (!$documentosRecentes): ?>

        <div class="empty">
            Nenhum documento cadastrado.
        </div>

        <?php else: ?>

        <div class="list">

        <?php foreach ($documentosRecentes as $doc): ?>

            <div class="list-item">

                <div class="list-main">

                    <strong>
                        <?= e($doc['titulo'] ?? 'Documento') ?>
                    </strong>

                    <span>
                        <?= e($doc['tipo_documento'] ?? 'Outro') ?>
                        · Processo <?= e($doc['processo'] ?? '—') ?>
                    </span>

                </div>

                <span class="tag blue">
                    Documento
                </span>

            </div>

        <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </div>

</section>

</main>

</div>

</body>
</html>
