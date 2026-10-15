# Facebook API - Taller Backend

API REST desarrollada con Laravel para gestionar publicaciones, imágenes, comentarios y reacciones de «Me gusta».

## Funcionalidades

- Crear publicaciones con una o varias imágenes.
- Listar publicaciones con paginación, búsqueda, imágenes y comentarios.
- Consultar una publicación individual.
- Agregar comentarios a una publicación.
- Registrar «Me gusta» por usuario, sin duplicados.
- Autenticar usuarios con tokens de Laravel Sanctum.
- Notificar por correo al dueño de la publicación cuando recibe un comentario.
- Mantener endpoints versionados en `/api/v1` y compatibilidad con las rutas anteriores.
- Generar documentación web y especificaciones OpenAPI automáticamente con Scribe.
- Eliminar publicaciones y sus imágenes físicas.
- Ejecutar pruebas automatizadas con PHPUnit.

## Tecnologías

- PHP y Laravel
- SQLite
- Eloquent ORM
- Postman
- PHPUnit
- Git y GitHub

## Requisitos previos

- PHP compatible con la versión de Laravel utilizada.
- Composer.
- Git.
- Postman para probar los endpoints.

## Instalación

Clona el repositorio y entra en la carpeta del proyecto:

```bash
git clone https://github.com/JaimeLuiss/facebook-api.git
cd facebook-api
```

Instala las dependencias:

```bash
composer install
```

Crea el archivo de configuración local en Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Genera la clave de la aplicación:

```bash
php artisan key:generate
```

En el archivo `.env`, configura la conexión a SQLite:

```env
DB_CONNECTION=sqlite
```

Crea el archivo de la base de datos si todavía no existe:

```powershell
if (!(Test-Path database\database.sqlite)) {
    New-Item database\database.sqlite -ItemType File
}
```

Ejecuta las migraciones:

```bash
php artisan migrate
```

Crea el enlace simbólico para acceder a las imágenes:

```bash
php artisan storage:link
```

Inicia el servidor:

```bash
php artisan serve
```

La API estará disponible normalmente en `http://127.0.0.1:8000`.

## Endpoints

Los endpoints actuales se encuentran en `/api/v1`. Las rutas antiguas bajo `/api` siguen disponibles como aliases de compatibilidad.

La API utiliza las siguientes rutas:

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/v1/register` | Registra un usuario y devuelve un token. |
| POST | `/api/v1/login` | Inicia sesión y devuelve un token. |
| POST | `/api/v1/logout` | Revoca el token actual. Requiere autenticación. |
| GET | `/api/v1/posts` | Lista publicaciones paginadas; acepta `page` y `search`. |
| GET | `/api/v1/posts/{id}` | Consulta una publicación específica. |
| POST | `/api/v1/posts` | Crea una publicación. Requiere autenticación. |
| POST | `/api/v1/posts/{id}/comments` | Agrega un comentario y notifica al dueño. Requiere autenticación. |
| POST | `/api/v1/posts/{id}/like` | Registra un like único por usuario. Requiere autenticación. |
| DELETE | `/api/v1/posts/{id}` | Elimina una publicación y sus imágenes. Requiere autenticación. |

En las rutas protegidas envía `Authorization: Bearer <token>`. La lista devuelve una página de diez publicaciones por defecto, dentro de `data`, e incluye `current_page`, `last_page`, `per_page` y `total`. `search` busca coincidencias parciales en el título o contenido.

### Crear una publicación

Envía `POST /api/v1/posts` mediante `multipart/form-data` con estos campos y un token válido:

| Campo | Tipo | Obligatorio |
|---|---|---|
| `title` | Texto | Sí |
| `content` | Texto | Sí |
| `images[]` | Archivo de imagen | No |

Las imágenes admitidas por el controlador son JPEG, PNG, JPG, GIF y WEBP, con un tamaño máximo de 2 MB por archivo.

### Listar publicaciones

Envía `GET /api/v1/posts?page=1&search=viaje`. La respuesta incluye los datos de la página, imágenes con URL completa `image_url`, comentarios y la cantidad de likes.

### Autenticación

Registra un usuario con `POST /api/v1/register` o inicia sesión con `POST /api/v1/login`, enviando JSON. Copia `token` de la respuesta y úsalo como Bearer Token en las operaciones protegidas.

```json
{
  "name": "Carlos Gomez",
  "email": "carlos@example.com",
  "password": "password123"
}
```

### Agregar un comentario

Envía `POST /api/v1/posts/{id}/comments` con un token válido y un cuerpo JSON:

```json
{
  "content": "¡Qué buena foto!"
}
```

### Dar «Me gusta»

Envía `POST /api/v1/posts/{id}/like` con un token válido. No requiere cuerpo. Un usuario repetido no incrementa `likes_count`.

### Eliminar una publicación

Envía `DELETE /api/v1/posts/{id}` con un token válido. La respuesta esperada es `204 No Content`. También se eliminan los archivos de imagen asociados almacenados en el disco público.

## Documentación automática

Scribe genera el sitio de documentación y una especificación OpenAPI:

```bash
php artisan scribe:generate
```

Después de iniciar Laravel, abre `http://127.0.0.1:8000/docs`. La especificación OpenAPI y una colección Postman generada también están disponibles en `/docs.openapi` y `/docs.postman`.

## Notificaciones por correo

Al comentar una publicación que tenga usuario propietario, se envía un correo con asunto «Nuevo comentario». En desarrollo `.env.example` usa `MAIL_MAILER=log`, por lo que el mensaje queda registrado en `storage/logs/laravel.log`; configura SMTP en `.env` para entregarlo a un buzón real.

## Pruebas automatizadas

Ejecuta:

```bash
php artisan test
```

Las pruebas automatizadas verifican los casos incluidos en el directorio `tests`.

## Colección de Postman

La colección completa está en `Facebook_API_Taller_Backend.postman_collection.json`. En Postman, selecciona **Import** y elige ese archivo. Incluye solicitudes para registrarse/iniciar sesión, crear/listar/buscar publicaciones, paginar, comentar, comprobar correo, probar likes con dos usuarios, verificar rechazo sin token, comprobar el alias antiguo, consultar la documentación, eliminar la publicación y cerrar sesión.

La colección usa `http://127.0.0.1:8000` por defecto. Inicia Laravel con `php artisan serve` antes de enviar las solicitudes. Empieza por **Registrar usuario principal**; el token y el ID de la publicación se guardan automáticamente. Después sigue el orden numerado de las carpetas. Para probar la página 2, primero crea más de diez publicaciones. Para probar imágenes, habilita el campo `images[]` y selecciona un archivo local. La prueba de notificación puede comprobarse en `storage/logs/laravel.log` si se mantiene `MAIL_MAILER=log`. Ejecuta la carpeta de limpieza antes de cerrar sesión.

Si cambias el host o puerto, modifica las variables `app_url` y `base_url` de la colección. Las credenciales del endpoint **Iniciar sesión** son de ejemplo; reemplázalas por una cuenta existente o usa **Registrar usuario principal**, que genera un correo de prueba único.

## Estructura principal

```text
facebook-api/
├── app/
│   ├── Http/Controllers/
│   │   ├── PostController.php
│   │   └── CommentController.php
│   └── Models/
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── tests/
├── Facebook_API_Taller_Backend.postman_collection.json
└── README.md
```

## Repositorio

https://github.com/JaimeLuiss/facebook-api
