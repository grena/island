<?php

use Island\ItemRepository;
use Island\Kaamelott;
use Island\Notifier;

include_once('vendor/autoload.php');

$itemRepo = new ItemRepository();
$itemRepo->ensureTableExists();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'quote':
            quoteAction();
            break;
        case 'items':
            listAction();
            break;
        case 'create':
            createAction();
            break;
        case 'finished':
            finishedAction();
            break;
        case 'delete':
            deleteAction();
            break;
    }
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'sorted':
            sortedAction();
            break;
    }
}

/**
 * Send JSON of all items.
 */
function listAction()
{
    $itemRepo = new ItemRepository();
    $items = $itemRepo->getAll();

    header('Content-Type: application/json');
    echo json_encode($items);
}

/**
 * Create a new Item into DB.
 */
function createAction() {
    $itemRepo = new ItemRepository();
    $itemRepo->create(
        htmlspecialchars($_GET['username']),
        htmlspecialchars($_GET['url'])
    );

    $notifier = new Notifier();
    $notifier->notify(
        htmlspecialchars($_GET['username']),
        htmlspecialchars($_GET['url'])
    );

    header('Location: index.html');
    exit();
}

/**
 * Mark an Item as finished.
 */
function finishedAction() {
    $itemRepo = new ItemRepository();
    $itemRepo->markAsFinished($_GET['id']);

    header('Location: index.html');
    exit();
}

/**
 * Delete an Item.
 */
function deleteAction() {
    $itemRepo = new ItemRepository();
    $itemRepo->delete($_GET['id']);

    header('Location: index.html');
    exit();
}

/**
 * Reorder all items.
 */
function sortedAction() {
    $itemRepo = new ItemRepository();
    $items = $itemRepo->getAll();
    $ordersIds = $_POST['orders'];

    foreach ($items as $item) {
        $id = $item['id'];
        if (isset($ordersIds[$id])) {
            $item['pos'] = $ordersIds[$id]['pos'];
        }

        $itemRepo->update($item);
    }

    header('Location: index.html');
    exit();
}

/**
 * Display a JSON quote of Kaamelott.
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function quoteAction()
{
    $kaamelott = new Kaamelott();

    header('Content-Type: application/json');
    echo $kaamelott->getQuote();
}
