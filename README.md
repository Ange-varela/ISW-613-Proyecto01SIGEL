# SIGEL - Sistema de Gestion de Equipos de Laboratorio

Proyecto 1 del curso ISW-613 - Programacion de Ambientes Web I.

## Descripcion

SIGEL es una aplicacion web desarrollada en PHP y MySQL para gestionar equipos de laboratorio y sus prestamos.

Permite administrar usuarios, categorias, equipos, imagenes y profesores, asi como registrar y gestionar solicitudes de prestamo.

## Tecnologias utilizadas

- PHP 8
- MySQL
- HTML
- CSS
- JavaScript
- Apache (XAMPP)
- PDO para la conexion con MySQL
- Git y GitHub

## Funcionalidades implementadas

### Autenticacion
- Inicio y cierre de sesion.
- Roles de administrador y operador.
- Control de permisos.
- Proteccion de formularios mediante tokens CSRF.
- Almacenamiento seguro de contrasenas.

### Usuarios
- Registrar usuarios.
- Consultar y buscar usuarios.
- Editar usuarios.
- Desactivar usuarios.

### Categorias
- Registrar categorias.
- Consultar y buscar categorias.
- Editar categorias.
- Desactivar categorias.
- Paginacion y preferencia de registros por pagina.

### Equipos
- Registrar equipos.
- Consultar y buscar equipos.
- Editar equipos.
- Desactivar equipos.
- Subir varias imagenes por equipo.
- Visualizar las imagenes almacenadas.

### Profesores
- Consultar profesores.
- Buscar por nombre o correo.
- Registrar profesores.

### Prestamos
- Registrar solicitudes con uno o varios equipos.
- Asociar cada solicitud con un profesor y un operador.
- Validar las fechas de inicio y fin.
- Evitar prestamos superpuestos para un mismo equipo.
- Aprobar solicitudes.
- Cancelar prestamos.
- Liberar la reserva de un equipo al cancelar.

### Bitacora
- Registrar operaciones importantes del sistema.
- Guardar el usuario responsable y los cambios realizados.

## Funcionalidades pendientes

- Registrar la entrega y devolucion de equipos.
- Implementar el codigo de retiro seguro (Regla 18).
- Generar reportes y exportarlos a CSV.
- Completar las funcionalidades adicionales de cookies.
- Realizar las pruebas finales.

## Requisitos

- XAMPP con Apache y MySQL.
- PHP 8 o superior.
- Navegador web.
- Git (opcional).

## Instalacion local

1. Clonar el repositorio en la carpeta htdocs de XAMPP.

2. Iniciar Apache y MySQL.

3. Crear la base de datos sigel_db en phpMyAdmin.

4. Ejecutar los archivos SQL del proyecto:

   - sql/schema.sql
   - sql/seed.sql

5. Configurar las credenciales de la base de datos en config/.env.

6. Configurar el host virtual sigel.local en Apache y Windows.

7. Reiniciar Apache.

8. Acceder desde el navegador:

   http://sigel.local/

## Estructura del proyecto

- config/: configuracion y conexion a la base de datos.
- includes/: funciones de autenticacion y permisos.
- public/: paginas y acciones de la aplicacion.
- sql/: estructura y datos iniciales de MySQL.
- storage/equipos/: almacenamiento privado de imagenes.

## Seguridad

El sistema utiliza:

- Consultas preparadas con PDO.
- Validacion de datos.
- Proteccion contra CSRF.
- Escapado de contenido HTML.
- Contrasenas protegidas mediante hash.
- Sesiones y control de permisos.
- Transacciones para operaciones importantes.
- Validacion de imagenes antes de almacenarlas.

## Estado del proyecto

En desarrollo.

Proyecto academico desarrollado como parte del curso ISW-613.

## Repositorio

https://github.com/Ange-varela/ISW-613-Proyecto01SIGEL