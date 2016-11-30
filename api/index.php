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
require('../core/phpmailer/class.phpmailer.php');
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
		if(isset($codigoCelular))
		{
			//actualizo el código del celular
			$updateCelular = $db->execute(sprintf("UPDATE usuarios set codigoCelular='%s' WHERE idusuario=%s",$codigoCelular,$queryVerifica[0]['idusuario']));
		}
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
	$query = sprintf("SELECT * FROM solicitudes WHERE idUsuario=%s %s ORDER BY idSolicitud DESC",$usuario,$resto);
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

			$transacciones = $db->GetAll(sprintf("SELECT * FROM transacciones t 
										INNER JOIN usuarios u ON u.idusuario=t.idUsuario
										WHERE idSolicitud=%s",$orden['idSolicitud']));
			$dataTr				=	array();
			foreach($transacciones as $tr)
			{
				$pedazos111			=  	explode(" ",$tr['fecha']);
				$pedazos21 			=   explode("-",$pedazos11[0]);
				$fechaTr 		=	$pedazos21[2]." de ".$funciones->TraducirMes($pedazos21[1])." de ".$pedazos21[0];
				$arra = array("idTransaccion"=>$tr['idTransaccion'],
							  "idSolicitud"=>$tr['idSolicitud'],
							  "idEstado"=>$tr['idEstado'],
							  "fecha"=>$fechaTr,
							  "textoTransaccion"=>$funciones->deduceEstado($tr['idSolicitud']),
							  "textoTransaccion"=>$tr['textoTransaccion']);
				array_push($dataTr,$arra);
			}

			//var_dump($dataServicio[0]['titulo']);

			$estado = $funciones->deduceEstado($orden['estado']);
			
			if($orden["idPrestador"] > 0)
			{
				$queryInfoUsuario = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE idusuario=%s",$orden["idPrestador"]));
				$dataPrestador = $queryInfoUsuario[0];
			}
			else
			{
				$dataPrestador = array();
			}


			$ajuste		=	array("idSolicitud"=>$orden['idSolicitud'],
								  "idServicio"=>$orden['servicio'],
								  "servicio"=>utf8_encode($dataServicio[0]['titulo']),
								  "fechaFavor"=>$fechaFavor,
								  "fechaSolicitud"=>$fechaSolicitud,
								  "hora"=>$orden["horaFavor"],
								  "prestador"=>$orden["idPrestador"],
								  "dataPrestador"=>$dataPrestador,
								  "texto"=>$orden["texto"],
								  "estado"=>$orden["estado"],
								  "estadoText"=>$estado,
								  "costo"=>$orden["costo"],
								  "direccion1"=>$orden["direccion1"],
								  "telefono1"=>$orden["telefono1"],
								  "persona1"=>$orden["persona1"],
								  "direccion2"=>$orden["direccion2"],
								  "telefono2"=>$orden["telefono2"],
								  "persona2"=>$orden["persona2"],
								  "form"=>$orden["form"],
								  "transacciones"=>$dataTr);

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
	$mensaje_armado	 =	'Se ha enviado una solicitud por medio de la app m&oacute;vil con la siguiente informaci&oacute;n:<br><br><br>';
	if($form == 1)//formulario sencillo
	{
		$queryInsert = sprintf("INSERT INTO solicitudes (fechaFavor,horaFavor,idUsuario,texto,form,fecha,servicio) VALUES ('%s','%s','%s','%s','%s','%s','%s')",$fecha,$hora,$usuario,$texto,$form,date("Y-m-d H:i:s"),$servicio);

		$dataServicio		=	$funciones->consultaUniversal("principal"," id=".$servicio,'titulo');
		$dataUsuario		=	$funciones->consultaUniversal("usuarios"," idusuario=".$usuario,'*');
		//var_dump($dataUsuario;die();

		$mensaje_armado	.= '<b>Nombres y apellidos:</b> '.$dataUsuario[0]['nombres'].' '.$dataUsuario[0]['apellidos'].'<br>';
		$mensaje_armado	.= '<b>Correo Electr&oacute;nico:</b> '.$dataUsuario[0]['email'].'<br>';
		$mensaje_armado	.= '<b>Tipo de servicio:</b> '.utf8_encode($dataServicio[0]['titulo']).'<br>';
		$mensaje_armado	.= '<b>Fecha del favor: </b>'.$fecha.'<br>';
		$mensaje_armado	.= '<b>Hora del favor: </b>'.$hora.'<br>';
		$mensaje_armado	.= '<b>Detalle del favor:</b> '.$texto.'<br>';
	}
	elseif($form == 2)//form solo datos origen
	{
		$queryInsert = sprintf("INSERT INTO solicitudes (fechaFavor,horaFavor,idUsuario,texto,form,fecha,servicio,persona1,direccion1,telefono1) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$fecha,$hora,$usuario,$texto,$form,date("Y-m-d H:i:s"),$servicio,$persona1,$direccion1,$telefono1);

		$dataServicio		=	$funciones->consultaUniversal("principal"," id=".$servicio,'titulo');
		$dataUsuario		=	$funciones->consultaUniversal("usuarios"," idusuario=".$usuario,'*');

		$mensaje_armado	.= '<b>Nombres y apellidos:</b> '.$dataUsuario[0]['nombres'].' '.$dataUsuario[0]['apellidos'].'<br>';
		$mensaje_armado	.= '<b>Correo Electr&oacute;nico:</b> '.$dataUsuario[0]['email'].'<br>';
		$mensaje_armado	.= '<b>Tipo de servicio:</b> '.utf8_encode($dataServicio[0]['titulo']).'<br>';
		$mensaje_armado	.= '<b>Fecha del favor: </b>'.$fecha.'<br>';
		$mensaje_armado	.= '<b>Hora del favor: </b>'.$hora.'<br>';
		$mensaje_armado	.= '<b>Dirección de origen: </b>'.$direccion1.'<br>';
		$mensaje_armado	.= '<b>Persona de contacto: </b>'.$persona1.'<br>';
		$mensaje_armado	.= '<b>Teléfono de contacto: </b>'.$telefono1.'<br>';
		$mensaje_armado	.= '<b>Detalle del favor:</b> '.$texto.'<br>';
	}
	elseif($form == 3)//form solo datos destino
	{
		$queryInsert = sprintf("INSERT INTO solicitudes (fechaFavor,horaFavor,idUsuario,texto,form,fecha,servicio,persona2,direccion2,telefono2) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$fecha,$hora,$usuario,$texto,$form,date("Y-m-d H:i:s"),$servicio,$persona2,$direccion2,$telefono2);

		$dataServicio		=	$funciones->consultaUniversal("principal"," id=".$servicio,'titulo');
		$dataUsuario		=	$funciones->consultaUniversal("usuarios"," idusuario=".$usuario,'*');

		$mensaje_armado	.= '<b>Nombres y apellidos:</b> '.$dataUsuario[0]['nombres'].' '.$dataUsuario[0]['apellidos'].'<br>';
		$mensaje_armado	.= '<b>Correo Electr&oacute;nico:</b> '.$dataUsuario[0]['email'].'<br>';
		$mensaje_armado	.= '<b>Tipo de servicio:</b> '.utf8_encode($dataServicio[0]['titulo']).'<br>';
		$mensaje_armado	.= '<b>Fecha del favor: </b>'.$fecha.'<br>';
		$mensaje_armado	.= '<b>Hora del favor: </b>'.$hora.'<br>';
		$mensaje_armado	.= '<b>Dirección de destino: </b>'.$direccion2.'<br>';
		$mensaje_armado	.= '<b>Persona de contacto: </b>'.$persona2.'<br>';
		$mensaje_armado	.= '<b>Teléfono de contacto: </b>'.$telefono2.'<br>';
		$mensaje_armado	.= '<b>Detalle del favor:</b> '.$texto.'<br>';
	}
	elseif($form == 4)//form datos origen y destino
	{
		$queryInsert = sprintf("INSERT INTO solicitudes (fechaFavor,horaFavor,idUsuario,texto,form,fecha,servicio,persona1,direccion1,telefono1,persona2,direccion2,telefono2) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$fecha,$hora,$usuario,$texto,$form,date("Y-m-d H:i:s"),$servicio,$persona1,$direccion1,$telefono1,$persona2,$direccion2,$telefono2);

		$dataServicio		=	$funciones->consultaUniversal("principal"," id=".$servicio,'titulo');
		$dataUsuario		=	$funciones->consultaUniversal("usuarios"," idusuario=".$usuario,'*');

		$mensaje_armado	.= '<b>Nombres y apellidos:</b> '.$dataUsuario[0]['nombres'].' '.$dataUsuario[0]['apellidos'].'<br>';
		$mensaje_armado	.= '<b>Correo Electr&oacute;nico:</b> '.$dataUsuario[0]['email'].'<br>';
		$mensaje_armado	.= '<b>Tipo de servicio:</b> '.utf8_encode($dataServicio[0]['titulo']).'<br>';
		$mensaje_armado	.= '<b>Fecha del favor: </b>'.$fecha.'<br>';
		$mensaje_armado	.= '<b>Hora del favor: </b>'.$hora.'<br><br>';
		$mensaje_armado	.= '<b>DATOS DE ORIGEN</b><br><br>';
		$mensaje_armado	.= '<b>Dirección: </b>'.$direccion1.'<br>';
		$mensaje_armado	.= '<b>Persona de contacto: </b>'.$persona1.'<br>';
		$mensaje_armado	.= '<b>Teléfono de contacto: </b>'.$telefono1.'<br><br>';
		$mensaje_armado	.= '<b>DATOS DE DESTINO</b><br><br>';
		$mensaje_armado	.= '<b>Dirección: </b>'.$direccion2.'<br>';
		$mensaje_armado	.= '<b>Persona de contacto: </b>'.$persona2.'<br>';
		$mensaje_armado	.= '<b>Teléfono de contacto: </b>'.$telefono2.'<br>';
		$mensaje_armado	.= '<b>Detalle del favor:</b> '.$texto.'<br>';
	}

	$resultado = $db->Execute($queryInsert) or die($queryInsert);
	$idInsertado = $db->Insert_ID();

	if($resultado)
	{

		//realizo el envio de la solicitid vía correo
		$asunto			 =	'Nueva solicitud de favor - '._NOMBRE_EMPRESA;
		
		$envio			 =	$funciones->SendMAIL(_MAIL_ADMIN,$asunto,$mensaje_armado,'',_SMTP_USER,_NOMBRE_EMPRESA);


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
elseif($accion == 7)
{
	define( 'API_ACCESS_KEY', 'AIzaSyDATt8X74QH8JMuF-oJMiP7mz7cupavmjY' );
	$registrationId = array('APA91bE4sZQGdLWEefOfl-nFMXcRFc0drzsUZv08E7IqL6MvXKEOFcGlCdsR9sXk9firIQlYENCOPXL7d3NwhkZePhjidHkRz-VYah4JeQTZR2vWxrj2ipLQG5j7DcmO6Mb65fDTPz8VPV3sRbKin76sI08jfEY_1A');
	//$registrationId = consultaGCMReg();
	$msg = array
	(
		'message' 	=> "Prueba del mensaje",
		'title'		=> "Titulo del mensaje",
		'subtitle'	=> '',
		'tickerText'	=> '',
		'vibrate'	=> 1,
		'sound'		=> 1,
		'largeIcon'	=> 'large_icon',
		'smallIcon'	=> 'small_icon'
	);
	//$msg = "please note this..";
	$fields = array
	(
		'registration_ids' 	=> $registrationId,
		'data'			=> $msg
	);
	 
	$headers = array
	(
		'Authorization: key='.API_ACCESS_KEY,
		'Content-Type: application/json',
	        'delay_while_idle: true',
	);
	 
	try{
	    $ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send' );
	curl_setopt( $ch,CURLOPT_POST, true );
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
	$result = curl_exec($ch );
	curl_close( $ch );
	echo $result;
	}
	catch(Exception $e){
	    echo $e;
	    echo "inside catch";
	}
}
elseif($accion == 8)//envio de preguntas
{
	//inserto la nueva transacción
	$queryTransaccion	=	sprintf("INSERT INTO transacciones (idSolicitud,idUsuario,idEstado,fecha,textoTransaccion) 
											VALUES('%s','%s','%s','%s','%s')",
											$idSolicitud,
											$usuario,
											$estado,
											date("Y-m-d H:i:s"),
											$pregunta);
	$resultadoTra		=	$db->Execute($queryTransaccion);

	if($resultadoTra > 0)
	{
		//saco la información de la solicitud que se va a gestionar
		$query = sprintf("SELECT * FROM solicitudes WHERE idSolicitud=%s",$idSolicitud);
		$resultado = $db->GetAll($query);
		//debo detectar a quién le debo enviar el mail de información.
		if($resultado[0]['idPrestador'] != 0)
		{
			//sonsulto la data del usuario
			$usuarioData = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE idusuario='%s'",$resultado[0]['idPrestador']));
			$paraQuien =	$usuarioData[0]['email'];
		}
		else
		{
			$paraQuien =	_MAIL_ADMIN;
		}

		$mensaje_armado	 = 'Han realizado una pregunta al respecto del servicio.<br><br>';
		$mensaje_armado	.= '<b>Pregunta:</b> '.$pregunta.'<br>';
		//realizo el envio de la solicitid vía correo
		$asunto			 =	'Pregunta al respecto de la solicitud '.$idSolicitud.' - '._NOMBRE_EMPRESA;
		
		$envio			 =	$funciones->SendMAIL($paraQuien,$asunto,$mensaje_armado,'',_SMTP_USER,_NOMBRE_EMPRESA);
		$salida = array("mensaje"=>"Su pregunta ha sido enviada con éxito, pronto estaremos en contacto con usted.",
						    "continuar"=>1,
						    "datos"=>array());
	}
	else
	{
		$salida = array("mensaje"=>"Su pregunta no ha podido ser enviada, intende de nuevo más tarde.",
						    "continuar"=>0,
						    "datos"=>array());
	}

	echo json_encode($salida);

}

elseif($accion == 9)//Aprueba o rechaza el servicio
{
	$query = sprintf("SELECT * FROM solicitudes WHERE idSolicitud=%s",$idSolicitud);
	$resultado = $db->GetAll($query);

	//inserto la nueva transacción
	$queryEstado	=	sprintf("UPDATE solicitudes SET estado=%s WHERE idSolicitud=%s",$estado,$idSolicitud);
	$resultadoTra		=	$db->Execute($queryEstado);

	if($resultadoTra > 0)
	{

		$titulo 		 = ($estado == 6)?"Solicitud Nro: ".$idSolicitud." rechazada por el usuario":"Solicitud Nro: ".$idSolicitud." aprobada por el usuario"; 

		$funciones->insertaTransaccion($resultado[0]['estado'],$estado,$idSolicitud,$usuario,$titulo);

		//saco la información de la solicitud que se va a gestionar
		$query = sprintf("SELECT * FROM solicitudes WHERE idSolicitud=%s",$idSolicitud);
		$resultado = $db->GetAll($query);
		//debo detectar a quién le debo enviar el mail de información.
		if($resultado[0]['idPrestador'] != 0)
		{
			//sonsulto la data del usuario
			$usuarioData = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE idusuario='%s'",$resultado[0]['idPrestador']));
			$paraQuien =	$usuarioData[0]['email'];
		}
		else
		{
			$paraQuien =	_MAIL_ADMIN;
		}

		 	

		if($estado == 6)
		{
			$mensaje_armado	 = 'El usuario ha enviado una respuesta de rechazo a la solicitud que ha estado gestionando.<br><br>';
			$mensaje_armado	.= '<b>ID SOLICITUD:</b> '.$idSolicitud.'<br>';
		}
		else
		{
			$mensaje_armado	 = 'El usuario ha enviado una respuesta de aprobación a la solicitud que ha estado gestionando, es necesario enviar un persona lo antes posible.<br><br>';
			$mensaje_armado	.= '<b>ID SOLICITUD:</b> '.$idSolicitud.'<br>';
		}


		//realizo el envio de la solicitid vía correo
		$asunto			 =	$titulo." - "._NOMBRE_EMPRESA;
		
		$envio			 =	$funciones->SendMAIL($paraQuien,$asunto,$mensaje_armado,'',_SMTP_USER,_NOMBRE_EMPRESA);
		$salida = array("mensaje"=>"Su solicitud ha sido enviada exitosamente, pronto estaremos en contacto con usted.",
						    "continuar"=>1,
						    "datos"=>array());
	}
	else
	{
		$salida = array("mensaje"=>"Su solicitud no ha podido ser procesada, intende de nuevo más tarde.",
						    "continuar"=>0,
						    "datos"=>array());
	}

	echo json_encode($salida);

}
elseif($accion == 10)//contacto
{
	//envio el mail
	$mensaje_armado	 = 'Se ha enviado un mensaje de contacto por medio del formulario de la app móvil con la siguiente información:.<br><br>';
	$mensaje_armado	.= '<b>Nombre:</b> '.$nombre.'<br>';
	$mensaje_armado	.= '<b>Correo electrónico:</b> '.$email.'<br>';
	$mensaje_armado	.= '<b>Teléfono:</b> '.$telefono.'<br>';
	$mensaje_armado	.= '<b>Mensaje:</b> '.$mensaje.'<br>';
	//realizo el envio de la solicitid vía correo
	$asunto			 =	'Mensaje de contacto - '._NOMBRE_EMPRESA;
	
	$envio			 =	$funciones->SendMAIL($paraQuien,$asunto,$mensaje_armado,'',_SMTP_USER,_NOMBRE_EMPRESA);
	if($envio)
	{
		$salida = array("mensaje"=>"Gracias por contactarnos, pronto estaremos en contacto con usted.",
						    "continuar"=>1,
						    "datos"=>array());

	}
	else
	{
		$salida = array("mensaje"=>"No se ha podido enviar el mensaje, intente de nuevo más tarde.",
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