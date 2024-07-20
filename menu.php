<?php

    function cerrarSesion()
    {
        // Destruye todas las variables de sesión.
        session_unset();

        // Destruye la sesión.
        session_destroy();

        // Puedes redirigir al usuario a la página de inicio de sesión o a cualquier otra página aquí.
    }

    if (isset($_POST['cerrarSesion'])) 
        cerrarSesion();

?>
<script>
    function cerrarSesion()
    {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () 
        {
            if (xhr.readyState == 4 && xhr.status == 200) 
            {
                // Puedes redirigir al usuario a la página de inicio de sesión o a cualquier otra página aquí.
                window.location.href = "form_login.php";
            }
        };
        xhr.send("cerrarSesion=true");
    }
</script>
<header>
    <?php 
        if (isset($_SESSION['name']) && !empty($_SESSION['name']))
            echo "<p> Sesión iniciada como: " . $_SESSION['name'] . "</p>";
    ?>
    <h1>Tienda on-line</h1>
    <nav>
        <ul>
            <li>
                <a href="./index.php">Home</a>
            </li>
            <li>
                <a href="./crear_producto.php">Crear producto</a>
            </li>
            <li>
                <a href="./listado_productos.php">Consultar el listado de productos</a>
            </li>
            <li>
                <a href="./edita_producto.php">Modificar producto</a>
            </li>
            <li>
                <a href="./elimina_producto.php">Eliminar producto</a>
            </li>
        </ul>
    </nav>
    <button onclick="cerrarSesion()">
        Cerrar Sesión
    </button>
</header>
