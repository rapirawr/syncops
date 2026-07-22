import React from 'react';
import { createRoot } from 'react-dom/client';
import Strands from './components/Strands';

function initStrands() {
    const containers = document.querySelectorAll('#strands-root, [data-strands]');
    containers.forEach((container) => {
        if (container.dataset.mounted) return;
        container.dataset.mounted = 'true';

        const root = createRoot(container);

        const colors = container.dataset.colors ? JSON.parse(container.dataset.colors) : ["#646dc8", "#646dc8", "#7d8ea0"];
        const count = parseFloat(container.dataset.count || "3");
        const speed = parseFloat(container.dataset.speed || "0.5");
        const amplitude = parseFloat(container.dataset.amplitude || "1");
        const waviness = parseFloat(container.dataset.waviness || "1");
        const thickness = parseFloat(container.dataset.thickness || "0.7");
        const glow = parseFloat(container.dataset.glow || "2.6");
        const taper = parseFloat(container.dataset.taper || "3");
        const spread = parseFloat(container.dataset.spread || "1");
        const intensity = parseFloat(container.dataset.intensity || "0.6");
        const saturation = parseFloat(container.dataset.saturation || "1.5");
        const opacity = parseFloat(container.dataset.opacity || "1");
        const scale = parseFloat(container.dataset.scale || "1.5");
        const glass = container.dataset.glass === 'true';
        const refraction = parseFloat(container.dataset.refraction || "1");
        const dispersion = parseFloat(container.dataset.dispersion || "1");
        const glassSize = parseFloat(container.dataset.glassSize || "1");

        root.render(
            React.createElement(Strands, {
                colors,
                count,
                speed,
                amplitude,
                waviness,
                thickness,
                glow,
                taper,
                spread,
                intensity,
                saturation,
                opacity,
                scale,
                glass,
                refraction,
                dispersion,
                glassSize
            })
        );
    });
}

if (document.readyState !== 'loading') {
    initStrands();
} else {
    document.addEventListener('DOMContentLoaded', initStrands);
}

// Re-initialize if dynamic content loads or Alpine state changes
window.addEventListener('init-strands', initStrands);
