<?php
$termo = trim($_GET['termo'] ?? '');
$filtro = $_GET['filtro'] ?? 'todos';
$rpi = trim($_GET['rpi'] ?? '');
$despacho = trim($_GET['despacho'] ?? '');

$resultados = [];

if ($termo !== '' || $rpi !== '' || $despacho !== '') {
    // Dados demonstrativos por enquanto.
    // Depois esta parte será substituída pela consulta real ao INPI/RPI.
    $base = [
        [
            'processo' => '930123456',
            'marca' => 'Marca Exemplo',
            'titular' => 'Empresa Exemplo LTDA',
            'rpi' => '2845',
            'data' => '20/08/2026',
            'despacho' => 'Deferimento do pedido de registro',
            'pagina' => '154',
            'status' => 'Deferido'
        ],
        [
            'processo' => '921987654',
            'marca' => 'Nova Identidade',
            'titular' => 'Comércio Nova Identidade ME',
            'rpi' => '2844',
            'data' => '13/08/2026',
            'despacho' => 'Publicação de pedido de registro para oposição',
            'pagina' => '89',
            'status' => 'Publicado'
        ],
        [
            'processo' => '910456789',
            'marca' => 'Studio Alfa',
            'titular' => 'Studio Alfa Serviços LTDA',
            'rpi' => '2843',
            'data' => '06/08/2026',
            'despacho' => 'Prorrogação de registro',
            'pagina' => '217',
            'status' => 'Registrado'
        ]
    ];

    foreach ($base as $item) {
        $texto = mb_strtolower(
            $item['processo'] . ' ' .
            $item['marca'] . ' ' .
            $item['titular'] . ' ' .
            $item['rpi'] . ' ' .
            $item['despacho']
        );

        $okTermo = $termo === '' || str_contains($texto, mb_strtolower($termo));
        $okRpi = $rpi === '' || $item['rpi'] === $rpi;
        $okDespacho = $despacho === '' || str_contains(mb_strtolower($item['despacho']), mb_strtolower($despacho));

        if ($filtro === 'processo' && $termo !== '') {
            $okTermo = str_contains(mb_strtolower($item['processo']), mb_strtolower($termo));
        } elseif ($filtro === 'marca' && $termo !== '') {
            $okTermo = str_contains(mb_strtolower($item['marca']), mb_strtolower($termo));
        } elseif ($filtro === 'titular' && $termo !== '') {
            $okTermo = str_contains(mb_strtolower($item['titular']), mb_strtolower($termo));
        }

        if ($okTermo && $okRpi && $okDespacho) {
            $resultados[] = $item;
        }
    }
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MarcaFácil | Consulta RPI</title>
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
      --white:#ffffff;
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
      max-width:720px;
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
      box-shadow:0 2px 10px rgba(27,39,65,.03);
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
      grid-template-columns:1.35fr .8fr .65fr 1fr auto;
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
      box-shadow:0 0 0 3px rgba(37,99,235,.08);
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
      min-height:42px;
    }

    .primary:hover {
      background:var(--blue-dark);
    }

    .helper-row {
      display:flex;
      gap:8px;
      flex-wrap:wrap;
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

    .quick:hover {
      background:#eef4ff;
      color:#234ea7;
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
      grid-template-columns:1fr auto;
      gap:18px;
    }

    .result-top {
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:10px;
    }

    .result-card h3 {
      font-size:18px;
    }

    .tag {
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

    .meta {
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:12px;
      margin-top:14px;
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
      line-height:1.35;
    }

    .dispatch {
      margin-top:14px;
      padding:13px 14px;
      border-left:3px solid #8fb1f7;
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
      letter-spacing:.4px;
    }

    .dispatch p {
      font-size:13px;
      line-height:1.5;
    }

    .result-actions {
      display:flex;
      flex-direction:column;
      gap:8px;
      justify-content:center;
      min-width:150px;
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

    .secondary:hover {
      background:#f7f9fc;
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

    .hint-box {
      margin-top:18px;
      padding:14px 16px;
      border-radius:12px;
      background:#fff9eb;
      border:1px solid #f3e2b8;
      color:#7a5a18;
      font-size:12px;
      line-height:1.5;
    }

    @media(max-width:1050px) {
      .search-grid {
        grid-template-columns:1fr 1fr;
      }

      .search-grid .primary {
        width:100%;
      }

      .meta {
        grid-template-columns:1fr 1fr;
      }
    }

    @media(max-width:850px) {
      .layout {
        grid-template-columns:1fr;
      }

      aside {
        padding:15px;
      }

      .side-note,
      .menu-title {
        display:none;
      }

      nav {
        display:flex;
        overflow:auto;
      }

      nav a {
        white-space:nowrap;
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

      .result-actions {
        flex-direction:row;
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
  <aside>
    <div class="brand">Marca<span>Fácil</span></div>

    <div class="menu-title">Consultas</div>

    <nav>
      <a href="index.php">▦ &nbsp; Visão geral</a>
      <a href="#" class="active">⌕ &nbsp; Consulta RPI</a>
      <a href="#">◫ &nbsp; Processos</a>
      <a href="#">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Consulta RPI</strong>
      Pesquise processos, marcas, titulares e publicações em um só lugar.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Consulta RPI</h1>
        <p>
          Pesquise ocorrências de processos e publicações da Revista da Propriedade Industrial.
        </p>
      </div>

      <span class="badge">Protótipo sem banco</span>
    </div>

    <section class="search-card">
      <h2>Pesquisar publicação</h2>
      <p>Use um ou mais campos para refinar a busca.</p>

      <form method="GET" class="search-grid">
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
            <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="processo" <?= $filtro === 'processo' ? 'selected' : '' ?>>Processo</option>
            <option value="marca" <?= $filtro === 'marca' ? 'selected' : '' ?>>Marca</option>
            <option value="titular" <?= $filtro === 'titular' ? 'selected' : '' ?>>Titular</option>
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
            placeholder="Ex.: prorrogação"
          >
        </label>

        <button class="primary" type="submit">Pesquisar</button>
      </form>

      <div class="helper-row">
        <button type="button" class="quick" data-fill="prorrogação">Prorrogação</button>
        <button type="button" class="quick" data-fill="deferimento">Deferimento</button>
        <button type="button" class="quick" data-fill="oposição">Oposição</button>
        <button type="button" class="quick" data-fill="registro">Registro</button>
      </div>

      <div class="hint-box">
        Nesta versão, os resultados abaixo são demonstrativos. Depois conectamos esta tela à fonte real das RPIs do INPI.
      </div>
    </section>

    <?php if ($termo !== '' || $rpi !== '' || $despacho !== ''): ?>
      <div class="result-head">
        <h2>Resultados encontrados</h2>
        <span><?= count($resultados) ?> resultado(s)</span>
      </div>

      <?php if (!$resultados): ?>
        <div class="empty-state">
          <strong>Nenhum resultado encontrado.</strong>
          Tente outro número de processo, marca, titular, RPI ou tipo de despacho.
        </div>
      <?php else: ?>
        <section class="results">
          <?php foreach ($resultados as $item): ?>
            <article class="result-card">
              <div>
                <div class="result-top">
                  <h3><?= e($item['marca']) ?></h3>

                  <?php
                    $classeTag = 'blue';
                    if ($item['status'] === 'Deferido' || $item['status'] === 'Registrado') {
                        $classeTag = 'green';
                    } elseif ($item['status'] === 'Publicado') {
                        $classeTag = 'amber';
                    }
                  ?>

                  <span class="tag <?= e($classeTag) ?>">
                    <?= e($item['status']) ?>
                  </span>
                </div>

                <div class="meta">
                  <div class="meta-item">
                    <span>Processo</span>
                    <strong><?= e($item['processo']) ?></strong>
                  </div>

                  <div class="meta-item">
                    <span>Titular</span>
                    <strong><?= e($item['titular']) ?></strong>
                  </div>

                  <div class="meta-item">
                    <span>RPI</span>
                    <strong><?= e($item['rpi']) ?> · <?= e($item['data']) ?></strong>
                  </div>

                  <div class="meta-item">
                    <span>Página</span>
                    <strong><?= e($item['pagina']) ?></strong>
                  </div>
                </div>

                <div class="dispatch">
                  <span>Despacho publicado</span>
                  <p><?= e($item['despacho']) ?></p>
                </div>
              </div>

              <div class="result-actions">
                <button type="button" class="secondary">Ver processo</button>
                <button type="button" class="primary demo-rpi">Ver na RPI</button>
              </div>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <strong>Faça uma pesquisa para começar.</strong>
        Você pode procurar por número de processo, nome da marca, titular, número da RPI ou despacho.
      </div>
    <?php endif; ?>
  </main>
</div>

<script>
document.querySelectorAll('.quick').forEach(button => {
  button.addEventListener('click', () => {
    const input = document.querySelector('input[name="despacho"]');
    input.value = button.dataset.fill;
    input.focus();
  });
});

document.querySelectorAll('.demo-rpi').forEach(button => {
  button.addEventListener('click', () => {
    alert('Na próxima etapa, este botão abrirá a página correspondente da RPI.');
  });
});
</script>

</body>
</html>
