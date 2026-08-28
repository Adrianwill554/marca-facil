<?php
$dados = [
    'clientes' => 4,
    'processos' => 10,
    'prazos_urgentes' => 2,
    'publicacoes_recentes' => 4,
    'documentos' => 4
];

$processosRecentes = [
    [
        'marca' => 'Marca Exemplo',
        'cliente' => 'Empresa Exemplo LTDA',
        'processo' => '930123456',
        'status' => 'Deferido',
        'rpi' => '2845'
    ],
    [
        'marca' => 'Nova Identidade',
        'cliente' => 'Nova Identidade ME',
        'processo' => '921987654',
        'status' => 'Aguardando análise',
        'rpi' => '2844'
    ],
    [
        'marca' => 'Studio Alfa',
        'cliente' => 'Studio Alfa Serviços LTDA',
        'processo' => '910456789',
        'status' => 'Registrado',
        'rpi' => '2843'
    ],
    [
        'marca' => 'Urban Prime',
        'cliente' => 'Urban Prime Comércio LTDA',
        'processo' => '902334455',
        'status' => 'Exigência',
        'rpi' => '2842'
    ]
];

$prazos = [
    [
        'marca' => 'Studio Beta',
        'cliente' => 'Studio Beta LTDA',
        'tipo' => 'Manifestação',
        'vencimento' => '31/08/2026',
        'dias' => 3,
        'status' => 'Urgente'
    ],
    [
        'marca' => 'Prime Solutions',
        'cliente' => 'Prime Solutions LTDA',
        'tipo' => 'Recurso',
        'vencimento' => '26/08/2026',
        'dias' => -2,
        'status' => 'Atrasado'
    ],
    [
        'marca' => 'Urban Prime',
        'cliente' => 'Urban Prime Comércio LTDA',
        'tipo' => 'Cumprimento de exigência',
        'vencimento' => '29/09/2026',
        'dias' => 32,
        'status' => 'Atenção'
    ]
];

