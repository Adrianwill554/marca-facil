<?php
$paginaAtual = 'backup';
require __DIR__ . '/backup_service.php';

function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['baixar'])) {
    $nome = basename((string)$_GET['baixar']);
    if (!preg_match('/^marcafacil-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.tar\.gz$/', $nome)) {
        http_response_code(400);
        exit('Nome de backup inválido.');
    }

    $arquivo = pastaBackups() . '/' . $nome;
    if (!is_file($arquivo)) {
        http_response_code(404);
        exit('Backup não encontrado.');
    }

    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $nome . '"');
    header('Content-Length: ' . filesize($arquivo));
    header('X-Content-Type-Options: nosniff');
    readfile($arquivo);
    exit;
}

$mensagem = '';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    try {
        $arquivoCriado = criarBackup();
        $mensagem = 'Backup criado com sucesso: ' . basename($arquivoCriado);
    } catch (Throwable $excecao) {
        $erro = $excecao->getMessage();
    }
}

$backups = listarBackups();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MarcaFácil | Backups</title>
<style>
:root{--navy:#111827;--blue:#2563eb;--blue-dark:#1d4ed8;--bg:#f6f8fc;--text:#172033;--muted:#6b7280;--line:#e7eaf0;--green:#147a55;--red:#c73b4b}
*{box-sizing:border-box;margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body{min-height:100vh;background:var(--bg);color:var(--text)}.layout{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
aside{background:var(--navy);color:#d7deeb;padding:26px 16px;display:flex;flex-direction:column}.brand{padding:0 12px 30px;color:#fff;font-size:21px;font-weight:800;letter-spacing:-.7px}.brand span{color:#78a5ff}.menu-title{color:#8ea0bb;text-transform:uppercase;font-size:10px;letter-spacing:.8px;margin:8px 12px 10px;font-weight:800}nav{display:grid;gap:5px}nav a{text-decoration:none;color:inherit;padding:12px;border-radius:9px;font-size:14px}nav a:hover,nav a.active{background:#263757;color:#fff}.side-note{margin-top:auto;padding:14px;border:1px solid #334155;border-radius:10px;font-size:12px;line-height:1.5}
main{width:100%;max-width:1150px;margin:0 auto;padding:34px 42px}.header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.header h1{font-size:28px}.header p{color:var(--muted);margin-top:7px;line-height:1.5}.button{border:0;background:var(--blue);color:#fff;border-radius:9px;padding:12px 16px;font-size:14px;font-weight:750;cursor:pointer;text-decoration:none}.button:hover{background:var(--blue-dark)}
.notice{padding:13px 15px;border-radius:10px;margin-bottom:18px;font-size:14px}.success{background:#ecfdf5;color:var(--green)}.error{background:#fff1f2;color:var(--red)}
.info{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px}.info div,.card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(27,39,65,.03)}.info div{padding:18px}.info span{display:block;color:var(--muted);font-size:12px;margin-bottom:6px}.info strong{font-size:17px}.card{overflow:hidden}.card-head{padding:20px;display:flex;justify-content:space-between;align-items:center}.card-head h2{font-size:18px}.backup-row{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:18px;padding:16px 20px;border-top:1px solid var(--line)}.backup-row strong{font-size:14px}.backup-row span{color:var(--muted);font-size:13px}.download{color:var(--blue);font-weight:750;text-decoration:none;font-size:13px}.empty{padding:32px;text-align:center;color:var(--muted);border-top:1px solid var(--line)}
@media(max-width:800px){.layout{grid-template-columns:1fr}main{padding:24px 16px}.header{flex-direction:column}.info{grid-template-columns:1fr}.backup-row{grid-template-columns:1fr}.button{width:100%}}
</style>
</head>
<body>
<div class="layout">
<?php require __DIR__ . '/menu.php'; ?>
<main>
  <header class="header">
    <div><h1>Backups</h1><p>Proteja os dados, vínculos, prazos e documentos cadastrados no MarcaFácil.</p></div>
    <form method="post"><input type="hidden" name="acao" value="criar"><button class="button" type="submit">Criar backup agora</button></form>
  </header>

  <?php if ($mensagem !== ''): ?><div class="notice success"><?= e($mensagem) ?></div><?php endif; ?>
  <?php if ($erro !== ''): ?><div class="notice error"><?= e($erro) ?></div><?php endif; ?>

  <section class="info">
    <div><span>Cópias disponíveis</span><strong><?= count($backups) ?></strong></div>
    <div><span>Retenção automática</span><strong>10 backups</strong></div>
    <div><span>Conteúdo</span><strong>Dados + documentos</strong></div>
  </section>

  <section class="card">
    <div class="card-head"><h2>Histórico de backups</h2></div>
    <?php if ($backups === []): ?>
      <div class="empty">Nenhum backup criado até o momento.</div>
    <?php endif; ?>
    <?php foreach ($backups as $arquivo): ?>
      <div class="backup-row">
        <strong><?= e(basename($arquivo)) ?></strong>
        <span><?= e(tamanhoLegivelBackup((int)filesize($arquivo))) ?> · <?= date('d/m/Y H:i:s', filemtime($arquivo)) ?></span>
        <a class="download" href="backup.php?baixar=<?= urlencode(basename($arquivo)) ?>">Baixar cópia</a>
      </div>
    <?php endforeach; ?>
  </section>
</main>
</div>
</body>
</html>
