<?php
$paginaAtual = 'processos';
$numeroProcesso = preg_replace('/\D+/', '', (string)($_GET['processo'] ?? ''));
$publicacoes = [];
$edicoesRpi = [];
$mensagem = '';
$erro = '';

function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$arquivoComplementos = __DIR__ . '/processos_detalhes.json';
$complementos = is_file($arquivoComplementos)
    ? json_decode((string)file_get_contents($arquivoComplementos), true)
    : [];
if (!is_array($complementos)) $complementos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $numeroProcesso !== '') {
    $detalhes = [
        'apresentacao' => trim((string)($_POST['apresentacao'] ?? '')),
        'classes_nice' => trim((string)($_POST['classes_nice'] ?? '')),
        'data_deposito' => trim((string)($_POST['data_deposito'] ?? '')),
        'data_concessao' => trim((string)($_POST['data_concessao'] ?? '')),
        'data_renovacao' => trim((string)($_POST['data_renovacao'] ?? '')),
        'status_interno' => trim((string)($_POST['status_interno'] ?? 'Em acompanhamento')),
        'observacoes' => trim((string)($_POST['observacoes'] ?? '')),
        'atualizado_em' => date('Y-m-d H:i:s'),
        'imagem' => (string)($complementos[$numeroProcesso]['imagem'] ?? ''),
    ];

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK || $_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            $erro = 'A imagem deve ter no máximo 5 MB.';
        } else {
            $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagem']['tmp_name']);
            if (!isset($tipos[$mime])) {
                $erro = 'Use uma imagem JPG, PNG ou WEBP.';
            } else {
                $pasta = __DIR__ . '/uploads_marcas';
                if (!is_dir($pasta)) @mkdir($pasta, 0775, true);
                $nome = $numeroProcesso . '-' . date('YmdHis') . '.' . $tipos[$mime];
                if (is_dir($pasta) && move_uploaded_file($_FILES['imagem']['tmp_name'], $pasta . '/' . $nome)) {
                    $detalhes['imagem'] = 'uploads_marcas/' . $nome;
                } else $erro = 'Não foi possível salvar a imagem.';
            }
        }
    }

    if ($erro === '') {
        $complementos[$numeroProcesso] = $detalhes;
        if (file_put_contents($arquivoComplementos, json_encode($complementos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false) {
            $mensagem = 'Ficha complementar atualizada com sucesso.';
        } else $erro = 'Não foi possível salvar a ficha complementar.';
    }
}

$complemento = $complementos[$numeroProcesso] ?? [];

$arquivoDados = __DIR__ . '/dados_rpi.json';
if ($numeroProcesso !== '' && is_file($arquivoDados)) {
    $conteudo = file_get_contents($arquivoDados);
    $dados = $conteudo !== false ? json_decode($conteudo, true) : null;

    if (is_array($dados)) {
        foreach ($dados as $item) {
            $numeroItem = preg_replace('/\D+/', '', (string)($item['processo'] ?? ''));
            if ($numeroItem === $numeroProcesso) {
                $publicacoes[] = $item;
            }
        }
    }
}

$arquivoEdicoes = __DIR__ . '/rpis.json';
if (is_file($arquivoEdicoes)) {
    $conteudo = file_get_contents($arquivoEdicoes);
    $edicoes = $conteudo !== false ? json_decode($conteudo, true) : null;

    if (is_array($edicoes)) {
        foreach ($edicoes as $edicao) {
            $numero = trim((string)($edicao['numero'] ?? ''));
            if ($numero === '') continue;

            $arquivo = trim((string)($edicao['arquivo'] ?? ''));
            $urlOficial = trim((string)($edicao['url_origem'] ?? ''));
            $edicoesRpi[$numero] = [
                'local' => $arquivo !== '' && is_file(__DIR__ . '/rpis/' . $arquivo)
                    ? 'rpis/' . rawurlencode($arquivo)
                    : '',
                'oficial' => filter_var($urlOficial, FILTER_VALIDATE_URL) ? $urlOficial : '',
                'tamanho' => (int)($edicao['tamanho'] ?? 0),
            ];
        }
    }
}

usort($publicacoes, function ($a, $b) {
    $dataA = strtotime(str_replace('/', '-', (string)($a['data'] ?? ''))) ?: 0;
    $dataB = strtotime(str_replace('/', '-', (string)($b['data'] ?? ''))) ?: 0;
    return $dataB <=> $dataA;
});

$processo = $publicacoes[0] ?? null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Detalhes do processo</title>
<style>
:root{--navy:#111827;--blue:#2563eb;--bg:#f6f8fc;--text:#172033;--muted:#6b7280;--line:#e7eaf0;--red:#c73b4b}
*{box-sizing:border-box;margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body{min-height:100vh;background:var(--bg);color:var(--text)}
.layout{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
aside{background:var(--navy);color:#d7deeb;padding:26px 16px;display:flex;flex-direction:column}
.brand{padding:0 12px 30px;color:#fff;font-size:21px;font-weight:800;letter-spacing:-.7px}.brand span{color:#78a5ff}
.menu-title{color:#8ea0bb;text-transform:uppercase;font-size:10px;letter-spacing:.8px;margin:8px 12px 10px;font-weight:800}
nav{display:grid;gap:5px}nav a{text-decoration:none;color:inherit;padding:12px;border-radius:9px;font-size:14px}nav a:hover,nav a.active{background:#263757;color:#fff}
.side-note{margin-top:auto;padding:14px;border:1px solid #334155;border-radius:10px;font-size:12px;line-height:1.5}
main{width:100%;max-width:1300px;margin:0 auto;padding:34px 42px}
.back{display:inline-block;margin-bottom:20px;color:var(--blue);font-size:14px;font-weight:750;text-decoration:none}
.header{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:24px}.header h1{font-size:28px}.header p{color:var(--muted);margin-top:7px}
.badge{background:#eaf1ff;color:#2452aa;border-radius:999px;padding:8px 11px;font-size:12px;font-weight:800;white-space:nowrap}
.summary,.card,.empty{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 10px rgba(27,39,65,.03)}
.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;overflow:hidden;margin-bottom:24px}.summary div{padding:20px;border-right:1px solid var(--line)}.summary div:last-child{border:0}.summary span{display:block;color:var(--muted);font-size:12px;margin-bottom:7px}.summary strong{font-size:15px}
.card{padding:22px;margin-bottom:14px}.card-head{display:flex;justify-content:space-between;gap:16px;margin-bottom:16px}.card h2{font-size:17px}.card-head span{color:var(--muted);font-size:13px}
.details{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.details div{padding:13px;background:#f8faff;border-radius:10px}.details span{display:block;color:var(--muted);font-size:11px;margin-bottom:5px}.details strong{font-size:13px}.details .wide{grid-column:1/-1}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:17px}.button{display:inline-flex;text-decoration:none;background:var(--blue);color:#fff;border-radius:9px;padding:10px 13px;font-size:13px;font-weight:750}.button.secondary{background:#fff;color:#344054;border:1px solid #d9dfeb}.file-note{color:var(--muted);font-size:12px;align-self:center}.empty{padding:36px;text-align:center}.empty h2{margin-bottom:8px}.empty p{color:var(--muted);margin-bottom:18px}
.notice{padding:13px 15px;border-radius:10px;margin-bottom:18px;font-size:14px}.success{background:#ecfdf5;color:#147a55}.error{background:#fff1f2;color:#c73b4b}.profile{display:grid;grid-template-columns:150px 1fr;gap:20px;padding:22px;margin-bottom:24px}.brand-image{width:150px;height:150px;border-radius:14px;background:#f3f6fb;border:1px solid var(--line);display:grid;place-items:center;overflow:hidden;color:var(--muted);font-size:12px;text-align:center}.brand-image img{width:100%;height:100%;object-fit:contain}.profile-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.profile-grid div{padding:12px;background:#f8faff;border-radius:9px}.profile-grid span{display:block;color:var(--muted);font-size:11px;margin-bottom:5px}.profile-grid strong{font-size:13px;overflow-wrap:anywhere}.edit-details{margin-bottom:24px}.edit-details summary{cursor:pointer;color:var(--blue);font-weight:750}.edit-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px}.edit-form label{display:grid;gap:6px;font-size:12px;font-weight:700}.edit-form input,.edit-form select,.edit-form textarea{width:100%;padding:10px;border:1px solid #d9dfeb;border-radius:8px}.edit-form .wide{grid-column:1/-1}.edit-form .button{border:0;cursor:pointer;justify-self:start}
@media(max-width:800px){.layout{grid-template-columns:1fr}main{padding:24px 16px}.header{flex-direction:column}.summary,.details,.profile-grid,.edit-form{grid-template-columns:1fr}.summary div{border-right:0;border-bottom:1px solid var(--line)}.details .wide,.edit-form .wide{grid-column:auto}.profile{grid-template-columns:1fr}.brand-image{width:120px;height:120px}}
</style>
</head>
<body>
<div class="layout">
<?php require __DIR__ . '/menu.php'; ?>
<main>
  <a class="back" href="publicacoes.php">← Voltar para Publicações</a>

  <?php if ($mensagem !== ''): ?><div class="notice success"><?= e($mensagem) ?></div><?php endif; ?>
  <?php if ($erro !== ''): ?><div class="notice error"><?= e($erro) ?></div><?php endif; ?>

  <?php if ($processo === null): ?>
    <section class="empty">
      <h2>Processo não encontrado</h2>
      <p>Não encontramos publicações para o processo <?= e($numeroProcesso ?: 'informado') ?>.</p>
      <a class="button" href="consulta_rpi.php">Pesquisar outro processo</a>
    </section>
  <?php else: ?>
    <header class="header">
      <div>
        <h1>Processo <?= e($numeroProcesso) ?></h1>
        <p>Histórico completo das publicações encontradas nas revistas RPI.</p>
      </div>
      <span class="badge"><?= count($publicacoes) ?> publicação(ões)</span>
    </header>

    <section class="summary">
      <div><span>Marca</span><strong><?= e($processo['marca'] ?? 'Não informada') ?></strong></div>
      <div><span>Titular</span><strong><?= e($processo['titular'] ?? 'Não informado') ?></strong></div>
      <div><span>Última RPI</span><strong><?= e($processo['rpi'] ?? 'Não informada') ?></strong></div>
    </section>

    <section class="card profile">
      <div class="brand-image">
        <?php if (!empty($complemento['imagem'])): ?>
          <img src="<?= e($complemento['imagem']) ?>" alt="Imagem da marca <?= e($processo['marca'] ?? '') ?>">
        <?php else: ?>Imagem da marca<br>não cadastrada<?php endif; ?>
      </div>
      <div class="profile-grid">
        <div><span>Apresentação</span><strong><?= e($complemento['apresentacao'] ?? 'Não informada') ?></strong></div>
        <div><span>Classes de Nice</span><strong><?= e($complemento['classes_nice'] ?? 'Não informadas') ?></strong></div>
        <div><span>Status interno</span><strong><?= e($complemento['status_interno'] ?? 'Em acompanhamento') ?></strong></div>
        <div><span>Data de depósito</span><strong><?= e($complemento['data_deposito'] ?? 'Não informada') ?></strong></div>
        <div><span>Concessão</span><strong><?= e($complemento['data_concessao'] ?? 'Não informada') ?></strong></div>
        <div><span>Possível renovação</span><strong><?= e($complemento['data_renovacao'] ?? 'Não informada') ?></strong></div>
        <div class="wide"><span>Procurador</span><strong><?= e($processo['procurador'] ?? 'Não informado') ?></strong></div>
        <?php if (!empty($complemento['observacoes'])): ?><div class="wide"><span>Observações internas</span><strong><?= e($complemento['observacoes']) ?></strong></div><?php endif; ?>
      </div>
    </section>

    <details class="card edit-details">
      <summary>Completar ou editar a ficha do processo</summary>
      <form method="post" enctype="multipart/form-data" class="edit-form">
        <label>Apresentação<select name="apresentacao"><option value="">Não informada</option><?php foreach(['Nominativa','Figurativa','Mista','Tridimensional'] as $opcao): ?><option <?= ($complemento['apresentacao']??'')===$opcao?'selected':'' ?>><?=e($opcao)?></option><?php endforeach; ?></select></label>
        <label>Classes de Nice<input name="classes_nice" value="<?=e($complemento['classes_nice']??'')?>" placeholder="Ex.: 30, 35 e 43"></label>
        <label>Status interno<select name="status_interno"><?php foreach(['Em acompanhamento','Aguardando cliente','Providência necessária','Concluído','Arquivado'] as $opcao): ?><option <?= ($complemento['status_interno']??'Em acompanhamento')===$opcao?'selected':'' ?>><?=e($opcao)?></option><?php endforeach; ?></select></label>
        <label>Data de depósito<input type="date" name="data_deposito" value="<?=e($complemento['data_deposito']??'')?>"></label>
        <label>Data de concessão<input type="date" name="data_concessao" value="<?=e($complemento['data_concessao']??'')?>"></label>
        <label>Possível renovação<input type="date" name="data_renovacao" value="<?=e($complemento['data_renovacao']??'')?>"></label>
        <label class="wide">Imagem ou logotipo (JPG, PNG ou WEBP — até 5 MB)<input type="file" name="imagem" accept="image/jpeg,image/png,image/webp"></label>
        <label class="wide">Observações internas<textarea name="observacoes" rows="3"><?=e($complemento['observacoes']??'')?></textarea></label>
        <button class="button" type="submit">Salvar ficha complementar</button>
      </form>
    </details>

    <?php foreach ($publicacoes as $item): ?>
      <?php
        $numeroRpi = trim((string)($item['rpi'] ?? ''));
        $edicao = $edicoesRpi[$numeroRpi] ?? ['local' => '', 'oficial' => '', 'tamanho' => 0];
        $pagina = max(1, (int)($item['pagina'] ?? 1));
        $tamanho = $edicao['tamanho'] > 0 ? number_format($edicao['tamanho'] / 1048576, 1, ',', '.') . ' MB' : '';
      ?>
      <article class="card">
        <div class="card-head">
          <h2>RPI <?= e($numeroRpi ?: 'não informada') ?></h2>
          <span><?= e($item['data'] ?? 'Data não informada') ?></span>
        </div>
        <div class="details">
          <div><span>Página</span><strong><?= e($item['pagina'] ?? 'Não informada') ?></strong></div>
          <div><span>Código do despacho</span><strong><?= e($item['codigo_despacho'] ?? 'Não informado') ?></strong></div>
          <div><span>Importado em</span><strong><?= e($item['importado_em'] ?? 'Não informado') ?></strong></div>
          <div class="wide"><span>Despacho</span><strong><?= e($item['despacho'] ?? 'Não informado') ?></strong></div>
        </div>
        <div class="actions">
          <?php if ($edicao['local'] !== ''): ?>
            <a class="button" target="_blank" rel="noopener" href="<?= e($edicao['local']) ?>#page=<?= $pagina ?>">Abrir PDF salvo</a>
          <?php endif; ?>
          <?php if ($edicao['oficial'] !== ''): ?>
            <a class="button secondary" target="_blank" rel="noopener" href="<?= e($edicao['oficial']) ?>#page=<?= $pagina ?>">Abrir PDF oficial</a>
          <?php endif; ?>
          <?php if ($edicao['local'] === '' && $edicao['oficial'] === ''): ?>
            <a class="button secondary" href="rpi.php">Consultar edições RPI</a>
          <?php endif; ?>
          <?php if ($tamanho !== ''): ?><span class="file-note">Tamanho do PDF: <?= e($tamanho) ?></span><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
</div>
</body>
</html>
