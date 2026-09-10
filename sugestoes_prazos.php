<?php
$paginaAtual = 'sugestoes';

function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function lerJson(string $arquivo): array {
    if (!is_file($arquivo)) return [];
    $dados = json_decode((string)file_get_contents($arquivo), true);
    return is_array($dados) ? $dados : [];
}
function salvarJson(string $arquivo, array $dados): bool {
    return file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}
function chaveSugestao(array $item): string {
    return hash('sha256', implode('|', [$item['processo'] ?? '', $item['rpi'] ?? '', $item['codigo_despacho'] ?? '', $item['despacho'] ?? '']));
}
function tipoSugestao(string $despacho): ?array {
    $texto = mb_strtolower($despacho);
    return match (true) {
        str_contains($texto, 'indefer') => ['Indeferimento', 'Revisar possível medida após indeferimento', 'red'],
        str_contains($texto, 'exig') => ['Exigência', 'Responder exigência publicada', 'amber'],
        str_contains($texto, 'oposi') => ['Oposição', 'Revisar publicação relacionada à oposição', 'amber'],
        str_contains($texto, 'defer') => ['Deferimento', 'Revisar providências após deferimento', 'green'],
        str_contains($texto, 'prorroga') => ['Prorrogação', 'Revisar prazo de prorrogação', 'blue'],
        default => null,
    };
}

$arquivoPublicacoes = __DIR__ . '/dados_rpi.json';
$arquivoMonitorados = __DIR__ . '/processos_monitorados.json';
$arquivoVinculos = __DIR__ . '/vinculos_processos.json';
$arquivoClientes = __DIR__ . '/clientes.json';
$arquivoPrazos = __DIR__ . '/prazos.json';
$arquivoDecisoes = __DIR__ . '/sugestoes_prazos.json';

$publicacoes = lerJson($arquivoPublicacoes);
$monitoradosLista = lerJson($arquivoMonitorados);
$vinculos = lerJson($arquivoVinculos);
$clientes = lerJson($arquivoClientes);
$prazos = lerJson($arquivoPrazos);
$decisoes = lerJson($arquivoDecisoes);
$monitorados = [];
$clientesPorId = [];
foreach ($monitoradosLista as $item) { $numero = trim((string)($item['processo'] ?? '')); if ($numero !== '') $monitorados[$numero] = true; }
foreach ($clientes as $cliente) { $clientesPorId[(int)($cliente['id'] ?? 0)] = $cliente; }

$mensagem = '';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chave = (string)($_POST['chave'] ?? '');
    $acao = (string)($_POST['acao'] ?? '');
    $publicacao = null;
    foreach ($publicacoes as $item) { if (hash_equals(chaveSugestao($item), $chave)) { $publicacao = $item; break; } }

    if (!$publicacao || !isset($monitorados[(string)($publicacao['processo'] ?? '')])) {
        $erro = 'A sugestão não foi encontrada ou não pertence à carteira monitorada.';
    } elseif ($acao === 'ignorar') {
        $decisoes[$chave] = ['status' => 'ignorada', 'decidido_em' => date('Y-m-d H:i:s')];
        salvarJson($arquivoDecisoes, $decisoes);
        $mensagem = 'Sugestão ignorada. Ela não será exibida novamente.';
    } elseif ($acao === 'confirmar') {
        $dataPrazo = trim((string)($_POST['data_prazo'] ?? ''));
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        if ($dataPrazo === '' || $titulo === '') {
            $erro = 'Revise o título e informe a data oficial antes de confirmar.';
        } else {
            $ids = array_map(fn($p) => (int)($p['id'] ?? 0), $prazos);
            $processo = (string)($publicacao['processo'] ?? '');
            $prazos[] = [
                'id' => $ids ? max($ids) + 1 : 1,
                'processo' => $processo,
                'cliente_id' => (int)($vinculos[$processo] ?? 0),
                'titulo' => $titulo,
                'data_prazo' => $dataPrazo,
                'observacoes' => 'Sugestão originada da RPI ' . ($publicacao['rpi'] ?? '') . ': ' . ($publicacao['despacho'] ?? ''),
                'concluido' => false,
                'origem_sugestao' => $chave,
                'criado_em' => date('Y-m-d H:i:s'),
            ];
            if (salvarJson($arquivoPrazos, $prazos)) {
                $decisoes[$chave] = ['status' => 'confirmada', 'decidido_em' => date('Y-m-d H:i:s')];
                salvarJson($arquivoDecisoes, $decisoes);
                $mensagem = 'Prazo confirmado e incluído na agenda.';
            } else $erro = 'Não foi possível salvar o prazo.';
        }
    }
}

