<?php

    // 1.1. Importación de configuraciones.
    require_once "./configuration.php";

    // 1.2. Como no se puede imprimir los errores antes de redireccionar los envío mediante la variable de sesión.
    session_start();

    // 1.3. Registra que formulario enviado contiene errores en los campos.
    $_error = '';

    // 1.4. Registra que campos del formulario enviado contiene errores.
    $_SESSION['error_messages'] = array();

    // 2.1. Válida el formulario de inicio de sesión.
    if (isset($_POST['login']) && $_POST['login'] == 'Entrar')
        foreach ($_REQUEST as $field => $value)
            if (!isset($value) || empty($value))
            {
                $_error = 'login';
                $_SESSION['error_messages'][$field] = "Error en el campo $field no almacenado";
            }

    // 2.2. Válida el formulario registro.
    elseif (isset($_POST['register']) && $_POST['register'] == 'Registrarse')
        foreach ($_REQUEST as $field => $value)
            if (!isset($value) || empty($value))
            {
                $_error = 'register';
                $_SESSION['error_messages'][$field] = "Error en el campo $field no almacenado";
            }

    // 3.1. Se redirecciona al formulario con errores para que vuelva a rellenarlo.
    if (!empty($_SESSION['error_messages']))
    {
        if ($_error === 'login')
        {
            header("Location: ./form_login.php");
            exit;
        }

        elseif ($_error === 'register')
        {
            header("Location: ./form_register.php");
            exit;
        }
    }

    // 3.2. Si no hay errores y el formulario enviado pertenece a login, intentamos autenticar al usuario.
    elseif (empty($_SESSION['error_messages']) && isset($_POST['login']) && $_POST['login'] == 'Entrar')
    {
        // Intentamos conectarnos a la base de datos;
        $connection = connect_to_database();

        // Registramos el email enviado.
        $email = $_POST['email'];

        // Registramos la password enviada.
        $password = $_POST['password'];

        // Preparamos la consulta.
        $stmt = $connection->prepare(
            "SELECT
                id, nombre, contrasena_hash, color_fondo
            FROM
                usuarios2
            WHERE
                correo_electronico = :email"
        );

        // Sincronizamos el email de la consulta con el enviado.
        $stmt->bindParam(':email', $email);

        // Ejecutamos la consulta.
        $stmt->execute();

        // Se convierte el registro en array asociativo y se comprueba si la consulta devolvió algún valor.
        if (($user = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
        {
            // Comprobamos la contraseña enviada.
            if (password_verify($password, $user['contrasena_hash']))
            {
                // Cerramos la conexión después de finalizar la consulta.
                $connection = null;

                // Si la contraseña es válida, le dejamos iniciar sesión o acceder a los privilegios que solicita.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['nombre'];

                // Si el usuario se loguea correctamente guardamos el color de fondo en la cookie.
                setcookie('color_fondo', $user['color_fondo'], time() + 86000);

                // Redirigimos a la página de inicio después del inicio de sesión exitoso.
                header("Location: ./index.php");
                
                // Detenemos la ejecución del script para prevenir un header location en bucle en caso de error.
                exit;
            }

            // En caso de que la contraseña sea incorrecta.
            else
            {
                // Cerramos la conexión después de finalizar la consulta.
                $connection = null;

                // Marcamos que la contraseña introducida es incorrecta para retornarle el error al usuario.
                $_SESSION['wrong_user']['login'] = "Error al intentar iniciar sesión: contraseña incorrecta";

                // Verificar si la cookie "errores_login" existe.
                if (isset($_COOKIE['errores_login']) && !empty($_COOKIE['errores_login']))
                {
                    // Si la cookie ya existe, incrementamos su valor en 1, y la volvemos a crear para que se actualice inmediatamente.
                    $erroresLogin = $_COOKIE['errores_login'] + 1;
                    setcookie('errores_login', $erroresLogin, 0, '/');
                }
                else 
                    // Si no existe, establecer el valor en 1, 0 que no caduca y la barra '/' que está disponible en todo el dominio.
                    setcookie('errores_login', 1, 0, '/');

                // Redirigimos a la página de inicio después del inicio de sesión no exitoso.
                header("Location: ./form_login.php");

                // Detenemos la ejecución del script para prevenir un header location en bucle en caso de error.
                exit;
            }
        }

        // Si la consulta dio error porque no se encuentra el registro del usuario.
        else 
        {
            // Cerramos la conexión después de finalizar la consulta.
            $connection = null;

            // Usuario no encontrado.
            $_SESSION['wrong_user']['login'] = "Error en el inicio de sesión: Usuario no encontrado";

            // Verificar si la cookie "errores_login" existe.
            if (isset($_COOKIE['errores_login']) && !empty($_COOKIE['errores_login']))
            {
                // Si la cookie ya existe, incrementamos su valor en 1, y la volvemos a crear para que se actualice inmediatamente.
                $erroresLogin = $_COOKIE['errores_login'] + 1;
                setcookie('errores_login', $erroresLogin, 0, '/');
            }
            else 
                // Si no existe, establecer el valor en 1, 0 que no caduca y la barra '/' que está disponible en todo el dominio.
                setcookie('errores_login', 1, 0, '/');

            // Acceder directamente al valor actualizado de la cookie.
            $erroresLogin = $_COOKIE['errores_login'];

            // Redirigimos a la página de inicio después del inicio de sesión no exitoso.
            header("Location: ./form_login.php");

            // Detenemos la ejecución del script para prevenir un header location en bucle en caso de error.
            exit;
        }
    }

    // 3.3. Si no hay errores y el formulario enviado pertenece a register, intentamos registrar al nuevo usuario.
    elseif (empty($_SESSION['error_messages']) && isset($_POST['register']) && $_POST['register'] == 'Registrarse')
    {
        // Intentamos conectarnos a la base de datos;
        $connection = connect_to_database();

        // Registramos el nombre enviado.
        $name = $_POST['name'];

        // Registramos el email enviado.
        $email = $_POST['email'];

        // Registramos la password enviada.
        $password = $_POST['password'];

        // Preparamos la consulta para verificar si el correo electrónico ya fue registrado.
        $stmt = $connection->prepare(
            "SELECT
                id
            FROM
                usuarios2
            WHERE
                correo_electronico = :email"
        );

        // Sincronizamos el email de la consulta con el enviado.
        $stmt->bindParam(':email', $email);

        // Ejecutamos la consulta.
        $stmt->execute();

        // Si se devuelve el registro con el email enviado, quiere decir que ya está registrado en la web.
        if ($stmt->fetch(PDO::FETCH_ASSOC))
        {
            // Cerramos la conexión después de finalizar la consulta.
            $connection = null; 

            // Registramos en la variable de sesión que el correo electrónico ya registrado.
            $_SESSION['wrong_user']['register'] = "Error en el registro: El correo electrónico ya está registrado";

            // Redirigimos a la página de login después de comprobar que usuario ya existe.
            header("Location: ./form_login.php");

            // Detenemos la ejecución del script para prevenir un header location en bucle en caso de error.
            exit;
        }

        // En caso de que el usuario no exista en la base de datos, lo registramos.
        else 
        {
            // Realizamos un hash de la contraseña antes de almacenarla.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el nuevo usuario en la base de datos.
            $stmt = $connection->prepare(
                "INSERT INTO 
                    usuarios2 (nombre, correo_electronico, contrasena_hash, color_fondo) 
                VALUES 
                    (:name, :email, :hashedPassword)");

            // Sincronizamos el nombre de la consulta con el enviado.
            $stmt->bindParam(':name', $name);

            // Sincronizamos el email de la consulta con el enviado.
            $stmt->bindParam(':email', $email);

            // Sincronizamos el password de la consulta con el enviado.
            $stmt->bindParam(':hashedPassword', $hashedPassword);

            // Ejecutamos la consulta.
            $stmt->execute();

            // Cerramos la conexión después de finalizar la consulta.
            $connection = null;

            // Añadimos un mensaje de confirmación de registro exitoso.
            $_SESSION['register_valid'] = '¡Usted se ha registrado correctamente, ya puede iniciar sesión si lo desea!';

            // Después del registro exitoso le invitamos a loguearse.
            header("Location: ./form_login.php");

            // Detenemos la ejecución del script para prevenir un header location en bucle en caso de error.
            exit;
        }
    }

    // 3.4. Si no hay errores y el formulario enviado no pertenece a la aplicación, finalizamos la ejecución del script.
    else
    {
        // Vacía las variables de sesión.
        session_unset();

        // Destruye la sesión actual.
        session_destroy();

        // Finalizamos la ejecución del script.
        die("Acceso no permitido");
    }
