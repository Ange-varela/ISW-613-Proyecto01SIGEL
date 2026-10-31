
<?php
// Leer la configuracion de la base de datos
$config = parse_ini_file(__DIR__ . '/.env');

try {
    // Crear la conexion con MySQL
    $pdo = new PDO(
        "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset={$config['DB_CHARSET']}",
        $config['DB_USER'],
        $config['DB_PASS']
    );

    // Configurar PDO para manejar errores y consultas seguras
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Registrar el error sin mostrar datos privados
    error_log($e->getMessage());
    exit("Error de conexion con la base de datos");
}
