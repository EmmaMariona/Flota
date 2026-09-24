

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle en dispositivos móviles / tabletas
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        let backdrop = null;

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');

            if (sidebar.classList.contains('show')) {
                backdrop = document.createElement('div');
                backdrop.className = 'sidebar-backdrop';
                document.body.appendChild(backdrop);

                backdrop.addEventListener('click', () => {
                    sidebar.classList.remove('show');
                    if (backdrop) backdrop.remove();
                });
            } else if (backdrop) {
                backdrop.remove();
            }
        });
    }

    // 2. Diálogos de confirmación para eliminar o acciones críticas
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', (e) => {
            const mensaje = element.getAttribute('data-confirm') || '¿Estás seguro de realizar esta acción?';
            if (!confirm(mensaje)) {
                e.preventDefault();
            }
        });
    });

    // 3. Auto-cerrar alertas flash después de 5 segundos
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});