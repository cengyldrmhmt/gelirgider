$(document).ready(function() {
    // Yeni hedef kaydetme
    $('#saveGoal').click(function() {
        const formData = new FormData($('#addGoalForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/FinancialGoalController.php?action=create',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Hedef başarıyla oluşturuldu');
                    $('#addGoalModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Bir hata oluştu');
                }
            },
            error: function() {
                toastr.error('Bir hata oluştu');
            }
        });
    });

    // Hedef düzenleme
    $('.edit-goal').click(function(e) {
        e.preventDefault();
        const data = $(this).data();
        
        $('#editGoalForm input[name="id"]').val(data.id);
        $('#editGoalForm input[name="title"]').val(data.title);
        $('#editGoalForm textarea[name="description"]').val(data.description);
        $('#editGoalForm input[name="target_amount"]').val(data.targetAmount);
        $('#editGoalForm input[name="current_amount"]').val(data.currentAmount);
        $('#editGoalForm input[name="target_date"]').val(data.targetDate);
        $('#editGoalForm select[name="category_id"]').val(data.categoryId);
        $('#editGoalForm select[name="wallet_id"]').val(data.walletId);
        $('#editGoalForm select[name="status"]').val(data.status);
        
        $('#editGoalModal').modal('show');
    });

    // Hedef güncelleme
    $('#updateGoal').click(function() {
        const formData = new FormData($('#editGoalForm')[0]);
        
        $.ajax({
            url: '/gelirgider/app/controllers/FinancialGoalController.php?action=update',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Hedef başarıyla güncellendi');
                    $('#editGoalModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Bir hata oluştu');
                }
            },
            error: function() {
                toastr.error('Bir hata oluştu');
            }
        });
    });

    // Hedef silme
    $('.delete-goal').click(function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        if (confirm('Bu hedefi silmek istediğinizden emin misiniz?')) {
            $.ajax({
                url: '/gelirgider/app/controllers/FinancialGoalController.php?action=delete',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Hedef başarıyla silindi');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        toastr.error(response.message || 'Bir hata oluştu');
                    }
                },
                error: function() {
                    toastr.error('Bir hata oluştu');
                }
            });
        }
    });
}); 