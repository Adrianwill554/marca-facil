<?php
session_start();

$paginaAtual = 'importar_rpi';

$erro = '';
$sucesso = '';
$preview = $_SESSION['preview_rpi'] ?? [];

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$arquivoDados = __DIR__ . '/dados_rpi.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? 'ler';

    if ($acao === 'ler') {

        if (
            !isset($_FILES['arquivo']) ||
            $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK
        ) {
            $erro = 'Selecione um arquivo CSV válido.';
        } else {

            $arquivo = $_FILES['arquivo'];

            if (
                strtolower(
                    pathinfo(
                        $arquivo['name'],
                        PATHINFO_EXTENSION
                    )
                ) !== 'csv'
            ) {
                $erro =
                    'Por enquanto, a importação aceita apenas arquivos CSV.';
            } else {

                $conteudo =
                    file_get_contents(
                        $arquivo['tmp_name']
                    );

                if ($conteudo === false) {

                    $erro =
                        'Não foi possível ler o arquivo enviado.';

                } else {

                    $primeiraLinha =
                        strtok(
                            $conteudo,
                            "\n"
                        );

                    $delimitador =
                        substr_count(
                            $primeiraLinha,
                            ';'
                        )
                        >=
                        substr_count(
                            $primeiraLinha,
                            ','
                        )
                        ? ';'
                        : ',';

                    $handle =
                        fopen(
                            'php://temp',
                            'r+'
                        );

                    fwrite(
                        $handle,
                        $conteudo
                    );

                    rewind($handle);

                    $cabecalho =
                        fgetcsv(
                            $handle,
                            0,
                            $delimitador
                        );

                    if (!$cabecalho) {

                        $erro =
                            'O arquivo está vazio ou sem cabeçalho.';

                    } else {

                        $cabecalho =
                            array_map(
                                function ($valor) {

                                    $valor =
                                        trim(
                                            (string)$valor
                                        );

                                    $valor =
                                        preg_replace(
                                            '/^\xEF\xBB\xBF/',
                                            '',
                                            $valor
                                        );

                                    return mb_strtolower(
                                        $valor
                                    );

                                },
                                $cabecalho
                            );

                        $obrigatorias = [
                            'processo',
                            'marca',
                            'titular',
                            'rpi',
                            'data',
                            'despacho',
                            'pagina'
                        ];

                        $faltando =
                            array_diff(
                                $obrigatorias,
                                $cabecalho
                            );

                        if ($faltando) {

                            $erro =
                                'Faltam colunas obrigatórias: '
                                .
                                implode(
                                    ', ',
                                    $faltando
                                );

                        } else {

                            $indices =
                                array_flip(
                                    $cabecalho
                                );

                            $preview = [];

                            while (
                                (
                                    $linha =
                                    fgetcsv(
                                        $handle,
                                        0,
                                        $delimitador
                                    )
                                ) !== false
                            ) {

                                if (
                                    count(
                                        array_filter(
                                            $linha,
                                            fn($v) =>
                                            trim(
                                                (string)$v
                                            ) !== ''
                                        )
                                    ) === 0
                                ) {
                                    continue;
                                }

                                $preview[] = [

                                    'processo' =>
                                        $linha[
                                            $indices[
                                                'processo'
                                            ]
                                        ] ?? '',

                                    'marca' =>
                                        $linha[
                                            $indices[
                                                'marca'
                                            ]
                                        ] ?? '',

                                    'titular' =>
                                        $linha[
                                            $indices[
                                                'titular'
                                            ]
                                        ] ?? '',

                                    'rpi' =>
                                        $linha[
                                            $indices[
                                                'rpi'
                                            ]
                                        ] ?? '',

                                    'data' =>
                                        $linha[
                                            $indices[
                                                'data'
                                            ]
                                        ] ?? '',

                                    'despacho' =>
                                        $linha[
                                            $indices[
                                                'despacho'
                                            ]
                                        ] ?? '',

                                    'pagina' =>
                                        $linha[
                                            $indices[
                                                'pagina'
                                            ]
                                        ] ?? ''
                                ];
                            }

                            $_SESSION['preview_rpi'] =
                                $preview;

                            if ($preview) {

                                $sucesso =
                                    'Arquivo lido com sucesso. Confira os dados abaixo antes de confirmar.';

                            } else {

                                $erro =
                                    'Nenhum registro foi encontrado no CSV.';
                            }
                        }
                    }

                    fclose($handle);
                }
            }
        }
    }

    if ($acao === 'confirmar') {

        $preview =
            $_SESSION['preview_rpi']
            ?? [];

        if (!$preview) {

            $erro =
                'Não há dados para importar.';

        } else {

            $dadosExistentes = [];

            if (
                file_exists(
                    $arquivoDados
                )
            ) {

                $conteudoAtual =
                    file_get_contents(
                        $arquivoDados
                    );

                $dadosExistentes =
                    json_decode(
                        $conteudoAtual,
                        true
                    ) ?? [];
            }

            foreach (
                $preview
                as $novoRegistro
            ) {

                $duplicado = false;

                foreach (
                    $dadosExistentes
                    as $existente
                ) {

                    if (
                        $existente['processo']
                        ===
                        $novoRegistro['processo']
                        &&
                        $existente['rpi']
                        ===
                        $novoRegistro['rpi']
                        &&
                        $existente['despacho']
                        ===
                        $novoRegistro['despacho']
                    ) {

                        $duplicado = true;
                        break;
                    }
                }

                if (!$duplicado) {

                    $novoRegistro[
                        'importado_em'
                    ] =
                        date(
                            'Y-m-d H:i:s'
                        );

                    $dadosExistentes[] =
                        $novoRegistro;
                }
            }

            file_put_contents(
                $arquivoDados,
                json_encode(
                    $dadosExistentes,
                    JSON_PRETTY_PRINT
                    |
                    JSON_UNESCAPED_UNICODE
                )
            );

            unset(
                $_SESSION[
                    'preview_rpi'
                ]
            );

            $preview = [];

            $sucesso =
                'Importação confirmada com sucesso!';
        }
    }

    if ($acao === 'cancelar') {

        unset(
            $_SESSION[
                'preview_rpi'
            ]
        );

        $preview = [];

        $sucesso =
            'Importação cancelada.';
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
MarcaFácil | Importar RPI
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
    justify-content:space-between;
    gap:20px;
    align-items:flex-start;
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

.panel {
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:
        0 2px 10px
        rgba(27,39,65,.03);
    margin-bottom:22px;
    overflow:hidden;
}

.upload {
    padding:22px;
}

.drop {
    border:1.5px dashed #cdd6e6;
    border-radius:14px;
    padding:28px;
    background:#fafcff;
    text-align:center;
}

.drop input {
    margin:15px 0;
}

.primary {
    border:0;
    background:var(--blue);
    color:#fff;
    border-radius:9px;
    padding:12px 17px;
    font-weight:800;
    cursor:pointer;
}

.primary:hover {
    background:var(--blue-dark);
}

.secondary {
    border:1px solid #d5dae5;
    background:#fff;
    color:#344054;
    border-radius:9px;
    padding:11px 15px;
    font-weight:700;
    cursor:pointer;
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

.table-wrap {
    overflow:auto;
}

table {
    width:100%;
    border-collapse:collapse;
    min-width:950px;
}

th,
td {
    padding:13px 16px;
    text-align:left;
    border-top:1px solid var(--line);
    font-size:12px;
}

th {
    color:var(--muted);
    text-transform:uppercase;
    font-size:10px;
    background:#fbfcfe;
}

td strong {
    display:block;
}

.confirm-actions {
    padding:20px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    border-top:1px solid var(--line);
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
Importar RPI
</h1>

<p>
Carregue um CSV e confira os dados antes
de confirmar a importação.
</p>

</div>

<span class="badge">
Importação de publicações
</span>

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

<section class="panel upload">

<h2>
Selecionar arquivo CSV
</h2>

<br>

<form
method="POST"
enctype="multipart/form-data"
>

<input
type="hidden"
name="acao"
value="ler"
>

<div class="drop">

<strong>
Arquivo da RPI
</strong>

<br>

<input
type="file"
name="arquivo"
accept=".csv"
required
>

<br>

<button
class="primary"
type="submit"
>

Ler arquivo

</button>

</div>

</form>

</section>

<?php if ($preview): ?>

<section class="panel">

<div class="panel-head">

<h2>
Prévia da importação
</h2>

<span>

<?= count($preview) ?>
linha(s)

</span>

</div>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>Processo</th>
<th>Marca</th>
<th>Titular</th>
<th>RPI</th>
<th>Data</th>
<th>Despacho</th>
<th>Página</th>

</tr>

</thead>

<tbody>

<?php foreach ($preview as $item): ?>

<tr>

<td>
<?= e($item['processo']) ?>
</td>

<td>
<strong>
<?= e($item['marca']) ?>
</strong>
</td>

<td>
<?= e($item['titular']) ?>
</td>

<td>
<?= e($item['rpi']) ?>
</td>

<td>
<?= e($item['data']) ?>
</td>

<td>
<?= e($item['despacho']) ?>
</td>

<td>
<?= e($item['pagina']) ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="confirm-actions">

<form method="POST">

<input
type="hidden"
name="acao"
value="cancelar"
>

<button
class="secondary"
type="submit"
>

Cancelar

</button>

</form>

<form method="POST">

<input
type="hidden"
name="acao"
value="confirmar"
>

<button
class="primary"
type="submit"
>

Confirmar importação

</button>

</form>

</div>

</section>

<?php endif; ?>

</main>

</div>

</body>

</html>