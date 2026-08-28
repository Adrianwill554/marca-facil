<?php
$busca = trim($_GET['busca'] ?? '');
$tipo = $_GET['tipo'] ?? 'todos';

$documentos = [
    [
        'nome' => 'Procuração assinada.pdf',
        'descricao' => 'Procuração do cliente',
        'processo' => '930123456',
        'marca' => 'Marca Exemplo',
        'cliente' => 'Empresa Exemplo LTDA',
        'tipo' => 'Procuração',
        'data' => '20/08/2026'
    ],
    [
        'nome' => 'Comprovante protocolo.pdf',
        'descricao' => 'Comprovante de protocolo do pedido',
        'processo' => '921987654',
        'marca' => 'Nova Identidade',
        'cliente' => 'Nova Identidade ME',
        'tipo' => 'Comprovante',
        'data' => '13/08/2026'
    ],
    [
        'nome' => 'Manifestacao oposicao.docx',
        'descricao' => 'Minuta de manifestação',
        'processo' => '902334455',
        'marca' => 'Urban Prime',
        'cliente' => 'Urban Prime Comércio LTDA',
        'tipo' => 'Manifestação',
        'data' => '08/08/2026'
    ],
    [
        'nome' => 'Certificado registro.pdf',
        'descricao' => 'Certificado de registro da marca',
        'processo' => '910456789',
        'marca' => 'Studio Alfa',
        'cliente' => 'Studio Alfa Serviços LTDA',
        'tipo' => 'Certificado',
        'data' => '06/08/2026'
    ]
];

