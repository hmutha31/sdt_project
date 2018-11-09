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
	$def_bal = 5000;
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
		$query2 = $conn->prepare("INSERT INTO balance (reg_no , balance_amt) VALUES(?,?)");
		$query2->bind_param("si",$reg_no,$def_bal);
		$query2->execute();

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
	if(sizeof($row)==0)
	{
		$message = "no such user found ";
		$code = 2;
		$data = array("code"=>$code,"message"=>$message);

		return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

	}

	//else if user exists
	$name = $row["name"];
	$email = $row["email"];
	$reg_no = $row["reg"];
	$contact = $row["contact"];

	$budget_query = "select amount as budget , month from budget where reg_no = '".$reg_no."';";
	$result1 = $conn->query($budget_query);
	while($row1 = $result1->fetch_assoc())
	{
		$data[] = $row1;
	}

	if(sizeof($data)==0)
	{
		$data = "no monthly budget set yet ";
	}

	$message = "user details with budget ";
	$code = 1;
	$output = array("code" => $code , "message"=>$message,"reg"=>$reg_no,"name"=>$name,"email"=>$email,"contact"=>$contact,"data"=>$data);

	return $response->withJson($output)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

});

$app->post('/transferMoney',function(Request $request , Response $response , array $args)
{
	require_once("db.php");

	$input = $request->getParsedBody();
	$reg_no = $input["reg_no"];
	$reg_no2 = $input["receiver_reg_no"];
	$amount = $input["amount"];
	$desc = $input["description"];

	//checking with users balance amount
	$query = "select balance_amt as balance from balance where reg_no = '".$reg_no."';";
	echo($query);
	$result = $conn->query($query);
	$row = $result->fetch_assoc();
	$balance = $row["balance"];

	$queryx = "select balance_amt as balance from balance where reg_no = '".$reg_no2."';";
	$resultx = $conn->query($queryx);
	$rowx = $resultx->fetch_assoc();
	$balancex = $rowx["balance"];

	if($amount<=$balance)
	{
		$query2 = "select reg_no from users where reg_no = '".$reg_no2."';";
		$result2 = $conn->query($query2);
		$row = $result2->fetch_assoc();

		//receiver exists or not ?
		if(sizeof($row)!=0)
		{
			

			$insert_query = $conn->prepare("INSERT INTO transactions (reg_no,receiver_reg_no,amount,description) VALUES (?,?,?,?)");
			$insert_query->bind_param("ssis",$reg_no,$reg_no2,$amount,$desc);
			if($insert_query->execute())
			{
				$balance_amt = $balance - $amount;
				$query3 = "update balance set balance_amt = ".$balance_amt." where reg_no = '".$reg_no."';";
				$balance_amtx = $balancex + $amount;
				$query4 = "update balance set balance_amt = ".$balance_amtx." where reg_no = '".$reg_no2."';";

				$stmnt1 = $conn->prepare($query3);
				$stmnt2 = $conn->prepare($query4);
				$stmnt1->execute();
				$stmnt2->execute();

				$message = "Transfer Successfull";
				$code = 1;
			}
		}
		else
		{
			$message = "Receiver doesnt exist or hasnt signed up with us";
			$code = 2;
		}
	}
	else
	{
		$message = "Unable to transfer funds as balance is low";
		$code = 3;
	}

	$data = array("message"=>$message,"code"=>$code);

	return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

});

//login route
$app->post('/login',function(Request $request , Response $response)
{
	require_once("db.php");
	$input = $request->getParsedBody();

	$reg_no = $input["reg_no"];
	$password = $input["password"];

	$query = "select reg_no , password as pwd from users where reg_no = '".$reg_no."';";
	$result = $conn->query($query);
	$row = $result->fetch_assoc();
	if(sizeof($row)==0)
	{
		$message = "No such user found ";
		$code = 2;
	}
	else
	{
		$pass = $row["pwd"];
		if($pass == $password)
		{
			$message = "Login successfull";
			$code = 1;
		}
		else
		{
			$message = "incorrect password , please try again ";
			$code = 3;
		}
	}

	$data = array("code"=>$code,"message"=>$message);

	return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


$app->post('/expenditure/{reg_no}',function(Request $request , Response $response , array $args)
{
	require_once("db.php");
	$reg_no = $args["reg_no"];
	$input = $request->getParsedBody();

	$amount = $input["amount"];
	$date = $input["date"];
	echo ($date);
	$date_field = date('Y-m-d',strtotime($date));
	//echo ($date_field);
	$month = date('m',strtotime($date));
	//echo ($month);
	$category = $input["category"];
	$description = $input["description"];

	//get budget for that month 
	$budget_query = "select amount as budget from budget where reg_no = '".$reg_no."' and month = ".$month.";";
	//echo($budget_query);
	$result1 = $conn->query($budget_query);
	$row = $result1->fetch_assoc();
	$budget = $row["budget"];
	//echo($budget);
	// $insert_query = $conn->prepare("INSERT INTO expenditure(reg_no,amount,tx_date,type,description) VALUES(?,?,?,?,?)");
	// $insert_query->bind_param("sisss",$reg_no,$amount,$date_field,$category,$description);
	// $insert_query->execute();

	$insert_query = "INSERT INTO expenditure (reg_no,amount,tx_date,type,description) VALUES ('".$reg_no."',".$amount.",'".$date."','".$category."',".$description."');";
	$conn->query($insert_query);

	//current amount spent for that month 
	$current_query = "select current_amount_spent as amt from monthly_expenditure where reg_no = '".$reg_no."' and month = ".$month.";";
	$result = $conn->query($current_query);
	$row = $result->fetch_assoc();

	if(sizeof($row)==0)
	{
		$query2 = "INSERT INTO monthly_expenditure (reg_no,month,current_amount_spent) VALUES('".$reg_no."',".$month.",".$amount.");";
		$conn->query($query2);

		//current expenditure doesnt exist 
		if($amount < $budget)
		{
			
			$message = "Successfully added expenditure";
			$code = 1;
		}
		else
		{
			$message = "Successfully added expenditure but amount overshoots budget";
			$code = 2;
		}

		$data = array("code" => $code , "message"=>$message);
		return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

	}
	else
	{
		$current_amt = $row["amt"]+$amount;
		echo($current_amt);
		$query5 = "UPDATE monthly_expenditure SET current_amount_spent = ".$current_amt." where reg_no = '".$reg_no."' and month = ".$month.";";
		$conn->query($query5);

		if($current_amt > $budget)
		{
			$message = "Successfully added expenditure but amount overshoots budget";
			$code = 2;
		}
		else
		{
			$message = "Successfully added expenditure";
			$code = 1;
		}

		$data = array("code" => $code , "message"=>$message);
		return $response->withJson($data)
						->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

	}

});