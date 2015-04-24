<?php
session_start();
//set_include_path("google-api-php-client/src/" . PATH_SEPARATOR . get_include_path());
 require_once 'google-api-php-client/src/Google/autoload.php';
 //require_once 'Google/Service/Tasks.php';
 //require_once'Google/Service/Calendar.php';

 require_once 'xajax/xajax_core/xajax.inc.php';
// Creamos el objeto xajax
$xajax = new xajax('ajaxmaps.php');
 
// Configuramos la ruta en que se encuentra la carpeta xajax_js
$xajax->configure('javascript URI','xajax');
 
// Y registramos las funciones que vamos a llamar desde JavaScript
//Estas funciones vienen implementadas en el fichero facilitado '''''ajaxmaps.php'''''
$xajax->register(XAJAX_FUNCTION,"obtenerCoordenadas");
$xajax->register(XAJAX_FUNCTION,"ordenarReparto");

 $idCliente='clave id';
 $passCliente ='password';
 $keyDeveloper='api clave';
 
//URL Donde google redirigirá la aplicación una vez que se haya autentificado
//En mi caso el mismo fichero php que contiene la aplicación
 $urlRedirect = 'la misma paguina que recibe la respuesta';
 
 
// Creamos el objeto de la API de Google, primero un objeto de la clase Client
$cliente = new Google_Client();
 $error="";
 
// Y lo configuramos con los nuestros identificadores
 
$cliente->setApplicationName("repartos");
 
//Establecemos las credenciales para este cliente
$cliente->setClientId($idCliente);
$cliente->setClientSecret($passCliente);
$cliente->setDeveloperKey($keyDeveloper);
 
//Este método especificará la url donde queremos que google redirija la aplicación una vez que se haya logeado correctamente el usuario y que se hayan establecido de manera correcta las credenciales correspondiente. En nuestro caso será al mismo fichero.
$cliente->setRedirectUri($urlRedirect);
 
 
//Establecemos los permisos que queremos otorgar. En este caso queremos conceder acceso a tasks y a calendar para que el usuario pueda acceder a tareas y 
$cliente->setScopes(array('https://www.googleapis.com/auth/tasks','https://www.googleapis.com/auth/calendar'));

/************************************************
  If we're logging out we just need to clear our
  local access token in this case
 ************************************************/
if (isset($_REQUEST['logout'])) {
  unset($_SESSION['access_token']);
}
 
/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
 ************************************************/
if (isset($_GET['code'])) {
  $cliente->authenticate($_GET['code']);
  $_SESSION['access_token'] = $cliente->getAccessToken();
  header('Location: ' . filter_var($urlRedirect, FILTER_SANITIZE_URL));
}
 
/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $cliente->setAccessToken($_SESSION['access_token']);
 
} else {
  $authUrl = $cliente->createAuthUrl();
  header('Location: ' . filter_var( $authUrl, FILTER_SANITIZE_URL));
 
}


/************************************************
  If we're logging out we just need to clear our
  local access token in this case
 ************************************************/
if (isset($_REQUEST['logout'])) {
  unset($_SESSION['access_token']);
}
 
/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
 ************************************************/
if (isset($_GET['code'])) {
  $cliente->authenticate($_GET['code']);
  $_SESSION['access_token'] = $cliente->getAccessToken();
  header('Location: ' . filter_var($urlRedirect, FILTER_SANITIZE_URL));
}
 
/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $cliente->setAccessToken($_SESSION['access_token']);
 
} else {
  $authUrl = $cliente->createAuthUrl();
  header('Location: ' . filter_var( $authUrl, FILTER_SANITIZE_URL));
 
}


//Objeto con el api que queremos trabajar en este caso task
$apiTareas= new Google_Service_Tasks($cliente);
 
//Objeto con el api que queremos trabajar con el calendario
$apiCalendario = new Google_Service_Calendar($cliente);

