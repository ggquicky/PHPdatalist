<?php

namespace model;
require_once './vendor/autoload.php';

class Database
{
    protected array $db;

    public function __construct()
    {
        $this->db = json_decode(
            file_get_contents(__DIR__.'./db.json'),
            true
        );
    }

    public function all(): array
    {
        return $this->db;
    }

    public function find(string $id): ?array
    {
        if (empty($this->db[$id])) {
            return null;
        }

        return $this->db[$id];
    }

    public function delete(string $id): void
    {
        unset($this->db[$id]);

        $this->write();
    }

    public function edit(string $id, array $data): void
    {
        $this->db[$id] = $data;

        $this->write();
    }

    public function save(array $data): void
    {
        $id = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $this->db[$id] = $data;


        $this->write();
    }

    protected function write()
    {
        file_put_contents(
            __DIR__.'./db.json',
            json_encode($this->db)
        );
    }
}