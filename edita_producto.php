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
                $errors = "<p>'Error en el campo $field no almacenado.</p><br/>";

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
            update_data();
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

    function update_data()
    {
        try
        {
            // Obtiene la ruta de la imagen.
            $imagePath = image_path();

            // Conecta a la base de datos.
            $connection = connect_to_database();

            // Preparación de la consulta.
            $stmt = $connection->prepare(
                "UPDATE 
                    productos 
                SET 
                    Nombre = :nombre, Precio = :precio, Imagen = :imagen, Categoría = :categoria 
                WHERE 
                    id = :producto_id"
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
            $stmt->bindParam(':producto_id', $_POST['producto_id']);

            // Ejecución de la consulta.
            $stmt->execute();                              

            // Desplazamiento del fichero.
            move_uploaded_file($_FILES["imagen"]["tmp_name"], $imagePath);

            // Cierra la conexión.
            $connection = null;
        }

        catch (PDOException $e)
        {
            echo "Error al actualizar datos: " . $e->getMessage();
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
                <?php

                    // Comprobamos la variable id.
                    if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) 
                    {
                        // Conecta a la base de datos.
                        $connection = connect_to_database();

                        // Preparación de la consulta.
                        // Select tabla.columnas.
                        $stmt = $connection->prepare(
                            "SELECT 
                                productos.Nombre,
                                productos.Precio,
                                productos.Imagen,
                                productos.id,
                                Categorías.nombre AS CategoriaNombre,
                                
                            -- Tabla con la que va a trabajar.
                            FROM 
                                productos

                            -- Trae todo lo de categorías relacionadas con productos.
                            INNER JOIN 
                                Categorías 

                            -- tabla.columna(Primary key) y tabla.columna(Foreign key).
                            ON 
                                productos.Categoría = Categorías.id

                            -- Key que identifica la relación
                            WHERE 
                                productos.id = :id"
                        );
                        
                        // Purificación de datos.
                        $product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

                        // Sincronización de datos.
                        $stmt->bindParam(':id', $product_id);

                        // Consulta exitosa.
                        if ($stmt->execute())
                        {
                            // Consulta exitosa.
                            if (($row_data = $stmt->fetch()) !== false)
                            {
                                // Serialización del formulario.
                                echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">';
                                echo '
                                        <div class="inputs">
                                            <label for="nombre">Nombre:</label>
                                            <br/>
                                            <input type="text" id="nombre" name="nombre" value="' . $row_data['Nombre'] . '">
                                        </div>';
                                echo '  <br/>';
                                echo '      
                                        <div class="inputs">
                                            <label for="precio">Precio:</label>
                                            <br/>
                                            <input type="number" id="precio" name="precio" value="' . $row_data['Precio'] . '">
                                        </div>';
                                echo '  <br/>';
                                echo '      
                                        <div class="inputs">
                                            <label for="imagen">Imagen:</label>
                                            <br/>
                                            <input type="file" id="imagen" name="imagen" accept=".jpg,.png,.gif,.jfif" />
                                        </div>';
                                echo '  <br/>';
                                echo '
                                        <div class="inputs">
                                            <p style="margin: 0 0 0.2rem 0 ;"><b>Categoría:</b></p>
                                            <select name="categoria" id="categoria">';

                                                // Ejecuta la consulta.
                                                if (($stmt = $connection->query("SELECT id, nombre FROM Categorías")) !== false)
                                                    while ($categoria = $stmt->fetch())
                                                    {
                                                        $selected = ($categoria['id'] == $row['Categoria'])
                                                        ? ' selected'
                                                        : '';
                                                        
                                                        echo '<option value="' . $categoria['id'] . '"' . $selected . '>' . $categoria['nombre'] . '</option>';
                                                    }

                                                else
                                                    echo '<option value="null">Las categorías no están disponibles</option>';

                                echo '
                                    </select>
                                    </div>
                                    <br/>
                                    <input type="hidden" name="producto_id" value="' . $product_id . '">
                                    <button type="submit">Enviar</button>
                                </form>';
                            } 
                            
                            else 
                                echo '<p>No se encontró el producto con el ID proporcionado.</p>';
                        } 
                        
                        // Consulta no exitosa.
                        else 
                            echo '<p>No se pudieron recuperar los datos del producto.</p>';

                        // Cierre de conexión.
                        $connection = null;
                    } 
                    
                    else 
                    {
                        // Paso 1) Mostrar el select option dinámico con todos los productos de la base de datos.
                        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="get"><div class="inputs"><p style="margin: 0 0 0.2rem 0 ;"><b>Selecciona un producto:</b></p><br/><select name="id" id="id">';

                        // Paso 2) Realizamos una conexión a la base de datos.
                        $connection = connect_to_database();

                        // Paso 3) Guardamos la consulta en la variable sql.
                        $sql_query = "SELECT id, nombre FROM productos";

                        // Paso 4) Ejecutamos la consulta dentro de la conexión.
                        $stmt = $connection->query("SELECT id, nombre FROM productos");

                        // Paso 5) Comprobamos que la consulta se realizó correctamente y imprimimos los valores del array.
                        if ($stmt)
                            while ($producto = $stmt->fetch())
                                echo '<option value="' . $producto['id'] . '">' . $producto['nombre'] . '</option>';
                        
                        // Consulta no exitosa.
                        else 
                            echo '<option value="null">Los productos no están disponibles</option>';

                        echo '</select></div><br/><button type="submit">Seleccionar</button></form>';

                        // Cierre de conexión.
                        $connection = null;
                    }
                ?>
            </main>
        </div>
    </body>
</html>
