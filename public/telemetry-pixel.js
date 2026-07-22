(function () {
    try {
        const currentScript = document.currentScript || Array.from(document.querySelectorAll('script[data-project]')).pop();
        if (!currentScript) return;

        const projectSlug = currentScript.getAttribute('data-project');
        if (!projectSlug) return;

        let origin = 'http://127.0.0.1:8000';
        try {
            if (currentScript.src) {
                origin = new URL(currentScript.src).origin;
            }
        } catch (e) {}

        const endpointUrl = origin + '/api/v1/telemetry/collect';

        // Detect device type
        const ua = navigator.userAgent || '';
        let deviceType = 'desktop';
        if (/Mobi|Android|iPhone|iPod/i.test(ua)) {
            deviceType = 'mobile';
        } else if (/iPad|Tablet/i.test(ua)) {
            deviceType = 'tablet';
        }

        // Web Vitals collector container
        const vitals = {
            lcp_ms: null,
            inp_ms: null,
            cls: 0,
            ttfb_ms: null,
            fcp_ms: null,
            device_type: deviceType
        };

        // Gather Navigation Timing (TTFB)
        try {
            const navEntries = performance.getEntriesByType('navigation');
            if (navEntries.length > 0) {
                vitals.ttfb_ms = Math.round(navEntries[0].responseStart);
            }
        } catch (e) {}

        // PerformanceObservers for LCP, FCP, CLS, INP
        if ('PerformanceObserver' in window) {
            // FCP
            try {
                const fcpObserver = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (entry.name === 'first-contentful-paint') {
                            vitals.fcp_ms = Math.round(entry.startTime);
                        }
                    });
                });
                fcpObserver.observe({ type: 'paint', buffered: true });
            } catch (e) {}

            // LCP
            try {
                const lcpObserver = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    if (lastEntry) {
                        vitals.lcp_ms = Math.round(lastEntry.startTime);
                    }
                });
                lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
            } catch (e) {}

            // CLS
            try {
                const clsObserver = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (!entry.hadRecentInput) {
                            vitals.cls += entry.value;
                        }
                    });
                    vitals.cls = parseFloat(vitals.cls.toFixed(4));
                });
                clsObserver.observe({ type: 'layout-shift', buffered: true });
            } catch (e) {}

            // INP / First Input Delay
            try {
                const inpObserver = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        vitals.inp_ms = Math.round(entry.duration || (entry.processingStart - entry.startTime));
                    });
                });
                inpObserver.observe({ type: 'first-input', buffered: true });
            } catch (e) {}
        }

        function sendTelemetry() {
            const payload = JSON.stringify({
                project: projectSlug,
                path: window.location.pathname || '/',
                full_url: window.location.href,
                referrer: document.referrer || null,
                screen: `${window.screen.width}x${window.screen.height}`,
                title: document.title || null,
                lcp_ms: vitals.lcp_ms,
                inp_ms: vitals.inp_ms,
                cls: vitals.cls,
                ttfb_ms: vitals.ttfb_ms,
                fcp_ms: vitals.fcp_ms,
                device_type: vitals.device_type
            });

            if (navigator.sendBeacon) {
                navigator.sendBeacon(endpointUrl, new Blob([payload], { type: 'application/json' }));
            } else {
                fetch(endpointUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload,
                    keepalive: true,
                    mode: 'cors',
                    credentials: 'omit'
                }).catch(function () {});
            }
        }

        // Send initial beacon, then send updated vitals on visibility state change
        sendTelemetry();
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                sendTelemetry();
            }
        });
    } catch (e) {}
})();
