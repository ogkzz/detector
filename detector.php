<?php
declare(strict_types=1);

/* ================= CORES ================= */

const C = [
    'rst'      => "\e[0m",
    'bold'     => "\e[1m",
    'vermelho' => "\e[91m",
    'verde'    => "\e[92m",
    'amarelo'  => "\e[93m",
    'azul'     => "\e[34m",
    'ciano'    => "\e[36m",
    'cinza'    => "\e[37m",
    'branco'   => "\e[97m",
];

function c(string ...$n): string { return implode('', array_map(fn($x)=>C[$x]??'', $n)); }
function rst(): string { return C['rst']; }

/* ================= BANNER ================= */

function banner(): void
{
    echo c('bold','vermelho')."
███╗   ███╗ █████╗  ██████╗ ██╗███████╗██╗  ██╗
████╗ ████║██╔══██╗██╔════╝ ██║██╔════╝██║ ██╔╝
██╔████╔██║███████║██║  ███╗██║█████╗  █████╔╝
██║╚██╔╝██║██╔══██║██║   ██║██║██╔══╝  ██╔═██╗
██║ ╚═╝ ██║██║  ██║╚██████╔╝██║███████╗██║  ██╗
╚═╝     ╚═╝╚═╝  ╚═╝ ╚═════╝ ╚═╝╚══════╝╚═╝  ╚═╝
".rst();

    echo c('cinza')."  Android Root & Bootloader Scanner Profissional\n\n".rst();
}

/* ================= UTIL ================= */

function adb(string $cmd): string
{
    return trim((string)shell_exec($cmd.' 2>/dev/null'));
}

function conectado(): bool
{
    $out = shell_exec('adb devices');
    return $out && strpos($out, "device") !== false && strpos($out, "unauthorized") === false;
}

/* ================= PADRÃO DE DETECTOR ================= */

function detector(string $nome, callable $check): void
{
    echo c('bold','azul')."• Detector: $nome\n".rst();

    $r = $check();

    if ($r === true) {
        echo c('bold','vermelho')."    • Detectado\n\n".rst();
    } elseif ($r === 'suspeito') {
        echo c('bold','amarelo')."    • Suspeito\n\n".rst();
    } else {
        echo c('bold','verde')."    • Não detectado\n\n".rst();
    }
}

/* ================= SCAN ROOT ================= */

function scan(): void
{
    system('clear');
    banner();

    if (!conectado()) {
        echo c('vermelho')."Dispositivo não conectado no ADB.\n".rst();
        sleep(2);
        return;
    }

    echo c('ciano')."Coletando informações do sistema...\n\n".rst();

    $props    = adb('adb shell getprop');
    $mount    = adb('adb shell mount');
    $whichSu  = adb('adb shell which su');
    $packages = adb('adb shell pm list packages');

    detector('Binário SU presente', fn() =>
        stripos($whichSu, '/su') !== false
    );

    detector('Partições RW (system/vendor)', fn() =>
        stripos($mount, ' rw,') !== false ? 'suspeito' : false
    );

    detector('Bootloader desbloqueado (props)', function() use ($props) {
        if (stripos($props,'ro.boot.flash.locked=0')!==false) return true;
        if (stripos($props,'ro.boot.verifiedbootstate=orange')!==false) return true;
        return false;
    });

    detector('Pacotes Magisk / KernelSU / APatch', function() use ($packages) {
        foreach (['magisk','kernelsu','apatch','supersu','zygisk','riru'] as $p) {
            if (stripos($packages,$p)!==false) return true;
        }
        return false;
    });

    detector('Build test-keys', fn() =>
        stripos($props,'test-keys')!==false ? 'suspeito' : false
    );

    echo c('bold','branco')."Fim da análise.\n\nPressione ENTER...".rst();
    fgets(STDIN,1024);
}

/* ================= MENU ================= */

function menu(): void
{
    echo c('azul')."[1] Iniciar Scan\n[S] Sair\n\n".rst();
    echo c('ciano')."Escolha: ".rst();
}

/* ================= LOOP ================= */

while (true) {
    system('clear');
    banner();
    menu();

    $op = trim(fgets(STDIN,1024));

    if ($op === '1') {
        scan();
    }

    if (strtolower($op) === 's') {
        exit;
    }
}
