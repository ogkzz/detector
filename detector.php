<?php
declare(strict_types=1);

/* ================= LIMPAR TELA SEM CLEAR ================= */
function limparTela(): void {
    echo "\033[2J\033[;H";
}

/* ================= CORES ================= */
const C = [
    'rst' => "\e[0m", 'bold' => "\e[1m",
    'r' => "\e[91m", 'g' => "\e[92m",
    'y' => "\e[93m", 'b' => "\e[34m",
    'c' => "\e[36m", 'w' => "\e[97m",
];

function c(string ...$n){return implode('',array_map(fn($x)=>C[$x]??'',$n));}
function rst(){return C['rst'];}

/* ================= BANNER ================= */
function banner(){
    echo c('bold','r')."
███  ███  █████  ███████  ██  ██
████ ████ ██   ██ ██       ██ ██
██ ████ ██ ███████ █████     ███
██  ██  ██ ██   ██ ██       ██ ██
██      ██ ██   ██ ███████ ██  ██
".rst();
    echo c('c')." MAGISK • Root & Bootloader Advanced Scanner\n\n".rst();
}

/* ================= ADB ================= */
function adb(string $cmd): string {
    return trim((string)shell_exec("adb $cmd 2>/dev/null"));
}

function prepararADB(): void {
    @chmod('/data/data/com.termux/files/usr/bin/adb',0755);
}

function conectado(): bool {
    $d = shell_exec('adb devices');
    return $d && strpos($d,'device')!==false && strpos($d,'unauthorized')===false;
}

/* ================= PADRÃO DETECTOR ================= */
function detector(string $nome, callable $fn){
    echo c('bold','b')."• Detector: $nome\n".rst();
    $r = $fn();
    if($r===true)
        echo c('bold','r')."    • Detectado\n\n".rst();
    elseif($r==='suspeito')
        echo c('bold','y')."    • Suspeito\n\n".rst();
    else
        echo c('bold','g')."    • Não detectado\n\n".rst();
}

/* ================= SCAN ================= */
function scan(){
    limparTela();
    banner();

    if(!conectado()){
        echo c('r')."ADB não conectado. Faça o pareamento primeiro.\n".rst();
        sleep(2);
        return;
    }

    echo c('c')."Coletando dados do sistema...\n\n".rst();

    $props   = adb('shell getprop');
    $mount   = adb('shell mount');
    $su      = adb('shell which su');
    $pkgs    = adb('shell pm list packages');
    $files   = adb('shell ls /system/bin');

    detector('Binário SU', fn()=> stripos($su,'su')!==false);

    detector('Partições RW', fn()=> stripos($mount,' rw,')!==false ? 'suspeito':false);

    detector('Bootloader props', function()use($props){
        if(stripos($props,'flash.locked=0')!==false) return true;
        if(stripos($props,'verifiedbootstate=orange')!==false) return true;
        return false;
    });

    detector('Pacotes root conhecidos', function()use($pkgs){
        foreach(['magisk','kernelsu','apatch','supersu','zygisk','riru'] as $p)
            if(stripos($pkgs,$p)!==false) return true;
        return false;
    });

    detector('Build test-keys', fn()=> stripos($props,'test-keys')!==false?'suspeito':false);

    detector('Arquivos suspeitos em /system/bin', fn()=> stripos($files,'su')!==false);

    echo c('w')."Pressione ENTER para voltar...".rst();
    fgets(STDIN,1024);
}

/* ================= MENU ================= */
function menu(){
    echo c('b')."[1] Iniciar Scan\n[S] Sair\n\nEscolha: ".rst();
}

/* ================= LOOP ================= */
prepararADB();

while(true){
    limparTela();
    banner();
    menu();
    $op=trim(fgets(STDIN,1024));

    if($op==='1') scan();
    if(strtolower($op)==='s') exit;
}
