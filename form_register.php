<?php

    // 1. Iniciamos sesión para ver si el formulario fue enviado previamente y contenía errores.
    session_start();

    // 2. Comprueba si se declararon campos con errores y los serializa.
    function serializeError($field) 
    {
        if (isset($_SESSION['error_messages'][$field]))
            echo '<br/><div class="error-message">' . $_SESSION['error_messages'][$field] . '</div>';
    }

    // 3. Verifica si no hay una sesión activa o el usuario no está autenticado.
    if (isset($_SESSION['user_id']))
    {
        print "alert('Usted ya ha iniciado sesión')";

        // Redirige al usuario a la página de inicio de sesión.
        header("Location: index.php");

        // Asegura que el script se detenga después de la redirección.
        exit;
    }

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formulario de autentificación</title>

        <style>
            body 
            {
                font-family: Roboto, sans-serif;
                font-size: 16px;
                display: flex; 
                flex-direction: column; 
                justify-content: center; 
                align-items: center;
            }

            form 
            {
                border: 1px solid black;
                padding: 3rem;
                background-color: #ffffff;
                color: #000000;
            }

            input 
            {
                margin-top: 0.5rem;
                border: 1px solid #ccc;
                padding: 10px;
            }

            input[type="submit"] 
            {
                margin-top: 0;
                margin: 0 auto;
                background-color: #000000;
                color: #ffffff;
                font-size: 18px;
            }

            .error-message 
            {
                color: red;
            }

            .icon 
            {
                font-size: 16px;
            }
        </style>

    </head>

    <body
        <?php 
            if (isset($_COOKIE['color_fondo']) && !empty($_COOKIE['color_fondo']))
                echo 'style="background-color: ' . $_COOKIE['color_fondo'] . '"';
        ?>
    >

        <h1>Formulario de registro:</h1>

        <br/>

        <form action="form.php" method="post">

            <div>
                <label for="name">Nombre:</label>
                <br/>
                <input type="text" id="name" name="name" required minlength="3" maxlength="20" size="30" />
                <?php serializeError('name'); ?>
            </div>

            <br/><br/>

            <div>
                <label for="email">Correo electrónico:</label>
                <br/>
                <input type="email" id="email" name="email" pattern=".+@.+" size="30" required />
                <?php serializeError('email'); ?>
            </div>

            <br/><br/>

            <div>
                <label for="password">Contraseña:</label>
                <br/>
                <input type="password" id="password" name="password" minlength="8" maxlength="30" size="30" required />
                <?php serializeError('password'); ?>
            </div>

            <br/><br/>

            <div>
                <label for="body">Color de fondo:</label>
                <br/>
                <input type="color" id="body" name="body" value="#DCDBD9" />
            </div>

            <br/><br/>

            <?php
                // Limpia la variable de sesión después de imprimir los errores y antes de volver a enviar el formulario.
                session_unset();

                // Destruye la sesión actual después de imprimir los errores y antes de volver a enviar el formulario.
                session_destroy();
            ?>

            <input type="submit" id="register" name="register"  value="Registrarse">

            <br/><br/>

            <a href="./form_login.php">Iniciar sesión</a>

        </form>
    </body>
</html>
