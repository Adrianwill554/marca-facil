<?php
$busca = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? 'todos';
$classe = trim($_GET['classe'] ?? '');

$processos = [
    [
        'processo' => '930123456',
        'marca' => 'Marca Exemplo',
        'cliente' => 'Empresa Exemplo LTDA',
        'titular' => 'Empresa Exemplo LTDA',
        'classe' => '35',
        'natureza' => 'Serviço',
        'apresentacao' => 'Mista',
        'status' => 'Deferido',
        'rpi' => '2845',
        'publicacao' => '20/08/2026',
        'prazo' => '19/10/2026',
        'despacho' => 'Deferimento do pedido de registro'
    ],
    [
        'processo' => '921987654',
        'marca' => 'Nova Identidade',
        'cliente' => 'Nova Identidade ME',
        'titular' => 'Comércio Nova Identidade ME',
        'classe' => '25',
        'natureza' => 'Produto',
        'apresentacao' => 'Nominativa',
        'status' => 'Aguardando análise',
        'rpi' => '2844',
        'publicacao' => '13/08/2026',
        'prazo' => '12/10/2026',
        'despacho' => 'Publicação de pedido para oposição'
    ],
    [
        'processo' => '910456789',
        'marca' => 'Studio Alfa',
        'cliente' => 'Studio Alfa Serviços LTDA',
        'titular' => 'Studio Alfa Serviços LTDA',
        'classe' => '41',
        'natureza' => 'Serviço',
        'apresentacao' => 'Mista',
        'status' => 'Registrado',
        'rpi' => '2843',
        'publicacao' => '06/08/2026',
        'prazo' => '06/08/2036',
        'despacho' => 'Prorrogação de registro'
    ],
    [
        'processo' => '902334455',
        'marca' => 'Urban Prime',
        'cliente' => 'Urban Prime Comércio LTDA',
        'titular' => 'Urban Prime Comércio LTDA',
        'classe' => '18',
        'natureza' => 'Produto',
        'apresentacao' => 'Figurativa',
        'status' => 'Exigência',
        'rpi' => '2842',
        'publicacao' => '30/07/2026',
        'prazo' => '29/09/2026',
        'despacho' => 'Exigência formal'
    ]
];

