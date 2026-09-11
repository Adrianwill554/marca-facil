<?php
declare(strict_types=1);
function normalizarProcesso(string $numero): string { return preg_replace('/\D+/', '', $numero) ?? ''; }
function processosDaCarteira(): array { static $mapa; if (isset($mapa)) return $mapa; $mapa=[]; $dados=is_file(__DIR__.'/processos_monitorados.json')?json_decode((string)file_get_contents(__DIR__.'/processos_monitorados.json'),true):[]; foreach(is_array($dados)?$dados:[] as $item){$n=normalizarProcesso((string)($item['processo']??''));if($n!=='')$mapa[$n]=$item;} return $mapa; }
function processoPertenceACarteira(string $numero): bool { return isset(processosDaCarteira()[normalizarProcesso($numero)]); }
function filtrarPublicacoesDaCarteira(array $itens): array { return array_values(array_filter($itens,fn($i)=>is_array($i)&&processoPertenceACarteira((string)($i['processo']??'')))); }
