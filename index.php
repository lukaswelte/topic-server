<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();
$app->config('debug', true);
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

$app->container->singleton('db', function () {
    return new PDO('mysql:host=localhost;dbname=logicreative_topic;charset=utf8', 'logic_topic', '8q51S!dg', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
});

$db = $app->db;

function CurlGet($sURL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_URL, $sURL);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt ($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

    $sResult = curl_exec($ch);
    if (curl_errno($ch)) 
    {
        // Fehlerausgabe
        print curl_error($ch);
    } else 
    {
        // Kein Fehler, Ergebnis zurückliefern:
        curl_close($ch);
        return $sResult;
    }    
}

function validateToken($token)
{
	// jSON String for request
	$url = 'https://graph.facebook.com/me?access_token='.$token;
	$response = CurlGet($url);

	// get the result and parse to JSON
	$result = json_decode($response);
	
	if ($result->id == null) {
		$app->render(401,array(
				'error' => true,
                'msg' => 'Unauthorized!',
            ));
	}
}

$app->get('/', function() use ($app) {
        $app->render(200,array(
                'msg' => 'The Topic API!',
            ));
    });    
    
$app->get('/user/auth', function() use ($app) {
		$token = $app->request->headers->get('TOKEN');
		validateToken($token);

        $app->render(200,array(
                'msg' => 'success!',
            ));
    });      

$app->get('/topic', function() use ($app, $db) {
	$token = $app->request->headers->get('TOKEN');
		validateToken($token);

		$stmt = $db->query('SELECT * FROM topic');
		if (!$stmt) {
			$app->render(500,array(
                'error' => true,
            ));
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$app->render(200,array(
            'msg' => $results,
        ));
        
    });    
    
$app->post('/topic', function() use ($app, $db) {
		$token = $app->request->headers->get('TOKEN');
		validateToken($token);
		
		$body = $app->request()->getBody();
		$post = json_decode($body);
		
		$name = $post->name;
		$startdate = $post->startdate;
		$enddate = $post->enddate;
		$category = $post->category;
		$creatorimage = $post->creatorimage;
		
		$stmt = $db->prepare("INSERT INTO topic(name,startdate,enddate,category,creatorimage) VALUES(:name,:startdate,:enddate,:category,:creatorimage)");
		$stmt->execute(array(':name' => $name, ':startdate' => $startdate, ':enddate' => $enddate, ':category' => $category, ':creatorimage' => $creatorimage));
		$id = $db->lastInsertId();
		

		$affected_rows = $stmt->rowCount();

		if ($affected_rows>0) {
			$app->render(200,array(
                'msg' => $id,
            ));
		}
    }); 
    
$app->put('/topic/:id', function ($id) use ($app, $db) {
		$token = $app->request->headers->get('TOKEN');
		validateToken($token);
		
		$body = $app->request()->getBody();
		$post = json_decode($body);
		
		$name = $post->name;
		$startdate = $post->startdate;
		$enddate = $post->enddate;
		$category = $post->category;
		$creatorimage = $post->creatorimage;
		
		$stmt = $db->prepare("UPDATE topic SET name=?, startdate=?, enddate=?, category=?, creatorimage=? WHERE id = ?");
		$stmt->execute(array($name, $startdate, $enddate, $category, $creatorimage, $id));

		$affected_rows = $stmt->rowCount();

		if ($affected_rows>0) {
			$app->render(200,array(
                'msg' => 'success',
            ));
		}
    	
});   

$app->get('/category', function() use ($app, $db) {
	$token = $app->request->headers->get('TOKEN');
		validateToken($token);

		$stmt = $db->query('SELECT * FROM category');
		if (!$stmt) {
			$app->render(500,array(
                'error' => true,
            ));
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$app->render(200,array(
            'msg' => $results,
        ));
        
    });             

$app->run();
?>