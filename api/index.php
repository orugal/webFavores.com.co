<?php 
/*
* Archivo que controla el funcionamiento de los procesos de la aplicación móvil
* @author Farez Prieto
* @date 5 de Noviembre de 2016
*/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,x-prototype-version,x-requested-with');

require("../config/configuracion.php");
require("../config/conexion_2.php");
require("../core/funciones.class.php");
//llamo el objeto de conexión a la base de datos
global $db;
//instancio la clase funciones
$funciones = new Funciones();
extract($_REQUEST);
//realizo la validación
$salida = array();
if($accion == 0)//proceso de registro de personas
{
		$salida = array("mensaje"=>"esta es una prueba",
						"continuar"=>0,
						"datos"=>array());
}
else if($accion == 1)//proceso de registro de personas
{
	//antes de insertar el usuario debo verificar que no está en la base de datos
	$queryVerifica = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE email='%s'",strtolower($email)));
	//vaildo
	if(count($queryVerifica) > 0)
	{
		$salida = array("mensaje"=>"El correo que está tratando registrar ya existe en nuestra base de datos, por favor verifique ó intente con otro.",
						    "continuar"=>0,
						    "datos"=>array());
	}
	else
	{
		//realizo el query de inserción
		$query	=	sprintf("INSERT INTO usuarios (nombres,email,celular,ciudad,contrasena,username,acepta) 
							 VALUES('%s','%s','%s','%s','%s','%s','%s')",
	  						$nombre,
	  						strtolower($email),
	  						$celular,
	  						$ciudad,
	  						sha1($rclave),
	  						strtolower($email),
	  						1);
		//echo $query;
		$result = $db->Execute($query);
		if($result)
		{
			$salida = array("mensaje"=>"El usuario se ha insertado correctamente, a su correo hemos enviado un link de verificación",
						    "continuar"=>1,
						    "datos"=>array());
		}
		else
		{
			$salida = array("mensaje"=>"No se ha podido realizar la inserción del usuario, por favor intente de nuevo más tarde.",
						"continuar"=>0,
						"datos"=>array());
		}
	}
}
else if($accion == 2)//proceso de login
{
	//veerifico que la clave y el usuario esten correctos
	$queryVerifica = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE username='%s' AND contrasena=sha1('%s')",strtolower($username),$contrasena));
	//valido
	if(count($queryVerifica) == 0)
	{
		$salida = array("mensaje"=>"el usuario o contraseña no son válidos, probablemente los ha escrito mal ó no existen. Por favor verifique.",
						    "continuar"=>0,
						    "datos"=>array());
	}
	else
	{
		$salida = array("mensaje"=>"Usuario correcto",
						    "continuar"=>1,
						    "datos"=>$queryVerifica[0]);
	}
}
else
{
	$salida = array("mensaje"=>"No tiene acceso de ingresar a esta zona",
					"continuar"=>0,
					"datos"=>array());
}

echo json_encode($salida);
?>