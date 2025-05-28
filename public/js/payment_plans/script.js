$(document).ready(function() {
    // DataTable initialization
    $('#paymentPlansTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        },
        order: [[8, 'asc']], // Sonraki ödeme tarihine göre sırala
        pageLength: 25
    });

    // Load statistics
    loadStatistics();

    // Load upcoming payments
    loadUpcomingPayments();

    // Delete payment plan
    $('.delete-plan').click(function() {
        if (confirm('Bu ödeme planını silmek istediğinizden emin misiniz?')) {
            const id = $(this).data('id');
            
            $.ajax({
                url: '/gelirgider/app/controllers/PaymentPlanController.php',
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

    // Toggle plan status
    $('.toggle-plan').click(function() {
        const id = $(this).data('id');
        const status = $(this).data('status');
        
        $.ajax({
            url: '/gelirgider/app/controllers/PaymentPlanController.php',
            type: 'POST',
            data: {
                action: 'toggle',
                id: id,
                status: status === 'active' ? 'pending' : 'active'
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

    // Load statistics
    function loadStatistics() {
        $.ajax({
            url: '/gelirgider/app/controllers/PaymentPlanController.php',
            type: 'GET',
            data: {
                action: 'getStatistics'
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    
                    $('#thisMonthPayments').text(formatCurrency(stats.this_month_payments));
                    $('#totalDebt').text(formatCurrency(stats.total_debt));
                    $('#completedAmount').text(formatCurrency(stats.completed_amount));
                    $('#overduePayments').text(stats.overdue_payments);
                    
                    // Update statistics cards
                    updateStatisticsCards(stats);
                }
            }
        });
    }

    // Load upcoming payments
    function loadUpcomingPayments() {
        $.ajax({
            url: '/gelirgider/app/controllers/PaymentPlanController.php',
            type: 'GET',
            data: {
                action: 'getUpcomingPayments'
            },
            success: function(response) {
                if (response.success) {
                    const payments = response.data;
                    let html = '';
                    
                    if (payments.length > 0) {
                        html = '<div class="table-responsive"><table class="table table-sm">';
                        html += '<thead><tr><th>Tarih</th><th>Plan</th><th>Tutar</th><th>Durum</th></tr></thead><tbody>';
                        
                        payments.forEach(function(payment) {
                            html += `<tr>
                                <td>${formatDate(payment.due_date)}</td>
                                <td>${payment.plan_title}</td>
                                <td class="text-end">${formatCurrency(payment.amount)}</td>
                                <td><span class="badge bg-${getStatusColor(payment.status)}">${getStatusLabel(payment.status)}</span></td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                    } else {
                        html = '<p class="text-muted mb-0">Yaklaşan ödeme bulunmuyor.</p>';
                    }
                    
                    $('#upcomingPayments').html(html);
                }
            }
        });
    }

    // Update statistics cards
    function updateStatisticsCards(stats) {
        const cards = [
            {
                title: 'Toplam Plan',
                value: stats.total_plans,
                icon: 'fa-calendar',
                color: 'primary'
            },
            {
                title: 'Aktif Plan',
                value: stats.active_plans,
                icon: 'fa-check-circle',
                color: 'success'
            },
            {
                title: 'Bekleyen Plan',
                value: stats.pending_plans,
                icon: 'fa-clock',
                color: 'warning'
            },
            {
                title: 'Tamamlanan Plan',
                value: stats.completed_plans,
                icon: 'fa-flag-checkered',
                color: 'info'
            }
        ];
        
        let html = '';
        cards.forEach(function(card) {
            html += `
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-${card.color} shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-${card.color} text-uppercase mb-1">${card.title}</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">${card.value}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas ${card.icon} fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#statisticsCards').html(html);
    }

    // Helper functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY'
        }).format(amount);
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('tr-TR');
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'active': 'primary',
            'completed': 'success',
            'cancelled': 'secondary',
            'overdue': 'danger'
        };
        return colors[status] || 'secondary';
    }

    function getStatusLabel(status) {
        const labels = {
            'pending': 'Bekliyor',
            'active': 'Aktif',
            'completed': 'Tamamlandı',
            'cancelled': 'İptal',
            'overdue': 'Gecikmiş'
        };
        return labels[status] || status;
    }
}); 