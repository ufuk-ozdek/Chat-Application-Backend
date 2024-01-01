<?php


class Db{
    private $sqlite = "sqlite:../src/db/chat_app.db";

    public function connect()
    {
        $db = new PDO($this->sqlite);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }

}