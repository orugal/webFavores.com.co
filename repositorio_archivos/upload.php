<?
//ini_set("display_errors",1);
$status = "";
if ($_POST["action"] == "upload") {
    // obtenemos los datos del archivo 
    $tamano 	= $_FILES["archivo"]['size'];// tama�o
    $tipo 		= $_FILES["archivo"]['type'];//tipo
    $archivo	= $_FILES["archivo"]['name'];//nombre
	
    $prefijo	=	str_replace(' ','_',$archivo);
	$prefijo	=	str_replace('�','a',$prefijo);
	$prefijo	=	str_replace('�','e',$prefijo);
	$prefijo	=	str_replace('�','i',$prefijo);
	$prefijo	=	str_replace('�','o',$prefijo);
	$prefijo	=	str_replace('�','u',$prefijo);
	$prefijo	=	str_replace('�','a',$prefijo);
	$prefijo	=	str_replace('�','e',$prefijo);
	$prefijo	=	str_replace('�','i',$prefijo);
	$prefijo	=	str_replace('�','o',$prefijo);
	$prefijo	=	str_replace('�','u',$prefijo);
	$prefijo	=	str_replace('�','n',$prefijo);
	$prefijo	=	str_replace('�','n',$prefijo);

	if ($archivo != "")
	{
       
		$destino =  "../archivos/".$prefijo; 


			if(copy($_FILES['archivo']['tmp_name'],$destino))
			{ 
				echo "<table align='center'><tr><td><div style='color:#264F77'>El archivo <b>".$prefijo." </b>ha sido cargada con exito<br><br><a href='carga.php'>Volver</a></div></td></tr></table>";
		   	
	        }
	        else
	        {
	           
				echo "<table align='center'><tr><td><div style='color:#264F77'><img src='../diseno/cancel.png'> error al subir el archivo <b>".$prefijo.". </b></div></td></tr></table>";
	        }
	  
    }
    else
    {
        echo "<table align='center'><tr><td><div style='color:#264F77'><img src='../diseno/cancel.png'> Debe seleccionar un archivo!!<br><br><a href='carga.php'>Volver</a></div></td></tr></table>";
    }
} 
?>

