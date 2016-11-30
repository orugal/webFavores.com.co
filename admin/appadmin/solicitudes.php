<?php
/*
 * Control de las solicitudes
 * @autor Farez Prieto
 * @date 22 de Noviembre 2016
 * @version 1.0
 */
require('../core/PHPPaging.lib.php');
global $db;
global $id;
global $funciones;
extract($_GET);
$plantilla = 0;
$estados 	= $funciones->deduceEstado();

if(isset($idSolicitud))
{

		

		$personas = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE perfil=3 AND estado=1 AND eliminado=0"));

		$plantilla     = 2;
		$filtroPerfil  = " AND s.idSolicitud=".$idSolicitud;
		$permitoVision = false;
		//busco la solicitud que se haya seleccionado
		$query	=	sprintf("SELECT u.*,p.*,s.*,s.estado as estadoSol FROM solicitudes s
							 INNER JOIN usuarios u ON s.idusuario=u.idusuario
							 INNER JOIN principal p on p.id=s.servicio
							 WHERE s.eliminado = 0 
							 %s
							 ORDER BY s.idSolicitud DESC",$filtroPerfil);
		//echo $query;
		$resultado = $db->GetAll($query);


		$transacciones = $db->GetAll(sprintf("SELECT * FROM transacciones t 
										INNER JOIN usuarios u ON u.idusuario=t.idUsuario
										WHERE idSolicitud=%s",$idSolicitud));


		//var_dump($resultado);
		//valido si es un perfil 3 para verificar que la solicitud sea de el
		if($_SESSION['login']['perfil'] == 3)
		{
			
			if($resultado[0]['idPrestador'] == $_SESSION['login']['idusuario'])
			{
				$permitoVision = true;	
			}
		}
		else
		{
			$permitoVision = true;
		}

		///envio de la respuesta
	if(isset($_POST['enviar']))
	{
		extract($_POST);

		
		$estadoActual = $resultado[0]['estadoSol'];
		//procedo a insertar en la base de datos se la solicitud
		$queryUpdate = sprintf("UPDATE solicitudes SET idPrestador='%s', costo='%s',estado='%s' WHERE idSolicitud=%s",$idPrestador,$costo,$estado,$idSolicitud);
		//die($queryUpdate);
		$resultado   = $db->Execute($queryUpdate);

		//si escribe algo en el contenido guardará una transacción
		if(trim($contenido) != "")
		{
			$queryTransaccion	=	sprintf("INSERT INTO transacciones (idSolicitud,idUsuario,idEstado,fecha,textoTransaccion) 
											VALUES('%s','%s','%s','%s','%s')",
											$idSolicitud,
											$_SESSION['login']['idusuario'],
											$estado,
											date("Y-m-d H:i:s"),
											$contenido);
			$resultadoTra		=	$db->Execute($queryTransaccion);
		}
		else
		{
			$funciones->insertaTransaccion($estadoActual,$estado,$idSolicitud,$_SESSION['login']['idusuario'],"Cambio de estado de la solicitud");
		}

		if($resultado > 0)
		{
			/*//al enviar una transacción desde la web debo notificar al usuario que envio por medio del mail
			$queryInfoUsuario = $db->GetAll(sprintf("SELECT * FROM usuarios WHERE idusuario=%s",$resultado[0]["idUsuario"]));
			$dataPrestador = $queryInfoUsuario[0];
			//ahora procedo a enviar un mail.

			$mensaje_armado	 =	'Se ha enviado respuesta de una solicitud de '._NOMBRE_EMPRESA.':<br><br><br>';
			$mensaje_armado	.= 'Con respecto a la solicitud <strong>NRO:'.$resultado[0]['idSolicitud'].'</strong><br>';*/



			echo "<script>alert('Se ha enviado la información al usuario.');document.location='"._DOMINIO."admin/index.php?id=1324&idSolicitud=".$idSolicitud."';</script>";
		}
		else
		{
			echo "<script>alert('No se ha podido enviar la informació al usuario.');document.location='"._DOMINIO."admin/index.php?id=1324&idSolicitud=".$idSolicitud."';</script>";
		}

		//estado
		//idPrestador
		//costo
		//enviar
	}


}
else
{

	if($_SESSION['login']['perfil'] == 3)
	{
		$filtroPerfil = "AND s.idPrestador=".$_SESSION['login']['idusuario'];
	}
	else
	{
		$filtroPerfil = "";
	}

	$ssql	=	sprintf("SELECT * FROM solicitudes s
						 INNER JOIN usuarios u ON s.idusuario=u.idusuario
						 INNER JOIN principal p on p.id=s.servicio
						 WHERE s.eliminado = 0 
						 %s
						 ORDER BY s.idSolicitud DESC",$filtroPerfil);

	//echo $ssql;
	$paging=new PHPPaging;
	$paging->paginasAntes(6);
	$paging->paginasDespues(6);
	if($_GET['ver'])
	{
		$e_ssql=mysql_query($ssql);
		$numver=mysql_num_rows($e_ssql);
	}
	else
	{
		$numver=10;
	}
	
	$paging->porPagina($numver);
	$paging->agregarConsulta($ssql);
	$paging->ejecutar();
	if(!$paging->numTotalRegistros())
	 {
		$no_encon="No hay regitros con este criterio de b&uacute;squeda!!!";
	}
	
	$links = $paging->fetchNavegacion();
	$plantilla = 1;
}


?>


<?php if($plantilla == 1){ ?>
	<div class="container-fluid">
		<table class="table">
			<thead>
				<tr>
					<th class="text-center">ID SOLICITUD</th>
					<th >SOLICITANTE</th>
					<th class="text-center">FECHA</th>
					<th class="text-center">HORA</th>
					<th class="text-center">TIPO</th>
					<th class="text-center">ESTADO</th>
					<th class="text-center">ACCIONES</th>
				</tr>	
			</thead>
			<tbody>
				<?php while($sol = $paging->fetchResultado()){ ?>
					<tr>
						<td class="text-center"><?php echo $sol['idSolicitud'] ?></td>
						<td><?php echo $sol['nombres'] ?> <?php echo $sol['apellidos'] ?></td>
						<td class="text-center"><?php echo date("Y-m-d",strtotime($sol['fechaFavor'])) ?></td>
						<td class="text-center"><?php echo $sol['horaFavor'] ?></td>
						<td class="text-center"><?php echo utf8_encode($sol['titulo']) ?></td>
						<td class="text-center"><?php echo $estados[$sol['estado']] ?></td>
						<td class="text-center">
							<a href="<?php echo _DOMINIO ?>admin/index.php?id=<?php echo $id ?>&idSolicitud=<?php echo $sol['idSolicitud'] ?>" class="btn btn-primary glyphicon glyphicon-info-sign" title="Ver detalles de la solicitud"></a>
						</td>
					</tr>
				<?php } ?>
					<tr>
						<td colspan="7" class="text-center" style="font-size: 1.5em">
							<?php echo $links; ?>
						</td>
					</tr>
			</tbody>
			
		</table>
<!--
			<div class="alert alert-info">
				  No hay solicitudes creadas hasta el momento.
			</div>
		-->


	</div>
<?php }?>

<?php if($plantilla == 2){ ?>
<div class="container-fluid">
	<?php if($permitoVision){ ?>
			<ol class="breadcrumb">
			  <li><a href="<?php echo _DOMINIO ?>admin/index.php?id=1324">Inicio</a></li>
			  <li class="active"><?php echo $resultado[0]['nombres'] ." ".$resultado[0]['apellidos'] ?></li>
			</ol>
			<h1>
				<?php echo $resultado[0]['nombres'] ." ".$resultado[0]['apellidos'] ?>
				<span class="badge"><?php echo utf8_encode($resultado[0]['titulo']) ?></span>
			</h1>
			<h5>
				<?php echo date("d",strtotime($resultado[0]['fechaFavor']))." de ".$funciones->TraducirMes(date("m",strtotime($resultado[0]['fechaFavor'])))." de ".date("Y",strtotime($resultado[0]['fechaFavor'])) ?> - <?php echo $resultado[0]['horaFavor'] ?>
			</h5>
			<blockquote>
			<p><?php echo ($resultado[0]['texto']) ?></p>
			</blockquote>
			<div class="row">
				<?php if($resultado[0]['direccion1'] != ""){ ?>
					<div class="col col-lg-6 text-left">
						<div class="panel panel-default">
						  <div class="panel-heading">DATOS DE ORIGEN</div>
						  <div class="panel-body">
								Dirección: <?php echo $resultado[0]['direccion1'] ?><br>
								Contacto: <?php echo $resultado[0]['persona1'] ?><br>
								Tel&eacute;fono: <?php echo $resultado[0]['telefono1'] ?><br>
						  </div>
						</div>
					</div>
				<?php } ?>


				<?php if($resultado[0]['direccion2'] != ""){ ?>
					<div class="col col-lg-6 text-left">
						<div class="panel panel-default">
						  <div class="panel-heading">DATOS DE DESTINO</div>
						  <div class="panel-body">
								Dirección: <?php echo $resultado[0]['direccion2'] ?><br>
								Contacto: <?php echo $resultado[0]['persona2'] ?><br>
								Tel&eacute;fono: <?php echo $resultado[0]['telefono2'] ?><br>
						  </div>
						</div>
					</div>
				<?php } ?>
			</div>
				<form method="post" action="">
			<div class="row">
					<div class="col col-lg-12 text-center">
						<h4>GESTIONAR</h4>
					</div>
					<div class="col col-lg-4">
						<div class="form-group">
							<label class="control-label" for="nombreLista">ESTADO</label>
							<select id="estado" name="estado" class="form-control" <?php if($resultado[0]['estadoSol'] == 4 or $resultado[0]['estadoSol'] == 5 or $resultado[0]['estadoSol'] == 6){?> disabled <?php }?>>
								<?php foreach($estados as $llave=>$est){ ?>
									<option value="<?php echo $llave ?>" <?php if($resultado[0]['estadoSol'] == $llave){ ?> selected <?php }?>><?php echo $est ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
					<div class="col col-lg-4">
						<div class="form-group">
							<label class="control-label" for="nombreLista">MOTORIZADO</label>
							<select id="idPrestador" name="idPrestador" class="form-control" <?php if($resultado[0]['estadoSol'] == 4 or $resultado[0]['estadoSol'] == 5 or $resultado[0]['estadoSol'] == 6){?> disabled <?php }?>>
								<option value="">SELECCIONE...</option>
								<?php foreach($personas as $pe){ ?>
									<option value="<?php echo $pe['idusuario'] ?>" <?php if($pe['idusuario'] == $resultado[0]['idPrestador']){ ?> selected <?php }?>><?php echo $pe['nombres']." ".$pe['apellidos'] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
					<div class="col col-lg-4">
						<div class="form-group">
							<label class="control-label" for="nombreLista">COSTO DEL SERVICIO</label>
							<input <?php if($resultado[0]['estadoSol'] == 4 or $resultado[0]['estadoSol'] == 5 or $resultado[0]['estadoSol'] == 6){?> disabled <?php }?> type="text" id="costo" placeholder="Sin . , $" name="costo" class="form-control" value="<?php echo $resultado[0]['costo'] ?>"/>
						</div>
					</div>
					<div class="col col-lg-12">
						<div class="form-group">
							<label class="control-label" for="contenido">HACER UNA PREGUNTA AL USUARIO</label>
							<textarea id="contenido" class="form-control" rows="3" style="width: 100%;margin:0 0 5% 0;padding: 1%"  name="contenido" <?php if($resultado[0]['estadoSol'] == 4 or $resultado[0]['estadoSol'] == 5 or $resultado[0]['estadoSol'] == 6){?> disabled <?php }?> placeholder="Puede realizar preguntas al usuario para aclarar las condiciones del servicio."></textarea>
						</div>
					</div>
					<?php if($resultado[0]['estadoSol'] != 4 and $resultado[0]['estadoSol'] != 5 and $resultado[0]['estadoSol'] != 6){?>
						<div class="col col-lg-12 text-center">
							<div class="form-group">
								<button class="btn btn-primary" type="submit" name="enviar">ENVIAR INFORMACI&Oacute;N</button>
							</div>
						</div>
					<?php }?>
			</div>
				</form>

			<?php if(count($transacciones) > 0){ ?>
				<div class="row">
					<div class="col col-lg-12"><br>	
						<h4><center>MENSAJES ENVIADOS ENTRE EL USUARIO Y EL PRESTADOR.</center></h4><br>
						<dl class="dl-horizontal">
						  <?php foreach($transacciones as $tr){ 
						  		$estadosTr 	= $funciones->deduceEstado($tr['idEstado']);

						  		$pedazos11			=  	explode(" ",$tr['fecha']);
								$pedazos21 			=   explode("-",$pedazos11[0]);
								$fechaTr 		=	$pedazos21[2]." de ".$funciones->TraducirMes($pedazos21[1])." de ".$pedazos21[0];
						  	?>
						  	<dt style="text-align: left">
						  		<?php echo $tr['nombres']." ".$tr['apellidos'] ?><br>
						  		<span class="small" style="font-weight: normal"><?php echo $fechaTr ?></span><br>
								<span class="badge"><?php echo $estadosTr ?></span>
						  	</dt>
						  	<dd>
						  	<?php echo $tr['textoTransaccion'] ?><br>
						  	</dd>
						  	<br>
						  <?php } ?>
						</dl>
					</div>
				</div>
			<?php } ?>

			<!--<blockquote>
			<p>
			Tipo de soliditud: <?php echo utf8_encode($resultado[0]['titulo']) ?></p>
			</blockquote>-->
	<?php }else{ ?>
			
			<div class="alert alert-info">
			  <strong>Atenci&oacute;n!</strong> La solicitud que intenta editar no est&aacute; asignada a su usuario.
			</div>

	<?php } ?>
</div>	
<?php }?>
<br><br><br>