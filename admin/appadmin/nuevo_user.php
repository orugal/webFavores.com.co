<?php
global $db;
global $funciones;
//capturo el id del usuario enviado
$user	=	(isset($_GET['user']))?$_GET['user']:0;
//traigo un listado de los minisites creados
$query_sites	=	$db->GetAll(sprintf("SELECT * FROM principal WHERE tipo_contenido=42 AND visible=1 AND eliminado=0"));

//realizo el query con la informacion del usuario
if(isset($user))
{
	$query_info	=	$db->Execute(sprintf("SELECT * FROM usuarios WHERE idusuario=%s",$user));
	extract($query_info->fields);
}


if(isset($_GET['edit']))
{
	if(isset($_POST['guardar']))
	{
		extract($_POST);
		//valido solo los tipo de datos
		if(empty($nombres))
		{
			echo "<script>alert('Debe escribir el nombre del usuario');</script>";
		}
		elseif(empty($apellidos))
		{
			echo "<script>alert('Debe escribir los apellidos del usuario');</script>";
		}
		elseif(empty($email))
		{
			echo "<script>alert('Debe escribir el correo electrónico');</script>";
		}
		elseif(!empty($email) and !ereg('([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+',$email))
		{
			echo "<script>alert('La sintanxis del correo principal es incorrecta');</script>";
		}
		elseif(!empty($altemail) and !ereg('([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+',$altemail))
		{
			echo "<script>alert('La sintanxis del correo alternativo es incorrecta');</script>";
		}
		elseif(!empty($telefono) and !is_numeric($telefono))
		{
			echo "<script>alert('El campo telefono solo debe contener Numeros');</script>";
		}
		elseif(!empty($telefonomovil) and !is_numeric($telefonomovil))
		{
			echo "<script>alert('El campo celular solo debe contener Numeros');</script>";
		}
		else
		{
			
			$resultado	=	$funciones->insertarDatos('usuarios','2','idusuario='.$user,$_POST);
			if($resultado)
			{
				echo "<script>alert('Datos guardados con exito');document.location='"._DOMINIO."admin/index.php?id=1325'</script>";
			}
		}
	}
}
elseif(isset($_GET['new']))
{
	if(isset($_POST['guardar']))
	{
		extract($_POST);

		if(empty($nombres))
		{
			echo "<script>alert('Debe escribir el nombre del usuario');</script>";
		}
		elseif(empty($apellidos))
		{
			echo "<script>alert('Debe escribir los apellidos del usuario');</script>";
		}
		elseif(empty($email))
		{
			echo "<script>alert('Debe escribir el correo electrónico');</script>";
		}
		elseif(!empty($email) and !ereg('([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+',$email))
		{
			echo "<script>alert('La sintanxis del correo principal es incorrecta');</script>";
		}
		elseif(!empty($altemail) and !ereg('([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+',$altemail))
		{
			echo "<script>alert('La sintanxis del correo alternativo es incorrecta');</script>";
		}
		elseif(!empty($telefono) and !is_numeric($telefono))
		{
			echo "<script>alert('El campo telefono solo debe contener Numeros');</script>";
		}
		elseif(!empty($telefonomovil) and !is_numeric($telefonomovil))
		{
			echo "<script>alert('El campo celular solo debe contener Numeros');</script>";
		}
		else
		{
			
			$resultado	=	$funciones->insertarDatos('usuarios','1','',$_POST);
			if($resultado)
			{
				echo "<script>alert('".strip_tags($resultado)."');document.location='"._DOMINIO."admin/index.php?id=1325'</script>";
			}
		}
	}
}
?>

<div id="container-fluid">
	<form method="post">
		<table width="100%" cellspacing="5" cellpadding="0">
			<div class="form-group">
				Por favor complete el formulario para realizar la creación de un nuevo usuario en nuestra base de datos.<br>
				<span style="color:#a63b5f">Recuerde que todos los campos son obligatorios</span><br><br>
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Nombres</label>
				<input type="text" class="form-control" name="nombres" value="<?=$nombres ?>">
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Apellidos</label>
				<input type="text" class="form-control" name="apellidos" value="<?=$apellidos ?>">
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Usuario</label>
				<input type="text" class="form-control" name="username" <?if(!isset($_GET['new'])){?>readonly<?} ?> value="<?=$username ?>">
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Genero</label>
				<select name="genero"  class="form-control">
					<option value="M" <?if($genero=='M'){echo 'selected';} ?>>Masculino</option>
					<option value="F" <?if($genero=='F'){echo 'selected';} ?>>Femenino</option>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Perfil</label>
				<select name="perfil"  class="form-control">
					<option value="3" <?if($perfil=='3'){echo 'selected';} ?>>Admin Solicitudes</option>
					<option value="1" <?if($perfil=='1'){echo 'selected';} ?>>S&uacute;per administrador</option>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="nombreLista">Correo electrr&oacute;nico</label>
				<input type="text" class="form-control" name="email" value="<?=$email ?>">
			</div>
			<div class="form-group">
				<input type="submit" name="guardar" value="Crear Usuario" class="btn btn-primary">
				<input type="button" name="cerrar" value="Cerrar" onClick="history.back()" class="btn btn-default">
			</div>
	</form>
	</div>