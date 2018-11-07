<?php
	$conn = new mysqli("localhost","root","harshm@31","expense_tracker");

	if($conn->connect_error)
	{
		die('connection failed : '.$conn->connect_error);
	}
?>