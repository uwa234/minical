<?php

$projectRoot = realpath(__DIR__ . '/../../');
require_once $projectRoot . '/bootstrap/env.php';
minical_load_dotenv($projectRoot);

$dbHost = minical_env('DATABASE_HOST');
$dbUser = minical_env('DATABASE_USER');
$dbPass = minical_env('DATABASE_PASS');
$dbName = minical_env('DATABASE_NAME');

$mysqli_connection = @mysqli_connect("$dbHost", "$dbUser", "$dbPass", "$dbName");
if (!$mysqli_connection) {
    echo json_encode(array('success' => false, 'message' => "Database connection failed with error: " . mysqli_connect_error()), true);
    return;
}else{
    
    $query = "SELECT count(*) AS TOTALNUMBEROFTABLES  FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_SCHEMA = '$dbName'";
     if($result = mysqli_query($mysqli_connection, $query)){
            $row = mysqli_fetch_row($result);
            echo json_encode(array('success' => true, 'message' => $row[0]), true);
            return;
        }
    echo json_encode(array('success' => false, 'error' => 'Database validation failed'), true);  
    return;
}


