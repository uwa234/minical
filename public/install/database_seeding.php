<?php

header('Content-Type: application/json');

$projectRoot = realpath(__DIR__ . '/../../');
require_once $projectRoot . '/bootstrap/env.php';
minical_load_dotenv($projectRoot);

$filename = __DIR__ . '/minical-seed.sql';

$dbHost = minical_env('DATABASE_HOST');
$dbUser = minical_env('DATABASE_USER');
$dbPass = minical_env('DATABASE_PASS');
$dbName = minical_env('DATABASE_NAME');

if (!is_file($filename)) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Seed file not found: ' . $filename,
    ));
    exit;
}

$maxRuntime = 20;
$deadline = time() + $maxRuntime;

$mysqli_connection = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$mysqli_connection) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Database connection failed: ' . mysqli_connect_error(),
    ));
    exit;
}

mysqli_set_charset($mysqli_connection, 'utf8');
mysqli_report(MYSQLI_REPORT_OFF);

function minical_seed_query($connection, $sql)
{
    $result = mysqli_query($connection, $sql);
    if ($result === false) {
        return mysqli_error($connection);
    }

    return true;
}

mysqli_query($mysqli_connection, "CREATE TABLE IF NOT EXISTS `minical_installation_meta` (
    `pointer` bigint(20) NOT NULL,
    `error` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

$fp = fopen($filename, 'r');
if ($fp === false) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Failed to open seed file: ' . $filename,
    ));
    exit;
}

$file_position = 0;
$result = mysqli_query($mysqli_connection, 'SELECT pointer FROM minical_installation_meta LIMIT 1');
if ($result) {
    if ($row = mysqli_fetch_row($result)) {
        $file_position = (int) $row[0];
        fseek($fp, $file_position);
    }
    mysqli_free_result($result);
}

$query = '';
$queryCount = 0;
$lastError = '';

while ($deadline > time() && ($line = fgets($fp, 102400)) !== false) {
    if (substr($line, 0, 2) === '--' || trim($line) === '') {
        continue;
    }

    $query .= $line;
    if (substr(trim($query), -1) !== ';') {
        continue;
    }

    $queryResult = minical_seed_query($mysqli_connection, $query);
    if ($queryResult !== true) {
        $lastError = $queryResult;
        echo json_encode(array(
            'success' => false,
            'message' => 'Seed query failed: ' . $lastError,
            'file_position' => ftell($fp),
        ));
        fclose($fp);
        mysqli_close($mysqli_connection);
        exit;
    }

    $query = '';
    $queryCount++;

    $file_position = ftell($fp);
    $metaExists = mysqli_query($mysqli_connection, 'SELECT 1 FROM minical_installation_meta LIMIT 1');
    if ($metaExists && mysqli_num_rows($metaExists) > 0) {
        mysqli_free_result($metaExists);
        mysqli_query(
            $mysqli_connection,
            "UPDATE minical_installation_meta SET pointer = {$file_position}, error = 'no'"
        );
    } else {
        if ($metaExists) {
            mysqli_free_result($metaExists);
        }
        mysqli_query(
            $mysqli_connection,
            "INSERT INTO minical_installation_meta (pointer, error) VALUES ({$file_position}, 'no')"
        );
    }
}

if (feof($fp)) {
    fclose($fp);
    mysqli_close($mysqli_connection);

    echo json_encode(array(
        'success' => true,
        'project_url' => trim(minical_env('PROJECT_URL')),
        'queries_executed' => $queryCount,
    ));
    exit;
}

fclose($fp);
mysqli_close($mysqli_connection);

echo json_encode(array(
    'success' => false,
    'in_progress' => true,
    'file_position' => $file_position,
    'file_size' => filesize($filename),
));
