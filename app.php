<?php

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

function create() {
    $now = new \DateTime('now');

    $item = [
        'id' => md5(time()),
        'username' => htmlspecialchars($_GET['username']),
        'url' => htmlspecialchars($_GET['url']),
        'finished' => false,
        'date' => $now->format('Y-m-d H:i:s')
    ];

    $items = json_decode(file_get_contents('items.json'), true);
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

    $items = array_filter($items, function ($item) use ($id) {
        return $item['id'] != $id;
    });

    file_put_contents('items.json', json_encode($items, true));
    header('Location: index.html');
    exit();
}