//Si ejecutamos el fichero habiendo dando al botón de un formulario llamado accion
//Si ejecutamos el fichero habiendo dando al botón de un formulario llamado accion
if (isset($_GET['accion'])){
    switch ($_GET['accion']) {
          case 'nuevalista':
            //Si no está vacío el titulo creamos una nueva lista de reparto
            if (!empty($_GET['fechaReparto'])) {
                // Crear una nueva lista de reparto
                try {
                    // Vamos a analizar la fecha que obtememos a ver si es válida
                    //Esta parte de verificación cortesía de Felix  Esteban (alumno del ciclo), GRACIAS!!!!!!
                    $fecha = explode("/", $_GET['fechaReparto']);
                    if (count($fecha) == 3 && checkdate($fecha[1], $fecha[0], $fecha[2])) {
                        // La fecha es correcta creamos la entrada en Calendar
                        // Insertar evento
                        $evento = new Google_Service_Calendar_Event();
                        $evento->setSummary("Reparto");
                        // hora de comienzo
                        $comienzo = new Google_Service_Calendar_EventDateTime();
                        $comienzo->setDateTime("$fecha[2]-$fecha[1]-$fecha[0]T09:00:00.000");
                        $comienzo->setTimeZone("Europe/Madrid");
                        $evento->setStart($comienzo);
                        // hora de terminación
                        $final = new  Google_Service_Calendar_EventDateTime();
                        $final->setDateTime("$fecha[2]-$fecha[1]-$fecha[0]T20:00:00.000");
                        $final->setTimeZone("Europe/Madrid");
                        $evento->setEnd($final);
                        $createdEvent = $apiCalendario->events->insert('primary', $evento);
                    } else {
                        // La fecha está mal
                        
                        throw new Exception("Fecha incorrecta");
                                           }
                    $nuevalista = new Google_Service_Tasks_TaskList();
                    $nuevalista->setTitle("Reparto ".$_GET['fechaReparto']);
                  
                    $apiTareas->tasklists->insert($nuevalista);
                }
                catch (Exception $e) {
                      $error="Error al crear un nuevo reparto.";
                      
                }
            }
            break;
          case 'nuevatarea':
            if (!empty($_GET['nuevotitulo']) && !empty($_GET['idreparto']) && !empty($_GET['latitud']) && !empty($_GET['longitud'])) {
            // Crear una nueva tarea de envío
                try {
                    $nuevatarea = new Google_Service_Tasks_Task();
                    $nuevatarea->setTitle($_GET['nuevotitulo']);
                    if (isset($_GET['direccion']))
                        $nuevatarea->setTitle($_GET['nuevotitulo']." - ".$_GET['direccion']);
                    else
                         $nuevatarea->setTitle($_GET['nuevotitulo']);
                    $nuevatarea->setNotes($_GET['latitud'].",".$_GET['longitud']);
                    // Añadimos la nueva tarea de envío a la lista de reparto
                    $apiTareas->tasks->insert($_GET['idreparto'], $nuevatarea);
                }
                catch (Exception $e) {
                    $error="Se ha producido un error al intentar crear un nuevo envío.";
                }
            }
            break;
            case 'borrarlista':
               if (!empty($_GET['reparto'])) {
                // Borrar una lista de reparto
                try {
                    $apiTareas->tasklists->delete($_GET['reparto']);
                }
                catch (Exception $e) {
                    $error="Se ha producido un error al intentar borrar el reparto.";
                }
            }
            break;
        case 'borrartarea':
             if (!empty($_GET['reparto']) && !empty($_GET['envio'])) {
                // Borrar una tarea de envío
                try {
                    $apiTareas->tasks->delete($_GET['reparto'],$_GET['envio']);
                }
                catch (Exception $e) {
                    $error="Se ha producido un error al intentar borrar el envío.";
                }                
             } 
             break;
     }//end switch... accion
  }//end isset...accion
// Obtenemos el id de la lista de tareas por defecto
 
 //Listas actual de tareas
 $listapordefecto = $apiTareas->tasklists->listTasklists();
 //Seleccionamos una lista por defecto
 $id_defecto = $listapordefecto['id'];
?>

