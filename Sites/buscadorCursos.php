<head>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>RENNAB</title>
	<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

</head>

<?php

// #################### LIBRERIAS ####################
require_once('functions.php');

// #################### VARIABLES ####################
$nombreRamo = $siglaCurso = $escuelaRamo = $nombreProfesor =$apellidoPProfesor = $apellidoMProfesor = "";

// #################### AHORA A HACER MAGIA ####################

//FUENTE: http://www.w3schools.com/php/php_form_validation.asp

$bienvenidaBuscadorCursos = "
	<h2>Buscador de Cursos</h2>
	<form method='post' action='buscadorCursos.php'>
		Año: <input type='number' name='anoCurso' value=2015>
		<br><br>
		Semestre: <input type='number' name='semestreCurso' value=2>
		<br><br>
		Nombre del Ramo: <input type='text' name='nombreRamo'>
		<br><br>
		Sigla: <input type='text' name='siglaCurso'>
		<br><br>
		Escuela: <input type='text' name='escuelaRamo'>
		<br><br>
		Nombre del Profesor: <input type='text' name='nombreProfesor'>
		<br><br>
		Apellido Paterno del Profesor: <input type='text' name='apellidoPProfesor'>
		<br><br>
		Apellido Materno del Profesor: <input type='text' name='apellidoMProfesor'>
		<br><br>
		<input type='submit' name='submit' value='Buscar'> 
	</form>
";

echo $bienvenidaBuscadorCursos;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
   $nombreRamo = $_POST["nombreRamo"];
   $siglaCurso = $_POST["siglaCurso"];
   $escuelaRamo = $_POST["escuelaRamo"];
   $nombreProfesor = $_POST["nombreProfesor"];
   $apellidoPProfesor = $_POST["apellidoPProfesor"];
   $apellidoMProfesor = $_POST["apellidoMProfesor"];
   $semestreCurso = $_POST["semestreCurso"];
   $anoCurso = $_POST["anoCurso"];

	$ultimaBusqueda = "
		<h2>Tu última búsqueda:</h2>
		Año: $anoCurso
		Semestre: $semestreCurso
		Nombre ramo: $nombreRamo
		Sigla curso: $siglaCurso
		Escuela ramo: $escuelaRamo
		Nombre Profesor: $nombreProfesor
		Apellido Paterno profesor: $apellidoPProfesor
		Apellido Materno profesor: $apellidoMProfesor
	";
	
	imprimirLineasConTabsAPartirDe($ultimaBusqueda, 1);
	
	// Nuestras Consultas
	$queryBuscadorCursos = "SELECT curso.nrc, ramo.nombre, curso.sigla, curso.seccion, curso.semestre, curso.ano, ramo.escuela, ramo.ncreditos, curso.cupos
							FROM curso, ramo
							WHERE ramo.sigla = curso.sigla
							AND ramo.nombre LIKE CONCAT('{$nombreRamo}', '%')
							AND curso.sigla LIKE CONCAT('{$siglaCurso}', '%')
							AND curso.semestre = {$semestreCurso}
							AND curso.ano = {$anoCurso}
							AND ramo.escuela LIKE CONCAT('{$escuelaRamo}', '%')
							AND (curso.sigla, curso.nrc) IN (SELECT curso.sigla, curso.nrc
															FROM curso, profesorcurso, usuario
															WHERE curso.nrc = profesorcurso.nrc
															AND profesorcurso.username = usuario.username
															AND (usuario.nombres LIKE CONCAT('%', '{$nombreProfesor}', '%'))
																AND usuario.apellidop LIKE CONCAT('%', '{$apellidoPProfesor}', '%')
																AND usuario.apellidom LIKE CONCAT('%', '{$apellidoMProfesor}', '%')
															)
							ORDER BY curso.sigla, ramo.nombre;";
		
	// ##### Hacemos las consultas #####
	$infoBuscadorCursosRowArray = $dbp->query($queryBuscadorCursos)->fetchAll();
	
	// ##### Tabla información curso #####
	$columnas = array (
		"NRC",
		"Curso",
		"Sigla",
		"Sección",
		"Semestre",
		"Año",
		"Escuela",
		"Créditos",
		"Cupos"
		);
	imprimirTabla($columnas, $infoBuscadorCursosRowArray);
}

?>