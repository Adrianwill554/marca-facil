<?php
$busca = trim($_GET['busca'] ?? '');

$clientes = [
    [
        'nome' => 'Empresa Exemplo LTDA',
        'email' => 'contato@empresaexemplo.com.br',
        'telefone' => '(11) 99999-1111',
        'cpf_cnpj' => '12.345.678/0001-90',
        'processos' => 3,
        'ativos' => 2,
        'ultima_marca' => 'Marca Exemplo'
    ],
    [
        'nome' => 'Nova Identidade ME',
        'email' => 'contato@novaidentidade.com.br',
        'telefone' => '(11) 98888-2222',
        'cpf_cnpj' => '23.456.789/0001-10',
        'processos' => 2,
        'ativos' => 2,
        'ultima_marca' => 'Nova Identidade'
    ],
    [
        'nome' => 'Studio Alfa Serviços LTDA',
        'email' => 'studio@alfa.com.br',
        'telefone' => '(11) 97777-3333',
        'cpf_cnpj' => '34.567.890/0001-20',
        'processos' => 4,
        'ativos' => 1,
        'ultima_marca' => 'Studio Alfa'
    ],
    [
        'nome' => 'Urban Prime Comércio LTDA',
        'email' => 'contato@urbanprime.com.br',
        'telefone' => '(11) 96666-4444',
        'cpf_cnpj' => '45.678.901/0001-30',
        'processos' => 1,
        'ativos' => 1,
        'ultima_marca' => 'Urban Prime'
    ]
];

