<?php
$busca = trim($_GET['busca'] ?? '');
$situacao = $_GET['situacao'] ?? 'todos';

$prazos = [
    [
        'marca' => 'Marca Exemplo',
        'cliente' => 'Empresa Exemplo LTDA',
        'processo' => '930123456',
        'tipo' => 'Pagamento de concessão',
        'vencimento' => '19/10/2026',
        'dias' => 52,
        'status' => 'No prazo'
    ],
    [
        'marca' => 'Nova Identidade',
        'cliente' => 'Nova Identidade ME',
        'processo' => '921987654',
        'tipo' => 'Prazo para oposição',
        'vencimento' => '12/10/2026',
        'dias' => 45,
        'status' => 'No prazo'
    ],
    [
        'marca' => 'Urban Prime',
        'cliente' => 'Urban Prime Comércio LTDA',
        'processo' => '902334455',
        'tipo' => 'Cumprimento de exigência',
        'vencimento' => '29/09/2026',
        'dias' => 32,
        'status' => 'Atenção'
    ],
    [
        'marca' => 'Studio Beta',
        'cliente' => 'Studio Beta LTDA',
        'processo' => '900112233',
        'tipo' => 'Manifestação',
        'vencimento' => '31/08/2026',
        'dias' => 3,
        'status' => 'Urgente'
    ],
    [
        'marca' => 'Prime Solutions',
        'cliente' => 'Prime Solutions LTDA',
        'processo' => '899445566',
        'tipo' => 'Recurso',
        'vencimento' => '26/08/2026',
        'dias' => -2,
        'status' => 'Atrasado'
    ]
];