$sugestoes = [];
foreach ($publicacoes as $item) {
    $processo = trim((string)($item['processo'] ?? ''));
    $tipo = tipoSugestao((string)($item['despacho'] ?? ''));
    $chave = chaveSugestao($item);
    if ($processo === '' || !$tipo || !isset($monitorados[$processo]) || isset($decisoes[$chave])) continue;
    $clienteId = (int)($vinculos[$processo] ?? 0);
    $item['_chave'] = $chave; $item['_tipo'] = $tipo[0]; $item['_titulo'] = $tipo[1]; $item['_classe'] = $tipo[2];
    $item['_cliente'] = $clientesPorId[$clienteId]['nome'] ?? 'Sem cliente vinculado';
    $sugestoes[] = $item;
}
usort($sugestoes, fn($a, $b) => strcmp((string)($b['data'] ?? ''), (string)($a['data'] ?? '')));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MarcaFácil | Sugestões de prazos</title>
<style>
:root{--navy:#111827;--blue:#2563eb;--bg:#f6f8fc;--text:#172033;--muted:#6b7280;--line:#e7eaf0}*{box-sizing:border-box;margin:0;font-family:Inter,system-ui,"Segoe UI",sans-serif}body{background:var(--bg);color:var(--text);min-height:100vh}.layout{display:grid;grid-template-columns:250px 1fr;min-height:100vh}aside{background:var(--navy);color:#d7deeb;padding:26px 16px;display:flex;flex-direction:column}.brand{padding:0 12px 30px;color:#fff;font-size:21px;font-weight:800}.brand span{color:#78a5ff}.menu-title{color:#8ea0bb;text-transform:uppercase;font-size:10px;margin:8px 12px}.side-note{margin-top:auto;padding:14px;border:1px solid #334155;border-radius:10px;font-size:12px}nav{display:grid;gap:5px}nav a{text-decoration:none;color:inherit;padding:12px;border-radius:9px;font-size:14px}nav a:hover,nav a.active{background:#263757;color:#fff}main{width:100%;max-width:1150px;margin:auto;padding:34px 42px}.header{margin-bottom:24px}.header h1{font-size:28px}.header p{color:var(--muted);margin-top:7px;line-height:1.5}.notice{padding:13px;border-radius:10px;margin-bottom:16px}.success{background:#ecfdf5;color:#147a55}.error{background:#fff1f2;color:#c73b4b}.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:14px}.card-head{display:flex;justify-content:space-between;gap:15px}.card h2{font-size:17px}.meta{color:var(--muted);font-size:13px;margin:6px 0 13px}.dispatch{background:#f8faff;padding:13px;border-radius:9px;font-size:13px;line-height:1.5}.tag{padding:6px 9px;border-radius:999px;font-size:11px;font-weight:800}.green{background:#dff7ed;color:#126149}.amber{background:#fff1d8;color:#9a5a00}.red{background:#ffeaed;color:#a6283a}.blue{background:#e8f0ff;color:#2452aa}.review{display:grid;grid-template-columns:1fr 170px auto auto;gap:9px;margin-top:15px;align-items:end}label{display:grid;gap:5px;font-size:11px;font-weight:750}input{width:100%;padding:10px;border:1px solid #d9dfeb;border-radius:8px}.button{border:0;border-radius:8px;padding:11px 13px;font-weight:750;cursor:pointer}.primary{background:var(--blue);color:#fff}.secondary{background:#fff;border:1px solid #d9dfeb;color:#344054}.empty{background:#fff;border:1px solid var(--line);border-radius:14px;padding:40px;text-align:center;color:var(--muted)}@media(max-width:800px){.layout{grid-template-columns:1fr}main{padding:24px 16px}.review{grid-template-columns:1fr}.card-head{flex-direction:column}}
.card-head{align-items:flex-start}.card-head .tag{display:inline-flex;align-items:center;align-self:flex-start;flex:0 0 auto;white-space:nowrap;line-height:1}
</style></head><body><div class="layout"><?php require __DIR__ . '/menu.php'; ?><main><header class="header"><h1>Sugestões de prazos</h1><p>Publicações relevantes dos processos monitorados. Revise a data jurídica antes de confirmar.</p></header>
<?php if($mensagem):?><div class="notice success"><?=e($mensagem)?></div><?php endif;?><?php if($erro):?><div class="notice error"><?=e($erro)?></div><?php endif;?>
<?php if(!$sugestoes):?><div class="empty">Nenhuma sugestão pendente. As novas publicações aparecerão aqui automaticamente.</div><?php endif;?>
<?php foreach($sugestoes as $item):?><article class="card"><div class="card-head"><div><h2><?=e($item['marca']??'Marca não informada')?> · Processo <?=e($item['processo'])?></h2><p class="meta"><?=e($item['_cliente'])?> · RPI <?=e($item['rpi']??'—')?> · <?=e($item['data']??'Sem data')?></p></div><span class="tag <?=e($item['_classe'])?>"><?=e($item['_tipo'])?></span></div><div class="dispatch"><?=e($item['despacho']??'')?></div><form method="post" class="review"><input type="hidden" name="chave" value="<?=e($item['_chave'])?>"><label>Título sugerido<input name="titulo" value="<?=e($item['_titulo'])?>" required></label><label>Data oficial do prazo<input type="date" name="data_prazo" required></label><button class="button primary" name="acao" value="confirmar">Confirmar prazo</button><button class="button secondary" name="acao" value="ignorar" formnovalidate onclick="return confirm('Ignorar esta sugestão definitivamente?')">Ignorar</button></form></article><?php endforeach;?>
</main></div></body></html>
