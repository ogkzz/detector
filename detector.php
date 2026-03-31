<?php
declare(strict_types=1);

/* ========= LIMPAR ========= */
function limpar(){ echo "\033[2J\033[;H"; }

/* ========= CORES ========= */
const C=[
 'r'=>"\e[91m",'g'=>"\e[92m",'y'=>"\e[93m",
 'b'=>"\e[94m",'c'=>"\e[96m",'w'=>"\e[97m",
 'bold'=>"\e[1m",'rst'=>"\e[0m"
];
function c(...$n){return implode('',array_map(fn($x)=>C[$x]??'',$n));}

/* ========= ASCII CLEAN ========= */
function banner(){
echo c('bold','r')."
╔══════════════════════════════════════╗
║              MAGISK SCAN            ║
╚══════════════════════════════════════╝
".c('rst');
echo c('c')."     Advanced Android Root / Bootloader Detector\n\n".c('rst');
}

/* ========= ADB ========= */
function adb($c){ return trim((string)shell_exec("adb $c 2>/dev/null")); }
function conectado(){
 $d=shell_exec('adb devices');
 return $d && strpos($d,'device')!==false && strpos($d,'unauthorized')===false;
}

/* ========= DETECTOR PADRÃO ========= */
function detector($nome,$fn){
 echo c('bold','b')."• $nome\n".c('rst');
 $r=$fn();
 if($r===true) echo c('bold','r')."    • Detectado\n\n".c('rst');
 elseif($r==='suspeito') echo c('bold','y')."    • Suspeito\n\n".c('rst');
 else echo c('bold','g')."    • Não detectado\n\n".c('rst');
}

/* ========= SCAN ========= */
function scan(){
 limpar(); banner();

 if(!conectado()){
  echo c('r')."ADB não conectado.\n".c('rst');
  sleep(2); return;
 }

 echo c('c')."Coletando dados do sistema...\n\n".c('rst');

 $prop  = adb('shell getprop');
 $mount = adb('shell mount');
 $su    = adb('shell which su');
 $id    = adb('shell id');
 $pkgs  = adb('shell pm list packages');
 $bins  = adb('shell ls /system/bin');
 $xbin  = adb('shell ls /system/xbin');
 $sbin  = adb('shell ls /sbin');
 $magisk= adb('shell ls /data/adb');
 $sepol = adb('shell getenforce');

 detector('Binário su acessível', fn()=> stripos($su,'su')!==false);

 detector('UID 0 (root ativo)', fn()=> stripos($id,'uid=0')!==false);

 detector('/system montado RW', fn()=> stripos($mount,' /system ')!==false && stripos($mount,' rw,')!==false?'suspeito':false);

 detector('Bootloader desbloqueado (props)', function()use($prop){
  if(stripos($prop,'flash.locked=0')!==false) return true;
  if(stripos($prop,'verifiedbootstate=orange')!==false) return true;
  return false;
 });

 detector('Build test-keys', fn()=> stripos($prop,'test-keys')!==false?'suspeito':false);

 detector('Pacotes Magisk / KernelSU / APatch', function()use($pkgs){
  foreach(['magisk','kernelsu','apatch','zygisk','riru'] as $p)
   if(stripos($pkgs,$p)!==false) return true;
  return false;
 });

 detector('Arquivos su em /system/bin', fn()=> stripos($bins,'su')!==false);
 detector('Arquivos su em /system/xbin', fn()=> stripos($xbin,'su')!==false);
 detector('Arquivos su em /sbin', fn()=> stripos($sbin,'su')!==false);

 detector('Pasta /data/adb (Magisk moderno)', fn()=> stripos($magisk,'magisk')!==false);

 detector('SELinux Permissive', fn()=> stripos($sepol,'Permissive')!==false?'suspeito':false);

 echo c('w')."ENTER para voltar".c('rst');
 fgets(STDIN,1024);
}

/* ========= MENU ========= */
while(true){
 limpar(); banner();
 echo c('b')."[1] Iniciar Scan\n[S] Sair\n\nEscolha: ".c('rst');
 $o=trim(fgets(STDIN,1024));
 if($o==='1') scan();
 if(strtolower($o)==='s') exit;
}
