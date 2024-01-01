<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // Add a Message
    $app->post('/message/add', function (Request $request, Response $response) {
        $db = new Db();
        $parsedBody = $request->getBody();
        $jsonData = json_decode($parsedBody, true);
        $groupID = $jsonData["group_id"];
        $userID = $jsonData["user_id"];
        $messageText = $jsonData["message_text"];

        try {
            $db = $db->connect();
            // Check if userID is enrolled in the specified groupID
            $checkEnrollmentStatement = "SELECT COUNT(*) FROM group_members WHERE user_id = :user_id AND group_id = :group_id";
            $checkEnrollmentPrepare = $db->prepare($checkEnrollmentStatement);
            $checkEnrollmentPrepare->bindParam("user_id", $userID);
            $checkEnrollmentPrepare->bindParam("group_id", $groupID);
            $checkEnrollmentPrepare->execute();
            $enrollmentResult = $checkEnrollmentPrepare->fetch(PDO::FETCH_ASSOC);
            var_dump($enrollmentResult);
            if ($enrollmentResult["COUNT(*)"] == 0) {
                $response->getBody()->write(json_encode(array("error" => "User is not enrolled in the specified group")));
                return $response
                    ->withStatus(403)
                    ->withHeader("Content-Type", 'application/json');
            }

            $statement = "INSERT INTO messages (group_id, user_id, message_text) VALUES (:group_id, :user_id, :message_text)";
            $prepare = $db->prepare($statement);
            $prepare->bindParam("group_id", $groupID);
            $prepare->bindParam("user_id", $userID);
            $prepare->bindParam("message_text", $messageText);
            $message = $prepare->execute();

            if ($message) {
                $result = array("text" => "Message sent successfully");
                $response = $response->withStatus(200);
            } else {
                $result = array("error" => "Error while sending message");
                $response = $response->withStatus(500);
            }

        } catch (PDOException $e) {
            $result = array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode(),
                )
            );
            $response = $response->withStatus(500);
        }

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));
        return $response;
    });

    // List Messages in a Group
    $app->get('/group/{group_id}/messages', function (Request $request, Response $response) {
        $groupID = $request->getAttribute("group_id");
        $db = new Db();

        try {
            $db = $db->connect();

            $statement = "SELECT messages.message_id, messages.group_id, messages.user_id, users.username, messages.message_text, messages.sent_at
                          FROM messages
                          JOIN users ON messages.user_id = users.user_id
                          WHERE messages.group_id = :group_id
                          ORDER BY messages.sent_at";

            $prepare = $db->prepare($statement);
            $prepare->bindParam("group_id", $groupID);
            $prepare->execute();

            $messages = $prepare->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode(array("messages" => $messages)));
            return $response
                ->withStatus(200);

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array(
                "error" => array(
                    "text" => $e->getMessage(),
                    "code" => $e->getCode()
                )
            )));
            return $response->withStatus(500);
        } finally {
            $db = null;
        }
    });
};