<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

//get groups
return function (App $app) {
    $app->get('/groups', function (Request $request, Response $response) {
        $db = new Db();

        try {
            $db = $db->connect();
            $groups = $db->query("SELECT * FROM groups")->fetchALL(PDO::FETCH_OBJ);

            $response->getBody()->write((string)json_encode($groups));
            return $response
                ->withStatus(200)
                ->withHeader("Content-Type", 'application/json');


        } catch (PDOException $e) {
            return $response->getBody()->write((string)json_encode(array(
                    "error" => array(
                        "text" => $e->getMessage(),
                        "code" => $e->getCode()
                    )
                )
            ));
        } finally {
            $db = null;
        }

    });

    // create group
    $app->post('/group/add', function (Request $request, Response $response) {
        $db = new Db();
        $parsedBody = $request->getBody();
        $jsonData = json_decode($parsedBody, true);
        $groupName = $jsonData["group_name"];
        $userID = $jsonData["created_by_user_id"];
        if (empty($groupName) || empty($userID)) {
            return $response->getBody()->write(json_encode(array("error" => "Invalid input data"), 400));
        }

        try {
            $db = $db->connect();
            $statement = "INSERT INTO groups (group_name, created_by_user_id) VALUES(:group_name, :created_by_user_id)";
            $prepare = $db->prepare($statement);
            $prepare->bindParam("group_name", $groupName);
            $prepare->bindParam("created_by_user_id", $userID);
            $group = $prepare->execute();

            // Get the ID of the newly created group
            $groupID = $db->lastInsertId();

            // Insert the user into the group_members table
            $groupMembersStatement = "INSERT INTO group_members (user_id, group_id) VALUES(:user_id, :group_id)";
            $groupMembersPrepare = $db->prepare($groupMembersStatement);
            $groupMembersPrepare->bindParam("user_id", $userID);
            $groupMembersPrepare->bindParam("group_id", $groupID);
            $groupMembersPrepare->execute();


            if ($group) {

                $result = array("text" => "Group added successfully");
            } else {
                $result = array(
                    "error" => array(
                        "text" => "Error while adding group",
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

    // show group
    $app->get('/group/{id}', function (Request $request, Response $response) {
        $id = $request->getAttribute("id");
        $db = new Db();
        try {
            $db = $db->connect();
            $group = $db->query("SELECT * FROM groups WHERE group_id = $id")->fetch(PDO::FETCH_OBJ);

            $response->getBody()->write((string)json_encode($group));
            return $response
                ->withStatus(200)
                ->withHeader("Content-Type", 'application/json');


        } catch (PDOException $e) {
            return $response->getBody()->write((string)json_encode(array(
                    "error" => array(
                        "text" => $e->getMessage(),
                        "code" => $e->getCode()
                    )
                )
            ));
        } finally {
            $db = null;
        }

    });

    //show members of the group
    $app->get('/group/members/{group_id}', function (Request $request, Response $response) {
        $groupID = $request->getAttribute("group_id");
        $db = new Db();

        try {
            $db = $db->connect();

            $statement = "SELECT u.user_id, u.username FROM users u
                          JOIN group_members gm ON u.user_id = gm.user_id
                          WHERE gm.group_id = :group_id";

            $prepare = $db->prepare($statement);
            $prepare->bindParam("group_id", $groupID);
            $prepare->execute();

            $members = $prepare->fetchAll(PDO::FETCH_ASSOC);

            $result = array("members" => $members);
        } catch (PDOException $e) {
            $result = array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode(),
                )
            );
        } finally {
            $db = null;
        }

        // Create a new response with JSON content
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));

        return $response;
    });

    // enroll a group
    $app->post('/group/enroll/{group_id}', function (Request $request, Response $response) {
        $db = new Db();
        $groupID = $request->getAttribute("group_id");
        $parsedBody = $request->getBody();
        $jsonData = json_decode($parsedBody, true);
        $userID = $jsonData["user_id"];

        try {
            $db = $db->connect();
            $group = $db->query("SELECT * FROM groups WHERE group_id = $groupID")->fetch(PDO::FETCH_OBJ);

            if (!$group) {
                $response->getBody()->write((string)json_encode(array("error" => "Group not found")));
                return $response
                    ->withStatus(404)
                    ->withHeader("Content-Type", 'application/json');
            }

            // Insert the user into the group_members table
            $statement = "INSERT INTO group_members (user_id, group_id) VALUES(:user_id, :group_id)";
            $prepare = $db->prepare($statement);
            $prepare->bindParam("user_id", $userID);
            $prepare->bindParam("group_id", $groupID);
            $prepare->execute();

            $response->getBody()->write((string)json_encode(array("text" => "Enrolled in the group successfully")));
            return $response
                ->withStatus(200)
                ->withHeader("Content-Type", 'application/json');

        } catch (PDOException $e) {
            $response->getBody()->write((string)json_encode(array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode()
                )
            )));
            return $response
                ->withStatus(500)
                ->withHeader("Content-Type", 'application/json');
        } finally {
            $db = null;
        }
    });
};
