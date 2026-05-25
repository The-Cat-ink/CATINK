<?php
    //datos de coneccion
    $server="localhost";
    $user="u780114275_catink_news";
    $pass="3N@KIrckPDm#";
    $dbname="u780114275_cat_ink";
    //sentencia de coneccion
    $con=new mysqli($server,$user,$pass,$dbname);
    if($con->connect_error){
        die("la coneccion fallo: "+$con->connect_error);
    }
    mysqli_set_charset($con, "utf8mb4");
?>