$filtrados = array_filter($documentos, function ($item) use ($busca, $tipo) {
    $texto = mb_strtolower(
        $item['nome'] . ' ' .
        $item['descricao'] . ' ' .
        $item['processo'] . ' ' .
        $item['marca'] . ' ' .
        $item['cliente'] . ' ' .
        $item['tipo']
    );

    $okBusca = $busca === '' || str_contains($texto, mb_strtolower($busca));
    $okTipo = $tipo === 'todos' || mb_strtolower($item['tipo']) === mb_strtolower($tipo);

    return $okBusca && $okTipo;
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
  <title>MarcaFácil | Documentos</title>

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

    .top-actions {
      display:flex;
      gap:10px;
      align-items:center;
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

    .metrics {
      display:grid;
      grid-template-columns:repeat(3,1fr);
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

    .file-cell {
      display:flex;
      align-items:center;
      gap:10px;
    }

    .file-icon {
      width:38px;
      height:38px;
      min-width:38px;
      border-radius:10px;
      display:grid;
      place-items:center;
      background:#eef4ff;
      color:#2452aa;
      font-size:17px;
      font-weight:800;
    }

    .tag {
      display:inline-block;
      font-size:11px;
      font-weight:800;
      padding:5px 8px;
      border-radius:999px;
      color:#2452aa;
      background:#e8f0ff;
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
      width:min(560px,calc(100% - 30px));
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

    .form-grid {
      display:grid;
      gap:12px;
    }

    .modal-actions {
      display:flex;
      justify-content:flex-end;
      gap:8px;
      margin-top:18px;
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
      <a href="prazos.php">◷ &nbsp; Prazos</a>
      <a href="#" class="active">▤ &nbsp; Documentos</a>
      <a href="consulta_rpi.php">⌕ &nbsp; Consulta RPI</a>
      <a href="publicacoes.php">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Documentos</strong>
      Centralize arquivos relacionados aos clientes e processos.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Documentos</h1>
        <p>Organize procurações, comprovantes, certificados e demais arquivos dos processos.</p>
      </div>

      <div class="top-actions">
        <span class="badge">Protótipo sem banco</span>
        <button class="primary" id="novoDocumento">+ Adicionar documento</button>
      </div>
    </div>

    <section class="metrics">
      <div class="metric">
        <p>Total de documentos</p>
        <h2><?= count($documentos) ?></h2>
      </div>

      <div class="metric">
        <p>Processos com arquivos</p>
        <h2><?= count(array_unique(array_column($documentos, 'processo'))) ?></h2>
      </div>

      <div class="metric">
        <p>Tipos de documento</p>
        <h2><?= count(array_unique(array_column($documentos, 'tipo'))) ?></h2>
      </div>
    </section>

    <section class="filters">
      <h2>Filtrar documentos</h2>
      <p>Busque pelo arquivo, processo, marca, cliente ou tipo de documento.</p>

      <form method="GET" class="filter-grid">
        <label>
          Buscar
          <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Arquivo, processo, marca ou cliente"
          >
        </label>

        <label>
          Tipo
          <select name="tipo">
            <option value="todos" <?= $tipo === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="Procuração" <?= $tipo === 'Procuração' ? 'selected' : '' ?>>Procuração</option>
            <option value="Comprovante" <?= $tipo === 'Comprovante' ? 'selected' : '' ?>>Comprovante</option>
            <option value="Manifestação" <?= $tipo === 'Manifestação' ? 'selected' : '' ?>>Manifestação</option>
            <option value="Certificado" <?= $tipo === 'Certificado' ? 'selected' : '' ?>>Certificado</option>
          </select>
        </label>

        <button class="primary" type="submit">Filtrar</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Arquivos cadastrados</h2>
        <span><?= count($filtrados) ?> resultado(s)</span>
      </div>

      <?php if (!$filtrados): ?>
        <div class="empty">
          Nenhum documento encontrado.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Documento</th>
              <th>Processo</th>
              <th>Marca / Cliente</th>
              <th>Tipo</th>
              <th>Adicionado em</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($filtrados as $item): ?>
              <tr>
                <td>
                  <div class="file-cell">
                    <span class="file-icon">▤</span>
                    <div>
                      <strong><?= e($item['nome']) ?></strong>
                      <small><?= e($item['descricao']) ?></small>
                    </div>
                  </div>
                </td>

                <td><?= e($item['processo']) ?></td>

                <td>
                  <strong><?= e($item['marca']) ?></strong>
                  <small><?= e($item['cliente']) ?></small>
                </td>

                <td>
                  <span class="tag"><?= e($item['tipo']) ?></span>
                </td>

                <td><?= e($item['data']) ?></td>

                <td>
                  <button type="button" class="secondary abrir-arquivo">
                    Visualizar
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

<dialog id="documentModal">
  <div class="modal">
    <h2>Adicionar documento</h2>
    <p>Este formulário será conectado ao armazenamento real depois.</p>

    <div class="form-grid">
      <label>
        Processo
        <input type="text" placeholder="Ex.: 930123456">
      </label>

      <label>
        Tipo de documento
        <select>
          <option>Procuração</option>
          <option>Comprovante</option>
          <option>Manifestação</option>
          <option>Certificado</option>
          <option>Outro</option>
        </select>
      </label>

      <label>
        Descrição
        <input type="text" placeholder="Ex.: Procuração assinada pelo cliente">
      </label>

      <label>
        Arquivo
        <input type="file">
      </label>
    </div>

    <div class="modal-actions">
      <button type="button" class="secondary" id="cancelarDocumento">Cancelar</button>
      <button type="button" class="primary" id="salvarDocumento">Adicionar</button>
    </div>
  </div>
</dialog>

<script>
const documentModal = document.querySelector('#documentModal');

document.querySelector('#novoDocumento').addEventListener('click', () => {
  documentModal.showModal();
});

document.querySelector('#cancelarDocumento').addEventListener('click', () => {
  documentModal.close();
});

document.querySelector('#salvarDocumento').addEventListener('click', () => {
  alert('Quando conectarmos o banco, o documento será salvo de verdade.');
});

document.querySelectorAll('.abrir-arquivo').forEach(button => {
  button.addEventListener('click', () => {
    alert('Depois este botão abrirá o arquivo real.');
  });
});
</script>

</body>
</html>