$filtrados = array_filter($processos, function ($item) use ($busca, $status, $classe) {
    $texto = mb_strtolower(
        $item['processo'] . ' ' .
        $item['marca'] . ' ' .
        $item['cliente'] . ' ' .
        $item['titular']
    );

    $okBusca = $busca === '' || str_contains($texto, mb_strtolower($busca));
    $okStatus = $status === 'todos' || mb_strtolower($item['status']) === mb_strtolower($status);
    $okClasse = $classe === '' || $item['classe'] === $classe;

    return $okBusca && $okStatus && $okClasse;
});

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function classeStatus($status) {
    if ($status === 'Registrado' || $status === 'Deferido') {
        return 'green';
    }

    if ($status === 'Exigência') {
        return 'red';
    }

    return 'amber';
}
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
      grid-template-columns:1.4fr .8fr .6fr auto;
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

    dialog {
      border:0;
      border-radius:16px;
      padding:0;
      box-shadow:0 22px 65px rgba(0,0,0,.28);
      width:min(620px,calc(100% - 30px));
    }

    dialog::backdrop {
      background:rgba(15,23,42,.48);
    }

    .modal {
      padding:25px;
    }

    .modal h2 {
      font-size:21px;
    }

    .modal > p {
      color:var(--muted);
      font-size:13px;
      margin:5px 0 18px;
    }

    .detail-grid {
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
    }

    .detail-item {
      border:1px solid #edf0f5;
      background:#fafbfd;
      border-radius:10px;
      padding:12px;
    }

    .detail-item span {
      display:block;
      color:var(--muted);
      font-size:11px;
      margin-bottom:4px;
    }

    .detail-item strong {
      font-size:13px;
      line-height:1.4;
    }

    .detail-item.full {
      grid-column:1 / -1;
    }

    .modal-actions {
      display:flex;
      justify-content:flex-end;
      gap:8px;
      margin-top:18px;
    }

    @media(max-width:1050px) {
      .metrics {
        grid-template-columns:repeat(2,1fr);
      }

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
      .metrics,
      .filter-grid,
      .detail-grid {
        grid-template-columns:1fr;
      }

      .detail-item.full {
        grid-column:auto;
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
      <a href="#" class="active">◫ &nbsp; Processos</a>
      <a href="publicacoes.php">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Processos</strong>
      Acompanhe o andamento das marcas e seus principais prazos.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Processos</h1>
        <p>Centralize os processos de marcas e acompanhe status, RPI e próximos prazos.</p>
      </div>

      <span class="badge">Protótipo sem banco</span>
    </div>

    <section class="metrics">
      <div class="metric">
        <p>Total de processos</p>
        <h2><?= count($processos) ?></h2>
      </div>

      <div class="metric">
        <p>Registrados / deferidos</p>
        <h2><?= count(array_filter($processos, fn($p) => in_array($p['status'], ['Registrado','Deferido'], true))) ?></h2>
      </div>

      <div class="metric">
        <p>Em análise</p>
        <h2><?= count(array_filter($processos, fn($p) => $p['status'] === 'Aguardando análise')) ?></h2>
      </div>

      <div class="metric">
        <p>Com exigência</p>
        <h2><?= count(array_filter($processos, fn($p) => $p['status'] === 'Exigência')) ?></h2>
      </div>
    </section>

    <section class="filters">
      <h2>Filtrar processos</h2>
      <p>Procure por marca, processo, cliente, titular, status ou classe NCL.</p>

      <form method="GET" class="filter-grid">
        <label>
          Buscar
          <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Marca, processo, cliente ou titular"
          >
        </label>

        <label>
          Status
          <select name="status">
            <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="Aguardando análise" <?= $status === 'Aguardando análise' ? 'selected' : '' ?>>Aguardando análise</option>
            <option value="Deferido" <?= $status === 'Deferido' ? 'selected' : '' ?>>Deferido</option>
            <option value="Registrado" <?= $status === 'Registrado' ? 'selected' : '' ?>>Registrado</option>
            <option value="Exigência" <?= $status === 'Exigência' ? 'selected' : '' ?>>Exigência</option>
          </select>
        </label>

        <label>
          Classe NCL
          <input
            type="text"
            name="classe"
            value="<?= e($classe) ?>"
            placeholder="Ex.: 35"
          >
        </label>

        <button class="primary" type="submit">Filtrar</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Processos encontrados</h2>
        <span><?= count($filtrados) ?> resultado(s)</span>
      </div>

      <?php if (!$filtrados): ?>
        <div class="empty">
          Nenhum processo encontrado com esses filtros.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Marca / Cliente</th>
              <th>Processo</th>
              <th>Classe</th>
              <th>Status</th>
              <th>Última RPI</th>
              <th>Próximo prazo</th>
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
                <td><?= e($item['classe']) ?></td>

                <td>
                  <span class="tag <?= e(classeStatus($item['status'])) ?>">
                    <?= e($item['status']) ?>
                  </span>
                </td>

                <td>
                  <strong><?= e($item['rpi']) ?></strong>
                  <small><?= e($item['publicacao']) ?></small>
                </td>

                <td><?= e($item['prazo']) ?></td>

                <td>
                  <button
                    type="button"
                    class="secondary detalhes"
                    data-processo='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                  >
                    Detalhes
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</div>

<dialog id="detailModal">
  <div class="modal">
    <h2 id="modalMarca">Detalhes do processo</h2>
    <p id="modalCliente">Informações principais do processo.</p>

    <div class="detail-grid">
      <div class="detail-item">
        <span>Processo</span>
        <strong id="modalProcesso">—</strong>
      </div>

      <div class="detail-item">
        <span>Titular</span>
        <strong id="modalTitular">—</strong>
      </div>

      <div class="detail-item">
        <span>Classe NCL</span>
        <strong id="modalClasse">—</strong>
      </div>

      <div class="detail-item">
        <span>Natureza</span>
        <strong id="modalNatureza">—</strong>
      </div>

      <div class="detail-item">
        <span>Apresentação</span>
        <strong id="modalApresentacao">—</strong>
      </div>

      <div class="detail-item">
        <span>Status</span>
        <strong id="modalStatus">—</strong>
      </div>

      <div class="detail-item">
        <span>Última RPI</span>
        <strong id="modalRpi">—</strong>
      </div>

      <div class="detail-item">
        <span>Próximo prazo</span>
        <strong id="modalPrazo">—</strong>
      </div>

      <div class="detail-item full">
        <span>Último despacho</span>
        <strong id="modalDespacho">—</strong>
      </div>
    </div>

    <div class="modal-actions">
      <button type="button" class="secondary" id="fecharModal">Fechar</button>
      <button type="button" class="primary" id="verPublicacao">Ver publicação</button>
    </div>
  </div>
</dialog>

<script>
const modal = document.querySelector('#detailModal');

document.querySelectorAll('.detalhes').forEach(button => {
  button.addEventListener('click', () => {
    const processo = JSON.parse(button.dataset.processo);

    document.querySelector('#modalMarca').textContent = processo.marca;
    document.querySelector('#modalCliente').textContent = processo.cliente;
    document.querySelector('#modalProcesso').textContent = processo.processo;
    document.querySelector('#modalTitular').textContent = processo.titular;
    document.querySelector('#modalClasse').textContent = processo.classe;
    document.querySelector('#modalNatureza').textContent = processo.natureza;
    document.querySelector('#modalApresentacao').textContent = processo.apresentacao;
    document.querySelector('#modalStatus').textContent = processo.status;
    document.querySelector('#modalRpi').textContent = processo.rpi + ' · ' + processo.publicacao;
    document.querySelector('#modalPrazo').textContent = processo.prazo;
    document.querySelector('#modalDespacho').textContent = processo.despacho;

    modal.showModal();
  });
});

document.querySelector('#fecharModal').addEventListener('click', () => {
  modal.close();
});

document.querySelector('#verPublicacao').addEventListener('click', () => {
  alert('Depois este botão abrirá a publicação correspondente na RPI.');
});
</script>

</body>
</html>
