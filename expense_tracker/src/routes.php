<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

// $app->get('/[{name}]', function (Request $request, Response $response, array $args) {
//     // Sample log message
//     $this->logger->info("Slim-Skeleton '/' route");

//     // Render index view
//     return $this->renderer->render($response, 'index.phtml', $args);
// });

$app->post('/signUp',function(Request $request , Response $response)
{
	require_once("db.php");
	$input = $request->getParsedBody();

	$reg_no = $input["reg_no"];
	$email = $input["email"];
	$name = $input["name"];
	$password = $input["password"];
	$contact = $input["contact"];

	$query = $conn->prepare("INSERT INTO users (reg_no,email,name,password,contact) VALUES(?,?,?,?,?)");
	$query->bind_param("sssss",$reg_no,$email,$name,$password,$contact);

	if($query->execute())
	{
		$message = "Successfully Registered";
		$code = 1;
		$data = array("code"=>$code,"message"=>$message);
		return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
	}
	else
	{
		$message = "Error Registering User";
		$code = 2;
		$data = array("code"=>$code,"message"=>$message);
		return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
	}
});


//getting a particular user's budget for each month 
$app->get('/userDetails/{reg_no}',function(Request $request , Response $response, array $args)
{
	$reg_no = $args["reg_no"];
	require_once("db.php");

	$query = "select u.reg_no as reg , name,email,contact from users u where u.reg_no = '".$reg_no."';";
	$result = $conn->query($query);

	$row = $result->fetch_assoc();
	$name = $row["name"];
	$email = $row["email"];
	$reg_no = $row["reg"];
	$contact = $row["contact"];

	$budget_query = "select amount as budget , MONTH(month) as month from budget where reg_no = '".$reg_no."';";
	$result1 = $conn->query($budget_query);
	while($row1 = $result1->fetch_assoc())
	{
		$data[] = $row1;
	}

	$message = "user details with budget ";
	$code = 1;
	$output = array("code" => $code , "message"=>$message,"reg"=>$reg_no,"name"=>$name,"email"=>$email,"contact"=>$contact,"data"=>$data);

	return $response->withJson($output)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

});
