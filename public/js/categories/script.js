document.addEventListener('DOMContentLoaded', function() {
    initializeCategoryForms();
    initializeCategoryList();
    loadCategoryStats();
});

// Form İşlemleri
function initializeCategoryForms() {
    const forms = document.querySelectorAll('.category-form');
    forms.forEach(form => {
        form.addEventListener('submit', handleCategorySubmit);
        
        // İkon seçimi
        const iconSelect = form.querySelector('select[name="icon"]');
        if (iconSelect) {
            iconSelect.addEventListener('change', updateIconPreview);
        }
        
        // Renk seçimi
        const colorInput = form.querySelector('input[name="color"]');
        if (colorInput) {
            colorInput.addEventListener('change', updateIconPreview);
        }
    });
}

// Liste İşlemleri
function initializeCategoryList() {
    const table = document.querySelector('.category-list table');
    if (table) {
        new DataTable(table, {
            language: {
                url: '/gelirgider/public/js/datatables-tr.json'
            },
            responsive: true,
            order: [[0, 'asc']]
        });
    }
    
    // Silme işlemi için onay
    const deleteButtons = document.querySelectorAll('.delete-category');
    deleteButtons.forEach(button => {
        button.addEventListener('click', handleCategoryDelete);
    });
}

// İstatistik Yükleme
function loadCategoryStats() {
    fetch('/gelirgider/app/controllers/CategoryController.php?action=getStats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCategoryStats(data.data);
            } else {
                console.error('İstatistik yüklenirken hata:', data.message);
            }
        })
        .catch(error => {
            console.error('İstatistik yüklenirken hata:', error);
        });
}

// CRUD İşlemleri
function addCategory(formData) {
    return fetch('/gelirgider/app/controllers/CategoryController.php?action=create', {
        method: 'POST',
        body: formData
    }).then(response => response.json());
}

function editCategory(id, formData) {
    formData.append('id', id);
    return fetch('/gelirgider/app/controllers/CategoryController.php?action=update', {
        method: 'POST',
        body: formData
    }).then(response => response.json());
}

function deleteCategory(id) {
    const formData = new FormData();
    formData.append('id', id);
    return fetch('/gelirgider/app/controllers/CategoryController.php?action=delete', {
        method: 'POST',
        body: formData
    }).then(response => response.json());
}

// Event Handlers
function handleCategorySubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    const action = id ? editCategory(id, formData) : addCategory(formData);
    
    action.then(response => {
        if (response.success) {
            showNotification('success', response.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification('error', response.message);
        }
    }).catch(error => {
        console.error('İşlem sırasında hata:', error);
        showNotification('error', 'Bir hata oluştu');
    });
}

function handleCategoryDelete(event) {
    event.preventDefault();
    const button = event.target;
    const id = button.dataset.id;
    
    if (confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')) {
        deleteCategory(id)
            .then(response => {
                if (response.success) {
                    showNotification('success', response.message);
                    button.closest('tr').remove();
                } else {
                    showNotification('error', response.message);
                }
            })
            .catch(error => {
                console.error('Silme işlemi sırasında hata:', error);
                showNotification('error', 'Silme işlemi sırasında bir hata oluştu');
            });
    }
}

// Yardımcı Fonksiyonlar
function updateIconPreview() {
    const form = this.closest('form');
    const iconSelect = form.querySelector('select[name="icon"]');
    const colorInput = form.querySelector('input[name="color"]');
    const preview = form.querySelector('.icon-preview');
    
    if (preview && iconSelect && colorInput) {
        const icon = iconSelect.value;
        const color = colorInput.value;
        
        preview.style.backgroundColor = color;
        preview.innerHTML = `<i class="fas fa-${icon}"></i>`;
    }
}

function updateCategoryStats(data) {
    // Toplam kategori sayısı
    const totalCategoriesElement = document.querySelector('.total-categories');
    if (totalCategoriesElement) {
        totalCategoriesElement.textContent = data.totalCategories;
    }
    
    // Gelir kategorileri
    const incomeCategoriesElement = document.querySelector('.income-categories');
    if (incomeCategoriesElement) {
        incomeCategoriesElement.textContent = data.incomeCategories;
    }
    
    // Gider kategorileri
    const expenseCategoriesElement = document.querySelector('.expense-categories');
    if (expenseCategoriesElement) {
        expenseCategoriesElement.textContent = data.expenseCategories;
    }
    
    // En çok kullanılan kategori
    const mostUsedCategoryElement = document.querySelector('.most-used-category');
    if (mostUsedCategoryElement && data.mostUsedCategory) {
        mostUsedCategoryElement.textContent = data.mostUsedCategory.name;
        mostUsedCategoryElement.style.color = data.mostUsedCategory.color;
    }
}

function showNotification(type, message) {
    // Toastr kullan, eğer yoksa fallback alert
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        // Toastr yoksa alert kullansın
        alert(message);
    }
} 