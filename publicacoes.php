<?php
$tipo = $_GET['tipo'] ?? 'todas';
$busca = trim($_GET['busca'] ?? '');
$rpi = trim($_GET['rpi'] ?? '');

$publicacoes = [
    [
        'processo' => '930123456',
        'marca' => 'Marca Exemplo',
        'titular' => 'Empresa Exemplo LTDA',
        'rpi' => '2845',
        'data' => '20/08/2026',
        'tipo' => 'Deferimento',
        'despacho' => 'Deferimento do pedido de registro',
        'pagina' => '154'
    ],
    [
        'processo' => '921987654',
        'marca' => 'Nova Identidade',
        'titular' => 'Comércio Nova Identidade ME',
        'rpi' => '2844',
        'data' => '13/08/2026',
        'tipo' => 'Oposição',
        'despacho' => 'Publicação de pedido de registro para oposição',
        'pagina' => '89'
    ],
    [
        'processo' => '910456789',
        'marca' => 'Studio Alfa',
        'titular' => 'Studio Alfa Serviços LTDA',
        'rpi' => '2843',
        'data' => '06/08/2026',
        'tipo' => 'Prorrogação',
        'despacho' => 'Prorrogação de registro',
        'pagina' => '217'
    ],
    [
        'processo' => '902334455',
        'marca' => 'Urban Prime',
        'titular' => 'Urban Prime Comércio LTDA',
        'rpi' => '2842',
        'data' => '30/07/2026',
        'tipo' => 'Registro',
        'despacho' => 'Concessão de registro de marca',
        'pagina' => '301'
    ]
];

