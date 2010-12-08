<?php

function dbConnect() {
    $dbname = "./newspaper.db";

    try {
        $db = new PDO("sqlite:" . $dbname);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $e->getMessage();
        exit();
    }

    return $db;
}

function getPubInCity($cityName) {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = "select * from pub_by_year where city=\"".
                 $cityName ."\"";
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        return $result;
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

?>
