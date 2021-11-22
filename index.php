<?php


use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => __DIR__ . '/models/',
    ]
);
$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () {
        return new PdoMysql(
            [
                'host'     => '127.0.0.1',
                'username' => 'root',
                'password' => '',
                'dbname'   => 'bank',
            ]
        );
    }
);

$app = new Micro($container);
$app->get(
'/bank/api/accounts',
    function () use ($app) {
        $phql = 'SELECT id, name, balance '
            . 'FROM MyApp\Models\Accounts '
            . 'ORDER BY balance'
        ;

        $accounts = $app
            ->modelsManager
            ->executeQuery($phql)
        ;

        $data = [];

        foreach ($accounts as $account) {
            $data[] = [
                'id'   => $account->id,
                'name' => $account->name,
                'balance' => $account->balance,
            ];
        }

        echo json_encode($data);
    }
);

$app->get(
'/bank/api/accounts/search/{name}',
    function ($name) use ($app) {
        $phql = 'SELECT * '
            . 'FROM MyApp\Models\Accounts '
            . 'WHERE name '
            . 'LIKE :name: '
            . 'ORDER BY balance'
        ;

        $accounts = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'name' => '%' . $name . '%',
                ]
            )
        ;

        $data = [];

        foreach ($accounts as $account) {
            $data[] = [
                'id'   => $account->id,
                'name' => $account->name,
                'balance' => $account->balance,
            ];
        }

        echo json_encode($data);
    }
);

$app->get(
'/bank/api/accounts/{id:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'SELECT * '
            . 'FROM MyApp\Models\Accounts '
            . 'WHERE id = :id:'
        ;

        $account = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id' => $id,
                ]
            )
            ->getFirst()
        ;

        $response = new Response();
        if ($account === false) {
            $response->setJsonContent(
                [
                    'status' => 'NOT-FOUND'
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    'status' => 'FOUND',
                    'data'   => [
                        'id'   => $account->id,
                        'name' => $account->name,
                        'balance' => $account->balance,
                    ]
                ]
            );
        }

        return $response;
    }
);

$app->post(
'/bank/api/accounts',
    function () use ($app) {
        $account = $app->request->getJsonRawBody();
        $phql  = 'INSERT INTO MyApp\Models\Accounts '
            . '(name, type, balance) '
            . 'VALUES '
            . '(:name:, :type:, :balance:)'
        ;

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $account->balance,
                ]
            )
        ;

        $response = new Response();

        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');

            $account->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data'   => $account,
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->put(
'/bank/api/accounts/{id:[0-9]+}',
    function ($id) use ($app) {
        $account = $app->request->getJsonRawBody();
        $phql  = 'UPDATE MyApp\Models\Accounts '
            . 'SET name = :name:, type = :type:, balance = :balance: '
            . 'WHERE id = :id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id'   => $id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $account->balance,
                ]
            )
        ;

        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->delete(
'/bank/api/accounts/{id:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'DELETE '
            . 'FROM MyApp\Models\Accounts '
            . 'WHERE id = :id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id' => $id,
                ]
            )
        ;

        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle(
$_SERVER["REQUEST_URI"]
);
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo 'This is crazy, but this page was not found!';
});
