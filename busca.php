<?php
$paginaAtual = 'busca';
$q = trim((string)($_GET['q'] ?? ''));

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ler(string $nome): array { $d=is_file(__DIR__.'/'.$nome)?json_decode((string)file_get_contents(__DIR__.'/'.$nome),true):[]; return is_array($d)?$d:[]; }
function normalizar($v): string {
    $v = mb_strtolower(trim((string)$v));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
    return $ascii === false ? $v : $ascii;
}
function contem(array $item, string $termo, array $campos): bool {
    $texto=''; foreach($campos as $campo) $texto.=' '.($item[$campo]??'');
    return str_contains(normalizar($texto), $termo);
}

$clientes=ler('clientes.json'); $publicacoes=ler('dados_rpi.json'); $prazos=ler('prazos.json'); $documentos=ler('documentos.json'); $vinculos=ler('vinculos_processos.json');
$termo=normalizar($q); $clientesEncontrados=[]; $publicacoesEncontradas=[]; $processos=[]; $prazosEncontrados=[]; $documentosEncontrados=[]; $idsClientes=[]; $numeros=[];

if ($termo !== '') {
    foreach($clientes as $c) if(contem($c,$termo,['nome','apelido','cpf_cnpj','email','telefone','responsavel','contato_nome','cidade','pasta'])) { $clientesEncontrados[]=$c; $idsClientes[(int)($c['id']??0)]=true; }
    foreach($vinculos as $numero=>$id) if(isset($idsClientes[(int)$id])) $numeros[(string)$numero]=true;
    foreach($publicacoes as $p) {
        $numero=(string)($p['processo']??'');
        if(isset($numeros[$numero]) || contem($p,$termo,['processo','marca','titular','procurador','despacho','rpi','codigo_despacho'])) {
            $publicacoesEncontradas[]=$p; $numeros[$numero]=true;
            if(!isset($processos[$numero]) || strcmp((string)($p['data']??''),(string)($processos[$numero]['data']??''))>0) $processos[$numero]=$p;
        }
    }
    foreach($prazos as $p) if(isset($numeros[(string)($p['processo']??'')]) || contem($p,$termo,['processo','titulo','observacoes'])) $prazosEncontrados[]=$p;
    foreach($documentos as $d) if(isset($numeros[(string)($d['processo']??'')]) || contem($d,$termo,['processo','titulo','tipo_documento','descricao','referencia'])) $documentosEncontrados[]=$d;
}
$processos=array_values($processos); $totalPublicacoes=count($publicacoesEncontradas); $publicacoesEncontradas=array_slice($publicacoesEncontradas,0,20); $processos=array_slice($processos,0,20);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MarcaFácil | Busca geral</title><style>
:root{--navy:#111827;--blue:#2563eb;--bg:#f6f8fc;--text:#172033;--muted:#6b7280;--line:#e7eaf0}*{box-sizing:border-box;margin:0;font-family:Inter,system-ui,"Segoe UI",sans-serif}body{background:var(--bg);color:var(--text);min-height:100vh}.layout{display:grid;grid-template-columns:250px 1fr;min-height:100vh}aside{background:var(--navy);color:#d7deeb;padding:26px 16px;display:flex;flex-direction:column}.brand{padding:0 12px 30px;color:#fff;font-size:21px;font-weight:800}.brand span{color:#78a5ff}.menu-title{color:#8ea0bb;text-transform:uppercase;font-size:10px;margin:8px 12px}.side-note{margin-top:auto;padding:14px;border:1px solid #334155;border-radius:10px;font-size:12px}nav{display:grid;gap:5px}nav a{text-decoration:none;color:inherit;padding:12px;border-radius:9px;font-size:14px}nav a:hover,nav a.active{background:#263757;color:#fff}main{width:100%;max-width:1200px;margin:auto;padding:34px 42px}.header h1{font-size:28px}.header p{color:var(--muted);margin-top:7px}.search{display:grid;grid-template-columns:1fr auto;gap:10px;margin:22px 0}.search input{padding:14px;border:1px solid #d6dce8;border-radius:10px;font-size:15px}.button{background:var(--blue);color:#fff;border:0;border-radius:9px;padding:12px 17px;font-weight:750;cursor:pointer}.summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}.summary div,.section{background:#fff;border:1px solid var(--line);border-radius:13px}.summary div{padding:15px}.summary span{color:var(--muted);font-size:11px;display:block}.summary strong{font-size:20px}.section{margin-bottom:15px;overflow:hidden}.section-head{display:flex;justify-content:space-between;padding:17px 19px;border-bottom:1px solid var(--line)}.section-head h2{font-size:17px}.section-head a{color:var(--blue);font-size:13px;text-decoration:none}.items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;background:var(--line)}.item{background:#fff;padding:16px;min-width:0}.item strong{display:block;font-size:14px;overflow-wrap:anywhere}.item p{color:var(--muted);font-size:12px;line-height:1.5;margin-top:5px;overflow-wrap:anywhere}.item a{display:inline-block;color:var(--blue);font-weight:750;font-size:12px;text-decoration:none;margin-top:9px}.empty{padding:28px;text-align:center;color:var(--muted)}@media(max-width:800px){.layout{grid-template-columns:1fr}main{padding:24px 16px}.summary{grid-template-columns:repeat(2,1fr)}.items{grid-template-columns:1fr}.search{grid-template-columns:1fr}}
.layout{align-items:start}.layout>main{min-width:0;margin:0 auto;padding-top:34px;padding-bottom:40px}.search{min-width:0}.search input{min-width:0}.summary{grid-template-columns:repeat(5,minmax(125px,1fr))}.summary div{min-width:0}.items{min-width:0}.menu-lateral{padding-top:18px;padding-bottom:18px}.menu-lateral .brand{padding-bottom:18px}.menu-lateral nav{gap:2px}.menu-lateral nav a{padding-top:9px;padding-bottom:9px}.menu-lateral .side-note{padding:11px;margin-top:16px}@media(max-width:1050px){.summary{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:800px){.layout>main{padding:24px 16px 36px}.summary{grid-template-columns:repeat(2,minmax(0,1fr))}.menu-lateral{padding-top:15px}.menu-lateral .brand{padding-bottom:14px}}@media(max-width:480px){.summary{grid-template-columns:1fr}.section-head{align-items:flex-start;gap:10px}.search .button{width:100%}}
</style></head><body><div class="layout"><?php require __DIR__.'/menu.php';?><main><header class="header"><h1>Busca geral</h1><p>Encontre uma pessoa, empresa, marca ou processo e veja tudo relacionado em um só lugar.</p></header><form class="search"><input name="q" value="<?=e($q)?>" autofocus placeholder="Nome, CPF/CNPJ, processo, marca, telefone ou e-mail"><button class="button">Buscar</button></form>
<?php if($q===''):?><div class="empty">Digite qualquer informação para começar.</div><?php else:?><section class="summary"><div><span>Clientes</span><strong><?=count($clientesEncontrados)?></strong></div><div><span>Processos</span><strong><?=count($processos)?></strong></div><div><span>Publicações</span><strong><?=$totalPublicacoes?></strong></div><div><span>Prazos</span><strong><?=count($prazosEncontrados)?></strong></div><div><span>Documentos</span><strong><?=count($documentosEncontrados)?></strong></div></section>
<?php
$secoes=[
 ['Clientes',$clientesEncontrados,'clientes.php?busca='.urlencode($q),fn($x)=>[$x['nome']??'Cliente','CPF/CNPJ: '.($x['cpf_cnpj']?:'não informado').' · '.($x['email']?:'sem e-mail'),'clientes.php?busca='.urlencode($x['nome']??'')]],
 ['Processos',$processos,'processos.php?busca='.urlencode($q),fn($x)=>['Processo '.($x['processo']??''),($x['marca']??'Marca não informada').' · RPI '.($x['rpi']??'—'),'detalhes_processo.php?processo='.urlencode($x['processo']??'')]],
 ['Prazos',$prazosEncontrados,'prazos.php?busca='.urlencode($q),fn($x)=>[$x['titulo']??'Prazo','Processo '.($x['processo']??'').' · '.($x['data_prazo']??'Sem data'),'prazos.php?busca='.urlencode($x['processo']??'')]],
 ['Documentos',$documentosEncontrados,'documentos.php?busca='.urlencode($q),fn($x)=>[$x['titulo']??'Documento',($x['tipo_documento']??'').' · Processo '.($x['processo']??''),'documentos.php?busca='.urlencode($x['processo']??'')]],
 ['Publicações',$publicacoesEncontradas,'publicacoes.php?busca='.urlencode($q),fn($x)=>[$x['marca']??'Publicação','Processo '.($x['processo']??'').' · RPI '.($x['rpi']??'').' · '.($x['despacho']??''),'detalhes_processo.php?processo='.urlencode($x['processo']??'')]],
]; foreach($secoes as [$titulo,$itens,$todos,$montar]): if(!$itens)continue;?>
<section class="section"><div class="section-head"><h2><?=e($titulo)?></h2><a href="<?=e($todos)?>">Ver na área →</a></div><div class="items"><?php foreach($itens as $item):[$nome,$meta,$link]=$montar($item);?><article class="item"><strong><?=e($nome)?></strong><p><?=e($meta)?></p><a href="<?=e($link)?>">Abrir detalhes →</a></article><?php endforeach;?></div></section><?php endforeach;?>
<?php if(!$clientesEncontrados&&!$processos&&!$prazosEncontrados&&!$documentosEncontrados&&!$publicacoesEncontradas):?><div class="empty">Nenhum resultado encontrado para “<?=e($q)?>”.</div><?php endif;?><?php endif;?></main></div></body></html>
