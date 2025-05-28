document.addEventListener('DOMContentLoaded', function() {
    initializeAnalyticsFilters();
    initializeAnalyticsCharts();
    initializeAnalyticsTables();
    loadAnalyticsData();
});

// Filtre İşlemleri
function initializeAnalyticsFilters() {
    const filterForm = document.querySelector('.analytics-filters form');
    if (filterForm) {
        // Tarih aralığı seçimi
        const dateRangeInputs = filterForm.querySelectorAll('.date-range');
        dateRangeInputs.forEach(input => {
            input.addEventListener('change', handleFilterChange);
        });

        // Kategori seçimi
        const categorySelects = filterForm.querySelectorAll('.category-select');
        categorySelects.forEach(select => {
            select.addEventListener('change', handleFilterChange);
        });

        // Form gönderimi
        filterForm.addEventListener('submit', handleFilterSubmit);
    }
}

// Grafik İşlemleri
function initializeAnalyticsCharts() {
    const chartContainers = document.querySelectorAll('.analytics-chart');
    chartContainers.forEach(container => {
        const ctx = container.querySelector('canvas').getContext('2d');
        const type = container.dataset.chartType;
        const data = JSON.parse(container.dataset.chartData);
        
        createChart(ctx, type, data);
    });
}

function createChart(ctx, type, data) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += formatCurrency(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    };

    switch (type) {
        case 'line':
            new Chart(ctx, {
                type: 'line',
                data: data,
                options: options
            });
            break;
        case 'bar':
            new Chart(ctx, {
                type: 'bar',
                data: data,
                options: options
            });
            break;
        case 'pie':
            new Chart(ctx, {
                type: 'pie',
                data: data,
                options: options
            });
            break;
        case 'doughnut':
            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: options
            });
            break;
    }
}

// Tablo İşlemleri
function initializeAnalyticsTables() {
    const tables = document.querySelectorAll('.analytics-table');
    tables.forEach(table => {
        new DataTable(table, {
            responsive: true,
            language: {
                url: '/gelirgider/public/js/datatables-tr.json'
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
}

// Veri Yükleme
function loadAnalyticsData() {
    showLoading();
    
    fetch('/gelirgider/analytics/getData')
        .then(response => response.json())
        .then(data => {
            updateAnalyticsData(data);
            hideLoading();
        })
        .catch(error => {
            console.error('Veri yüklenirken hata:', error);
            showNotification('Veriler yüklenirken bir hata oluştu', 'error');
            hideLoading();
        });
}

// Event Handlers
function handleFilterChange(e) {
    const form = e.target.closest('form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch('/gelirgider/analytics/filter', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        updateAnalyticsData(data);
        hideLoading();
    })
    .catch(error => {
        console.error('Filtre işlemi sırasında hata:', error);
        showNotification('Filtre işlemi sırasında bir hata oluştu', 'error');
        hideLoading();
    });
}

function handleFilterSubmit(e) {
    e.preventDefault();
    handleFilterChange(e);
}

// Yardımcı Fonksiyonlar
function updateAnalyticsData(data) {
    // Metrikleri güncelle
    updateMetrics(data.metrics);
    
    // Grafikleri güncelle
    updateCharts(data.charts);
    
    // Tabloları güncelle
    updateTables(data.tables);
    
    // İçgörüleri güncelle
    updateInsights(data.insights);
}

function updateMetrics(metrics) {
    Object.entries(metrics).forEach(([key, value]) => {
        const card = document.querySelector(`[data-metric-key="${key}"]`);
        if (card) {
            const valueElement = card.querySelector('.metric-value');
            const changeElement = card.querySelector('.metric-change');
            
            if (valueElement) {
                valueElement.textContent = formatCurrency(value.value);
            }
            
            if (changeElement) {
                changeElement.textContent = `${value.change > 0 ? '+' : ''}${value.change}%`;
                changeElement.className = `metric-change ${value.change >= 0 ? 'positive' : 'negative'}`;
            }
        }
    });
}

function updateCharts(charts) {
    charts.forEach(chart => {
        const container = document.querySelector(`[data-chart-id="${chart.id}"]`);
        if (container) {
            const ctx = container.querySelector('canvas').getContext('2d');
            createChart(ctx, chart.type, chart.data);
        }
    });
}

function updateTables(tables) {
    tables.forEach(table => {
        const container = document.querySelector(`[data-table-id="${table.id}"]`);
        if (container) {
            const dataTable = DataTable.getInstance(container);
            if (dataTable) {
                dataTable.clear();
                dataTable.rows.add(table.data);
                dataTable.draw();
            }
        }
    });
}

function updateInsights(insights) {
    const container = document.querySelector('.analytics-insights');
    if (container) {
        container.innerHTML = insights.map(insight => `
            <div class="insight-card">
                <div class="insight-title">${insight.title}</div>
                <div class="insight-description">${insight.description}</div>
            </div>
        `).join('');
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}

function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'analytics-loading';
    loading.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.analytics-loading');
    if (loading) {
        loading.remove();
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.notification-container');
    if (container) {
        container.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
} 