$filtrados = array_filter($clientes, function ($item) use ($busca) {
    if ($busca === '') return true;

    $texto = mb_strtolower(
        $item['nome'] . ' ' .
        $item['email'] . ' ' .
        $item['telefone'] . ' ' .
        $item['cpf_cnpj'] . ' ' .
        $item['ultima_marca']
    );

    return str_contains($texto, mb_strtolower($busca));
});

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function iniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $resultado = '';

    foreach (array_slice($partes, 0, 2) as $parte) {
        $resultado .= mb_strtoupper(mb_substr($parte, 0, 1));
    }

    return $resultado;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MarcaFácil | Clientes</title>

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

    .top-actions {
      display:flex;
      gap:10px;
      align-items:center;
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
    .search-card,
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

    .search-card {
      padding:18px;
      margin-bottom:22px;
    }

    .search-form {
      display:grid;
      grid-template-columns:1fr auto;
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

    input {
      width:100%;
      padding:11px 12px;
      border:1px solid #d5dae5;
      border-radius:9px;
      font-size:14px;
      outline:none;
    }

    input:focus {
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

    .client-cell {
      display:flex;
      align-items:center;
      gap:10px;
    }

    .avatar {
      width:36px;
      height:36px;
      min-width:36px;
      border-radius:50%;
      display:grid;
      place-items:center;
      background:#e8f0ff;
      color:#2452aa;
      font-size:12px;
      font-weight:800;
    }

    .tag {
      display:inline-block;
      font-size:11px;
      font-weight:800;
      padding:5px 8px;
      border-radius:999px;
      color:#126149;
      background:#dff7ed;
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
    }

    .detail-item.full {
      grid-column:1 / -1;
    }

    .modal-actions {
      display:flex;
      justify-content:flex-end;
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
      .search-form,
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

    <div class="menu-title">Gestão</div>

    <nav>
      <a href="index.php">▦ &nbsp; Visão geral</a>
      <a href="#" class="active">♙ &nbsp; Clientes</a>
      <a href="processos.php">◫ &nbsp; Processos</a>
      <a href="consulta_rpi.php">⌕ &nbsp; Consulta RPI</a>
      <a href="publicacoes.php">▤ &nbsp; Publicações</a>
    </nav>

    <div class="side-note">
      <strong style="color:#fff; display:block; margin-bottom:4px;">Clientes</strong>
      Organize os titulares e empresas vinculadas aos processos.
    </div>
  </aside>

  <main>
    <div class="header">
      <div>
        <h1>Clientes</h1>
        <p>Consulte os clientes do escritório e veja rapidamente os processos associados.</p>
      </div>

      <div class="top-actions">
        <span class="badge">Protótipo sem banco</span>
        <button class="primary" id="novoCliente">+ Novo cliente</button>
      </div>
    </div>

    <section class="metrics">
      <div class="metric">
        <p>Total de clientes</p>
        <h2><?= count($clientes) ?></h2>
      </div>

      <div class="metric">
        <p>Processos vinculados</p>
        <h2><?= array_sum(array_column($clientes, 'processos')) ?></h2>
      </div>

      <div class="metric">
        <p>Processos ativos</p>
        <h2><?= array_sum(array_column($clientes, 'ativos')) ?></h2>
      </div>
    </section>

    <section class="search-card">
      <form method="GET" class="search-form">
        <label>
          Buscar cliente
          <input
            type="text"
            name="busca"
            value="<?= e($busca) ?>"
            placeholder="Nome, e-mail, CNPJ, telefone ou marca"
          >
        </label>

        <button class="primary" type="submit">Pesquisar</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Clientes cadastrados</h2>
        <span><?= count($filtrados) ?> resultado(s)</span>
      </div>

      <?php if (!$filtrados): ?>
        <div class="empty">
          Nenhum cliente encontrado.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Contato</th>
              <th>CPF / CNPJ</th>
              <th>Processos</th>
              <th>Última marca</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($filtrados as $item): ?>
              <tr>
                <td>
                  <div class="client-cell">
                    <span class="avatar"><?= e(iniciais($item['nome'])) ?></span>
                    <div>
                      <strong><?= e($item['nome']) ?></strong>
                      <small><?= e($item['email']) ?></small>
                    </div>
                  </div>
                </td>

                <td>
                  <strong><?= e($item['telefone']) ?></strong>
                  <small><?= e($item['email']) ?></small>
                </td>

                <td><?= e($item['cpf_cnpj']) ?></td>

                <td>
                  <span class="tag"><?= e($item['ativos']) ?> ativo(s)</span>
                  <small style="display:block; margin-top:4px;">
                    <?= e($item['processos']) ?> no total
                  </small>
                </td>

                <td><?= e($item['ultima_marca']) ?></td>

                <td>
                  <button
                    type="button"
                    class="secondary detalhes"
                    data-cliente='<?= e(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
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
    <h2 id="modalNome">Detalhes do cliente</h2>
    <p>Informações principais do cliente e seus processos.</p>

    <div class="detail-grid">
      <div class="detail-item">
        <span>E-mail</span>
        <strong id="modalEmail">—</strong>
      </div>

      <div class="detail-item">
        <span>Telefone</span>
        <strong id="modalTelefone">—</strong>
      </div>

      <div class="detail-item">
        <span>CPF / CNPJ</span>
        <strong id="modalDocumento">—</strong>
      </div>

      <div class="detail-item">
        <span>Processos</span>
        <strong id="modalProcessos">—</strong>
      </div>

      <div class="detail-item">
        <span>Processos ativos</span>
        <strong id="modalAtivos">—</strong>
      </div>

      <div class="detail-item">
        <span>Última marca</span>
        <strong id="modalMarca">—</strong>
      </div>
    </div>

    <div class="modal-actions">
      <button type="button" class="primary" id="fecharModal">Fechar</button>
    </div>
  </div>
</dialog>

<dialog id="newClientModal">
  <div class="modal">
    <h2>Novo cliente</h2>
    <p>Este formulário é apenas demonstrativo nesta etapa.</p>

    <div class="detail-grid">
      <label>
        Nome / Razão social
        <input type="text" placeholder="Ex.: Empresa Exemplo LTDA">
      </label>

      <label>
        CPF / CNPJ
        <input type="text" placeholder="00.000.000/0001-00">
      </label>

      <label>
        E-mail
        <input type="email" placeholder="contato@empresa.com">
      </label>

      <label>
        Telefone
        <input type="text" placeholder="(11) 99999-9999">
      </label>
    </div>

    <div class="modal-actions" style="gap:8px;">
      <button type="button" class="secondary" id="cancelarCliente">Cancelar</button>
      <button type="button" class="primary" id="salvarDemo">Salvar cliente</button>
    </div>
  </div>
</dialog>

<script>
const detailModal = document.querySelector('#detailModal');
const newClientModal = document.querySelector('#newClientModal');

document.querySelectorAll('.detalhes').forEach(button => {
  button.addEventListener('click', () => {
    const cliente = JSON.parse(button.dataset.cliente);

    document.querySelector('#modalNome').textContent = cliente.nome;
    document.querySelector('#modalEmail').textContent = cliente.email;
    document.querySelector('#modalTelefone').textContent = cliente.telefone;
    document.querySelector('#modalDocumento').textContent = cliente.cpf_cnpj;
    document.querySelector('#modalProcessos').textContent = cliente.processos;
    document.querySelector('#modalAtivos').textContent = cliente.ativos;
    document.querySelector('#modalMarca').textContent = cliente.ultima_marca;

    detailModal.showModal();
  });
});

document.querySelector('#fecharModal').addEventListener('click', () => {
  detailModal.close();
});

document.querySelector('#novoCliente').addEventListener('click', () => {
  newClientModal.showModal();
});

document.querySelector('#cancelarCliente').addEventListener('click', () => {
  newClientModal.close();
});

document.querySelector('#salvarDemo').addEventListener('click', () => {
  alert('Quando conectarmos o banco, este formulário salvará o cliente de verdade.');
});
</script>

</body>
</html>
