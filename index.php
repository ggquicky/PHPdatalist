<?php

require __DIR__.'/vendor/autoload.php';
require_once './model/Database.php';
use model\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use League\Container\Container;

$container = new Container();
$container->add('db', Database::class);
AppFactory::setContainer($container);
$app = AppFactory::create();
$twig = Twig::create('views', ['cache' => false]);


$app->add(TwigMiddleware::create($app, $twig)); // agrega metodos para poder usar las vistas HTML
$app->add(new MethodOverrideMiddleware());  // agrega sobrecarga de metodos para poder agregar POST + PATCH OR DELETE

$app->get('/', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);

    return $view->render($response, 'index.html', [ ]);
});

$app->post('/users', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    $params = (array) $request->getParsedBody();

    $firstName = $params['first_name'] ?? null;
    $lastName = $params['last_name'] ?? null;
    $hobbies = $params['hobbies']?? null;

    $errors = [];

    if (! $firstName) {
        $errors['first_name'] = 'First name is required.';
    }

    if (! $lastName) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (count($errors) > 0) {
        return $view->render($response, 'index.html', ['errors' => $errors]);

        //die;
    }
    //$db = new Database(); al agregar  $db = $this->get('db'); ya no es necesaria instanciar
    $db = $this->get('db');
    $db->save(
        [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'hobbies' => $hobbies,
        ]
    );

    $response = $response->withStatus(302);
    return $response->withHeader('Location', '/users');
    //header("Location: /users");
});
$app->get('/users', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    //$db = new Database(); al agregar  $db = $this->get('db'); ya no es necesaria instanciar
    $db = $this->get('db');
    $dataTemp = $db->all();

    return $view->render($response, 'users/show.html', ["datatemp" => $dataTemp] );
});

$app->get('/users/sort', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    //$db = new Database(); al agregar  $db = $this->get('db'); ya no es necesaria instanciar
    $db = $this->get('db');
    $sortData = $db->all();

    array_multisort($sortData,SORT_ASC,SORT_REGULAR);


    return $view->render($response, 'users/show.html', ["datatemp" => $sortData] );
});

$app->get('/users/{id}', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    //$db = new Database(); al agregar  $db = $this->get('db'); ya no es necesaria instanciar
    $id = $args['id'];
    $db = $this->get('db');
    $user = $db->find($id);
    $array =$request->getQueryParams();



    if (! $id || ! $user) {
        header('Location: /users');
        die;
    }

    if($array){
        $errors_Array = $array['errors'];
      return $view->render($response, 'users/index.html', [
            'user' => $user,
            'id' => $id,
            'errors_Array' => $errors_Array,


        ]);

    }


    return $view->render($response, 'users/index.html', [
        'user' => $user,
        'id' => $id,


    ]);


});

$app->patch('/users/{id}', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    $params = (array) $request->getParsedBody();
    $id = $args['id'];
    $firstName = $params['first_name'] ?? null;
    $lastName = $params['last_name'] ?? null;
    $hobbies = $params['hobbies']?? null;


   // $db = new Database();  al agregar  $db = $this->get('db'); ya no es necesaria instanciar
    $db = $this->get('db');
    $errors = [];

    if (! $firstName) {
        $errors['first_name'] = 'First name was empty.';
    }

    if (! $lastName) {
        $errors['last_name'] = 'Last name was empty.';
    }

    if (count($errors) > 0) {
        $url = "/users/$id?".http_build_query(['errors' => $errors]);
        $user = $db->find($id);

        //return $view->render($response, 'users/index.html', ['errors' => $errors ,  'user' => $user ,
        //    'id' => $id,]);

        $response = $response->withStatus(302);
        return $response->withHeader('Location',$url);
    }


    $db->edit(
        $id,
        [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'hobbies' => $hobbies,
        ]
    );

    $response = $response->withStatus(302);
    return $response->withHeader('Location','/users/{id}');
    //header("Location: ");
});
$app->delete('/users/{id}', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    //$db = new Database();
    $db = $this->get('db');
    $id = $args['id'];
    $user = $db->find($id);
    /*if (! $id || ! $user) {
        header('Location: /');
        die;
    }*/

    $db->delete($id);
    header("Location: /users");
});


$app->run();
