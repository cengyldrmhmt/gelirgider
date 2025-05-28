$(document).ready(function() {
    // Profil güncelleme
    $('#profileForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '/gelirgider/app/controllers/ProfileController.php',
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

    // Şifre değiştirme
    $('#changePasswordForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '/gelirgider/app/controllers/ProfileController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#changePasswordModal').modal('hide');
                    alert('Şifreniz başarıyla değiştirildi.');
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Profil fotoğrafı yükleme
    $('#profilePhoto').change(function() {
        const file = this.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('photo', file);
            
            $.ajax({
                url: '/gelirgider/app/controllers/ProfileController.php',
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
        }
    });
}); 