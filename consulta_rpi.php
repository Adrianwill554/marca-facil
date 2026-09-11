<?php
$paginaAtual = 'consulta_rpi';
require_once __DIR__ . '/carteira_service.php';

$termo = trim($_GET['termo'] ?? '');
$filtro = $_GET['filtro'] ?? 'todos';
$rpi = trim($_GET['rpi'] ?? '');
$despacho = trim($_GET['despacho'] ?? '');

$resultados = [];

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$arquivoDados = __DIR__ . '/dados_rpi.json';

$dados = [];

if (file_exists($arquivoDados)) {

    $conteudo = file_get_contents($arquivoDados);

    if ($conteudo !== false) {

        $dados = json_decode($conteudo, true);

        if (!is_array($dados)) {
            $dados = [];
        }
        $dados = filtrarPublicacoesDaCarteira($dados);
    }
}

$edicoesRpi = [];
$arquivoEdicoes = __DIR__ . '/rpis.json';

if (is_file($arquivoEdicoes)) {
    $conteudo = file_get_contents($arquivoEdicoes);
    $edicoes = $conteudo !== false ? json_decode($conteudo, true) : null;

    if (is_array($edicoes)) {
        foreach ($edicoes as $edicao) {
            $numero = trim((string)($edicao['numero'] ?? ''));
            if ($numero === '') continue;

            $arquivo = trim((string)($edicao['arquivo'] ?? ''));
            $urlOficial = trim((string)($edicao['url_origem'] ?? ''));
            $edicoesRpi[$numero] = [
                'local' => $arquivo !== '' && is_file(__DIR__ . '/rpis/' . $arquivo)
                    ? 'rpis/' . rawurlencode($arquivo)
                    : '',
                'oficial' => filter_var($urlOficial, FILTER_VALIDATE_URL) ? $urlOficial : '',
                'tamanho' => (int)($edicao['tamanho'] ?? 0),
            ];
        }
    }
}

if (
    $termo !== ''
    ||
    $rpi !== ''
    ||
    $despacho !== ''
) {

    foreach ($dados as $item) {

        $processoItem =
            mb_strtolower(
                (string)($item['processo'] ?? '')
            );

        $marcaItem =
            mb_strtolower(
                (string)($item['marca'] ?? '')
            );

        $titularItem =
            mb_strtolower(
                (string)($item['titular'] ?? '')
            );

        $rpiItem =
            mb_strtolower(
                (string)($item['rpi'] ?? '')
            );

        $despachoItem =
            mb_strtolower(
                (string)($item['despacho'] ?? '')
            );

        $termoBusca =
            mb_strtolower($termo);

        $okTermo = true;

        if ($termo !== '') {

            if ($filtro === 'processo') {

                $okTermo =
                    str_contains(
                        $processoItem,
                        $termoBusca
                    );

            } elseif ($filtro === 'marca') {

                $okTermo =
                    str_contains(
                        $marcaItem,
                        $termoBusca
                    );

            } elseif ($filtro === 'titular') {

                $okTermo =
                    str_contains(
                        $titularItem,
                        $termoBusca
                    );

            } else {

                $textoCompleto =
                    $processoItem
                    . ' '
                    . $marcaItem
                    . ' '
                    . $titularItem
                    . ' '
                    . $rpiItem
                    . ' '
                    . $despachoItem;

                $okTermo =
                    str_contains(
                        $textoCompleto,
                        $termoBusca
                    );
            }
        }

        $okRpi = true;

        if ($rpi !== '') {

            $okRpi =
                str_contains(
                    $rpiItem,
                    mb_strtolower($rpi)
                );
        }

        $okDespacho = true;

        if ($despacho !== '') {

            $okDespacho =
                str_contains(
                    $despachoItem,
                    mb_strtolower($despacho)
                );
        }

        if (
            $okTermo
            &&
            $okRpi
            &&
            $okDespacho
        ) {

            $resultados[] = $item;
        }
    }
}
?>

<!doctype html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
MarcaFácil | Consulta RPI
</title>

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
    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
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
    align-items:flex-start;
    justify-content:space-between;
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

.search-card,
.result-card,
.empty-state {
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:
        0 2px 10px
        rgba(27,39,65,.03);
}

.search-card {
    padding:22px;
    margin-bottom:24px;
}

.search-card h2 {
    font-size:17px;
    margin-bottom:4px;
}

.search-card > p {
    color:var(--muted);
    font-size:13px;
    margin-bottom:18px;
}

.search-grid {
    display:grid;
    grid-template-columns:
        1.35fr
        .8fr
        .65fr
        1fr
        auto;
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
    color:var(--text);
    outline:none;
}