<!DOCTYPE html>
 <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <title>Ejemplo Tema 8: Rutas de reparto</title>
    <link href="estilos.css" rel="stylesheet" type="text/css" />
    <?php
     // Le indicamos a Xajax que incluya el código JavaScript necesario
       $xajax->printJavascript(); 
    ?>    
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
    <script type="text/javascript" src="codigo.js"></script>
 </head>
 
 <body>
  <div id="dialogo">
    <a id="cerrarDialogo" onclick="ocultarDialogo();">x</a>
    <h1>Datos del nuevo envío</h1>
    <form id="formenvio" name="formenvio" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
      <fieldset>
        <div id="datosDireccion">
         <p>
           <label for='direccion' >Dirección:</label>
           <input type='text' size="45" name='direccion' id='direccion' />
         </p>
         <input type='button' id='obtenerCoordenadas' value='Obtener coordenadas' onclick="getCoordenadas();"/><br />
        </div>
        <div id="datosEnvio">
         <p>
           <label for='latitud' >Latitud:</label>
           <input type='text' size="10" name='latitud' id='latitud' />
         </p>
         <p>
           <label for='longitud' >Longitud:</label>
           <input type='text' size="10" name='longitud' id='longitud' />
         </p>
         <p>
           <label for='nuevotitulo' >Altitud:</label>
           <input type='text' size="10" name='altitud' id='altitud' />
         </p>
         <p>
           <label for='nuevotitulo' >Título:</label>
           <input type='text' size="40" name='nuevotitulo' id='titulo' />
         </p>
           <input type='hidden' name='accion' value='nuevatarea' />
           <input type='hidden' name='idreparto' id='idrepartoactual' />
           <input type='submit' id='nuevoEnvio' value='Crear nuevo Envío' />
           <a href="#" onclick="abrirMaps();">Ver en Google Maps</a><br />
       </div>
      </fieldset>
    </form>
 </div>  <!-- end div dialogo-->
 <div id="fondonegro" onclick="ocultarDialogo();"></div>
  <div class="contenedor">
    <div class="encabezado">
      <h1>Ejemplo Tema 8: Rutas de reparto</h1>
      <form id="nuevoreparto" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
        <fieldset>
          <input type='hidden' name='accion' value='nuevalista' />
          <input type='submit' id='crearnuevotitulo' value='Crear Nueva Lista de Reparto' />
          <label for='nuevotitulo' >Fecha de reparto:</label>
          <input type='text' name='fechaReparto' id='fechaReparto' />
        </fieldset>
      </form>
    </div>
    <div class="contenido">
      <?php
        $repartos = $apiTareas->tasklists->listTasklists();
        // Para cada lista de reparto
        foreach ($repartos['items'] as $reparto) {
            // Excluyendo la lista por defecto de Google Tasks
            if($reparto['id'] == $id_defecto) 
                   continue;
            print '<div id="'.$reparto['id'].'">';
            print '<span class="titulo">'.$reparto['title'].'</span>';
            $idreparto = "'".$reparto['id']."'";
            print '<span class="accion">(<a href="#" onclick="ordenarReparto('.$idreparto.');">Ordenar</a>)</span>';
            print '<span class="accion">(<a href="#" onclick="nuevoEnvio('.$idreparto.');">Nuevo Envío</a>)</span>';
            print '<span class="accion">(<a href="'.$_SERVER['PHP_SELF'].'?accion=borrarlista&reparto='.$reparto['id'].'">Borrar</a>)</span>';
            print '<ol>';
            // Cogemos de la lista de reparto las tareas de envío
            $envios = $apiTareas->tasks->listTasks($reparto['id']);
            // Por si no hay tareas de envío en la lista
            if (!empty($envios['items'])){
               foreach ($envios['items'] as $envio) {
                 // Creamos un elemento para cada una de las tareas de envío
                 $idenvio = "'".$envio['id']."'";
                 print '<li title="'.$envio['notes'].'" id="'.$idenvio.'">'.$envio['title'].' ('.$envio['notes'].')';
                 $coordenadas =  "'".$envio['notes']."'";
                 print '<span class="accion">  (<a href="#" onclick="abrirMaps('.$coordenadas.');">Ver mapa</a>)</span>';
                 print '<span class="accion">  (<a href="'.$_SERVER['PHP_SELF'].'?accion=borrartarea&reparto='.$reparto['id'].'&envio='.$envio['id'].'">Borrar</a>)</span>';
                 print '</li>';
               }
            }
            print '</ol>';
            print '</div>';
        }
      ?>
    </div>
    <div class="pie">
       <?php print $error; ?>
    </div>
  </div>
 </body>
</html>