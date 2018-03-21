<?php

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
    header('Location: index.html');
    exit();
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

