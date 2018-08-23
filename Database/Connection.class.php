<?php
/**
 *  @author Dmitri Puscas <degwelloa@gmail.com>
 */

namespace App\Database;

use PDO;
use PDOException;

/**
 * Класс обеспечивающий создание PDO обьекта и соединение с базой данных
 *
 * <code>
 * <?php
 *
 * use App\Config\Config;
 * use App\Database\Connection;
 *
 * $pdoConnection = Connection::make( Config::get('db') );
 *
 * </code>
 */
class Connection
{

    /**
     * Создаёт новый обьект PDO и конектится к базе данных
     *
     * @param array $config
     * @throws PDOException
     */
    public static function make($config)
    {
        $options[] = "PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION";
        $options[] = "PDO::ATTR_DEFAULT_FETCH_MODE = PDO::FETCH_ASSOC";
        $options[] = "PDO::ATTR_EMULATE_PREPARES   = false";

        try {
            return new PDO(
                'mysql:host='.$config['host'].';dbname='.$config['base'].';charset='.$config['charset'],
                $config['user'], $config['password'], $options
            );
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}
