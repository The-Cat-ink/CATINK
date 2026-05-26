<?php
    require_once(__DIR__ . '/env.php');
    //datos de coneccion
    $server = env('DB_HOST', 'localhost');
    $user   = env('DB_USER', 'root');
    $pass   = env('DB_PASS', '');
    $dbname = env('DB_NAME', 'u780114275_cat_ink');
    //sentencia de coneccion
    $con=new mysqli($server,$user,$pass,$dbname);
    if($con->connect_error){
        die("la coneccion fallo: ".$con->connect_error);
    }
    mysqli_set_charset($con, "utf8mb4");
?>