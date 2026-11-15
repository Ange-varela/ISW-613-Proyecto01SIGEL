<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que el usuario tenga permiso
verificarPermiso('ver_prestamos');

// Consultar los prestamos registrados
$sql = "SELECT
            p.id,
            pr.nombre AS profesor,
            u.nombre AS operador,
            p.estado,
            p.fecha_inicio,
            p.fecha_fin
        FROM prestamos p
        INNER JOIN profesores pr ON p.profesor_id = pr.id
        INNER JOIN usuarios u ON p.operador_id = u.id
        ORDER BY p.id DESC";

$consulta = $pdo->prepare($sql);
$consulta->execute();

$prestamos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$titulo = 'Prestamos';
require __DIR__ . '/../../includes/header.php';
?>
        <h1>Prestamos</h1>
        <?php if (tienePermiso('crear_prestamo')): ?>
            <p>
                <a class="boton" href="crear_prestamo.php">+ Registrar nuevo prestamo</a>
            </p>
        <?php endif; ?>
        <h2>Lista de prestamos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Profesor</th>
                    <th>Operador</th>
                    <th>Estado</th>
                    <th>Fecha de inicio</th>
                    <th>Fecha de fin</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="7">
                            No se encontraron prestamos.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td>
                                <?php echo (int)$prestamo['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($prestamo['profesor'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($prestamo['operador'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($prestamo['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($prestamo['fecha_inicio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($prestamo['fecha_fin'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>

                            <td>
                                <?php if (in_array($prestamo['estado'], ['solicitado', 'aprobado', 'entregado'], true)): ?>
                                    <a href="gestionar_prestamo.php?id=<?php echo (int)$prestamo['id']; ?>">
                                        Gestionar
                                    </a>
                                <?php else: ?>
                                    Finalizado
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
