/**
 * SupportFlow — Reports & Analytics Client Module
 *
 * Initializes Chart.js visualizations from embedded JSON dataset and
 * handles quick-range filter triggers.
 */
class SupportFlowReports {
    constructor() {
        this.dataElement = document.getElementById('report-data');
        this.filterForm = document.getElementById('reportFiltersForm');
        this.charts = {};

        if (!this.dataElement || typeof Chart === 'undefined') {
            return;
        }

        try {
            this.payload = JSON.parse(this.dataElement.textContent || '{}');
        } catch (e) {
            console.error('Failed to parse report dataset JSON:', e);
            return;
        }

        this.initQuickRanges();
        this.initTrendChart();
        this.initStatusChart();
        this.initPriorityChart();
        this.initCategoryChart();
    }

    initQuickRanges() {
        const buttons = document.querySelectorAll('.quick-range-btn');
        const fromInput = document.getElementById('filter-from');
        const toInput = document.getElementById('filter-to');

        if (!fromInput || !toInput || !this.filterForm) {
            return;
        }

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const fromVal = btn.getAttribute('data-from');
                const toVal = btn.getAttribute('data-to');
                if (fromVal && toVal) {
                    fromInput.value = fromVal;
                    toInput.value = toVal;
                    this.filterForm.submit();
                }
            });
        });
    }

    initTrendChart() {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;

        const trendData = this.payload.trend || { labels: [], data: [] };

        this.charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: 'Tickets Created',
                    data: trendData.data,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: trendData.labels.length > 31 ? 2 : 3.5,
                    pointHoverRadius: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` ${context.parsed.y} tickets`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 11 }
                        },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }

    initStatusChart() {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;

        const statusData = this.payload.status || { labels: [], data: [], colors: [] };
        const total = statusData.data.reduce((a, b) => a + b, 0);

        if (total === 0) {
            ctx.parentElement.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>No tickets found in this period.</div>';
            return;
        }

        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.data,
                    backgroundColor: statusData.colors.length ? statusData.colors : ['#2563eb', '#7c3aed', '#d97706', '#64748b', '#059669', '#334155'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const val = context.parsed;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    initPriorityChart() {
        const ctx = document.getElementById('priorityChart');
        if (!ctx) return;

        const priorityData = this.payload.priority || { labels: [], data: [], colors: [] };

        this.charts.priority = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: priorityData.labels,
                datasets: [{
                    label: 'Tickets',
                    data: priorityData.data,
                    backgroundColor: priorityData.colors.length ? priorityData.colors : ['#64748b', '#2563eb', '#ea580c', '#dc2626'],
                    borderRadius: 6,
                    maxBarThickness: 45,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 11 } },
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    }

    initCategoryChart() {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        const catData = this.payload.category || { labels: [], data: [] };
        const total = catData.data.reduce((a, b) => a + b, 0);

        if (total === 0) {
            return;
        }

        this.charts.category = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: catData.labels,
                datasets: [{
                    label: 'Tickets',
                    data: catData.data,
                    backgroundColor: '#2563eb',
                    borderRadius: 4,
                    maxBarThickness: 24,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SupportFlowReports();
});
