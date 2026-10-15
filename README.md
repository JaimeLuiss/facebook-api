# Facebook API - Taller Backend

API REST desarrollada con Laravel para gestionar publicaciones, imágenes, comentarios y reacciones de «Me gusta».

## Funcionalidades

- Crear publicaciones con una o varias imágenes.
- Listar publicaciones con sus imágenes y comentarios.
- Consultar una publicación individual.
- Agregar comentarios a una publicación.
- Incrementar el contador de «Me gusta».
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

La API utiliza las siguientes rutas:

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/posts` | Lista las publicaciones, imágenes y comentarios. |
| POST | `/api/posts` | Crea una publicación y guarda sus imágenes. |
| GET | `/api/posts/{id}` | Consulta una publicación específica. |
| POST | `/api/posts/{id}/comments` | Agrega un comentario. |
| POST | `/api/posts/{id}/like` | Incrementa el contador de «Me gusta». |
| DELETE | `/api/posts/{id}` | Elimina una publicación y sus imágenes físicas. |

### Crear una publicación

Envía `POST /api/posts` mediante `multipart/form-data` con estos campos:

| Campo | Tipo | Obligatorio |
|---|---|---|
| `title` | Texto | Sí |
| `content` | Texto | Sí |
| `images[]` | Archivo de imagen | No |

Las imágenes admitidas por el controlador son JPEG, PNG, JPG, GIF y WEBP, con un tamaño máximo de 2 MB por archivo.

### Listar publicaciones

Envía `GET /api/posts`. La respuesta incluye los datos de cada publicación, sus imágenes, la URL completa `image_url` y los comentarios asociados.

### Agregar un comentario

Envía `POST /api/posts/{id}/comments` con un cuerpo JSON:

```json
{
  "author": "Carlos Gomez",
  "content": "¡Qué buena foto!"
}
```

### Dar «Me gusta»

Envía `POST /api/posts/{id}/like`. No requiere cuerpo. La operación incrementa `likes_count`.

### Eliminar una publicación

Envía `DELETE /api/posts/{id}`. La respuesta esperada es `204 No Content`. También se eliminan los archivos de imagen asociados almacenados en el disco público.

## Pruebas automatizadas

Ejecuta:

```bash
php artisan test
```

Las pruebas automatizadas verifican los casos incluidos en el directorio `tests`.

## Colección de Postman

En la raíz del proyecto se encuentra:

`Facebook_API_Taller_Backend.postman_collection.json`

Para utilizarla:

1. Abre Postman.
2. Selecciona **Import**.
3. Selecciona el archivo JSON de la colección.
4. Comprueba que Laravel esté ejecutándose en el puerto configurado.
5. Envía las solicitudes de la colección.

Las solicitudes utilizan la URL base `http://127.0.0.1:8000`. Si el servidor utiliza otro puerto, actualiza las URLs en Postman.

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
