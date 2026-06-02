/**
 * Banner crop helper — Instagram-style pan & zoom inside 3:1 viewport.
 */
window.BannerCanvas = (function () {
    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function parseConfig(raw) {
        if (!raw) {
            return { scale: 1, panNormX: 0, panNormY: 0, version: 2 };
        }

        let conf = raw;
        if (typeof raw === 'string') {
            try {
                conf = JSON.parse(raw);
            } catch (e) {
                return { scale: 1, panNormX: 0, panNormY: 0, version: 2 };
            }
        }

        const scale = clamp(parseFloat(conf.scale ?? 1), 1, 4);

        if (conf.version >= 2 && (conf.panNormX !== undefined || conf.panNormY !== undefined)) {
            return {
                scale,
                panNormX: clamp(parseFloat(conf.panNormX ?? 0), -1, 1),
                panNormY: clamp(parseFloat(conf.panNormY ?? 0), -1, 1),
                version: 2,
            };
        }

        const bgX = clamp(parseFloat(conf.bgX ?? 50), 0, 100);
        const bgY = clamp(parseFloat(conf.bgY ?? 50), 0, 100);
        return {
            scale,
            panNormX: clamp((bgX - 50) / 50, -1, 1),
            panNormY: clamp((bgY - 50) / 50, -1, 1),
            version: 2,
        };
    }

    function getMetrics(viewport, img, scale) {
        const vw = viewport.clientWidth;
        const vh = viewport.clientHeight;
        const iw = img.naturalWidth;
        const ih = img.naturalHeight;

        if (!vw || !vh || !iw || !ih) return null;

        const coverScale = Math.max(vw / iw, vh / ih);
        const baseW = iw * coverScale;
        const baseH = ih * coverScale;
        const displayW = baseW * scale;
        const displayH = baseH * scale;

        return {
            vw,
            vh,
            iw,
            ih,
            coverScale,
            baseW,
            baseH,
            displayW,
            displayH,
            maxPanX: Math.max(0, (displayW - vw) / 2),
            maxPanY: Math.max(0, (displayH - vh) / 2),
        };
    }

    function getMaxZoom(viewport, img) {
        const m = getMetrics(viewport, img, 1);
        if (!m) return 3;

        const widthRatio = m.iw / m.vw;
        const heightRatio = m.ih / m.vh;
        const dominant = Math.max(widthRatio, heightRatio);

        return clamp(Math.max(2, dominant * 1.35), 1.5, 4);
    }

    function normToPan(m, panNormX, panNormY) {
        return {
            panX: panNormX * m.maxPanX,
            panY: panNormY * m.maxPanY,
        };
    }

    function panToNorm(m, panX, panY) {
        return {
            panNormX: m.maxPanX > 0 ? clamp(panX / m.maxPanX, -1, 1) : 0,
            panNormY: m.maxPanY > 0 ? clamp(panY / m.maxPanY, -1, 1) : 0,
        };
    }

    function apply(viewport, img, config) {
        const cfg = parseConfig(config);
        const m = getMetrics(viewport, img, cfg.scale);
        if (!m) return cfg;

        const { panX, panY } = normToPan(m, cfg.panNormX, cfg.panNormY);

        img.style.position = 'absolute';
        img.style.width = m.displayW + 'px';
        img.style.height = m.displayH + 'px';
        img.style.maxWidth = 'none';
        img.style.maxHeight = 'none';
        img.style.objectFit = 'fill';
        img.style.objectPosition = 'center';
        img.style.transform = 'none';
        img.style.transformOrigin = 'center';
        img.style.left = ((m.vw - m.displayW) / 2 + panX) + 'px';
        img.style.top = ((m.vh - m.displayH) / 2 + panY) + 'px';

        return cfg;
    }

    function buildSaveConfig(scale, panNormX, panNormY) {
        const s = clamp(scale, 1, 4);
        const nx = clamp(panNormX, -1, 1);
        const ny = clamp(panNormY, -1, 1);

        return {
            scale: s,
            panNormX: nx,
            panNormY: ny,
            bgX: 50 + nx * 50,
            bgY: 50 + ny * 50,
            version: 2,
        };
    }

    function initAll(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-banner-canvas]').forEach((viewport) => {
            const img = viewport.querySelector('img');
            if (!img) return;

            const run = () => apply(viewport, img, viewport.dataset.bannerCanvas);

            if (img.complete && img.naturalWidth) {
                run();
            } else {
                img.addEventListener('load', run, { once: true });
            }
        });
    }

    return {
        clamp,
        parseConfig,
        getMetrics,
        getMaxZoom,
        normToPan,
        panToNorm,
        apply,
        buildSaveConfig,
        initAll,
    };
})();

document.addEventListener('DOMContentLoaded', () => BannerCanvas.initAll());
