document.addEventListener('DOMContentLoaded', function() {
    initializeReportFilters();
    initializeReportCharts();
    initializeReportTables();
    initializeExportOptions();
});

// Filtre İşlemleri
function initializeReportFilters() {
    const filterForm = document.querySelector('.report-filters form');
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
function initializeReportCharts() {
    const chartContainers = document.querySelectorAll('.report-chart');
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
            }
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
function initializeReportTables() {
    const tables = document.querySelectorAll('.report-table');
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

// Dışa Aktarma İşlemleri
function initializeExportOptions() {
    const exportButtons = document.querySelectorAll('.export-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', handleExport);
    });
}

// Event Handlers
function handleFilterChange(e) {
    const form = e.target.closest('form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch('/gelirgider/reports/filter', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        updateReportData(data);
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

function handleExport(e) {
    e.preventDefault();
    const button = e.target;
    const format = button.dataset.format;
    const reportId = button.dataset.reportId;
    
    showLoading();
    
    fetch(`/gelirgider/reports/export/${format}/${reportId}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report-${reportId}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            hideLoading();
        })
        .catch(error => {
            console.error('Dışa aktarma sırasında hata:', error);
            showNotification('Dışa aktarma sırasında bir hata oluştu', 'error');
            hideLoading();
        });
}

// Yardımcı Fonksiyonlar
function updateReportData(data) {
    // Grafikleri güncelle
    data.charts.forEach(chart => {
        const container = document.querySelector(`[data-chart-id="${chart.id}"]`);
        if (container) {
            const ctx = container.querySelector('canvas').getContext('2d');
            createChart(ctx, chart.type, chart.data);
        }
    });

    // Tabloları güncelle
    data.tables.forEach(table => {
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

    // Özet bilgileri güncelle
    updateSummaryCards(data.summary);
}

function updateSummaryCards(summary) {
    Object.entries(summary).forEach(([key, value]) => {
        const card = document.querySelector(`[data-summary-key="${key}"]`);
        if (card) {
            const valueElement = card.querySelector('.summary-value');
            const changeElement = card.querySelector('.summary-change');
            
            if (valueElement) {
                valueElement.textContent = formatCurrency(value.value);
            }
            
            if (changeElement) {
                changeElement.textContent = `${value.change > 0 ? '+' : ''}${value.change}%`;
                changeElement.className = `summary-change ${value.change >= 0 ? 'positive' : 'negative'}`;
            }
        }
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}

function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'report-loading';
    loading.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.report-loading');
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