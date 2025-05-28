// Kredi kartı işlemleri için JavaScript fonksiyonları

// Yeni kredi kartı ekleme
function addCreditCard() {
    window.location.href = '/gelirgider/app/views/credit-cards/add.php';
}

// Kredi kartı düzenleme
function editCreditCard(cardId) {
    window.location.href = `/gelirgider/app/views/credit-cards/edit.php?id=${cardId}`;
}

// Kredi kartı silme
function deleteCreditCard(cardId) {
    if (confirm('Bu kredi kartını silmek istediğinizden emin misiniz?')) {
        fetch(`/gelirgider/app/controllers/CreditCardController.php?action=delete&id=${cardId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Kredi kartı silinirken bir hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}

// Harcama ekleme
function addTransaction(cardId) {
    window.location.href = `/gelirgider/app/views/credit-cards/add-transaction.php?card_id=${cardId}`;
}

// Ödeme yapma
function makePayment(cardId) {
    window.location.href = `/gelirgider/app/views/credit-cards/make-payment.php?card_id=${cardId}`;
}

// İşlemleri görüntüleme
function viewTransactions(cardId) {
    window.location.href = `/gelirgider/app/views/credit-cards/transactions.php?card_id=${cardId}`;
}

// Yaklaşan ödeme düzenleme
function editUpcomingPayment(cardId) {
    window.location.href = `/gelirgider/app/views/credit-cards/edit-upcoming-payment.php?card_id=${cardId}`;
}

// Yaklaşan ödeme silme
function deleteUpcomingPayment(cardId) {
    if (confirm('Bu yaklaşan ödemeyi silmek istediğinizden emin misiniz?')) {
        fetch(`/gelirgider/app/controllers/CreditCardController.php?action=deleteUpcomingPayment&card_id=${cardId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Yaklaşan ödeme silinirken bir hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}

// Sayfa yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    // Tooltip'leri etkinleştir
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Dropdown menüleri etkinleştir
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}); 