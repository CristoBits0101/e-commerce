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

    // Importación de configuraciones.
    require_once "./configuration.php";

    // Comprobación de envío.
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
        validations();

    // Validación del formulario.
    function validations()
    {
        // Validación de campos.
        foreach ($_REQUEST as $field => $value)
            if (!isset($value) || empty($value))
                $errors = "<p>'Error en campo $field no almacenado.</p><br/>";

        // Validación de ficheros.
        foreach ($_FILES as $key => $file)
        {
            // Validación de tipo.
            $allowed_types = array("image/jpeg", "image/png", "image/gif");

            if (!in_array($file['type'], $allowed_types))
            {
                $errors .= "<p>Error en campo $key: Tipo de archivo no permitido.</p><br/>";
                continue;
            }

            // Validación de tamaño.
            $max_size = 2 * 1024 * 1024;

            if ($file['size'] > $max_size)
            {
                $errors .= "<p>Error en campo $key: El archivo supera el tamaño permitido.</p><br/>";
                continue;
            }
        }

        // Comprobación de errores.
        if (empty($errors))
        {
            save_data();
            echo "<script>alert('¡Datos almacenados correctamente!')</script>";
        }

        // Comunicación de errores.
        else
        {
            echo '<div id="mensajes">';
                echo $errors;
                echo '<a href="./crear_producto.php">Volver a rellenar formulario</a>';
            echo '</div>';
        }
    }

    function save_data()
    {
        try
        {
            // Obtiene image path.
            $imagePath = image_path();

            // Conecta a la base de datos.
            $connection = connect_to_database();

            // Preparación de la consulta.
            $stmt = $connection->prepare(
                "INSERT INTO 
                    productos (Nombre, Precio, Imagen, Categoría) 
                VALUES 
                    (:nombre, :precio, :imagen, :categoria)"
            );

            // Purificación de datos.
            $nombre    = filter_var($_POST['nombre'], FILTER_UNSAFE_RAW);
            $precio    = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
            $categoria = filter_var($_POST['categoria'], FILTER_UNSAFE_RAW);

            // Sincronización de datos.
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':imagen', $imagePath);
            $stmt->bindParam(':categoria', $categoria);

            // Ejecución de la consulta.
            $stmt->execute();                              

            // Desplazamiento del fichero.
            move_uploaded_file($_FILES["imagen"]["tmp_name"], $imagePath);

            // Cierra la conexión.
            $connection = null;
        }

        catch (PDOException $e)
        {
            echo "Error al insertar datos: " . $e->getMessage();
        }
    }

    function image_path()
    {
        // Carpeta de destino.
        $target_dir  = "ficheros\\";

        // Destino del fichero.
        $target_file = $target_dir . basename($_FILES["imagen"]["name"]);

        // Incrementador de nombres.
        $increment = 0;

        // Comprobación de existencia.
        while (file_exists($target_file))
        {
            // Incremento de nombre.
            $increment++;

            // Datos del archivo.
            $pathinfo  = pathinfo($target_file);
            $name      = $pathinfo["filename"];
            $extension = $pathinfo["extension"];

            // Refactorización del nombre.
            $target_file =  $target_dir . $name . $increment . "." . $extension;
        }

        // Retorno del path.
        return basename($target_file);
    }

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Crear producto</title>
        <link rel="stylesheet" href="style.css">
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
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                    <div class="inputs">
                        <label for="nombre">Nombre:</label>
                        <br/>
                        <input type="text" id="nombre" name="nombre">
                    </div>
                    <br/>
                    <div class="inputs">
                        <label for="precio">Precio:</label>
                        <br/>
                        <input type="number" id="precio" name="precio">
                    </div>
                    <br/>
                    <div class="inputs">
                        <label for="imagen">Imagen:</label>
                        <br/>
                        <input type="file" id="imagen" name="imagen" accept=".jpg,.png,.gif,.jfif"/>
                    </div>
                    <br/>
                    <div class="inputs">
                        <p style="margin: 0 0 0.2rem 0 ;"><b>Categoría:</b></p>
                        <select name="categoria" id="categoria">
                            <?php
                                // Conecta a la base de datos.
                                $connection = connect_to_database();

                                // Ejecuta la consulta.
                                $stmt = $connection->query(
                                    "SELECT 
                                        id, nombre 
                                    FROM 
                                        Categorías"
                                );

                                // Consulta exitosa.
                                if ($stmt) 
                                    while ($row = $stmt->fetch())
                                        echo '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';

                                // Consulta no exitosa.
                                else
                                    echo '<option value="null"> Las categorías no están disponibles </option>';

                                // Cierre de conexión.
                                $connection = null;
                            ?>
                        </select>
                    </div>
                    <br/>
                    <button type="submit">Envíar</button>
                </form>
            </main>
        </div>
    </body>
</html>