$filtrados = array_filter($prazos, function ($item) use ($busca, $situacao) {
    $texto = mb_strtolower(
        $item['marca'] . ' ' .
        $item['cliente'] . ' ' .
        $item['processo'] . ' ' .
        $item['tipo']
    );

    $okBusca = $busca === '' || str_contains($texto, mb_strtolower($busca));
    $okSituacao = $situacao === 'todos' || mb_strtolower($item['status']) === mb_strtolower($situacao);

    return $okBusca && $okSituacao;
});

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function classeStatus($status) {
    return match ($status) {
        'No prazo' => 'green',
        'Atenção' => 'amber',
        'Urgente' => 'red',
        'Atrasado' => 'darkred',
        default => 'blue'
    };
}
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
      --darkred:#7f1d1d;
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

    .tag.amber {
      color:#9a5a00;
      background:#fff1d8;
    }

    .tag.red {
      color:#a6283a;
      background:#ffeaed;
    }

    .tag.darkred {
      color:#7f1d1d;
      background:#fee2e2;
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

    .timeline {
      margin-top:22px;
      display:grid;
      gap:12px;
    }

    .timeline-item {
      background:#fff;
      border:1px solid var(--line);
      border-radius:14px;
      padding:16px;
      display:flex;
      gap:14px;
      align-items:center;
    }

    .date-box {
      width:58px;
      min-width:58px;
      text-align:center;
      border-radius:10px;
      background:#eef4ff;
      color:#2452aa;
      padding:8px 6px;
      font-size:11px;
      font-weight:800;
    }

    .date-box strong {
      display:block;
      font-size:20px;
      line-height:1;
      margin-bottom:4px;
    }

    .timeline-content {
      flex:1;
    }

    .timeline-content strong {
      display:block;
      margin-bottom:3px;
      font-size:14px;
    }

    .timeline-content span {
      color:var(--muted);
      font-size:12px;
    }

    @media(max-width:1050px) {
      .metrics {
        grid-template-columns:repeat(2,1fr);
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

      .panel {
        overflow:auto;
      }

      table {
        min-width:850px;
      }
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
  <aside>
    <div class="brand">Marca<span>Fácil</span></div>

    <div class="menu-title">Gestão</div>

    <nav>
      <a href="index.php">▦ &nbsp; Visão geral</a>
      <a href="clientes.php">♙ &nbsp; Clientes</a>
      <a href="processos.php">◫ &nbsp; Processos</a>
      <a href="#" class="active">◷ &nbsp; Prazos</a>
      <a href="consulta_rpi.php">⌕ &nbsp; Consulta RPI</a>
      <a href="publicacoes.php">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Prazos</strong>
      Acompanhe vencimentos e priorize as ações mais urgentes.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Prazos</h1>
        <p>Visualize os próximos vencimentos dos processos e identifique rapidamente o que exige atenção.</p>
      </div>

      <span class="badge">Protótipo sem banco</span>
    </div>

    <section class="metrics">
      <div class="metric">
        <p>Total de prazos</p>
        <h2><?= count($prazos) ?></h2>
      </div>

      <div class="metric">
        <p>Urgentes</p>
        <h2><?= count(array_filter($prazos, fn($p) => $p['status'] === 'Urgente')) ?></h2>
      </div>

      <div class="metric">
        <p>Atrasados</p>
        <h2><?= count(array_filter($prazos, fn($p) => $p['status'] === 'Atrasado')) ?></h2>
      </div>

      <div class="metric">
        <p>No prazo</p>
        <h2><?= count(array_filter($prazos, fn($p) => in_array($p['status'], ['No prazo', 'Atenção'], true))) ?></h2>
      </div>
    </section>

    <section class="filters">
      <h2>Filtrar prazos</h2>
      <p>Busque por processo, marca, cliente ou tipo de prazo.</p>

      <form method="GET" class="filter-grid">
        <label>
          Buscar
          <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Marca, processo, cliente ou prazo"
          >
        </label>

        <label>
          Situação
          <select name="situacao">
            <option value="todos" <?= $situacao === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="No prazo" <?= $situacao === 'No prazo' ? 'selected' : '' ?>>No prazo</option>
            <option value="Atenção" <?= $situacao === 'Atenção' ? 'selected' : '' ?>>Atenção</option>
            <option value="Urgente" <?= $situacao === 'Urgente' ? 'selected' : '' ?>>Urgente</option>
            <option value="Atrasado" <?= $situacao === 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
          </select>
        </label>

        <button class="primary" type="submit">Filtrar</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Agenda de prazos</h2>
        <span><?= count($filtrados) ?> resultado(s)</span>
      </div>

      <?php if (!$filtrados): ?>
        <div class="empty">
          Nenhum prazo encontrado com esses filtros.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Marca / Cliente</th>
              <th>Processo</th>
              <th>Tipo de prazo</th>
              <th>Vencimento</th>
              <th>Tempo restante</th>
              <th>Situação</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($filtrados as $item): ?>
              <tr>
                <td>
                  <strong><?= e($item['marca']) ?></strong>
                  <small><?= e($item['cliente']) ?></small>
                </td>

                <td><?= e($item['processo']) ?></td>
                <td><?= e($item['tipo']) ?></td>
                <td><?= e($item['vencimento']) ?></td>

                <td>
                  <?php if ($item['dias'] < 0): ?>
                    <?= abs($item['dias']) ?> dia(s) atrasado
                  <?php elseif ($item['dias'] === 0): ?>
                    Vence hoje
                  <?php else: ?>
                    <?= e($item['dias']) ?> dia(s)
                  <?php endif; ?>
                </td>

                <td>
                  <span class="tag <?= e(classeStatus($item['status'])) ?>">
                    <?= e($item['status']) ?>
                  </span>
                </td>

                <td>
                  <button type="button" class="secondary ver-processo">
                    Ver processo
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <section class="timeline">
      <?php
        $proximos = array_values(array_filter($prazos, fn($p) => $p['dias'] >= 0));
        usort($proximos, fn($a, $b) => $a['dias'] <=> $b['dias']);
      ?>

      <?php foreach (array_slice($proximos, 0, 3) as $item): ?>
        <?php
          [$dia, $mes, $ano] = explode('/', $item['vencimento']);
        ?>

        <div class="timeline-item">
          <div class="date-box">
            <strong><?= e($dia) ?></strong>
            <?= e($mes) ?>/<?= e(substr($ano, -2)) ?>
          </div>

          <div class="timeline-content">
            <strong><?= e($item['tipo']) ?> — <?= e($item['marca']) ?></strong>
            <span><?= e($item['cliente']) ?> · Processo <?= e($item['processo']) ?></span>
          </div>

          <span class="tag <?= e(classeStatus($item['status'])) ?>">
            <?= e($item['status']) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </section>
  </main>
</div>

<script>
document.querySelectorAll('.ver-processo').forEach(button => {
  button.addEventListener('click', () => {
    alert('Depois este botão abrirá o processo correspondente.');
  });
});
</script>

</body>
</html>
