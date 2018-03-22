<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

include_once('vendor/autoload.php');

checkTableExists();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'items':
            httpItems();
            break;
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

function httpItems()
{
    $items = getItems();

    header('Content-Type: application/json');
    echo json_encode($items);
}

function checkTableExists()
{
    $pdo = getPdo();
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
        $result = $pdo->query("SELECT 1 FROM items LIMIT 1");
    } catch (Exception $e) {
        $sql ="CREATE TABLE `items` (
      `id` varchar(255) NOT NULL,
      `username` varchar(255) NOT NULL,
      `url` varchar(255) NOT NULL,
      `finished` bit NOT NULL,
      `date` varchar(255) NOT NULL,
      `pos` int NOT NULL
    );" ;

        try {
            $pdo->exec($sql);
        } catch(PDOException $e) {
            echo $e->getMessage();
            die();
        }
    }
}

function getPdo(): PDO
{
    $url = getenv('JAWSDB_MARIA_URL');
    $dbparts = parse_url($url);
//    $dbparts = [
//        'host' => 'localhost',
//        'user' => 'akeneo_pim',
//        'pass' => 'akeneo_pim',
//        'path' => 'akeneo_pim'
//    ];

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

    return $pdo;
}

function getItems()
{
    $pdo = getPdo();

    $items = $pdo->query('SELECT * FROM items;')->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(function ($item) {
        $item['finished'] = $item['finished'] == '0' ? false : true;

        return $item;
    }, $items);

    return $items;
}

function create() {
    $now = new \DateTime('now');
    $items = getItems();

    $item = [
        'id' => md5(time()),
        'username' => htmlspecialchars($_GET['username']),
        'url' => htmlspecialchars($_GET['url']),
        'finished' => false,
        'date' => $now->format('Y-m-d H:i:s'),
        'pos' => count($items)
    ];

    $pdo = getPdo();
    $sth = $pdo->prepare('INSERT INTO items VALUES(:id, :username, :url, :finished, :date, :pos)');
    $sth->execute($item);

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
    $pdo = getPdo();
    $sth = $pdo->prepare('UPDATE items SET finished = 1 WHERE id = :id');
    $sth->execute(['id' => $_GET['id']]);

    header('Location: index.html');
    exit();
}

function delete() {
    $deletedItem = getItem($_GET['id']);
    $items = getItems();

    foreach ($items as $item) {
        if ($item['pos'] > $deletedItem['pos']) {
            $item['pos'] = $item['pos'] - 1;
        }

        updateItem($item);
    }

    $pdo = getPdo();
    $sth = $pdo->prepare('DELETE FROM items WHERE id = :id');

    try {
        $sth->execute(['id' => $deletedItem['id']]);
    }
    catch(PDOException $e)
    {
        echo $e->getMessage();
    }

    header('Location: index.html');
    exit();
}

function getItem(string $id)
{
    $pdo = getPdo();
    $sth = $pdo->prepare('SELECT * FROM items WHERE id = :id');
    $sth->execute(['id' => $id]);

    return $sth->fetch(PDO::FETCH_ASSOC);
}

function updateItem(array $item)
{
    $pdo = getPdo();
    $sth = $pdo->prepare('UPDATE items SET pos = :pos WHERE id = :id');

    try {
        $sth->execute([
            'id' => $item['id'],
            'pos' => $item['pos'],
        ]);
    }
    catch(PDOException $e)
    {
        echo $e->getMessage();
        exit;
    }
}

function sorted() {
    $items = getItems();
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

