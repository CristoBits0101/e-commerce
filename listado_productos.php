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

    // Paso 1) Importamos el archivo de configuración para poder conectarnos a la base de datos.
    require_once "./configuration.php";
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Consultar el listado de productos</title>

        <link rel="stylesheet" href="style.css">
    </head>

    <body
        <?php 
            if (isset($_COOKIE['color_fondo']) && !empty($_COOKIE['color_fondo']))
                echo 'style="background-color: ' . $_COOKIE['color_fondo'] . '"';
        ?>
    >

        <!-- Almacena la aplicación -->
        <div class="container">

            <!-- Incluye el menú dentro de la aplicación -->
            <?php
                include_once "./menu.php";
            ?>

            <!-- Aquí va el cuerpo del contenido -->
            <main id="listado-productos">

                <!-- Creamos la tabla que va a almacenar la consulta -->
                <table>

                    <!-- Asignamos un título a la tabla -->
                    <caption><b>Listado de Productos</b></caption>

                    <!-- Añadimos el encabezado estático de la tabla -->
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Imagen</th>
                            <th>Categoría</th>
                            <th>Editar</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>

                    <!-- Añadimos el cuerpo de la tabla de forma dinámica -->
                    <tbody>
                        <?php
                            // Establece la conexión a la base de datos.
                            $conn = connect_to_database();

                            // Realiza una consulta SQL para obtener los datos de la tabla 'productos' con un JOIN en 'Categorías'.
                            $stmt = $conn->query(
                                "SELECT     
                                    productos.Nombre, 
                                    productos.Precio, 
                                    productos.Imagen, 
                                    Categorías.nombre 
                                AS         
                                    CategoriaNombre, 
                                    productos.id 
                                FROM       
                                    productos
                                INNER JOIN 
                                    Categorías 
                                ON         
                                    productos.Categoría = Categorías.id"
                            );

                            if ($stmt) 
                            {
                                while ($row = $stmt->fetch()) 
                                {
                                    echo '<tr>';
                                        echo '<td>'                                 . $row['Nombre']          .                                                         '</td>';
                                        echo '<td>'                                 . $row['Precio']          .                                                         '</td>';
                                        echo '<td><img src=".\\ficheros\\'          . $row['Imagen']          . '" alt="Imagen del producto" width="100" />              </td>';
                                        echo '<td>'                                 . $row['CategoriaNombre'] .                                                         '</td>';
                                        echo '<td><a href="edita_producto.php?id='  . $row['id']              . '"><img src=".\\iconos\\editar.svg" alt="Editar" />  </a></td>';
                                        echo '<td><a href="elimina_producto.php?id='. $row['id']              . '"><img src=".\\iconos\\eliminar.svg" alt="Eliminar"></a></td>';
                                    echo '</tr>';
                                }
                            }
                            
                            else
                            {
                                echo '<tr><td colspan="4">No se pudieron recuperar los datos de los productos.</td></tr>';
                            }

                            // Cierra la conexión a la base de datos.
                            $conn = null;
                        ?>
                    </tbody>
                </table>
            </main>
        </div>
    </body>
</html>
