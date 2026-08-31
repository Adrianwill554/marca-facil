<?php
session_start();

$paginaAtual = 'clientes';

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

$arquivoClientes = __DIR__ . '/clientes.json';
$arquivoDados = __DIR__ . '/dados_rpi.json';
$arquivoVinculos = __DIR__ . '/vinculos_processos.json';

if (!file_exists($arquivoClientes)) {
    file_put_contents(
        $arquivoClientes,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

$clientes = [];
$publicacoes = [];
$vinculos = [];

$conteudo = file_get_contents($arquivoClientes);

if ($conteudo !== false) {
    $clientes = json_decode($conteudo, true);

    if (!is_array($clientes)) {
        $clientes = [];
    }
}

if (file_exists($arquivoDados)) {
    $conteudo = file_get_contents($arquivoDados);

    if ($conteudo !== false) {
        $publicacoes = json_decode($conteudo, true);

        if (!is_array($publicacoes)) {
            $publicacoes = [];
        }
    }
}

if (file_exists($arquivoVinculos)) {
    $conteudo = file_get_contents($arquivoVinculos);

    if ($conteudo !== false) {
        $vinculos = json_decode($conteudo, true);

        if (!is_array($vinculos)) {
            $vinculos = [];
        }
    }
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {

        $nome = trim($_POST['nome'] ?? '');
        $cpfCnpj = trim($_POST['cpf_cnpj'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($nome === '') {
            $erro = 'Informe o nome do cliente.';
        } else {

            $novoId = 1;

            if ($clientes) {
                $ids = array_map(
                    fn($cliente) => (int)($cliente['id'] ?? 0),
                    $clientes
                );

                $novoId = max($ids) + 1;
            }

            $clientes[] = [
                'id' => $novoId,
                'nome' => $nome,
                'cpf_cnpj' => $cpfCnpj,
                'email' => $email,
                'telefone' => $telefone,
                'observacoes' => $observacoes,
                'criado_em' => date('Y-m-d H:i:s')
            ];

            $salvou = file_put_contents(
                $arquivoClientes,
                json_encode(
                    $clientes,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                LOCK_EX
            );

            if ($salvou === false) {
                $erro = 'Não foi possível salvar o cliente.';
            } else {
                $sucesso = 'Cliente cadastrado com sucesso!';
            }
        }
    }

    if ($acao === 'excluir') {

        $id = (int)($_POST['id'] ?? 0);

        $temProcessos = false;

        foreach ($vinculos as $processo => $clienteId) {
            if ((int)$clienteId === $id) {
                $temProcessos = true;
                break;
            }
        }

        if ($temProcessos) {
            $erro = 'Este cliente possui processo(s) vinculado(s). Remova os vínculos antes de excluir.';
        } else {

            $clientes = array_values(
                array_filter(
                    $clientes,
                    fn($cliente) => (int)($cliente['id'] ?? 0) !== $id
                )
            );

            $salvou = file_put_contents(
                $arquivoClientes,
                json_encode(
                    $clientes,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                LOCK_EX
            );

            if ($salvou === false) {
                $erro = 'Não foi possível excluir o cliente.';
            } else {
                $sucesso = 'Cliente excluído com sucesso!';
            }
        }
    }
}

/*
 * Monta o resumo atual de cada processo a partir das publicações da RPI.
 */
$processosAgrupados = [];

foreach ($publicacoes as $item) {

    $numero = trim((string)($item['processo'] ?? ''));

    if ($numero === '') {
        continue;
    }

    $timestamp = dataParaTimestamp($item['data'] ?? '');

    if (!isset($processosAgrupados[$numero])) {

        $processosAgrupados[$numero] = [
            'processo' => $numero,
            'marca' => $item['marca'] ?? 'Marca não informada',
            'titular' => $item['titular'] ?? 'Titular não informado',
            'rpi' => $item['rpi'] ?? '',
            'data' => $item['data'] ?? '',
            'pagina' => $item['pagina'] ?? '',
            'despacho' => $item['despacho'] ?? '',
            'tipo' => detectarTipo($item['despacho'] ?? ''),
            'total_publicacoes' => 1,
            '_timestamp' => $timestamp
        ];

    } else {

        $processosAgrupados[$numero]['total_publicacoes']++;

        if ($timestamp >= $processosAgrupados[$numero]['_timestamp']) {

            $processosAgrupados[$numero]['marca'] =
                $item['marca'] ?? $processosAgrupados[$numero]['marca'];

            $processosAgrupados[$numero]['titular'] =
                $item['titular'] ?? $processosAgrupados[$numero]['titular'];

            $processosAgrupados[$numero]['rpi'] = $item['rpi'] ?? '';
            $processosAgrupados[$numero]['data'] = $item['data'] ?? '';
            $processosAgrupados[$numero]['pagina'] = $item['pagina'] ?? '';
            $processosAgrupados[$numero]['despacho'] = $item['despacho'] ?? '';
            $processosAgrupados[$numero]['tipo'] =
                detectarTipo($item['despacho'] ?? '');

            $processosAgrupados[$numero]['_timestamp'] = $timestamp;
        }
    }
}

/*
 * Separa os processos por cliente usando vinculos_processos.json.
 */
$processosPorCliente = [];

foreach ($vinculos as $numeroProcesso => $clienteId) {

    $clienteId = (int)$clienteId;

    if (
        $clienteId > 0
        &&
        isset($processosAgrupados[$numeroProcesso])
    ) {
        $processosPorCliente[$clienteId][] =
            $processosAgrupados[$numeroProcesso];
    }
}

foreach ($processosPorCliente as &$lista) {
    usort(
        $lista,
        fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']
    );
}
unset($lista);

$busca = trim($_GET['busca'] ?? '');

$clientesFiltrados = array_filter(
    $clientes,
    function ($cliente) use ($busca) {

        if ($busca === '') {
            return true;
        }

        $texto = mb_strtolower(
            ($cliente['nome'] ?? '')
            . ' '
            . ($cliente['cpf_cnpj'] ?? '')
            . ' '
            . ($cliente['email'] ?? '')
            . ' '
            . ($cliente['telefone'] ?? '')
        );

        return str_contains(
            $texto,
            mb_strtolower($busca)
        );
    }
);
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
}

.actions-top {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:22px;
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
    text-decoration:none;
    display:inline-block;
}

.primary:hover {
    background:var(--blue-dark);
}

.secondary {
    border:1px solid #d9dfeb;
    background:#fff;
    color:#344054;
    border-radius:8px;
    padding:9px 11px;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.danger {
    border:1px solid #f3c5cc;
    background:#fff5f6;
    color:#a6283a;
    border-radius:8px;
    padding:9px 11px;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
}

.panel {
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:0 2px 10px rgba(27,39,65,.03);
    overflow:hidden;
}

.filters {
    padding:20px;
    border-bottom:1px solid var(--line);
}

.filters form {
    display:grid;
    grid-template-columns:1fr auto;
    gap:10px;
}

input,
textarea {
    width:100%;
    padding:11px 12px;
    border:1px solid #d5dae5;
    border-radius:9px;
    font-size:14px;
    background:#fff;
    outline:none;
}

input:focus,
textarea:focus {
    border-color:#8fb1f7;
    box-shadow:0 0 0 3px rgba(37,99,235,.08);
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

.table-wrap {
    overflow:auto;
}

table {
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

th,
td {
    padding:14px 18px;
    border-top:1px solid var(--line);
    text-align:left;
    font-size:13px;
    vertical-align:middle;
}

th {
    background:#fbfcfe;
    color:var(--muted);
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.4px;
}

td strong {
    display:block;
    margin-bottom:3px;
}

td small {
    color:var(--muted);
}

.message {
    margin-bottom:18px;
    padding:13px 15px;
    border-radius:10px;
    font-size:13px;
    font-weight:650;
}

.message.success {
    color:#126149;
    background:#dff7ed;
}

.message.error {
    color:#a6283a;
    background:#ffeaed;
}

.empty {
    padding:40px 24px;
    text-align:center;
    color:var(--muted);
    font-size:13px;
}

dialog {
    border:0;
    border-radius:16px;
    padding:0;
    width:min(860px,calc(100% - 30px));
    box-shadow:0 22px 65px rgba(0,0,0,.28);
}

dialog::backdrop {
    background:rgba(15,23,42,.48);
}

.modal {
    padding:24px;
}

.modal h2 {
    font-size:21px;
    margin-bottom:5px;
}

.modal > p {
    color:var(--muted);
    font-size:13px;
    margin-bottom:18px;
}

.form-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

label {
    display:grid;
    gap:6px;
    font-size:12px;
    font-weight:750;
    color:#344054;
}

.full {
    grid-column:1 / -1;
}

.modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:18px;
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
    line-height:1.45;
}

.detail-item.full {
    grid-column:1 / -1;
}

.row-actions {
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

.processos-title {
    margin-top:22px;
    padding-top:18px;
    border-top:1px solid var(--line);
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:center;
}

.processos-title h3 {
    font-size:16px;
}

.processos-title span {
    color:var(--muted);
    font-size:12px;
}

.process-list {
    display:grid;
    gap:10px;
    margin-top:12px;
    max-height:350px;
    overflow:auto;
}

.process-card {
    border:1px solid #e7eaf0;
    border-radius:11px;
    padding:13px;
    display:grid;
    grid-template-columns:1fr auto;
    gap:12px;
    background:#fbfcfe;
}

.process-card strong {
    display:block;
    font-size:13px;
    margin-bottom:4px;
}

.process-card p {
    color:var(--muted);
    font-size:12px;
    line-height:1.45;
    margin:0;
}

.process-card .meta {
    margin-top:6px;
    color:#4b5563;
    font-size:11px;
}

.process-card .actions {
    display:flex;
    flex-direction:column;
    gap:6px;
    justify-content:center;
}

.no-process {
    padding:20px;
    text-align:center;
    border:1px dashed #d9dfeb;
    border-radius:10px;
    color:var(--muted);
    font-size:12px;
}

.process-count {
    display:inline-block;
    margin-top:4px;
    color:#2452aa;
    background:#eaf1ff;
    border-radius:999px;
    padding:4px 7px;
    font-size:10px;
    font-weight:800;
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
}

@media(max-width:600px) {
    .filters form,
    .form-grid,
    .detail-grid {
        grid-template-columns:1fr;
    }

    .full,
    .detail-item.full {
        grid-column:auto;
    }

    .process-card {
        grid-template-columns:1fr;
    }

    .process-card .actions {
        flex-direction:row;
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
        <h1>Clientes</h1>
        <p>Cadastre clientes e consulte os processos vinculados a cada um.</p>
    </div>

    <span class="badge"><?= count($clientes) ?> cliente(s)</span>
</div>

<?php if ($sucesso): ?>
<div class="message success">
    <?= e($sucesso) ?>
</div>
<?php endif; ?>

<?php if ($erro): ?>
<div class="message error">
    <?= e($erro) ?>
</div>
<?php endif; ?>

<div class="actions-top">
    <button
        type="button"
        class="primary"
        id="abrirNovoCliente"
    >
        + Novo cliente
    </button>
</div>

<section class="panel">

    <div class="filters">

        <form method="GET">

            <input
                type="text"
                name="busca"
                value="<?= e($busca) ?>"
                placeholder="Buscar por nome, CPF/CNPJ, e-mail ou telefone"
            >

            <button
                type="submit"
                class="primary"
            >
                Buscar
            </button>

        </form>

    </div>

    <div class="panel-head">

        <h2>Clientes cadastrados</h2>

        <span>
            <?= count($clientesFiltrados) ?> resultado(s)
        </span>

    </div>

    <?php if (!$clientesFiltrados): ?>

        <div class="empty">
            Nenhum cliente encontrado.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>CPF / CNPJ</th>
                        <th>Contato</th>
                        <th>Processos</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($clientesFiltrados as $cliente): ?>

                    <?php
                    $clienteId = (int)($cliente['id'] ?? 0);
                    $processosCliente = $processosPorCliente[$clienteId] ?? [];
                    ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($cliente['nome'] ?? '') ?>
                            </strong>

                            <small>
                                ID #<?= $clienteId ?>
                            </small>
                        </td>

                        <td>
                            <?= e(
                                ($cliente['cpf_cnpj'] ?? '') !== ''
                                ? $cliente['cpf_cnpj']
                                : 'Não informado'
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= e(
                                    ($cliente['email'] ?? '') !== ''
                                    ? $cliente['email']
                                    : 'Sem e-mail'
                                ) ?>
                            </strong>

                            <small>
                                <?= e(
                                    ($cliente['telefone'] ?? '') !== ''
                                    ? $cliente['telefone']
                                    : 'Sem telefone'
                                ) ?>
                            </small>
                        </td>

                        <td>
                            <strong>
                                <?= count($processosCliente) ?>
                            </strong>

                            <small>
                                processo(s) vinculado(s)
                            </small>
                        </td>

                        <td>

                            <div class="row-actions">

                                <button
                                    type="button"
                                    class="secondary detalhes"
                                    data-cliente='<?= e(
                                        json_encode(
                                            [
                                                'cliente' => $cliente,
                                                'processos' => $processosCliente
                                            ],
                                            JSON_UNESCAPED_UNICODE |
                                            JSON_UNESCAPED_SLASHES
                                        )
                                    ) ?>'
                                >
                                    Detalhes
                                </button>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');"
                                    style="display:inline;"
                                >

                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="excluir"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $clienteId ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="danger"
                                    >
                                        Excluir
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

</main>

</div>

<dialog id="novoClienteModal">

<div class="modal">

    <h2>Novo cliente</h2>

    <p>Preencha os dados principais do cliente.</p>

    <form method="POST">

        <input type="hidden" name="acao" value="salvar">

        <div class="form-grid">

            <label class="full">
                Nome / Razão social

                <input
                    type="text"
                    name="nome"
                    required
                >
            </label>

            <label>
                CPF / CNPJ

                <input
                    type="text"
                    name="cpf_cnpj"
                    placeholder="Opcional"
                >
            </label>

            <label>
                Telefone

                <input
                    type="text"
                    name="telefone"
                    placeholder="Opcional"
                >
            </label>

            <label class="full">
                E-mail

                <input
                    type="email"
                    name="email"
                    placeholder="Opcional"
                >
            </label>

            <label class="full">
                Observações

                <textarea
                    name="observacoes"
                    rows="4"
                    placeholder="Informações adicionais do cliente"
                ></textarea>
            </label>

        </div>

        <div class="modal-actions">

            <button
                type="button"
                class="secondary"
                id="cancelarNovoCliente"
            >
                Cancelar
            </button>

            <button
                type="submit"
                class="primary"
            >
                Salvar cliente
            </button>

        </div>

    </form>

</div>

</dialog>

<dialog id="detalhesModal">

<div class="modal">

    <h2 id="detalheNome">
        Detalhes do cliente
    </h2>

    <p id="detalheSubtitulo">
        Informações cadastradas.
    </p>

    <div class="detail-grid">

        <div class="detail-item">
            <span>ID</span>
            <strong id="detalheId">—</strong>
        </div>

        <div class="detail-item">
            <span>CPF / CNPJ</span>
            <strong id="detalheDocumento">—</strong>
        </div>

        <div class="detail-item">
            <span>E-mail</span>
            <strong id="detalheEmail">—</strong>
        </div>

        <div class="detail-item">
            <span>Telefone</span>
            <strong id="detalheTelefone">—</strong>
        </div>

        <div class="detail-item full">
            <span>Observações</span>
            <strong id="detalheObservacoes">—</strong>
        </div>

    </div>

    <div class="processos-title">
        <h3>Processos vinculados</h3>
        <span id="detalheTotalProcessos">0 processo(s)</span>
    </div>

    <div
        class="process-list"
        id="listaProcessos"
    ></div>

    <div class="modal-actions">

        <button
            type="button"
            class="primary"
            id="fecharDetalhes"
        >
            Fechar
        </button>

    </div>

</div>

</dialog>

<script>
const novoClienteModal =
    document.querySelector('#novoClienteModal');

const detalhesModal =
    document.querySelector('#detalhesModal');

document
.querySelector('#abrirNovoCliente')
.addEventListener(
    'click',
    () => novoClienteModal.showModal()
);

document
.querySelector('#cancelarNovoCliente')
.addEventListener(
    'click',
    () => novoClienteModal.close()
);

document
.querySelectorAll('.detalhes')
.forEach(button => {

    button.addEventListener(
        'click',
        () => {

            const dados =
                JSON.parse(
                    button.dataset.cliente
                );

            const cliente = dados.cliente;
            const processos = dados.processos || [];

            document
            .querySelector('#detalheNome')
            .textContent =
                cliente.nome
                || 'Detalhes do cliente';

            document
            .querySelector('#detalheSubtitulo')
            .textContent =
                processos.length
                + ' processo(s) vinculado(s)';

            document
            .querySelector('#detalheId')
            .textContent =
                cliente.id
                || '—';

            document
            .querySelector('#detalheDocumento')
            .textContent =
                cliente.cpf_cnpj
                || 'Não informado';

            document
            .querySelector('#detalheEmail')
            .textContent =
                cliente.email
                || 'Não informado';

            document
            .querySelector('#detalheTelefone')
            .textContent =
                cliente.telefone
                || 'Não informado';

            document
            .querySelector('#detalheObservacoes')
            .textContent =
                cliente.observacoes
                || 'Nenhuma observação';

            document
            .querySelector('#detalheTotalProcessos')
            .textContent =
                processos.length
                + ' processo(s)';

            const lista =
                document.querySelector(
                    '#listaProcessos'
                );

            lista.innerHTML = '';

            if (processos.length === 0) {

                lista.innerHTML =
                    '<div class="no-process">'
                    + 'Nenhum processo vinculado a este cliente.'
                    + '</div>';

            } else {

                processos.forEach(
                    processo => {

                        const card =
                            document.createElement(
                                'div'
                            );

                        card.className =
                            'process-card';

                        const info =
                            document.createElement(
                                'div'
                            );

                        const titulo =
                            document.createElement(
                                'strong'
                            );

                        titulo.textContent =
                            processo.marca
                            || 'Marca não informada';

                        const linha =
                            document.createElement(
                                'p'
                            );

                        linha.textContent =
                            'Processo '
                            + (processo.processo || '—')
                            + ' · RPI '
                            + (processo.rpi || '—')
                            + ' · '
                            + (processo.data || 'Sem data');

                        const despacho =
                            document.createElement(
                                'p'
                            );

                        despacho.className =
                            'meta';

                        despacho.textContent =
                            processo.despacho
                            || 'Sem despacho informado';

                        info.appendChild(titulo);
                        info.appendChild(linha);
                        info.appendChild(despacho);

                        const actions =
                            document.createElement(
                                'div'
                            );

                        actions.className =
                            'actions';

                        const historico =
                            document.createElement(
                                'a'
                            );

                        historico.className =
                            'secondary';

                        historico.textContent =
                            'Ver histórico';

                        historico.href =
                            'consulta_rpi.php?termo='
                            + encodeURIComponent(
                                processo.processo
                                || ''
                            )
                            + '&filtro=processo';

                        const publicacoes =
                            document.createElement(
                                'a'
                            );

                        publicacoes.className =
                            'primary';

                        publicacoes.textContent =
                            'Publicações';

                        publicacoes.href =
                            'publicacoes.php?busca='
                            + encodeURIComponent(
                                processo.processo
                                || ''
                            );

                        actions.appendChild(
                            historico
                        );

                        actions.appendChild(
                            publicacoes
                        );

                        card.appendChild(info);
                        card.appendChild(actions);

                        lista.appendChild(card);
                    }
                );
            }

            detalhesModal.showModal();
        }
    );
});

document
.querySelector('#fecharDetalhes')
.addEventListener(
    'click',
    () => detalhesModal.close()
);
</script>

</body>
</html>
