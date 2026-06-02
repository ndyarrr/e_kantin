<?php

function bannerCanvasParseConfig(?string $canvasConfig): array
{
    if (empty($canvasConfig)) {
        return ['scale' => 1.0, 'panNormX' => 0.0, 'panNormY' => 0.0, 'bgX' => 50.0, 'bgY' => 50.0, 'version' => 2];
    }

    $conf = json_decode($canvasConfig, true);
    if (!is_array($conf)) {
        return ['scale' => 1.0, 'panNormX' => 0.0, 'panNormY' => 0.0, 'bgX' => 50.0, 'bgY' => 50.0, 'version' => 2];
    }

    $scale = max(1.0, min(4.0, (float) ($conf['scale'] ?? 1.0)));

    if (($conf['version'] ?? 0) >= 2 && (isset($conf['panNormX']) || isset($conf['panNormY']))) {
        $panNormX = max(-1.0, min(1.0, (float) ($conf['panNormX'] ?? 0.0)));
        $panNormY = max(-1.0, min(1.0, (float) ($conf['panNormY'] ?? 0.0)));
    } else {
        $bgX = max(0.0, min(100.0, (float) ($conf['bgX'] ?? 50.0)));
        $bgY = max(0.0, min(100.0, (float) ($conf['bgY'] ?? 50.0)));
        $panNormX = max(-1.0, min(1.0, ($bgX - 50) / 50));
        $panNormY = max(-1.0, min(1.0, ($bgY - 50) / 50));
    }

    return [
        'scale' => $scale,
        'panNormX' => $panNormX,
        'panNormY' => $panNormY,
        'bgX' => 50 + $panNormX * 50,
        'bgY' => 50 + $panNormY * 50,
        'version' => 2,
    ];
}

function bannerCanvasDataAttrs(?string $canvasConfig): string
{
    $cfg = bannerCanvasParseConfig($canvasConfig);
    $json = htmlspecialchars(json_encode([
        'scale' => $cfg['scale'],
        'panNormX' => $cfg['panNormX'],
        'panNormY' => $cfg['panNormY'],
        'version' => 2,
    ]), ENT_QUOTES, 'UTF-8');

    return 'data-banner-canvas="' . $json . '"';
}

function bannerCanvasStyleFromConfig(?string $canvasConfig): string
{
    return '';
}
