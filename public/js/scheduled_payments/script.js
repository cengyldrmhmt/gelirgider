$(document).ready(function() {
    // DataTable initialization
    $('#paymentsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        }
    });

    // Load categories and wallets for dropdowns
    loadCategories();
    loadWallets();

    // Save payment
    $('#savePayment').click(function() {
        const formData = new FormData($('#addPaymentForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/ScheduledPaymentController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Edit payment
    $('.edit-payment').click(function() {
        const id = $(this).data('id');
        const description = $(this).data('description');
        const categoryId = $(this).data('category-id');
        const walletId = $(this).data('wallet-id');
        const type = $(this).data('type');
        const amount = $(this).data('amount');
        const frequency = $(this).data('frequency');
        const startDate = $(this).data('start-date');
        const endDate = $(this).data('end-date');
        const isActive = $(this).data('is-active');

        $('#editPaymentForm [name="id"]').val(id);
        $('#editPaymentForm [name="description"]').val(description);
        $('#editPaymentForm [name="category_id"]').val(categoryId);
        $('#editPaymentForm [name="wallet_id"]').val(walletId);
        $('#editPaymentForm [name="type"]').val(type);
        $('#editPaymentForm [name="amount"]').val(amount);
        $('#editPaymentForm [name="frequency"]').val(frequency);
        $('#editPaymentForm [name="start_date"]').val(startDate);
        $('#editPaymentForm [name="end_date"]').val(endDate);
        $('#editPaymentForm [name="is_active"]').prop('checked', isActive);

        $('#editPaymentModal').modal('show');
    });

    // Update payment
    $('#updatePayment').click(function() {
        const formData = new FormData($('#editPaymentForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/ScheduledPaymentController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Toggle payment status
    $('.toggle-payment').click(function() {
        const id = $(this).data('id');
        const isActive = $(this).data('is-active');
        
        $.ajax({
            url: '/gelirgider/app/controllers/ScheduledPaymentController.php',
            type: 'POST',
            data: {
                action: 'toggle',
                id: id,
                is_active: !isActive
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Delete payment
    $('.delete-payment').click(function() {
        if (confirm('Bu planlanan ödemeyi silmek istediğinizden emin misiniz?')) {
            const id = $(this).data('id');
            
            $.ajax({
                url: '/gelirgider/app/controllers/ScheduledPaymentController.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }
    });

    // Load categories
    function loadCategories() {
        $.ajax({
            url: '/gelirgider/app/controllers/CategoryController.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const categories = response.data;
                    let options = '<option value="">Seçiniz</option>';
                    
                    categories.forEach(function(category) {
                        options += `<option value="${category.id}">${category.name}</option>`;
                    });
                    
                    $('select[name="category_id"]').html(options);
                }
            }
        });
    }

    // Load wallets
    function loadWallets() {
        $.ajax({
            url: '/gelirgider/app/controllers/WalletController.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const wallets = response.data;
                    let options = '<option value="">Seçiniz</option>';
                    
                    wallets.forEach(function(wallet) {
                        options += `<option value="${wallet.id}">${wallet.name}</option>`;
                    });
                    
                    $('select[name="wallet_id"]').html(options);
                }
            }
        });
    }
}); 