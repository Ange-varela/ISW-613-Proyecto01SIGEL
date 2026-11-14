# SIGEL - Sistema de Gestion de Prestamo de Equipos de Laboratorio

Proyecto 1 del curso ISW-613 Programacion en Ambiente Web I (UTN).

## Que es SIGEL

Es una pagina web para llevar el control de los equipos de un laboratorio (laptops, proyectores, routers, etc.) y de los prestamos que se le hacen a los profesores.

Con SIGEL se puede:

- Guardar los equipos y organizarlos por categorias.
- Subir fotos de cada equipo.
- Registrar a los profesores que piden prestado.
- Crear prestamos de uno o varios equipos para ciertas fechas.
- Aprobar, entregar, devolver o cancelar un prestamo.
- Ver cuales son los equipos que mas se prestan y descargar ese reporte en Excel (CSV).

## Quien puede hacer que

Hay dos tipos de usuario:

- **Administrador:** maneja los usuarios, las categorias, los equipos, los profesores y ve los reportes.
- **Operador:** consulta los equipos y se encarga de los prestamos.

Si un operador intenta entrar a una pagina del administrador escribiendo la direccion, el sistema no lo deja.

## Usuarios de prueba

| Tipo | Correo | Contrasena |
| --- | --- | --- |
| Administrador | admin@sigel.local | Admin123 |
| Operador | operador@sigel.local | Operador123 |

## Que se necesita

- XAMPP (trae Apache, MySQL y PHP 8).
- Un navegador (Chrome, Edge, etc.).
- Git, para descargar el proyecto.

## Como instalarlo desde cero

### 1. Descargar el proyecto

Abrir una terminal dentro de `C:\xampp\htdocs` y escribir:

```
git clone https://github.com/Ange-varela/ISW-613-Proyecto01SIGEL.git Proyecto01SIGEL
```

### 2. Crear el archivo de configuracion

Dentro de la carpeta `config`, crear un archivo llamado `.env` con esto (cambiar el usuario y la contrasena si su MySQL tiene otros):

```
DB_HOST=localhost
DB_NAME=sigel_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

Este archivo no se sube a GitHub porque tiene las contrasenas de la base de datos.

### 3. Crear la base de datos

1. Abrir XAMPP y darle Start a Apache y a MySQL.
2. En la fila de MySQL darle al boton **Admin** (se abre phpMyAdmin).
3. Crear una base de datos llamada `sigel_db`.
4. Entrar a `sigel_db`, ir a **Importar** y cargar primero `sql/schema.sql` y despues `sql/seed.sql`.

### 4. Configurar la direccion sigel.local

**a)** Abrir con el Bloc de notas el archivo `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y agregar al final:

```
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"
</VirtualHost>

<VirtualHost *:80>
    ServerName sigel.local
    DocumentRoot "C:/xampp/htdocs/Proyecto01SIGEL/public"

    <Directory "C:/xampp/htdocs/Proyecto01SIGEL/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Solo la carpeta `public` queda visible en el navegador. Las demas carpetas (config, includes, sql, storage) quedan protegidas.

**b)** Abrir el Bloc de notas **como administrador**, abrir el archivo `C:\Windows\System32\drivers\etc\hosts` y agregar al final:

```
127.0.0.1   sigel.local
```

**c)** En XAMPP darle Stop y despues Start a Apache.

### 5. Entrar

Abrir el navegador en `http://sigel.local/` e iniciar sesion con alguno de los usuarios de prueba.

## Como estan organizadas las carpetas

```
Proyecto01SIGEL/
├── config/      conexion a la base de datos y el archivo .env
├── includes/    funciones que usan todas las paginas (sesion, permisos, token, menu)
├── sql/         schema.sql (las tablas) y seed.sql (datos de prueba)
├── storage/     fotos de los equipos (no se pueden abrir directo desde el navegador)
└── public/      lo unico que se ve en el navegador
    ├── pages/   las pantallas (listas y formularios)
    ├── actions/ los archivos que guardan, editan o eliminan y luego regresan a la pantalla
    ├── css/     los colores y el diseno
    └── login.php
```

## Seguridad que tiene

- Las contrasenas se guardan cifradas, nunca como texto normal.
- Las consultas a la base de datos se hacen con sentencias preparadas, para evitar inyeccion SQL.
- Todo lo que se muestra en pantalla pasa por `htmlspecialchars`, para evitar XSS.
- Todos los formularios llevan un token secreto (CSRF), para que nadie pueda enviarlos desde otra pagina.
- La sesion se cierra sola despues de 30 minutos sin usar el sistema.
- Despues de 3 intentos fallidos la cuenta se bloquea.
- Las fotos se revisan antes de guardarlas (tamano, tipo y extension) y se guardan con un nombre nuevo.
- Los errores se guardan en un archivo y al usuario solo se le muestra un mensaje general.

## Cookies que usa

- **sigel_recordarme:** para no tener que poner la contrasena otra vez si se marco "Recordarme".
- **categorias_por_pagina, equipos_por_pagina, usuarios_por_pagina:** recuerdan cuantos registros quiere ver cada persona en cada lista.
- **equipos_recientes:** guarda los ultimos 5 equipos que se consultaron.