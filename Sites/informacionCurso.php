<head>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>RENNAB</title>
	<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

</head>

<?php

// #################### LIBRERIAS ####################
require_once('functions.php');

// #################### VARIABLES ####################
$esProfesorCurso = false;
$nrcCurso = intval($_POST['nrcCurso']);

// #################### AHORA A HACER MAGIA ####################
$queryInfoCurso = "SELECT curso.nrc, curso.sigla, curso.seccion, ramo.nombre, curso.semestre, curso.ano, ramo.escuela,
						ramo.ncreditos, curso.cupos, curso.programa
					FROM curso, ramo
					WHERE ramo.sigla = curso.sigla
					AND curso.nrc = $nrcCurso;";

$queryProfesoresCurso = "SELECT nombres, apellidop, apellidom, mailuc, departamento, facultad
					FROM usuario, profesor, profesorcurso
					WHERE usuario.username = profesor.username
					AND profesor.username = profesorcurso.username
					AND profesorcurso.nrc = $nrcCurso;";

$informacionCursoRowArray = $dbp->query($queryInfoCurso)->fetchAll();
$profesoresCursoRowArray = $dbp->query($queryProfesoresCurso)->fetchAll();

echo "<p>Informacion curso:</p>";

// ##### Mostrar info curso #####
$columnas = array(
	"NRC",
	"Sigla",
	"Seccion",
	"Nombre",
	"Semestre",
	"Año",
	"Escuela",
	"Creditos",
	"Cupos",
	"Programa"
	);
imprimirTabla($columnas, $informacionCursoRowArray, 9, "PROGRAMURL");

echo "<p>Profesores curso:</p>";

// ##### Mostrat info profesores curso #####
$columnas = array(
	"Nombre",
	"Apellido Paterno",
	"Apellido Materno",
	"Mail UC",
	"Departamento",
	"Facultad"
	);
imprimirTabla($columnas, $profesoresCursoRowArray);

if ($esAlumno)
{
	$inscribirCurso = isset($_POST['inscribirCurso']) ? $_POST['inscribirCurso'] : 0;

	if ($inscribirCurso == 1)
	{

		$usernameAlumno = $username;

		$equivalentesintercambio = "";

		if ($esAlumnoIntercambio) {
			$alumnos = $dbm->alumnos;
			$cursos = $dbm->cursos;
			$mongoid = new MongoId($usernameAlumno);
			$idQuery = array("_id" => $mongoid);
			$alumnosMatch = $alumnos->find($idQuery);
			$alumnosMatch->next();
			$alumno = $alumnosMatch->current();
			
			$cursosAlumno = $cursos->find(array('_id' => array('$in' => $alumno["cursos"])));
		
			foreach (iterator_to_array($cursosAlumno) as $curso)
			{
				$equivalencia = $curso["equivalencia"];
				if ($equivalentesintercambio == "") {
					$equivalentesintercambio = "'" . $equivalencia . "'";
				} else {
					$equivalentesintercambio .= ", " . "'" . $equivalencia . "'";
				}
			}
		}

		$queryCumpleRequisitos = "SELECT alumno.username, curso.nrc
								FROM alumno, curso
								WHERE alumno.username = '$usernameAlumno'
								AND curso.nrc = $nrcCurso
								AND (SELECT *
									FROM AlumnoCumpleRequisitos(alumno.username, curso.sigla, ARRAY[$equivalentesintercambio]::text[])) = true
								AND (SELECT *
									FROM CuposRestantes(curso.nrc)) > 0";
		$queryInscribirRamo = "INSERT INTO nota(username, nrc)
							(
								$queryCumpleRequisitos
							);";

		$dbp->query($queryInscribirRamo);

		$queryAlumnoEnRamo = "SELECT COUNT(*)
							FROM nota
							WHERE username = '$usernameAlumno'
							AND nrc = $nrcCurso";

		$alumnoEnCurso = $dbp->query($queryAlumnoEnRamo)->fetchAll();
		if ($alumnoEnCurso[0][0] > 0)
		{
			echo "Curso inscrito correctamente";
		}
		else
		{
			echo "Curso no fue inscrito, no se cumplen requisitos o no hay suficientes cupos";
		}

	}

	$columnas = array("Opciones de alumno");
	imprimirTabla($columnas, array(array(
		"<form action='informacionCurso.php' method='post'>" .
			"<input class='hidden' name='nrcCurso' value=$nrcCurso>" .
			"<input class='hidden' name='inscribirCurso' value=1>" .
			"<input type='submit' name='submit' value='Inscribir curso'>" .
		"</form>"
	)));
}