$filtradas = array_filter($publicacoes, function ($item) use ($tipo, $busca, $rpi) {
    $okTipo = $tipo === 'todas' || mb_strtolower($item['tipo']) === mb_strtolower($tipo);

    $texto = mb_strtolower(
        $item['processo'] . ' ' .
        $item['marca'] . ' ' .
        $item['titular'] . ' ' .
        $item['despacho']
    );

    $okBusca = $busca === '' || str_contains($texto, mb_strtolower($busca));
    $okRpi = $rpi === '' || $item['rpi'] === $rpi;

    return $okTipo && $okBusca && $okRpi;
});

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MarcaFácil | Publicações</title>

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

    .filters,
    .panel {
      background:#fff;
      border:1px solid var(--line);
      border-radius:16px;
      box-shadow:0 2px 10px rgba(27,39,65,.03);
    }

    .filters {
      padding:20px;
      margin-bottom:24px;
    }

    .filters h2 {
      font-size:17px;
      margin-bottom:4px;
    }

    .filters > p {
      font-size:13px;
      color:var(--muted);
      margin-bottom:16px;
    }

    .filter-grid {
      display:grid;
      grid-template-columns:1.2fr .8fr .7fr auto;
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
    }

    .primary:hover {
      background:var(--blue-dark);
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

    table {
      width:100%;
      border-collapse:collapse;
    }

    th,
    td {
      text-align:left;
      padding:14px 18px;
      border-top:1px solid var(--line);
      font-size:13px;
      vertical-align:middle;
    }

    th {
      font-size:11px;
      color:var(--muted);
      text-transform:uppercase;
      letter-spacing:.4px;
      background:#fbfcfe;
    }

    td strong {
      display:block;
      font-size:13px;
      margin-bottom:3px;
    }

    td small {
      color:var(--muted);
      font-size:12px;
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

    .actions {
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }

    .secondary {
      border:1px solid #d9dfeb;
      background:#fff;
      color:#344054;
      border-radius:8px;
      padding:8px 10px;
      font-size:12px;
      font-weight:750;
      cursor:pointer;
    }

    .secondary:hover {
      background:#f7f9fc;
    }

    .empty {
      padding:36px 20px;
      text-align:center;
      color:var(--muted);
      font-size:13px;
    }

    @media(max-width:1000px) {
      .filter-grid {
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

      nav {
        display:flex;
        overflow:auto;
      }

      nav a {
        white-space:nowrap;
      }

      .side-note,
      .menu-title {
        display:none;
      }

      main {
        padding:24px 16px;
      }

      .header {
        flex-direction:column;
      }

      .panel {
        overflow:auto;
      }

      table {
        min-width:850px;
      }
    }

    @media(max-width:560px) {
      .filter-grid {
        grid-template-columns:1fr;
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
      <a href="consulta_rpi.php">⌕ &nbsp; Consulta RPI</a>
      <a href="processos.php">◫ &nbsp; Processos</a>
      <a href="#" class="active">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Publicações</strong>
      Acompanhe os principais despachos publicados nas RPIs.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Publicações</h1>
        <p>Visualize e filtre despachos publicados em revistas da propriedade industrial.</p>
      </div>

      <span class="badge">Protótipo sem banco</span>
    </div>

    <section class="filters">
      <h2>Filtrar publicações</h2>
      <p>Refine a lista por marca, processo, titular, tipo ou número da RPI.</p>

      <form method="GET" class="filter-grid">
        <label>
          Buscar
          <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Marca, processo ou titular"
          >
        </label>

        <label>
          Tipo de publicação
          <select name="tipo">
            <option value="todas" <?= $tipo === 'todas' ? 'selected' : '' ?>>Todas</option>
            <option value="Deferimento" <?= $tipo === 'Deferimento' ? 'selected' : '' ?>>Deferimento</option>
            <option value="Oposição" <?= $tipo === 'Oposição' ? 'selected' : '' ?>>Oposição</option>
            <option value="Prorrogação" <?= $tipo === 'Prorrogação' ? 'selected' : '' ?>>Prorrogação</option>
            <option value="Registro" <?= $tipo === 'Registro' ? 'selected' : '' ?>>Registro</option>
          </select>
        </label>

        <label>
          Número da RPI
          <input
            type="text"
            name="rpi"
            value="<?= e($rpi) ?>"
            placeholder="Ex.: 2845"
          >
        </label>

        <button class="primary" type="submit">Filtrar</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Publicações encontradas</h2>
        <span><?= count($filtradas) ?> resultado(s)</span>
      </div>

      <?php if (!$filtradas): ?>
        <div class="empty">
          Nenhuma publicação encontrada com esses filtros.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Marca / Titular</th>
              <th>Processo</th>
              <th>Tipo</th>
              <th>RPI</th>
              <th>Data</th>
              <th>Despacho</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($filtradas as $item): ?>
              <?php
                $classe = 'blue';

                if ($item['tipo'] === 'Deferimento' || $item['tipo'] === 'Registro') {
                    $classe = 'green';
                } elseif ($item['tipo'] === 'Oposição') {
                    $classe = 'amber';
                } elseif ($item['tipo'] === 'Prorrogação') {
                    $classe = 'blue';
                }
              ?>

              <tr>
                <td>
                  <strong><?= e($item['marca']) ?></strong>
                  <small><?= e($item['titular']) ?></small>
                </td>

                <td><?= e($item['processo']) ?></td>

                <td>
                  <span class="tag <?= e($classe) ?>">
                    <?= e($item['tipo']) ?>
                  </span>
                </td>

                <td>
                  <strong><?= e($item['rpi']) ?></strong>
                  <small>Página <?= e($item['pagina']) ?></small>
                </td>

                <td><?= e($item['data']) ?></td>

                <td><?= e($item['despacho']) ?></td>

                <td>
                  <div class="actions">
                    <button class="secondary ver-processo" type="button">
                      Ver processo
                    </button>

                    <button class="primary ver-rpi" type="button">
                      Ver RPI
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</div>

<script>
document.querySelectorAll('.ver-rpi').forEach(button => {
  button.addEventListener('click', () => {
    alert('Depois este botão abrirá a revista e a página exata da publicação.');
  });
});

document.querySelectorAll('.ver-processo').forEach(button => {
  button.addEventListener('click', () => {
    alert('Depois este botão abrirá os detalhes completos do processo.');
  });
});
</script>

</body>
</html>
