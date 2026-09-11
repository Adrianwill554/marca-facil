<?php
declare(strict_types=1);
require_once __DIR__ . '/carteira_service.php';

$dados = is_file(__DIR__.'/dados_rpi.json') ? json_decode((string)file_get_contents(__DIR__.'/dados_rpi.json'), true) : [];
$itens = filtrarPublicacoesDaCarteira(is_array($dados) ? $dados : []);
foreach ($itens as &$item) {
    $texto = mb_strtolower((string)($item['despacho'] ?? ''));
    $item['tipo'] = $item['tipo'] ?? match (true) {
        str_contains($texto, 'indefer') => 'Indeferimento',
        str_contains($texto, 'defer') => 'Deferimento',
        str_contains($texto, 'oposi') => 'Oposição',
        str_contains($texto, 'prorroga') => 'Prorrogação',
        str_contains($texto, 'registro') || str_contains($texto, 'concess') => 'Registro',
        default => 'Outros'
    };
}
unset($item);
$selecionados = array_values(array_filter(array_map('normalizarProcesso', (array)($_REQUEST['processos'] ?? []))));
$busca = mb_strtolower(trim((string)($_REQUEST['busca'] ?? '')));
$tipo = trim((string)($_REQUEST['tipo'] ?? 'todas'));
$rpi = trim((string)($_REQUEST['rpi'] ?? ''));
$processo = normalizarProcesso((string)($_REQUEST['processo'] ?? ''));
$marca = mb_strtolower(trim((string)($_REQUEST['marca'] ?? '')));
$titular = mb_strtolower(trim((string)($_REQUEST['titular'] ?? '')));
$despacho = mb_strtolower(trim((string)($_REQUEST['despacho'] ?? '')));

$itens = array_values(array_filter($itens, function(array $i) use($selecionados,$busca,$tipo,$rpi,$processo,$marca,$titular,$despacho): bool {
    $n=normalizarProcesso((string)($i['processo']??''));
    if($selecionados && !in_array($n,$selecionados,true)) return false;
    $texto=mb_strtolower(implode(' ',[(string)($i['processo']??''),(string)($i['marca']??''),(string)($i['titular']??''),(string)($i['despacho']??'')]));
    return ($busca===''||str_contains($texto,$busca))
        && ($tipo==='todas'||mb_strtolower((string)($i['tipo']??''))===mb_strtolower($tipo))
        && ($rpi===''||(string)($i['rpi']??'')===$rpi)
        && ($processo===''||$n===$processo)
        && ($marca===''||str_contains(mb_strtolower((string)($i['marca']??'')),$marca))
        && ($titular===''||str_contains(mb_strtolower((string)($i['titular']??'')),$titular))
        && ($despacho===''||str_contains(mb_strtolower((string)(($i['codigo_despacho']??'').' '.($i['despacho']??''))),$despacho));
}));
if (!$itens) { http_response_code(404); exit('Nenhuma publicação da carteira corresponde aos filtros.'); }

$formato = (string)($_REQUEST['formato'] ?? 'csv');
if ($formato === 'pdf') {
    require __DIR__.'/vendor/autoload.php';
    $pt=fn(string $v): string => iconv('UTF-8','windows-1252//TRANSLIT',$v) ?: $v;
    $pdf=new FPDF('L','mm','A4'); $pdf->SetMargins(10,10,10); $pdf->AddPage();
    $pdf->SetFont('Arial','B',15); $pdf->Cell(0,9,$pt('MarcaFácil - Publicações da carteira Piramidy'),0,1);
    $pdf->SetFont('Arial','',9); $pdf->Cell(0,6,$pt(count($itens).' publicação(ões) selecionada(s)'),0,1); $pdf->Ln(2);
    foreach($itens as $i){$pdf->SetFont('Arial','B',9);$pdf->MultiCell(0,5,$pt('Processo '.($i['processo']??'').' | '.($i['marca']??'').' | RPI '.($i['rpi']??'')));$pdf->SetFont('Arial','',8);$pdf->MultiCell(0,4,$pt(($i['titular']??'').' | '.($i['codigo_despacho']??'').' '.($i['despacho']??'')));$pdf->Ln(2);}
    $pdf->Output('D','publicacoes-piramidy-'.date('Ymd-His').'.pdf'); exit;
}
header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="publicacoes-piramidy-'.date('Ymd-His').'.csv"');
$out=fopen('php://output','wb'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['Processo','Marca','Titular','RPI','Data','Código','Despacho','Classe'],';');
foreach($itens as $i) fputcsv($out,[$i['processo']??'',$i['marca']??'',$i['titular']??'',$i['rpi']??'',$i['data']??'',$i['codigo_despacho']??'',$i['despacho']??'',$i['classe']??''],';');
fclose($out);
