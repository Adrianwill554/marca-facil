<?php

$paginaAtual = $paginaAtual ?? '';

$menuItens = [
    ['id' => 'busca', 'href' => 'busca.php', 'icone' => '⌕', 'texto' => 'Busca geral'],
    ['id' => 'dashboard', 'href' => 'dashboard.php', 'icone' => '⌂', 'texto' => 'Dashboard'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icone' => '♙', 'texto' => 'Clientes'],
    ['id' => 'processos', 'href' => 'processos.php', 'icone' => '▣', 'texto' => 'Processos'],
    ['id' => 'prazos', 'href' => 'prazos.php', 'icone' => '◷', 'texto' => 'Prazos'],
    ['id' => 'sugestoes', 'href' => 'sugestoes_prazos.php', 'icone' => '!', 'texto' => 'Sugestões de prazos'],
    ['id' => 'documentos', 'href' => 'documentos.php', 'icone' => '▤', 'texto' => 'Documentos'],
    ['id' => 'consulta_rpi', 'href' => 'consulta_rpi.php', 'icone' => '⌕', 'texto' => 'Consulta RPI'],
    ['id' => 'publicacoes', 'href' => 'publicacoes.php', 'icone' => '◫', 'texto' => 'Publicações'],
    ['id' => 'importar_rpi', 'href' => 'importar_rpi.php', 'icone' => '⇧', 'texto' => 'Importar RPI'],
    ['id' => 'rpi', 'href' => 'rpi.php', 'icone' => 'R', 'texto' => 'Revista RPI'],
    ['id' => 'backup', 'href' => 'backup.php', 'icone' => '⇩', 'texto' => 'Backups'],
];

?>

<style>
html {
    overflow-x:clip;
}

body {
    overflow-x:clip;
}

.layout {
    width:100%;
    min-width:0;
    align-items:start;
}

.layout > main {
    width:100%;
    min-width:0;
    margin:0 auto !important;
    align-self:start;
    padding-top:34px;
    padding-bottom:24px;
}

.layout > main > :last-child {
    margin-bottom:0 !important;
}

.layout > main .empty,
.layout > main .empty-state,
.layout > main .no-process {
    min-height:0 !important;
    padding:20px 24px !important;
    margin-bottom:0 !important;
}

body:has(.layout > main .empty) .layout > main,
body:has(.layout > main .empty-state) .layout > main,
body:has(.layout > main .no-process) .layout > main {
    padding-top:24px;
    padding-bottom:12px;
}

body:has(.layout > main .empty) .layout > main > .header,
body:has(.layout > main .empty-state) .layout > main > .header {
    margin-bottom:18px;
}

body:has(.layout > main .empty) .metrics,
body:has(.layout > main .empty-state) .metrics,
body:has(.layout > main .empty) .info,
body:has(.layout > main .empty-state) .info {
    margin-bottom:16px;
}

body:has(.layout > main .empty) .filters,
body:has(.layout > main .empty-state) .filters,
body:has(.layout > main .empty) .top-actions,
body:has(.layout > main .empty-state) .top-actions,
body:has(.layout > main .empty) .actions-top,
body:has(.layout > main .empty-state) .actions-top {
    margin-bottom:16px;
}

aside.menu-lateral {
    position:sticky;
    top:0;
    align-self:start;
    height:100vh;
    max-height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    padding-top:18px;
    padding-bottom:18px;
}

aside.menu-lateral .brand { padding-bottom:18px; }
aside.menu-lateral nav { gap:2px; }
aside.menu-lateral nav a { padding-top:9px; padding-bottom:9px; }
aside.menu-lateral .side-note { margin-top:16px; padding:11px; }

aside.menu-lateral nav a {
    display:flex;
    align-items:center;
    gap:10px;
}

.menu-icon {
    display:inline-grid;
    place-items:center;
    width:22px;
    min-width:22px;
    height:22px;
    font-size:16px;
}

.menu-search { margin:0 0 14px; }
.menu-search input {
    width:100%;
    padding:10px 11px;
    border:1px solid #334155;
    border-radius:9px;
    background:#1f2937;
    color:#fff;
    outline:none;
}
.menu-search input::placeholder { color:#9aa8bc; }
.menu-search input:focus { border-color:#78a5ff; box-shadow:0 0 0 3px rgba(120,165,255,.14); }

.table-wrap,
.panel,
.card {
    max-width:100%;
    min-width:0;
}

.table-wrap {
    overflow-x:auto;
    overscroll-behavior-inline:contain;
}

table {
    max-width:100%;
}

img,
video,
canvas,
iframe {
    max-width:100%;
}

input,
select,
textarea,
button {
    max-width:100%;
}

.header,
.panel-head,
.card-head,
.actions,
.top-actions,
.actions-top {
    min-width:0;
}

h1,
h2,
h3,
p,
td,
th {
    overflow-wrap:anywhere;
}

@media(max-width:800px) {
    .layout {
        grid-template-columns:minmax(0,1fr) !important;
    }

    .layout > main {
        padding:24px 16px !important;
    }

    aside.menu-lateral {
        display:flex;
        position:static;
        width:100%;
        height:auto;
        max-height:none;
        overflow:visible;
        padding:15px;
    }

    aside.menu-lateral .brand {
        padding:0 12px 14px;
    }

    aside.menu-lateral nav {
        display:flex;
        overflow-x:auto;
        padding-bottom:4px;
    }

    aside.menu-lateral nav a {
        flex:0 0 auto;
        white-space:nowrap;
    }

    aside.menu-lateral .menu-title,
    aside.menu-lateral .side-note {
        display:none;
    }

    .header,
    .panel-head,
    .card-head {
        flex-wrap:wrap;
    }
}
</style>

<aside class="menu-lateral">
    <div class="brand">Marca<span>Fácil</span></div>

    <div class="menu-title">Navegação</div>

    <form action="busca.php" method="get" class="menu-search" role="search">
        <input type="search" name="q" value="<?= htmlspecialchars((string)($paginaAtual === 'busca' ? ($_GET['q'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar tudo..." aria-label="Buscar no sistema">
    </form>

    <nav aria-label="Navegação principal">
        <?php foreach ($menuItens as $item): ?>
            <a
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                class="<?= $paginaAtual === $item['id'] ? 'active' : '' ?>"
                <?= $paginaAtual === $item['id'] ? 'aria-current="page"' : '' ?>
            >
                <span class="menu-icon" aria-hidden="true"><?= htmlspecialchars($item['icone'], ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="side-note">
        <strong style="color:#fff;display:block;margin-bottom:4px;">MarcaFácil</strong>
        Gestão de marcas, processos, publicações e prazos em um só lugar.
    </div>
</aside>
