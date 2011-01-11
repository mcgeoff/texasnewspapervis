
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

function getStatsByPub() {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = 'select pub, year, mGood, mTotal, location.city,
                  longitude as lng, latitude as lat
                  from pub_by_year, location
                  where pub_by_year.city=location.city';
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        echo json_encode($result);
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

function getStatsByCity() {
    try {
        $db = dbConnect();
        $db->beginTransaction();

        $query = 'select city_by_year.city, year, mGood, mTotal,
                  longitude as lng, latitude as lat
                  from city_by_year, location
                  where city_by_year.city=location.city';
        $result = $db->query($query)->fetchAll();

        $db->commit();
        $db = null;

        echo json_encode($result);
    }
    catch (PDOException $e) {
        $e->getMessage();
        exit();
    }
}

?>

