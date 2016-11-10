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
		echo json_encode($salida);
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
	echo json_encode($salida);
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
		$salida = array("mensaje"=>"Bienvenido a Favores.com.co, será redirigido al home.",
						    "continuar"=>1,
						    "datos"=>$queryVerifica[0]);
	}
	echo json_encode($salida);
}
else if($accion == 3)//servicios app móvil
{
	$idServiciosDB = 1314;
	$queryRestante = "";
	if(isset($idServicio))
	{
		$queryRestante = " AND id=".$idServicio;
	}
	//realizo un query para traer la información de los servicios a mostrar en el home
	$query   = sprintf("SELECT * FROM principal WHERE id_padre=%s %s AND eliminado=0 AND visible=1 ORDER BY orden ASC",$idServiciosDB,$queryRestante);
	$resultado	 = $db->GetAll($query);
	$arregloN = array();
	if(count($resultado) > 0)
	{
		foreach($resultado as $res)
		{
			$data = array("titulo"=>utf8_encode($res['titulo']),
						  "id"=>$res['id'],
						  "id_padre"=>$res['id_padre'],
						  "iva"=>$res['iva'],
						  "ejemplo"=>$res['partefabricante'],
						  "foto"=>($res['imagen'] != "")?_DOMINIO."images/".$res['imagen']:_DOMINIO."images/diseno/sin.jpg",
						  "resumen"=>utf8_encode($res['resumen']),
						  "contenido"=>utf8_encode($res['contenido']),
						  "contenidoSolo"=>utf8_encode(strip_tags($res['contenido'])));
			array_push($arregloN,$data);
		}
		$salida = array("mensaje"=>"Se consultaron los servicios",
						"continuar"=>1,
						"datos"=>$arregloN);
	}
	else
	{
		$salida = array("mensaje"=>"No hay servicios disponibles en este momento.",
						    "continuar"=>0,
						    "datos"=>array());
	}

	echo json_encode($salida);
}

else if($accion == 4)//info del usuario logueado
{
	$query = sprintf("SELECT * FROM usuarios WHERE idusuario=%s",$idusuario);
	$resultado = $db->GetAll($query);
	if(count($resultado) > 0)
	{
		$salida = array("mensaje"=>"Info del usuario",
						    "continuar"=>1,
						    "datos"=>$resultado[0]);
	}
	else
	{
		$salida = array("mensaje"=>"No hay infórmación del usuario",
						    "continuar"=>0,
						    "datos"=>array());
	}
	echo json_encode($salida);
}
else
{
	$salida = array("mensaje"=>"No tiene acceso de ingresar a esta zona",
					"continuar"=>0,
					"datos"=>array());
	echo json_encode($salida);
}
?>