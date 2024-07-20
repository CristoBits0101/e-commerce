<?php

    session_start();

    // Verifica si no hay una sesión activa.
    if (!isset($_SESSION['user_id']))
    {
        // Se le comunica al usuario que para acceder al resto de páginas debe loguearse primero.
        $_SESSION['session_inactiva'] = '¡Debe inicar sesión primero para poder usar nuestros servicios!';

        // Redirige al usuario a la página de inicio de sesión.
        header("Location: form_login.php");

        // Asegura que el script se detenga después de la redirección.
        exit;
    }

    // Importamos el archivo de configuración para poder conectarnos a la base de datos.
    require_once "./configuration.php";

    // Verificamos si se ha enviado un formulario para eliminar un producto.
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['producto'])) 
    {
        // Obtenemos el ID del producto a eliminar.
        $idProducto = $_POST['producto'];

        // Eliminamos el producto de la base de datos.
        $conn = connect_to_database();
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->bindParam(':id', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        $stmt = null;
        $conn = null;

        // Redirige al usuario a la página de listado de productos.
        header("Location: listado_productos.php");
    }
    else
    {
        ?>
        <!DOCTYPE html>
        <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="style.css">
                <title>Eliminar Producto</title>
            </head>
            <body
                <?php 
                    if (isset($_COOKIE['color_fondo']) && !empty($_COOKIE['color_fondo']))
                        echo 'style="background-color: ' . $_COOKIE['color_fondo'] . '"';
                ?>
            >
                <div class="container">

                    <?php
                        include_once "./menu.php";
                    ?>

                    <main>
                        <div class="container">
                            <form action="elimina_producto.php" method="post">

                                <label for="producto">Seleccione el producto que desea eliminar:</label>

                                <select name="producto" id="producto">
                                    <?php
                                        $conn = connect_to_database();

                                        // Obtenemos todos los productos para llenar la lista.
                                        $stmt = $conn->query("SELECT id, Nombre FROM productos");

                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
                                        {
                                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['Nombre']) . "</option>";
                                        }

                                        $stmt = null;
                                    ?>
                                </select>

                                <input type="submit" value="Eliminar Producto">
                            </form>
                        </div>
                    </main>

                    <?php
                        $conn = null;
                    ?>

                </div>
            </body>
        </html>
<?php
}
?>