document.addEventListener('DOMContentLoaded', function() {
    initializeBudgetForms();
    initializeBudgetList();
    loadBudgetStats();
});

// Form İşlemleri
function initializeBudgetForms() {
    const forms = document.querySelectorAll('.budget-form');
    forms.forEach(form => {
        // Para formatı
        const amountInputs = form.querySelectorAll('.amount-input');
        amountInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value) {
                    value = parseInt(value, 10).toLocaleString('tr-TR');
                }
                e.target.value = value;
            });
        });

        // Form gönderimi
        form.addEventListener('submit', handleBudgetSubmit);
    });
}

// Liste İşlemleri
function initializeBudgetList() {
    const budgetTable = document.querySelector('#budgetTable');
    if (budgetTable) {
        new DataTable(budgetTable, {
            responsive: true,
            language: {
                url: '/gelirgider/public/js/datatables-tr.json'
            }
        });

        // Silme işlemi için onay
        const deleteButtons = document.querySelectorAll('.delete-budget');
        deleteButtons.forEach(button => {
            button.addEventListener('click', handleBudgetDelete);
        });
    }
}

// İstatistik Yükleme
function loadBudgetStats() {
    fetch('/gelirgider/budgets/getStats')
        .then(response => response.json())
        .then(data => {
            updateBudgetStats(data);
        })
        .catch(error => {
            console.error('İstatistik yüklenirken hata:', error);
            showNotification('İstatistikler yüklenirken bir hata oluştu', 'error');
        });
}

// CRUD İşlemleri
function addBudget(formData) {
    return fetch('/gelirgider/budgets/add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json());
}

function editBudget(formData) {
    return fetch('/gelirgider/budgets/edit', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json());
}

function deleteBudget(id) {
    return fetch('/gelirgider/budgets/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json());
}

// Event Handlers
function handleBudgetSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const isEdit = form.dataset.edit === 'true';

    const action = isEdit ? editBudget(formData) : addBudget(formData);
    
    action.then(response => {
        if (response.success) {
            showNotification(response.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(response.message, 'error');
        }
    })
    .catch(error => {
        console.error('İşlem sırasında hata:', error);
        showNotification('İşlem sırasında bir hata oluştu', 'error');
    });
}

function handleBudgetDelete(e) {
    e.preventDefault();
    const button = e.target;
    const id = button.dataset.id;
    
    if (confirm('Bu bütçeyi silmek istediğinizden emin misiniz?')) {
        deleteBudget(id)
            .then(response => {
                if (response.success) {
                    showNotification(response.message, 'success');
                    button.closest('tr').remove();
                } else {
                    showNotification(response.message, 'error');
                }
            })
            .catch(error => {
                console.error('Silme işlemi sırasında hata:', error);
                showNotification('Silme işlemi sırasında bir hata oluştu', 'error');
            });
    }
}

// Yardımcı Fonksiyonlar
function updateBudgetStats(data) {
    // Toplam bütçe
    const totalElement = document.querySelector('#totalBudget');
    if (totalElement) {
        totalElement.textContent = formatCurrency(data.total);
    }

    // Kullanılan bütçe
    const usedElement = document.querySelector('#usedBudget');
    if (usedElement) {
        usedElement.textContent = formatCurrency(data.used);
    }

    // Kalan bütçe
    const remainingElement = document.querySelector('#remainingBudget');
    if (remainingElement) {
        remainingElement.textContent = formatCurrency(data.remaining);
    }

    // İlerleme çubukları
    const progressBars = document.querySelectorAll('.budget-progress .progress-bar');
    progressBars.forEach(bar => {
        const budgetId = bar.dataset.budgetId;
        const budget = data.budgets.find(b => b.id === budgetId);
        if (budget) {
            const percentage = (budget.used / budget.total) * 100;
            bar.style.width = `${percentage}%`;
            
            if (percentage >= 90) {
                bar.classList.add('danger');
            } else if (percentage >= 75) {
                bar.classList.add('warning');
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