<?php
$paginaAtual = $paginaAtual ?? '';

$menuItens = [
    ['id' => 'dashboard', 'href' => 'dashboard.php', 'icone' => '▦', 'texto' => 'Dashboard'],
    ['id' => 'clientes', 'href' => 'clientes.php', 'icone' => '♙', 'texto' => 'Clientes'],
    ['id' => 'processos', 'href' => 'processos.php', 'icone' => '◫', 'texto' => 'Processos'],
    ['id' => 'prazos', 'href' => 'prazos.php', 'icone' => '◷', 'texto' => 'Prazos'],
    ['id' => 'documentos', 'href' => 'documentos.php', 'icone' => '▤', 'texto' => 'Documentos'],
    ['id' => 'consulta_rpi', 'href' => 'consulta_rpi.php', 'icone' => '⌕', 'texto' => 'Consulta RPI'],
    ['id' => 'publicacoes', 'href' => 'publicacoes.php', 'icone' => '▤', 'texto' => 'Publicações'],
];
?>

<aside>
  <div class="brand">Marca<span>Fácil</span></div>

  <div class="menu-title">Navegação</div>

  <nav>
    <?php foreach ($menuItens as $item): ?>
      <a
        href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
        class="<?= $paginaAtual === $item['id'] ? 'active' : '' ?>"
      >
        <?= $item['icone'] ?> &nbsp; <?= htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="side-note">
    <strong style="color:#fff; display:block; margin-bottom:4px;">MarcaFácil</strong>
    Gestão de marcas, processos, publicações e prazos em um só lugar.
  </div>
</aside>
