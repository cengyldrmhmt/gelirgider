$(document).ready(function() {
    if ($('#notificationsTable').length) {
        $('#notificationsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
            },
            "order": [[3, "desc"]],
            "pageLength": 25,
            "responsive": true
        });
    }
}); 