// ##### Veamos si es profesor del curso (para poder cambiar notas) #####
if ($esProfesor)
{
	// ##### Declaramos consulta para ver si es profesor del ramo #####
	$queryProfesoresCurso = "SELECT username
							FROM profesorcurso
							WHERE nrc = $nrcCurso;";

	// ##### Ejecutamos la consulta #####
	$profesoresCursoRowArray = $dbp->query($queryProfesoresCurso)->fetchAll();
	
	$profesoresCurso = [];
		
	foreach ($profesoresCursoRowArray as $profesorCurso)
	{
		array_push($profesoresCurso, $profesorCurso[0]);
	}

	// ##### Vemos si es profe del curso #####
	if (in_array($username, $profesoresCurso))
	{
		$esProfesorCurso = true;
	}
}

if ($esProfesorCurso)
{
	// ##### Primero veamos si hay notas que actualizar #####
	$actualizarNotas = isset($_POST['actualizarNotas']) ? $_POST['actualizarNotas'] : 0;

	if ($actualizarNotas == 1)
	{
		$cantidadAlumnos = $_POST["cantidadAlumnos"];
		for ($i=0; $i < $cantidadAlumnos; $i++)
		{
			$identificadorNota = "nota" . $i;
			$indentificadorAlumno = "alumno" . $i;
			$usernameAlumno = $_POST[$indentificadorAlumno];
			$notaAlumno = $_POST[$identificadorNota] != "" ? $_POST[$identificadorNota] : -1;
			$notaAlumno = round($notaAlumno, 1);
			if ($notaAlumno != -1)
			{
				$queryActualizarNota = "UPDATE nota
										SET notafinal = $notaAlumno
										WHERE username = '$usernameAlumno';";
				
				$dbp->query($queryActualizarNota);
			}
		}
	}

	// ##### Declaramos consulta para ver alumnos del curso #####
	$queryAlumnosCurso = "SELECT usuario.username, usuario.nombres, usuario.apellidop, usuario.apellidom, alumno.mailuc, nota.notafinal
						FROM usuario, alumno, nota
						WHERE usuario.username = alumno.username
						AND nota.username = alumno.username
						AND nota.nrc = {$nrcCurso};";

	// ##### Ejecutamos la consulta #####
	$alumnosCursoRowArray = $dbp->query($queryAlumnosCurso)->fetchAll();

	$cantidadAlumnos = count($alumnosCursoRowArray);
	echo "<form action='informacionCurso.php' method='post'>";
	echo "<input class=hidden type=number name='actualizarNotas' value=1>";
	echo "<input class=hidden type=number name='cantidadAlumnos' value=$cantidadAlumnos>";
	echo "<input class=hidden name='nrcCurso' value=$nrcCurso>";

	// ##### Mostrar info alumnos curso #####
	$columnas = array(
		"Alumno",
		"Nombres",
		"Apellido Paterno",
		"Apellido Materno",
		"Mail UC",
		"Nota final",
		"Nueva nota"
	);

	$alumnosCursoRowArrayConFormNota = [];
	for ($i=0; $i < $cantidadAlumnos; $i++)
	{
		$alumnoRow = $alumnosCursoRowArray[$i];
		$identificadorNota = "nota" . $i;
		$indentificadorAlumno = "alumno" . $i;
		$modificacionNota = array(
			"<input type='number' name=$identificadorNota step='0.1' min='1.0' max='7.0'>" .
			"<input type='text' class='hidden' name=$indentificadorAlumno value=$alumnoRow[0]>"
		);
		$nuevaRow = array_merge($alumnoRow, $modificacionNota);
		array_push($alumnosCursoRowArrayConFormNota, $nuevaRow);
	}

	imprimirTabla($columnas, $alumnosCursoRowArrayConFormNota);

	$columnas = array("Opciones de profesor");
	imprimirTabla($columnas, array(array("<input type='submit' name='submit' value='Actualizar notas'>")));

	echo "</form>";
}

if ($esAdmin)
{
	// ##### Declaramos consulta para ver alumnos del curso #####
	$queryAlumnosCurso = "SELECT usuario.username, usuario.nombres, usuario.apellidop, usuario.apellidom, alumno.mailuc, nota.notafinal
						FROM usuario, alumno, nota
						WHERE usuario.username = alumno.username
						AND nota.username = alumno.username
						AND nota.nrc = {$nrcCurso};";

	// ##### Ejecutamos la consulta #####
	$alumnosCursoRowArray = $dbp->query($queryAlumnosCurso)->fetchAll();

	// ##### Mostrar info alumnos curso #####
	$columnas = array(
		"Usuario",
		"Nombres",
		"Apellido Paterno",
		"Apellido Materno",
		"Mail UC",
		"Nota final"
		);
	imprimirTabla($columnas, $alumnosCursoRowArray);

	$columnas = array("Opciones de administrador");
	imprimirTabla($columnas, array(array(
		"<form action='agregarProfesorACurso.php' method='post'>" .
			"<input class='hidden' name='nrcCurso' value=$nrcCurso>" .
			"<input type='submit' name='submit' value='Agregar profesores al curso'>" .
		"</form>"
	)));
}

?>