input:focus,
select:focus {
    border-color:#8fb1f7;
    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.08);
}

.primary {
    border:0;
    background:var(--blue);
    color:#fff;
    border-radius:9px;
    padding:12px 17px;
    font-weight:800;
    cursor:pointer;
    font-size:14px;
}

.primary:hover {
    background:var(--blue-dark);
}

.quick-row {
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:16px;
}

.quick {
    border:1px solid #dfe5ef;
    background:#f9fbff;
    color:#44536c;
    border-radius:999px;
    padding:7px 10px;
    font-size:12px;
    cursor:pointer;
}

.result-head {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-bottom:12px;
}

.result-head h2 {
    font-size:18px;
}

.result-head span {
    color:var(--muted);
    font-size:13px;
}

.results {
    display:grid;
    gap:14px;
}

.result-card {
    padding:20px;
    display:grid;
    grid-template-columns:
        1fr
        auto;
    gap:18px;
}

.result-card h3 {
    font-size:18px;
    margin-bottom:12px;
}

.meta {
    display:grid;
    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );
    gap:12px;
}

.meta-item {
    padding:11px 12px;
    background:#fafbfd;
    border:1px solid #edf0f5;
    border-radius:10px;
}

.meta-item span {
    display:block;
    color:var(--muted);
    font-size:11px;
    margin-bottom:4px;
}

.meta-item strong {
    font-size:13px;
}

.dispatch {
    margin-top:14px;
    padding:13px 14px;
    border-left:
        3px solid #8fb1f7;
    background:#f7faff;
    border-radius:8px;
}

.dispatch span {
    display:block;
    font-size:11px;
    color:var(--muted);
    margin-bottom:4px;
    text-transform:uppercase;
    font-weight:800;
}

.dispatch p {
    font-size:13px;
    line-height:1.5;
}

.result-actions {
    display:flex;
    flex-direction:column;
    justify-content:center;
    gap:8px;
    min-width:140px;
}

.result-actions a {
    display:block;
    text-align:center;
    text-decoration:none;
}

.secondary {
    border:1px solid #d9dfeb;
    background:#fff;
    color:#344054;
    border-radius:8px;
    padding:10px 12px;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
}

.empty-state {
    padding:40px 24px;
    text-align:center;
    color:var(--muted);
}

.empty-state strong {
    display:block;
    color:var(--text);
    margin-bottom:6px;
    font-size:16px;
}

.info {
    margin-top:18px;
    padding:13px 15px;
    background:#f7f9fc;
    border-radius:10px;
    color:var(--muted);
    font-size:12px;
}

@media(max-width:1050px) {

    .search-grid {
        grid-template-columns:
            1fr 1fr;
    }

    .meta {
        grid-template-columns:
            1fr 1fr;
    }
}

@media(max-width:850px) {

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

    .result-card {
        grid-template-columns:1fr;
    }
}

@media(max-width:560px) {

    .search-grid,
    .meta {
        grid-template-columns:1fr;
    }

    .result-actions {
        flex-direction:column;
    }
}

</style>

</head>

<body>

<div class="layout">

<?php
require __DIR__ . '/menu.php';
?>

<main>

<div class="header">

<div>

<h1>
Consulta RPI
</h1>

<p>
Pesquise nas publicações importadas
para localizar processos, marcas,
titulares e despachos.
</p>

</div>

<span class="badge">

<?= count($dados) ?>
publicação(ões) importada(s)

</span>

</div>

<section class="search-card">

<h2>
Pesquisar publicação
</h2>

<p>
Use um ou mais campos para refinar sua pesquisa.
</p>

<form
method="GET"
class="search-grid"
>

<label>

Pesquisar por

<input
type="text"
name="termo"
value="<?= e($termo) ?>"
placeholder="Processo, marca ou titular"
>

</label>

<label>

Tipo de busca

<select name="filtro">

<option
value="todos"
<?= $filtro === 'todos'
    ? 'selected'
    : ''
?>
>
Todos
</option>

<option
value="processo"
<?= $filtro === 'processo'
    ? 'selected'
    : ''
?>
>
Processo
</option>

<option
value="marca"
<?= $filtro === 'marca'
    ? 'selected'
    : ''
?>
>
Marca
</option>

<option
value="titular"
<?= $filtro === 'titular'
    ? 'selected'
    : ''
?>
>
Titular
</option>

</select>

</label>

<label>

RPI

<input
type="text"
name="rpi"
value="<?= e($rpi) ?>"
placeholder="Ex.: 2845"
>

</label>

<label>

Despacho

