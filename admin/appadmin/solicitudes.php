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
extract($_GET);
$plantilla = 0;
if(isset($idSolicitud))
{
	$plantilla     = 2;
	$filtroPerfil  = " AND s.idSolicitud=".$idSolicitud;
	$permitoVision = false;
	//busco la solicitud que se haya seleccionado
	$query	=	sprintf("SELECT * FROM solicitudes s
						 INNER JOIN usuarios u ON s.idusuario=u.idusuario
						 INNER JOIN principal p on p.id=s.servicio
						 WHERE s.eliminado = 0 
						 %s
						 ORDER BY s.idSolicitud DESC",$filtroPerfil);
	//echo $query;
	$resultado = $db->GetAll($query);
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
						<td class="text-center">
							<a href="<?php echo _DOMINIO ?>admin/index.php?id=<?php echo $id ?>&idSolicitud=<?php echo $sol['idSolicitud'] ?>" class="btn btn-primary glyphicon glyphicon-info-sign" title="Ver detalles de la solicitud"></a>
						</td>
					</tr>
				<?php } ?>
					<tr>
						<td colspan="5" class="text-center" style="font-size: 1.5em">
							<?php echo $links; ?>
						</td>
					</tr>
			</tbody>
			
		</table>
	</div>
<?php }?>

<?php if($plantilla == 2){ ?>
<div class="container-fluid">
	<?php if($permitoVision){ ?>
			<h1><?php echo $resultado[0]['nombres'] ." ".$resultado[0]['apellidos'] ?></h1>
			<h4>Tipo de soliditud: <?php echo utf8_encode($resultado[0]['titulo']) ?></h4>
			<h5>Fecha: <?php echo date("Y-m-d",strtotime($resultado[0]['fechaFavor'])) ?> - Hora: <?php echo $resultado[0]['horaFavor'] ?></h5>
	<?php }else{ ?>
			
			<div class="alert alert-info">
			  <strong>Atenci&oacute;n!</strong> La solicitud que intenta editar no est&aacute; asignada a su usuario.
			</div>

	<?php } ?>
</div>	
<?php }?>