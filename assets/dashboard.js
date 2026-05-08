/* =========================================================
 * Masscan Web UI — dashboard.js
 * Chart.js bar/line charts for the dashboard page
 * ========================================================= */

(function () {
    'use strict';

    // Detect current theme for chart text colour
    var isDark      = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var textColour  = isDark ? '#dee2e6' : '#495057';
    var gridColour  = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';
    var brandColour = 'rgba(107,0,0,0.8)';
    var brandHover  = 'rgba(128,0,0,1)';

    var baseOpts = {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                ticks: { color: textColour },
                grid:  { color: gridColour }
            },
            y: {
                ticks: { color: textColour, precision: 0 },
                grid:  { color: gridColour },
                beginAtZero: true
            }
        }
    };

    // Top Ports chart
    var portsEl = document.getElementById('portsChart');
    if (portsEl && typeof portsData !== 'undefined') {
        new Chart(portsEl, {
            type: 'bar',
            data: {
                labels: portsData.map(function (p) { return 'Port ' + p; }),
                datasets: [{
                    data:            portsCounts,
                    backgroundColor: brandColour,
                    hoverBackgroundColor: brandHover
                }]
            },
            options: baseOpts
        });
    }

    // Top Services chart
    var servicesEl = document.getElementById('servicesChart');
    if (servicesEl && typeof servicesData !== 'undefined') {
        new Chart(servicesEl, {
            type: 'bar',
            data: {
                labels: servicesData,
                datasets: [{
                    data:            servicesCounts,
                    backgroundColor: brandColour,
                    hoverBackgroundColor: brandHover
                }]
            },
            options: baseOpts
        });
    }

    // Timeline chart
    var timelineEl = document.getElementById('timelineChart');
    if (timelineEl && typeof timelineDays !== 'undefined' && timelineDays.length > 0) {
        new Chart(timelineEl, {
            type: 'line',
            data: {
                labels: timelineDays,
                datasets: [{
                    data:         timelineCounts,
                    borderColor:  brandHover,
                    backgroundColor: 'rgba(107,0,0,0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                }]
            },
            options: Object.assign({}, baseOpts, {
                plugins: { legend: { display: false } }
            })
        });
    }
})();
