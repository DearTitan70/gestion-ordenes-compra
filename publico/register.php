<?php
require_once '../config/db.php';

// Obtener roles y áreas para los selects
$roles = $pdo->query("SELECT id, name FROM roles")->fetchAll();
$areas = $pdo->query("SELECT id, nombre FROM area")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario - Gestión O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Registrar Usuario</h2>
        <?php
        if (isset($_GET['error'])) {
            echo '<p style="color:red;">'.htmlspecialchars($_GET['error']).'</p>';
        }
        if (isset($_GET['success'])) {
            echo '<p style="color:green;">Usuario registrado exitosamente.</p>';
        }
        ?>
        <form action="register_process.php" method="post">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>
            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" required>
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <label for="role_id_oc">Rol:</label>
            <select id="role_id_oc" name="role_id_oc" required>
                <option value="">Seleccione un rol</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="area_id">Área:</label>
            <select id="area_id" name="area_id" required>
                <option value="">Seleccione un área</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Registrar</button>
        </form>
        <p style="margin-top:15px;"><a href="index.php">Volver al login</a></p>
    </div>
</body>
</html>