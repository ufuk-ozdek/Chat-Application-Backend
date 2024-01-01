<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // get users
    $app->get('/users', function (Request $request, Response $response) {
        $db = new Db();

        try {
            $db = $db->connect();
            $users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_OBJ);

            $response->getBody()->write(json_encode($users));
            return $response
                ->withStatus(200)
                ->withHeader("Content-Type", 'application/json');

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode(),
                )
            )));
            return $response->withStatus(500);
        } finally {
            $db = null;
        }
    });

    // add users
    $app->post('/user/add', function (Request $request, Response $response) {
        $db = new Db();
        $parsedBody = $request->getBody();
        $jsonData = json_decode($parsedBody, true);
        $username = $jsonData["username"];

        try {
            $db = $db->connect();
            $statement = "INSERT INTO users (username) VALUES(:username)";
            $prepare = $db->prepare($statement);
            $prepare->bindParam("username", $username);
            $user = $prepare->execute();

            if ($user) {
                $result = array("text" => "User added successfully");
            } else {
                $result = array(
                    "error" => array(
                        "text" => "Error while adding user",
                    )
                );
            }

        } catch (PDOException $e) {
            $result = array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode(),
                )
            );
        }

        // Create a new response with JSON content
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));

        return $response;
    });
};
