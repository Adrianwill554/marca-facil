<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
function pt(string $t): string { $v = iconv('UTF-8', 'windows-1252//TRANSLIT', $t); return $v === false ? $t : $v; }
function linha(FPDF $p, string $t, float $w): void { $words = preg_split('/\s+/', trim($t)) ?: []; $row = ''; foreach ($words as $word) { $test = $row === '' ? $word : $row . ' ' . $word; if ($p->GetStringWidth(pt($test)) > $w && $row !== '') { $p->Cell(0, 6, pt($row), 0, 1); $row = $word; } else $row = $test; } if ($row !== '') $p->Cell(0, 6, pt($row), 0, 1); }
$processo = preg_replace('/\D+/', '', (string) ($_GET['processo'] ?? ''));
$dados = is_file(__DIR__ . '/dados_rpi.json') ? json_decode((string) file_get_contents(__DIR__ . '/dados_rpi.json'), true) : [];
$items = is_array($dados) ? array_values(array_filter($dados, fn(array $i): bool => (string) ($i['processo'] ?? '') === $processo)) : [];
if ($processo === '' || $items === []) { http_response_code(404); exit('Não foram encontradas publicações para este processo.'); }
usort($items, fn(array $a, array $b): int => strtotime((string) ($b['data'] ?? '')) <=> strtotime((string) ($a['data'] ?? ''))); $item = $items[0];
$pdf = new FPDF('P', 'mm', 'A4'); $pdf->SetTitle(pt('Extrato RPI - Processo ' . $processo)); $pdf->SetMargins(18, 18, 18); $pdf->AddPage();
$pdf->SetFillColor(17,24,39); $pdf->Rect(0,0,210,34,'F'); $pdf->SetTextColor(255,255,255); $pdf->SetFont('Arial','B',19); $pdf->SetXY(18,13); $pdf->Cell(0,8,pt('MarcaFácil | Extrato da RPI')); $pdf->SetFont('Arial','',10); $pdf->SetXY(18,23); $pdf->Cell(0,6,pt('Publicação oficial resumida para acompanhamento do processo'));
$pdf->SetTextColor(23,32,51); $pdf->SetY(45); $pdf->SetFont('Arial','B',15); $pdf->Cell(0,8,pt('Processo ' . $processo),0,1); $pdf->Ln(4);
foreach (['Marca'=>(string)($item['marca']??'Não informada'),'Titular'=>(string)($item['titular']??'Não informado'),'Procurador'=>(string)($item['procurador']??'Não informado'),'RPI'=>(string)($item['rpi']??''),'Data da publicação'=>(string)($item['data']??''),'Código do despacho'=>(string)($item['codigo_despacho']??'Não informado')] as $k=>$v) { $pdf->SetFont('Arial','B',10); $pdf->Cell(48,7,pt($k.':'),0,0); $pdf->SetFont('Arial','',10); linha($pdf,$v,124); }
$pdf->Ln(5); $pdf->SetFillColor(239,246,255); $pdf->SetDrawColor(191,219,254); $y=$pdf->GetY(); $pdf->Rect(18,$y,174,36,'DF'); $pdf->SetXY(23,$y+5); $pdf->SetFont('Arial','B',11); $pdf->Cell(0,7,pt('Despacho publicado'),0,1); $pdf->SetX(23); $pdf->SetFont('Arial','',10); linha($pdf,(string)($item['despacho']??'Não informado'),160);
$pdf->SetY(270); $pdf->SetFont('Arial','',8); $pdf->SetTextColor(107,114,128); $pdf->MultiCell(0,4,pt('Extrato gerado a partir do XML oficial da Revista da Propriedade Industrial (RPI). Ele facilita a consulta, mas não substitui a publicação oficial completa do INPI.'));
$pdf->Output('D','extrato-rpi-'.$processo.'.pdf');
