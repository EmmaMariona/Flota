/**
 * APP.JS - Cliente Frontend para el Sistema de Gestión de Flota
 * Conexión vía Fetch API con el Backend MVC
 */

const BASE_URL = '/flota';
const API_URL = `${BASE_URL}/backend/controllers`;

const App = {
    currentUser: null,

    /**
     * Realiza peticiones a la API del Backend
     */
    async api(url, options = {}) {
        options.credentials = 'same-origin';
        if (!options.headers) {
            options.headers = {};
        }
        if (!options.headers['Accept']) {
            options.headers['Accept'] = 'application/json';
        }

        try {
            const response = await fetch(url, options);
            if (response.status === 401) {
                window.location.href = `${BASE_URL}/frontend/login.html`;
                return { success: false, mensaje: 'Sesión expirada' };
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error en llamada API:', error);
            return { success: false, mensaje: 'Error de comunicación con el servidor.' };
        }
    },

    /**
     * Inicializa el layout institucional (Sidebar, Navbar, Footer, Auth Check)
     */
    async initLayout(activePage = '') {
        if (window.location.pathname.endsWith('/vehiculos/crear.html') && !document.getElementById('select-marca')) {
            window.location.replace(`${BASE_URL}/frontend/vehiculos/crear.html?v=20260927`);
            return false;
        }

        // 1. Verificar sesión activa
        const check = await this.api(`${API_URL}/AuthController.php?accion=check`);
        if (!check || !check.success || !check.data || !check.data.authenticated) {
            window.location.href = `${BASE_URL}/frontend/login.html?v=20260927`;
            return false;
        }

        this.currentUser = check.data.usuario;
        window.currentUser = this.currentUser;

        // 2. Renderizar Sidebar si existe contenedor
        const sidebarContainer = document.getElementById('sidebar-container');
        if (sidebarContainer) {
            sidebarContainer.innerHTML = this.renderSidebar(activePage);
        }

        // 3. Renderizar Navbar si existe contenedor
        const navbarContainer = document.getElementById('navbar-container');
        if (navbarContainer) {
            navbarContainer.innerHTML = this.renderNavbar();
        }

        // 4. Renderizar Footer si existe contenedor
        const footerContainer = document.getElementById('footer-container');
        if (footerContainer) {
            footerContainer.innerHTML = `
                <footer class="mt-auto py-3 bg-dark border-top border-secondary border-opacity-25 px-4 text-center text-secondary small">
                    <span>&copy; ${new Date().getFullYear()} Sistema de Gestión de Flota Vehicular | Proyecto de Ingeniería en Computación</span>
                </footer>
            `;
        }

        // 5. Configurar listeners de interfaz (toggle móvil, logout, data-confirm)
        this.bindEvents();

        // 6. Mostrar alertas pendientes de sessionStorage
        this.checkFlashAlerts();

        // 7. Mostrar u ocultar elementos por rol
        this.applyRolePermissions();

        return true;
    },

    renderSidebar(activePage) {
        const u = this.currentUser || { nombre: 'Usuario', rol: 'Consulta' };
        const isAdmin = u.rol === 'Administrador';
        const puedeEditar = (u.rol === 'Administrador' || u.rol === 'Supervisor');
        const v = '?v=20260927';

        return `
        <aside class="sidebar bg-dark text-white d-flex flex-column flex-shrink-0 p-3">
            <a href="${BASE_URL}/frontend/index.html${v}" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none px-2">
                <div class="brand-icon me-2 rounded d-flex align-items-center justify-content-center bg-primary text-white">
                    <i class="bi bi-truck fs-4"></i>
                </div>
                <div>
                    <span class="fs-5 fw-bold tracking-wide">FLOTA</span>
                </div>
            </a>
            
            <hr class="border-secondary opacity-25 my-3">

            <ul class="nav nav-pills flex-column mb-auto gap-1">
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/index.html${v}" class="nav-link ${activePage === 'dashboard' ? 'active' : ''}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/vehiculos/index.html${v}" class="nav-link ${activePage === 'vehiculos' ? 'active' : ''}">
                        <i class="bi bi-truck-front me-2"></i> Vehículos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/mantenimientos/index.html${v}" class="nav-link ${activePage === 'mantenimientos' ? 'active' : ''}">
                        <i class="bi bi-tools me-2"></i> Mantenimientos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/llantas/index.html${v}" class="nav-link ${activePage === 'llantas' ? 'active' : ''}">
                        <i class="bi bi-record-circle me-2"></i> Llantas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/reportes/index.html${v}" class="nav-link ${activePage === 'reportes' ? 'active' : ''}">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i> Reportes
                    </a>
                </li>

                ${puedeEditar ? `
                <li class="nav-header text-uppercase text-secondary fw-semibold mt-3 mb-1 px-3" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    Administración
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/sucursales/index.html${v}" class="nav-link ${activePage === 'sucursales' ? 'active' : ''}">
                        <i class="bi bi-geo-alt me-2"></i> Sucursales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/catalogos/index.html${v}" class="nav-link ${activePage === 'catalogos' ? 'active' : ''}">
                        <i class="bi bi-tags me-2"></i> Marcas y Modelos
                    </a>
                </li>
                ` : ''}
                ${isAdmin ? `
                <li class="nav-item">
                    <a href="${BASE_URL}/frontend/usuarios/index.html${v}" class="nav-link ${activePage === 'usuarios' ? 'active' : ''}">
                        <i class="bi bi-people me-2"></i> Usuarios y Roles
                    </a>
                </li>
                ` : ''}
            </ul>

            <hr class="border-secondary opacity-25 my-3">

            <div class="user-profile-badge p-2 rounded d-flex align-items-center">
                <div class="avatar-circle me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                    ${this.escapeHtml(u.nombre.charAt(0).toUpperCase())}
                </div>
                <div class="user-info overflow-hidden">
                    <span class="d-block text-truncate fw-semibold small text-light">${this.escapeHtml(u.nombre)}</span>
                    <span class="d-block text-truncate text-secondary" style="font-size: 0.72rem;">${this.escapeHtml(u.rol)}</span>
                </div>
            </div>
        </aside>
        `;
    },

    renderNavbar() {
        const u = this.currentUser || { nombre: 'Usuario', email: '', rol: 'Consulta' };

        return `
        <nav class="navbar navbar-expand bg-dark border-bottom border-secondary border-opacity-25 px-4 py-2">
            <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-secondary d-md-none me-2" type="button" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark dropdown-toggle d-flex align-items-center gap-2 border border-secondary border-opacity-50 px-3 py-2 rounded-pill" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-primary"></i>
                            <span class="small fw-semibold">${this.escapeHtml(u.nombre)}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                            <li class="dropdown-header">
                                <strong>${this.escapeHtml(u.email)}</strong><br>
                                <small class="text-secondary">Rol: ${this.escapeHtml(u.rol)}</small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="#" id="btnLogout">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        `;
    },

    bindEvents() {
        // Toggle Sidebar Móvil
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

        // Logout
        const btnLogout = document.getElementById('btnLogout');
        if (btnLogout) {
            btnLogout.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.api(`${API_URL}/AuthController.php?accion=logout`);
                window.location.href = `${BASE_URL}/frontend/login.html`;
            });
        }

        // Diálogos de confirmación
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', (e) => {
                const msg = el.getAttribute('data-confirm') || '¿Deseas continuar?';
                if (!confirm(msg)) {
                    e.preventDefault();
                }
            });
        });
    },

    applyRolePermissions() {
        const u = this.currentUser;
        if (!u) return;
        const puedeEditar = (u.rol === 'Administrador' || u.rol === 'Supervisor');
        if (!puedeEditar) {
            document.querySelectorAll('.requires-edit').forEach(el => el.style.display = 'none');
        }
        if (u.rol !== 'Administrador') {
            document.querySelectorAll('.requires-admin').forEach(el => el.style.display = 'none');
        }
    },

    showAlert(type, message, containerId = 'alerts-container') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const icon = type === 'success' ? 'check-circle-fill' : (type === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill');
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-${icon} me-2 fs-5"></i>
                <div class="flex-grow-1">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        container.innerHTML = alertHtml;

        setTimeout(() => {
            const alertEl = container.querySelector('.alert');
            if (alertEl) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                if (bsAlert) bsAlert.close();
            }
        }, 5000);
    },

    flashAlert(type, message) {
        sessionStorage.setItem('flota_flash', JSON.stringify({ type, message }));
    },

    checkFlashAlerts() {
        const flash = sessionStorage.getItem('flota_flash');
        if (flash) {
            try {
                const { type, message } = JSON.parse(flash);
                this.showAlert(type, message);
            } catch (e) {}
            sessionStorage.removeItem('flota_flash');
        }
    },

    /**
     * Diálogo modal interactivo con estilos de Bootstrap y Dark Theme del sistema.
     * Devuelve una Promesa con true si confirma ('Sí') o false si cancela ('No').
     */
    confirmModal({
        title = 'Advertencia',
        message = 'Sucursal incorrecta, ¿desea continuar?',
        detail = '',
        btnConfirmText = 'Sí',
        btnCancelText = 'No',
        confirmClass = 'btn-warning text-dark',
        cancelClass = 'btn-outline-secondary',
        icon = 'bi-exclamation-triangle-fill text-warning'
    } = {}) {
        return new Promise((resolve) => {
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                const promptText = detail ? `${message}\n(${detail.replace(/<[^>]+>/g, '')})` : message;
                return resolve(window.confirm(promptText));
            }

            let modalEl = document.getElementById('app-confirm-modal');
            if (!modalEl) {
                modalEl = document.createElement('div');
                modalEl.id = 'app-confirm-modal';
                modalEl.className = 'modal fade';
                modalEl.setAttribute('tabindex', '-1');
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.setAttribute('data-bs-backdrop', 'static');
                modalEl.setAttribute('data-bs-keyboard', 'false');
                modalEl.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark border-secondary border-opacity-50 text-light shadow">
                            <div class="modal-header border-secondary border-opacity-25">
                                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="app-confirm-title"></h5>
                                <button type="button" class="btn-close" id="app-confirm-close" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body py-4">
                                <p class="mb-2 fs-6 fw-semibold text-light" id="app-confirm-message"></p>
                                <div class="text-secondary small mb-0" id="app-confirm-detail"></div>
                            </div>
                            <div class="modal-footer border-secondary border-opacity-25 d-flex justify-content-end gap-2">
                                <button type="button" class="btn px-3" id="app-confirm-btn-no"></button>
                                <button type="button" class="btn px-4 fw-semibold" id="app-confirm-btn-si"></button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalEl);
            }

            const titleEl = document.getElementById('app-confirm-title');
            const msgEl = document.getElementById('app-confirm-message');
            const detailEl = document.getElementById('app-confirm-detail');
            const btnNo = document.getElementById('app-confirm-btn-no');
            const btnSi = document.getElementById('app-confirm-btn-si');
            const btnClose = document.getElementById('app-confirm-close');

            titleEl.innerHTML = `<i class="bi ${icon}"></i> <span>${this.escapeHtml(title)}</span>`;
            msgEl.textContent = message;
            if (detail) {
                detailEl.innerHTML = detail;
                detailEl.style.display = 'block';
            } else {
                detailEl.style.display = 'none';
            }

            btnNo.textContent = btnCancelText;
            btnNo.className = `btn ${cancelClass} px-3`;

            btnSi.textContent = btnConfirmText;
            btnSi.className = `btn ${confirmClass} px-4 fw-semibold`;

            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

            let resolved = false;
            const finalize = (result) => {
                if (resolved) return;
                resolved = true;
                btnSi.removeEventListener('click', handleSi);
                btnNo.removeEventListener('click', handleNo);
                btnClose.removeEventListener('click', handleNo);
                modalEl.removeEventListener('hidden.bs.modal', handleHidden);
                bsModal.hide();
                resolve(result);
            };

            const handleSi = () => finalize(true);
            const handleNo = () => finalize(false);
            const handleHidden = () => finalize(false);

            btnSi.addEventListener('click', handleSi);
            btnNo.addEventListener('click', handleNo);
            btnClose.addEventListener('click', handleNo);
            modalEl.addEventListener('hidden.bs.modal', handleHidden);

            bsModal.show();
        });
    },

    escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    },

    formatMoney(val) {
        const num = parseFloat(val) || 0;
        return 'L. ' + num.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    formatDate(str) {
        if (!str) return '—';
        const parts = str.split(' ')[0].split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return str;
    },

    formatBytes(bytes) {
        const b = parseInt(bytes, 10) || 0;
        if (b <= 0) return '';
        if (b < 1024) return `${b} B`;
        if (b < 1024 * 1024) return `${(b / 1024).toFixed(1)} KB`;
        return `${(b / (1024 * 1024)).toFixed(2)} MB`;
    },

    /**
     * Valida en frontend un archivo adjunto según las políticas de seguridad:
     * - Permitidos: PDF (máx 10 MB), JPG, JPEG, PNG, WEBP (máx 5 MB)
     * - Bloqueados: ZIP, RAR, 7Z, EXE, MSI, BAT, CMD, JS, VBS, APK, DLL, SCR y cualquier otro ejecutable o comprimido
     * - Valida extensión, MIME type y que no esté vacío (size > 0)
     */
    validarArchivo(file) {
        if (!file) {
            return { valido: true, mensaje: '' };
        }

        if (file.size <= 0) {
            return {
                valido: false,
                mensaje: 'El archivo seleccionado está vacío (0 bytes). No se permite subir archivos vacíos.'
            };
        }

        const nombre = (file.name || '').trim();
        const partes = nombre.toLowerCase().split('.');
        if (partes.length < 2) {
            return {
                valido: false,
                mensaje: 'El archivo no tiene una extensión válida. Solo se permiten archivos PDF, JPG, JPEG, PNG y WEBP.'
            };
        }

        const ext = partes[partes.length - 1];
        const bloqueadas = [
            'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'cab', 'iso',
            'exe', 'msi', 'msp', 'bat', 'cmd', 'com', 'scr', 'pif', 'cpl', 'dll', 'sys', 'ocx',
            'js', 'jse', 'mjs', 'cjs', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psm1', 'hta', 'reg', 'lnk',
            'apk', 'aab', 'ipa', 'jar', 'war', 'class', 'sh', 'bash', 'bin', 'run',
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'asp', 'aspx', 'jsp', 'py', 'pl', 'cgi',
            'svg', 'html', 'htm', 'swf'
        ];

        // Revisar todas las extensiones posibles en el nombre (evitar doble extensión como virus.exe.pdf)
        for (let i = 1; i < partes.length; i++) {
            const subExt = partes[i].trim();
            if (bloqueadas.includes(subExt)) {
                return {
                    valido: false,
                    mensaje: `Por motivos de seguridad, los archivos comprimidos o ejecutables (.${subExt.toUpperCase()}) están bloqueados. Solo se permiten archivos PDF, JPG, JPEG, PNG y WEBP.`
                };
            }
        }

        const mimesPermitidos = {
            pdf: ['application/pdf'],
            jpg: ['image/jpeg', 'image/pjpeg'],
            jpeg: ['image/jpeg', 'image/pjpeg'],
            png: ['image/png'],
            webp: ['image/webp']
        };

        if (!Object.prototype.hasOwnProperty.call(mimesPermitidos, ext)) {
            return {
                valido: false,
                mensaje: `El formato de archivo (.${ext.toUpperCase()}) no está permitido. Únicamente puedes adjuntar archivos PDF, JPG, JPEG, PNG y WEBP.`
            };
        }

        const mimeArchivo = (file.type || '').toLowerCase().trim();
        if (!mimeArchivo || !mimesPermitidos[ext].includes(mimeArchivo)) {
            return {
                valido: false,
                mensaje: `El tipo de contenido del archivo (${mimeArchivo || 'desconocido'}) no corresponde a un archivo .${ext.toUpperCase()} válido o seguro.`
            };
        }

        const esPdf = (ext === 'pdf');
        const maxBytes = esPdf ? (10 * 1024 * 1024) : (5 * 1024 * 1024);
        const maxTexto = esPdf ? '10 MB' : '5 MB';
        const etiqueta = esPdf ? 'El documento PDF' : `La imagen (${ext.toUpperCase()})`;

        if (file.size > maxBytes) {
            const mbActual = (file.size / (1024 * 1024)).toFixed(2);
            return {
                valido: false,
                mensaje: `${etiqueta} supera el tamaño máximo permitido de ${maxTexto} (tamaño del archivo: ${mbActual} MB).`
            };
        }

        return { valido: true, mensaje: 'Archivo válido.' };
    },

    /**
     * Vincula validación automática en tiempo real a un <input type="file">
     */
    vincularValidacionArchivo(inputEl) {
        if (!inputEl) return;

        let feedbackEl = inputEl.parentElement.querySelector('.invalid-feedback-archivo');
        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.className = 'invalid-feedback invalid-feedback-archivo';
            inputEl.insertAdjacentElement('afterend', feedbackEl);
        }

        inputEl.addEventListener('change', () => {
            const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
            if (!file) {
                inputEl.classList.remove('is-invalid', 'is-valid');
                feedbackEl.textContent = '';
                return;
            }

            const res = this.validarArchivo(file);
            if (!res.valido) {
                inputEl.classList.add('is-invalid');
                inputEl.classList.remove('is-valid');
                feedbackEl.textContent = res.mensaje;
                this.showAlert('danger', res.mensaje);
                inputEl.value = '';
            } else {
                inputEl.classList.remove('is-invalid');
                inputEl.classList.add('is-valid');
                feedbackEl.textContent = '';
            }
        });
    }
};

window.App = App;
