<head>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>RENNAB</title>
	<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

</head>

<?php
	try {
		$db = new PDO("pgsql:dbname=grupo5;host=localhost;port=5432;user=grupo5;password=gruponico"); 
		}
	catch(PDOException $e) {
		echo $e->getMessage();
		}
	
	echo "<h1>Resultado Consulta 5:</h1>";
	
	$alumno=$_POST['alumno'];
	
	$query="SELECT usuario.nombres, usuario.apellidoP, usuario.apellidoM
	FROM profesorcurso, curso, usuario, nota
	WHERE curso.nrc = profesorcurso.nrc
	AND nota.nrc = curso.nrc
	AND usuario.username = profesorcurso.username
	AND nota.username = '$alumno'";
	
	echo '<table border="1" class="table">';
	echo '<tr>';
	echo "<th>Nombres</th>";
	echo "<th>Apellido Paterno</th>";
	echo "<th>Apellido Materno</th>";
	echo "</tr>";
	
	foreach($db->query($query) as $row)
	{
		echo "<tr>";
		echo "<td>" . $row[0] . "</td>";
		echo "<td>" . $row[1] . "</td>";
		echo "<td>" . $row[2] . "</td>";
		echo "</tr>";
	}
	
	echo "</table>";

?>