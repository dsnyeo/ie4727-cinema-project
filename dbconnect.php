<?php
@$dbcnx = new mysqli('localhost','root','','cinema');
if ($dbcnx->connect_error){
	echo "Database is not online"; 
	exit;
	}
if (!$dbcnx->select_db ("cinema"))
	exit("<p>Unable to locate the cinema database</p>");
?>	