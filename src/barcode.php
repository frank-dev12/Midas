<?php

declare(strict_types=1);

function ean13_check_digit(string $twelve): string
{
    if (!preg_match('/^\d{12}$/', $twelve)) {
        throw new InvalidArgumentException('EAN-13 incompleto.');
    }
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $twelve[$i] * ($i % 2 === 0 ? 1 : 3);
    }

    return (string) ((10 - ($sum % 10)) % 10);
}

function ean8_check_digit(string $seven): string
{
    if (!preg_match('/^\d{7}$/', $seven)) {
        throw new InvalidArgumentException('EAN-8 incompleto.');
    }
    $sum = 0;
    for ($i = 0; $i < 7; $i++) {
        $sum += (int) $seven[$i] * ($i % 2 === 0 ? 3 : 1);
    }

    return (string) ((10 - ($sum % 10)) % 10);
}

function assert_barcode(string $code): void
{
    if (preg_match('/^\d{13}$/', $code)) {
        if ($code[12] !== ean13_check_digit(substr($code, 0, 12))) {
            throw new InvalidArgumentException('El dígito de control del EAN-13 no coincide.');
        }

        return;
    }
    if (preg_match('/^\d{8}$/', $code)) {
        if ($code[7] !== ean8_check_digit(substr($code, 0, 7))) {
            throw new InvalidArgumentException('El dígito de control del EAN-8 no coincide.');
        }

        return;
    }
    if (!preg_match('/^[A-Za-z0-9\-_.]{3,48}$/', $code)) {
        throw new InvalidArgumentException('Usa un EAN-13, un EAN-8 o un código de 3 a 48 caracteres.');
    }
}

function ean13_bits(string $code): string
{
    if (!preg_match('/^\d{13}$/', $code)) {
        throw new InvalidArgumentException('Se necesita un EAN-13.');
    }
    $l = [
        '0001101', '0011001', '0010011', '0111101', '0100011',
        '0110001', '0101111', '0111011', '0110111', '0001011',
    ];
    $g = [
        '0100111', '0110011', '0011011', '0100001', '0011101',
        '0111001', '0000101', '0010001', '0001001', '0010111',
    ];
    $r = [
        '1110010', '1100110', '1101100', '1000010', '1011100',
        '1001110', '1010000', '1000100', '1001000', '1110100',
    ];
    $parity = [
        '0' => 'LLLLLL',
        '1' => 'LLGLGG',
        '2' => 'LLGGLG',
        '3' => 'LLGGGL',
        '4' => 'LGLLGG',
        '5' => 'LGGLLG',
        '6' => 'LGGGLL',
        '7' => 'LGLGLG',
        '8' => 'LGLGGL',
        '9' => 'LGGLGL',
    ];
    $bits = '101';
    $pattern = $parity[$code[0]];
    for ($i = 1; $i <= 6; $i++) {
        $digit = (int) $code[$i];
        $bits .= $pattern[$i - 1] === 'L' ? $l[$digit] : $g[$digit];
    }
    $bits .= '01010';
    for ($i = 7; $i <= 12; $i++) {
        $bits .= $r[(int) $code[$i]];
    }
    $bits .= '101';

    return $bits;
}

function ean13_svg(string $code): string
{
    $bits = ean13_bits($code);
    $module = 2;
    $quiet = 11;
    $barHeight = 70;
    $guardExtra = 8;
    $width = (strlen($bits) + ($quiet * 2)) * $module;
    $height = $barHeight + $guardExtra + 22;
    $rects = '';
    $length = strlen($bits);
    for ($i = 0; $i < $length; $i++) {
        if ($bits[$i] !== '1') {
            continue;
        }
        $guard = $i < 3 || ($i >= 45 && $i <= 49) || $i >= 92;
        $x = ($quiet + $i) * $module;
        $h = $guard ? $barHeight + $guardExtra : $barHeight;
        $rects .= '<rect x="' . $x . '" y="0" width="' . $module . '" height="' . $h . '" fill="#111"/>';
    }
    $y = $barHeight + $guardExtra + 16;
    $leftStart = ($quiet + 3) * $module;
    $sideWidth = 42 * $module;
    $rightStart = ($quiet + 50) * $module;
    $text = '<text x="' . (($quiet * $module) - 2) . '" y="' . $y . '" text-anchor="end" font-family="monospace" font-size="14" fill="#111">' . $code[0] . '</text>';
    $text .= '<text x="' . ($leftStart + ($sideWidth / 2)) . '" y="' . $y . '" text-anchor="middle" font-family="monospace" font-size="15" fill="#111">' . substr($code, 1, 6) . '</text>';
    $text .= '<text x="' . ($rightStart + ($sideWidth / 2)) . '" y="' . $y . '" text-anchor="middle" font-family="monospace" font-size="15" fill="#111">' . substr($code, 7, 6) . '</text>';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" shape-rendering="crispEdges" role="img" aria-label="Código ' . $code . '">'
        . '<rect width="100%" height="100%" fill="#fff"/>'
        . $rects
        . $text
        . '</svg>';
}

function text_code_svg(string $code): string
{
    $safe = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 96" role="img" aria-label="Código ' . $safe . '">'
        . '<rect width="320" height="96" fill="#fff" stroke="#111"/>'
        . '<text x="160" y="56" text-anchor="middle" font-family="monospace" font-size="22" fill="#111">' . $safe . '</text>'
        . '</svg>';
}

function barcode_svg(string $code): string
{
    if (preg_match('/^\d{13}$/', $code)) {
        return ean13_svg($code);
    }

    return text_code_svg($code);
}

function generate_barcode(PDO $pdo): string
{
    $start = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM products')->fetchColumn();
    $lookup = $pdo->prepare('SELECT 1 FROM products WHERE barcode = ?');
    for ($i = 0; $i < 1000; $i++) {
        $seq = $start + $i;
        $twelve = '200' . str_pad((string) $seq, 9, '0', STR_PAD_LEFT);
        if (strlen($twelve) !== 12) {
            break;
        }
        $code = $twelve . ean13_check_digit($twelve);
        $lookup->execute([$code]);
        if (!$lookup->fetch()) {
            return $code;
        }
    }

    throw new RuntimeException('No se pudo generar un código de barras.');
}
