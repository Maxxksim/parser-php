<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

class Db
{
    private Capsule $capsule;

    /**
     * Creates the Db instance and sets up the database connection manager.
     *
     * @param Config $config
     */
    public function __construct(private Config $config)
    {
        $this->capsule = $this->getInstance();
    }

    /**
     * Returns the active database connection.
     *
     * @return Connection The database connection instance.
     */
    public function connection(): Connection
    {
        return $this->capsule->getConnection();
    }

    /**
     * Creates and configures a Capsule instance for database operations.
     *
     * @return Capsule
     */
    public function getInstance(): Capsule
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => $this->config->config('database.mysql.driver'),
            'host' => $this->config->config('database.mysql.host'),
            'database' => $this->config->config('database.mysql.database'),
            'username' => $this->config->config('database.mysql.username'),
            'password' => $this->config->config('database.mysql.password'),
            'charset' => $this->config->config('database.mysql.charset'),
            'collation' => $this->config->config('database.mysql.collation'),
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }
}
