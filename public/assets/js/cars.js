/**
 * وحدة السيارات - JavaScript
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // ============================================================
        // تغيير حالة السيارة (AJAX)
        // ============================================================
        document.querySelectorAll('[data-change-status]').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                
                const carId = this.dataset.carId;
                const status = this.dataset.changeStatus;
                
                if (!confirm('هل أنت متأكد من تغيير الحالة؟')) return;
                
                try {
                    const formData = new FormData();
                    formData.append('_csrf_token', window.APP_CONFIG.csrfToken);
                    formData.append('status', status);
                    
                    const response = await fetch(window.APP_CONFIG.baseUrl + '/cars/' + carId + '/status', {
                        method: 'POST',
                        body: formData,
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ');
                    }
                } catch (error) {
                    console.error('Status change error:', error);
                    alert('حدث خطأ في الاتصال');
                }
            });
        });
        
        // ============================================================
        // التحقق من النموذج قبل الإرسال
        // ============================================================
        const carForm = document.querySelector('.car-form');
        if (carForm) {
            carForm.addEventListener('submit', function(e) {
                const plate = document.getElementById('plate_number');
                const chassis = document.getElementById('chassis_number');
                
                if (plate && plate.value.trim() === '') {
                    e.preventDefault();
                    alert('رقم اللوحة مطلوب');
                    plate.focus();
                    return false;
                }
                
                if (chassis && chassis.value.trim() === '') {
                    e.preventDefault();
                    alert('رقم الهيكل مطلوب');
                    chassis.focus();
                    return false;
                }
            });
        }
        
        // ============================================================
        // حذف سيارة
        // ============================================================
        document.querySelectorAll('[data-delete-car]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!confirm('هل أنت متأكد من حذف السيارة؟ لا يمكن التراجع!')) return;
                
                const form = this.closest('form');
                if (form) form.submit();
            });
        });
    });
})();