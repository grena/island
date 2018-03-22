<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

include_once('vendor/autoload.php');

$url = getenv('JAWSDB_MARIA_URL');
$dbparts = parse_url($url);

$table = 'items';
$hostname = $dbparts['host'];
$username = $dbparts['user'];
$password = $dbparts['pass'];
$database = ltrim($dbparts['path'],'/');

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    echo "Connected successfully";
}
catch(PDOException $e)
{
    echo "Connection failed: " . $e->getMessage();
}

// Try a select statement against the table
// Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
try {
    $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
} catch (Exception $e) {
    $sql ="CREATE TABLE `$table` (
      `id` varchar(255) NOT NULL,
      `username` varchar(255) NOT NULL,
      `url` varchar(255) NOT NULL,
      `finished` bit NOT NULL,
      `date` varchar(255) NOT NULL,
      `pos` int NOT NULL
    );" ;
    $db->exec($sql);
}

//'id' => md5(time()),
//'username' => htmlspecialchars($_GET['username']),
//'url' => htmlspecialchars($_GET['url']),
//'finished' => false,
//'date' => $now->format('Y-m-d H:i:s'),
//'pos' => count($items)

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create':
            create();
            break;
        case 'finished':
            finished();
            break;
        case 'delete':
            delete();
            break;
    }
}


if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'sorted':
            sorted();
            break;
    }
}

function create() {
    $now = new \DateTime('now');
    $items = json_decode(file_get_contents('items.json'), true);

    $item = [
        'id' => md5(time()),
        'username' => htmlspecialchars($_GET['username']),
        'url' => htmlspecialchars($_GET['url']),
        'finished' => false,
        'date' => $now->format('Y-m-d H:i:s'),
        'pos' => count($items)
    ];

    $items[] = $item;

    file_put_contents('items.json', json_encode($items, true));

    notify($item['username'], $item['url']);

    header('Location: index.html');
    exit();
}

function notify ($username, $url) {
    $SLACK_URL = getenv('SLACK_URL');
    if (!$SLACK_URL) {
        return;
    }

    $data = [
        'text' => sprintf(
            '%s wants to run a CI build for %s',
            $username,
            $url
        ),
    ];

    $client = new Client();
    $request = new Request(
        'POST',
        $SLACK_URL,
        ['Content-type' => 'application/json'],
        json_encode($data)
    );
    $promise = $client->sendAsync($request, ['timeout' => 10]);
    $promise->wait(false);
}

function finished() {
    $items = json_decode(file_get_contents('items.json'), true);
    $id = $_GET['id'];

    $items = array_map(function ($item) use ($id) {
        if ($item['id'] == $id) {
            $item['finished'] = true;
        }

        return $item;
    }, $items);

    file_put_contents('items.json', json_encode($items, true));
    header('Location: index.html');
    exit();
}

function delete() {
    $items = json_decode(file_get_contents('items.json'), true);
    $id = $_GET['id'];
    $keptItems = [];

    foreach ($items as $item) {
        if ($item['id'] == $id) {
            $deletedPosition = $item['pos'];
        } else {
            $keptItems[] = $item;
        }
    }

    $keptItems = array_map(function ($item) use ($deletedPosition) {
        if ($item['pos'] > $deletedPosition) {
            $item['pos'] = $item['pos'] - 1;
        }

        return $item;
    }, $keptItems);

    file_put_contents('items.json', json_encode($keptItems, true));
    header('Location: index.html');
    exit();
}

function sorted() {
    $items = json_decode(file_get_contents('items.json'), true);
    $orderedItems = [];

    $ordersIds = $_POST['orders'];

    foreach ($items as $item) {
        $id = $item['id'];
        if (isset($ordersIds[$id])) {
            $item['pos'] = $ordersIds[$id]['pos'];
        }

        $orderedItems[] = $item;
    }

    file_put_contents('items.json', json_encode($orderedItems, true));
}

