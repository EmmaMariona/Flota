# 🐳 Guía de Despliegue con Docker - Sistema de Gestión de Flota Vehicular

Esta guía detalla los pasos para levantar y ejecutar el **Sistema de Gestión de Flota Vehicular** en contenedores Docker de forma rápida, aislada y sin requerir la instalación manual de PHP, MySQL ni Apache en el sistema anfitrión.

---

## 📋 Requisitos Previos

Tener instalado en el equipo o servidor:
- **Docker** (versión 20.10 o superior)
- **Docker Compose** (v2 o superior)
- *En Windows / Mac:* **Docker Desktop** (asegurarse de que el motor de Docker esté en ejecución).

---

## 🚀 Puesta en Marcha Rápida (1 solo comando)

Abre una terminal (PowerShell, Bash o CMD) en la raíz del proyecto (`C:\xampp\htdocs\flota`) y ejecuta:

```bash
docker compose up -d
```

### ¿Qué hace este comando automáticamente?
1. **Construye la imagen de la aplicación (`flota_app`):**
   - PHP 8.2 con Apache.
   - Extensiones requeridas: `mysqli`, `pdo_mysql`, `gd` (con soporte WebP y JPEG), `zip`, `fileinfo`.
   - Módulos de Apache: `mod_rewrite`, `mod_headers`, `mod_alias`.
   - Configuración de subida de archivos (hasta 25 MB en PHP, soportando PDFs de 10 MB e imágenes de 5 MB).
   - Configuración de permisos automáticos para la carpeta de adjuntos `backend/uploads/`.
2. **Levanta la base de datos (`flota_db`):**
   - MySQL 8.0 con codificación `utf8mb4`.
   - Inicializa automáticamente las 10 tablas del sistema (`docker/db-init/01-flota-init.sql`).
   - Carga el catálogo de marcas, modelos, posiciones de llantas, sucursales y usuarios iniciales.
3. **Levanta phpMyAdmin (`flota_pma`):**
   - Interfaz web gráfica para administrar la base de datos conectada a `flota_db`.

---

## 🌐 URLs de Acceso

Una vez levantados los contenedores, abre tu navegador web:

| Servicio | URL | Descripción |
| :--- | :--- | :--- |
| **Sistema Flota Vehicular** | [http://localhost:8080/](http://localhost:8080/) | Acceso principal a la aplicación web (redirecciona automáticamente al login o dashboard). |
| **Ruta Directa Frontend** | [http://localhost:8080/flota/frontend/](http://localhost:8080/flota/frontend/) | Acceso explícito al frontend. |
| **phpMyAdmin** | [http://localhost:8081/](http://localhost:8081/) | Gestor web de MySQL. |

---

## 🔑 Credenciales de Acceso

### 1. Acceso a la Aplicación Web

| Usuario / Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Administrador Principal** | `admin@flota.com` | `admin123` |
| **Supervisor de Flota** | `supervisor@flota.com` | `admin123` |
| **Usuario de Consulta** | `consulta@flota.com` | `admin123` |

### 2. Acceso a la Base de Datos (MySQL / phpMyAdmin)

- **Servidor / Host interno:** `db`
- **Puerto interno:** `3306`
- **Puerto externo (desde el host / DBeaver / Workbench):** `3307` *(evita conflictos con XAMPP en el puerto 3306)*
- **Base de Datos:** `flota_vehicular`
- **Usuario de Aplicación:** `flota_user`
- **Contraseña de Usuario:** `flota_secret_2026`
- **Usuario Root:** `root`
- **Contraseña Root:** `root_secret_2026`

---

## 🛠️ Comandos de Gestión Frecuentes

### Ver estado de los contenedores:
```bash
docker compose ps
```

### Ver logs en tiempo real:
```bash
# Todos los servicios
docker compose logs -f

# Solo la aplicación PHP/Apache
docker compose logs -f app

# Solo la base de datos MySQL
docker compose logs -f db
```

### Detener los contenedores:
```bash
docker compose stop
```

### Reiniciar los contenedores:
```bash
docker compose restart
```

### Apagar y remover contenedores y redes (conservando datos de base de datos y archivos):
```bash
docker compose down
```

### Reconstruir la imagen si realizas cambios en dependencias o en el `Dockerfile`:
```bash
docker compose up -d --build
```

### Restablecer la base de datos a su estado inicial limpio:
```bash
docker compose down -v
docker compose up -d
```
*(⚠️ Nota: `-v` elimina los volúmenes, eliminando datos guardados y forzando la reejecución de los scripts de inicialización).*

---

## 💾 Persistencia de Datos

El archivo `docker-compose.yml` define dos volúmenes persistentes que garantizan que nunca se pierda información al detener o reiniciar los contenedores:

1. **`flota_db_data`**: Almacena todos los datos, tablas, vehículos, llantas y mantenimientos de MySQL.
2. **`flota_uploads`**: Almacena físicamente todos los documentos PDF, imágenes y comprobantes subidos al sistema en `backend/uploads/`.
3. **Código fuente sincronizado**: El directorio del proyecto está montado como volumen (`./:/var/www/html/flota`), por lo que cualquier modificación que hagas al código en tu editor se refleja instantáneamente sin necesidad de reconstruir la imagen.

---

## 🔒 Compatibilidad Dual (XAMPP y Docker)

El sistema detecta automáticamente si se está ejecutando dentro de un contenedor Docker o en un servidor local XAMPP:
- Si ejecutas **`docker compose up`**: El sistema utiliza las variables inyectadas por el contenedor (`db`, `flota_user`, `8080`).
- Si ejecutas **XAMPP localmente**: El sistema recurre a tu archivo `.env` local (`localhost`, `root`, `3306`).
- No requieres alterar ninguna línea de código para alternar entre uno y otro.
