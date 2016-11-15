<?php 
/*
* Archivo que controla el funcionamiento de los procesos de la aplicación móvil
* @author Farez Prieto
* @date 5 de Noviembre de 2016
*/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,x-prototype-version,x-requested-with');

ini_set("display_errors", 0);
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
else if($accion == 5)//listado Solicitudes
{
	$resto = (isset($idSolicitud))?" AND idSolicitud=".$idSolicitud:"";
	$query = sprintf("SELECT * FROM solicitudes WHERE idUsuario=%s %s",$usuario,$resto);
	$resultado = $db->GetAll($query);
	$vueltas 	=	array();
	if(count($resultado) > 0)
	{
		//recorro el arreglo para ordenar la información
		foreach($resultado as $orden)
		{
			$pedazos1			=  	explode(" ",$orden['fechaFavor']);
			$pedazos2 			=   explode("-",$pedazos1[0]);
			$fechaFavor 		=	$pedazos2[2]." de ".$funciones->TraducirMes($pedazos2[1])." de ".$pedazos2[0];

			$pedazos3			=  	explode(" ",$orden['fechaFavor']);
			$pedazos4 			=   explode("-",$pedazos3[0]);
			$fechaSolicitud 	=	$pedazos4[2]." de ".$funciones->TraducirMes($pedazos4[1])." de ".$pedazos4[0];

			$dataServicio		=	$funciones->consultaUniversal("principal"," id=".$orden['servicio'],'titulo');
			$estado				=	"";	

			//var_dump($dataServicio[0]['titulo']);

			if($orden['estado'] == 1)
			{
				$estado = "Recibido";
			}


			$ajuste		=	array("idSolicitud"=>$orden['idSolicitud'],
								  "idServicio"=>$orden['servicio'],
								  "servicio"=>utf8_encode($dataServicio[0]['titulo']),
								  "fechaFavor"=>$fechaFavor,
								  "fechaSolicitud"=>$fechaSolicitud,
								  "texto"=>$orden["texto"],
								  "estado"=>$estado,
								  "costo"=>$orden["costo"],
								  "direccion1"=>$orden["direccion1"],
								  "telefono1"=>$orden["telefono1"],
								  "persona1"=>$orden["persona1"],
								  "direccion2"=>$orden["direccion2"],
								  "telefono2"=>$orden["telefono2"],
								  "persona2"=>$orden["persona2"],
								  "form"=>$orden["form"]);

			array_push($vueltas,$ajuste);
		}
		$salida = array("mensaje"=>"Solicitudes consultadas",
						    "continuar"=>1,
						    "datos"=>$vueltas);
	}
	else
	{
		$salida = array("mensaje"=>"No hay solicitudes para mostrar",
						    "continuar"=>0,
						    "datos"=>array());
	}
	echo json_encode($salida);

}
else if($accion == 6)//insertar solicitudes Solicitudes
{
	//die($form);
	if($form == 1)//formulario sencillo
	{
		$queryInsert = sprintf("INSERT INTO solicitudes (fechaFavor,idUsuario,texto,form,fecha,servicio) VALUES ('%s','%s','%s','%s','%s','%s')",
							 $fecha,$usuario,$texto,$form,date("Y-m-d H:i:s"),$servicio);
	}
	$resultado = $db->Execute($queryInsert) or die($queryInsert);
	$idInsertado = $db->Insert_ID();

	if($resultado)
	{
		$salida = array("mensaje"=>"Su solicitud ha sido enviada, pronto estaremos en contacto con usted. El número de su solicitud es: ".$idInsertado,
						    "continuar"=>1,
						    "datos"=>$idInsertado);
	}
	else
	{
		$salida = array("mensaje"=>"La solicitud no ha podido ser enviada, por favor intente de nuevo má tarde.",
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