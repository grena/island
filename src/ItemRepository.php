<?php
declare(strict_types=1);

namespace Island;

use Exception;
use PDO;
use PDOException;

class ItemRepository
{
    /**
     * @return array
     */
    public function getAll(): array
    {
        $items = $this->getPdo()
            ->query('SELECT * FROM items;')
            ->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(function ($item) {
            $item['finished'] = $item['finished'] == '0' ? false : true;

            return $item;
        }, $items);

        return $items;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getById(string $id): array
    {
        $sth = $this->getPdo()->prepare('SELECT * FROM items WHERE id = :id');
        $sth->execute(['id' => $id]);

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param array $item
     */
    public function update(array $item): void
    {
        $sth = $this->getPdo()->prepare('UPDATE items SET pos = :pos WHERE id = :id');

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

    /**
     * Create a new Item and saves it in DB
     *
     * @param string $username
     * @param string $pullRequestUrl
     */
    public function create(string $username, string $pullRequestUrl): void
    {
        $now = new \DateTime('now');
        $items = $this->getAll();

        $item = [
            'id' => uniqid(),
            'username' => $username,
            'url' => $pullRequestUrl,
            'finished' => false,
            'date' => $now->format('Y-m-d H:i:s'),
            'pos' => count($items)
        ];

        $pdo = $this->getPdo();
        $sth = $pdo->prepare('INSERT INTO items VALUES(:id, :username, :url, :finished, :date, :pos)');
        $sth->execute($item);
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        $deletedItem = $this->getById($id);
        $items = $this->getAll();

        foreach ($items as $item) {
            if ($item['pos'] > $deletedItem['pos']) {
                $item['pos'] = $item['pos'] - 1;
            }

            $this->update($item);
        }

        $sth = $this->getPdo()->prepare('DELETE FROM items WHERE id = :id');

        try {
            $sth->execute(['id' => $deletedItem['id']]);
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Mark an Item as finished.
     *
     * @param string $id
     */
    public function markAsFinished(string $id)
    {
        $sth = $this->getPdo()->prepare('UPDATE items SET finished = 1 WHERE id = :id');
        $sth->execute(['id' => $_GET['id']]);
    }

    /**
     * Ensure the items table exists. If not, it creates it.
     */
    public function ensureTableExists(): void
    {
        $pdo = $this->getPdo();
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

    /**
     * @return PDO
     */
    private function getPdo(): PDO
    {
        $dbparts = [
            'host' => 'localhost',
            'user' => 'akeneo_pim',
            'pass' => 'akeneo_pim',
            'path' => 'akeneo_pim'
        ];

        $url = getenv('JAWSDB_MARIA_URL');
        if ($url) {
            $dbparts = parse_url($url);
        }

        $hostname = $dbparts['host'];
        $username = $dbparts['user'];
        $password = $dbparts['pass'];
        $database = ltrim($dbparts['path'],'/');

        try {
            $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }

        return $pdo;
    }
}