$publicacoes = [
    [
        'marca' => 'Marca Exemplo',
        'tipo' => 'Deferimento',
        'rpi' => '2845',
        'data' => '20/08/2026'
    ],
    [
        'marca' => 'Nova Identidade',
        'tipo' => 'Oposição',
        'rpi' => '2844',
        'data' => '13/08/2026'
    ],
    [
        'marca' => 'Studio Alfa',
        'tipo' => 'Prorrogação',
        'rpi' => '2843',
        'data' => '06/08/2026'
    ]
];

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function classeStatus($status) {
    if ($status === 'Registrado' || $status === 'Deferido') return 'green';
    if ($status === 'Exigência' || $status === 'Atrasado') return 'red';
    if ($status === 'Urgente' || $status === 'Atenção' || $status === 'Aguardando análise') return 'amber';
    return 'blue';
}
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
      grid-template-columns:repeat(5,1fr);
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
      grid-template-columns:1.45fr 1fr;
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

    .tag.amber {
      color:#9a5a00;
      background:#fff1d8;
    }

    .tag.red {
      color:#a6283a;
      background:#ffeaed;
    }

    .tag.blue {
      color:#2452aa;
      background:#e8f0ff;
    }

    .deadline-list {
      padding:0 20px 10px;
    }

    .deadline-item {
      display:flex;
      gap:12px;
      padding:15px 0;
      border-top:1px solid var(--line);
      align-items:center;
    }

    .date-box {
      width:52px;
      min-width:52px;
      text-align:center;
      background:#eef4ff;
      color:#2452aa;
      border-radius:10px;
      padding:7px 5px;
      font-size:11px;
      font-weight:800;
    }

    .date-box strong {
      display:block;
      font-size:18px;
      line-height:1;
      margin-bottom:4px;
    }

    .deadline-info {
      flex:1;
    }

    .deadline-info strong {
      display:block;
      font-size:13px;
      margin-bottom:3px;
    }

    .deadline-info span {
      color:var(--muted);
      font-size:12px;
    }

    .pub-list {
      padding:0 20px 10px;
    }

    .pub-item {
      display:flex;
      align-items:center;
      gap:12px;
      padding:14px 0;
      border-top:1px solid var(--line);
    }

    .pub-main {
      flex:1;
    }

    .pub-main strong {
      display:block;
      font-size:13px;
      margin-bottom:3px;
    }

    .pub-main span {
      color:var(--muted);
      font-size:12px;
    }

    @media(max-width:1150px) {
      .metrics {
        grid-template-columns:repeat(3,1fr);
      }

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

    @media(max-width:620px) {
      .metrics,
      .quick-actions {
        grid-template-columns:1fr;
      }

      .panel {
        overflow:auto;
      }

      table {
        min-width:650px;
      }
    }
  </style>
</head>

<body>

<div class="layout">
  <aside>
    <div class="brand">Marca<span>Fácil</span></div>

    <div class="menu-title">Visão geral</div>

    <nav>
      <a href="#" class="active">▦ &nbsp; Dashboard</a>
      <a href="clientes.php">♙ &nbsp; Clientes</a>
      <a href="processos.php">◫ &nbsp; Processos</a>
      <a href="prazos.php">◷ &nbsp; Prazos</a>
      <a href="documentos.php">▤ &nbsp; Documentos</a>
      <a href="consulta_rpi.php">⌕ &nbsp; Consulta RPI</a>
      <a href="publicacoes.php">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">MarcaFácil</strong>
      Acompanhe processos, publicações e prazos do escritório em um só lugar.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Dashboard</h1>
        <p>Resumo geral dos processos de marcas, prazos, clientes e publicações.</p>
      </div>

      <span class="badge">Protótipo sem banco</span>
    </div>

    <section class="metrics">
      <div class="metric">
        <p>Clientes</p>
        <h2><?= e($dados['clientes']) ?></h2>
      </div>

      <div class="metric">
        <p>Processos</p>
        <h2><?= e($dados['processos']) ?></h2>
      </div>

      <div class="metric">
        <p>Prazos urgentes</p>
        <h2><?= e($dados['prazos_urgentes']) ?></h2>
      </div>

      <div class="metric">
        <p>Publicações recentes</p>
        <h2><?= e($dados['publicacoes_recentes']) ?></h2>
      </div>

      <div class="metric">
        <p>Documentos</p>
        <h2><?= e($dados['documentos']) ?></h2>
      </div>
    </section>

    <section class="quick-actions">
      <a class="quick-card" href="consulta_rpi.php">
        <div class="quick-icon">⌕</div>
        <strong>Consultar RPI</strong>
        <span>Pesquise processo, marca, titular ou despacho.</span>
      </a>

      <a class="quick-card" href="processos.php">
        <div class="quick-icon">◫</div>
        <strong>Ver processos</strong>
        <span>Acompanhe status, classe NCL e últimas publicações.</span>
      </a>

      <a class="quick-card" href="prazos.php">
        <div class="quick-icon">◷</div>
        <strong>Conferir prazos</strong>
        <span>Veja vencimentos e identifique ações urgentes.</span>
      </a>

      <a class="quick-card" href="publicacoes.php">
        <div class="quick-icon">▤</div>
        <strong>Publicações</strong>
        <span>Consulte deferimentos, oposições e prorrogações.</span>
      </a>
    </section>

    <section class="grid">
      <div class="panel">
        <div class="panel-head">
          <h2>Processos recentes</h2>
          <a href="processos.php">Ver todos</a>
        </div>

        <table>
          <thead>
            <tr>
              <th>Marca / Cliente</th>
              <th>Processo</th>
              <th>RPI</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($processosRecentes as $item): ?>
              <tr>
                <td>
                  <strong><?= e($item['marca']) ?></strong>
                  <small><?= e($item['cliente']) ?></small>
                </td>

                <td><?= e($item['processo']) ?></td>
                <td><?= e($item['rpi']) ?></td>

                <td>
                  <span class="tag <?= e(classeStatus($item['status'])) ?>">
                    <?= e($item['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Próximos prazos</h2>
          <a href="prazos.php">Ver agenda</a>
        </div>

        <div class="deadline-list">
          <?php foreach ($prazos as $item): ?>
            <?php
              [$dia, $mes, $ano] = explode('/', $item['vencimento']);
            ?>

            <div class="deadline-item">
              <div class="date-box">
                <strong><?= e($dia) ?></strong>
                <?= e($mes) ?>/<?= e(substr($ano, -2)) ?>
              </div>

              <div class="deadline-info">
                <strong><?= e($item['tipo']) ?> — <?= e($item['marca']) ?></strong>
                <span><?= e($item['cliente']) ?></span>
              </div>

              <span class="tag <?= e(classeStatus($item['status'])) ?>">
                <?= e($item['status']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Publicações recentes</h2>
        <a href="publicacoes.php">Ver todas</a>
      </div>

      <div class="pub-list">
        <?php foreach ($publicacoes as $item): ?>
          <div class="pub-item">
            <div class="pub-main">
              <strong><?= e($item['marca']) ?></strong>
              <span>RPI <?= e($item['rpi']) ?> · <?= e($item['data']) ?></span>
            </div>

            <span class="tag blue"><?= e($item['tipo']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</div>

</body>
</html>
