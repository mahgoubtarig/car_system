/**
 * لوحة التحكم - JavaScript
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // ============================================================
        // Toggle Sidebar (Mobile)
        // ============================================================
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && sidebar && sidebarOverlay) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('show');
            });
            
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
            });
        }
        
        // ============================================================
        // Dropdowns
        // ============================================================
        function setupDropdown(toggleId, menuId) {
            const toggle = document.getElementById(toggleId);
            const menu = document.getElementById(menuId);
            
            if (!toggle || !menu) return;
            
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // إغلاق باقي القوائم
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                
                menu.classList.toggle('show');
            });
        }
        
        setupDropdown('userToggle', 'userMenu');
        setupDropdown('notificationsToggle', 'notificationsMenu');
        
        // إغلاق عند النقر خارج القوائم
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                m.classList.remove('show');
            });
        });
        
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', (e) => e.stopPropagation());
        });
        
        // ============================================================
        // Chart.js - Revenue Chart
        // ============================================================
        if (typeof Chart !== 'undefined' && window.APP_CONFIG.chartData) {
            const canvas = document.getElementById('revenueChart');
            if (canvas) {
                const data = window.APP_CONFIG.chartData;
                const isRTL = window.APP_CONFIG.direction === 'rtl';
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                
                const textColor = isDark ? '#e0e0e0' : '#212529';
                const gridColor = isDark ? '#404040' : '#e1e4e8';
                
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: data.months.map(m => {
                            const [y, mo] = m.split('-');
                            const months = isRTL
                                ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
                                : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                            return months[parseInt(mo) - 1] + ' ' + y.slice(2);
                        }),
                        datasets: [
                            {
                                label: isRTL ? 'الإيرادات' : 'Income',
                                data: data.income,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                            {
                                label: isRTL ? 'المصروفات' : 'Expenses',
                                data: data.expenses,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: textColor, font: { family: 'Cairo' } },
                            },
                        },
                        scales: {
                            x: {
                                ticks: { color: textColor, font: { family: 'Cairo' } },
                                grid: { color: gridColor },
                            },
                            y: {
                                ticks: { color: textColor, font: { family: 'Cairo' } },
                                grid: { color: gridColor },
                                beginAtZero: true,
                            },
                        },
                    },
                });
            }
        }
        
        // ============================================================
        // Notifications (AJAX)
        // ============================================================
        function loadNotifications() {
            const list = document.getElementById('notificationsList');
            const count = document.getElementById('notificationCount');
            
            if (!list) return;
            
            fetch(window.APP_CONFIG.baseUrl + '/api/notifications', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.items.length > 0) {
                    count.textContent = data.count;
                    count.style.display = 'flex';
                    
                    list.innerHTML = data.items.map(n => `
                        <div class="notification-item">
                            <span class="notification-dot"></span>
                            <div>
                                <strong>${n.type}</strong>
                                <small>${n.due_date}</small>
                            </div>
                        </div>
                    `).join('');
                } else {
                    count.style.display = 'none';
                }
            })
            .catch(() => {
                // تجاهل الأخطاء
            });
        }
        
        // تحميل التنبيهات بعد 2 ثانية
        setTimeout(loadNotifications, 2000);
    });
})();