<input
type="text"
name="despacho"
value="<?= e($despacho) ?>"
placeholder="Ex.: deferimento"
>

</label>

<button
class="primary"
type="submit"
>

Pesquisar

</button>

</form>

<div class="quick-row">

<button
class="quick"
type="button"
data-despacho="prorrogação"
>
Prorrogação
</button>

<button
class="quick"
type="button"
data-despacho="deferimento"
>
Deferimento
</button>

<button
class="quick"
type="button"
data-despacho="oposição"
>
Oposição
</button>

<button
class="quick"
type="button"
data-despacho="registro"
>
Registro
</button>

</div>

<div class="info">

Os dados exibidos nesta tela vêm do arquivo
<strong>dados_rpi.json</strong>.

</div>

</section>

<?php
$pesquisou =
    $termo !== ''
    ||
    $rpi !== ''
    ||
    $despacho !== '';
?>

<?php if ($pesquisou): ?>

<div class="result-head">

<h2>
Resultados encontrados
</h2>

<span>

<?= count($resultados) ?>
resultado(s)

</span>

</div>

<?php if (!$resultados): ?>

<div class="empty-state">

<strong>
Nenhuma publicação encontrada.
</strong>

Tente alterar os filtros da pesquisa.

</div>

<?php else: ?>

<section class="results">

<?php foreach ($resultados as $item): ?>

<?php
$numeroProcesso = preg_replace('/\D+/', '', (string)($item['processo'] ?? ''));
$numeroRpi = trim((string)($item['rpi'] ?? ''));
$edicaoRpi = $edicoesRpi[$numeroRpi] ?? ['local' => '', 'oficial' => '', 'tamanho' => 0];
$linkRpi = $edicaoRpi['local'] !== '' ? $edicaoRpi['local'] : $edicaoRpi['oficial'];
$paginaPdf = max(1, (int)($item['pagina'] ?? 1));
$tamanhoRpi = $edicaoRpi['tamanho'] > 0
    ? number_format($edicaoRpi['tamanho'] / 1048576, 1, ',', '.') . ' MB'
    : '';
?>

<article class="result-card">

<div>

<h3>

<?= e(
    $item['marca']
    ?? 'Marca não informada'
) ?>

</h3>

<div class="meta">

<div class="meta-item">

<span>
Processo
</span>

<strong>

<?= e(
    $item['processo']
    ?? 'Não informado'
) ?>

</strong>

</div>

<div class="meta-item">

<span>
Titular
</span>

<strong>

<?= e(
    $item['titular']
    ?? 'Não informado'
) ?>

</strong>

</div>

<div class="meta-item">

<span>
RPI
</span>

<strong>

<?= e(
    $item['rpi']
    ?? 'Não informado'
) ?>

</strong>

</div>

<div class="meta-item">

<span>
Data / Página
</span>

<strong>

<?= e(
    $item['data']
    ?? '—'
) ?>

· pág.

<?= e(
    $item['pagina']
    ?? '—'
) ?>

</strong>

</div>

</div>

<div class="dispatch">

<span>
Despacho publicado
</span>

<p>

<?= e(
    $item['despacho']
    ?? 'Não informado'
) ?>

</p>

</div>

</div>

<div class="result-actions">

<a
class="secondary"
href="detalhes_processo.php?processo=<?= urlencode($numeroProcesso) ?>"
>

Ver processo

</a>

<a
class="primary"
href="<?= $linkRpi !== '' ? e($linkRpi) . '#page=' . $paginaPdf : 'rpi.php' ?>"
<?= $linkRpi !== '' ? 'target="_blank" rel="noopener"' : '' ?>
title="<?= $tamanhoRpi !== '' ? 'PDF com ' . e($tamanhoRpi) : 'Consultar edição da RPI' ?>"
>

<?= $edicaoRpi['local'] !== '' ? 'Ver PDF salvo' : 'Ver na RPI' ?><?= $tamanhoRpi !== '' ? ' (' . e($tamanhoRpi) . ')' : '' ?>

</a>

</div>

</article>

<?php endforeach; ?>

</section>

<?php endif; ?>

<?php else: ?>

<div class="empty-state">

<strong>
Faça uma pesquisa para começar.
</strong>

Você já possui
<?= count($dados) ?>
publicação(ões) disponível(is)
para consulta.

</div>

<?php endif; ?>

</main>

</div>

<script>

document
.querySelectorAll('.quick')
.forEach(button => {

    button.addEventListener(
        'click',
        () => {

            const input =
                document.querySelector(
                    'input[name="despacho"]'
                );

            input.value =
                button.dataset.despacho;

            input.focus();
        }
    );

});

</script>

</body>

</html>
