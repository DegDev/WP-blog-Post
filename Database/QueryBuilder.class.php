<?php
/**
 *  @author Dmitri Puscas <degwelloa@gmail.com> 
 */

namespace Auslogics\Database;

use PDO;
use PDOException;

/**
 * Класс-обвёртка для построения PDO запросов и взаимодействия с базой данных
 *
 * <code>
 * <?php
 *
 * use App\Config\Config;
 * use App\Database\QueryBuilder;
 * use App\Database\Connection;
 *
 * $db = new QueryBuilder(   Connection::make( Config::get('db') )    );
 * 
 * $query = $db->find('table_name',[
 * 
 *           'id' => $id
 * 
 *      ]);
 *
 * echo $query['id'];
 * }
 * </code>
 */
class QueryBuilder
{
    /**
     * Экземляр PDO
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Создаём новый QueryBuilder экземпляр
     *
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     *  Выполняет sql запрос
     *
     * @param  string $sql         строка SQL запроса
     * @return PDOStatement        Готовое для получения данных PDO заявление
     * @throws PDOException 
     */
    public function query($sql)
    {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            return $statement;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     *  Select All из таблицы
     *
     * @param string $table         Название таблицы
     * @return array                Массив всех рядов таблицы
     * @throws PDOException
     */
    public function selectAll($table)
    {

        $statement = $this->pdo->prepare('SELECT * FROM '.$table);

        $statement->execute();

        try {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Добавить запись в таблицу
     *
     * @param  string $table           Имя таблицы
     * @param  array  $parameters      Параметры
     * @return bool
     * @throws PDOException
     */
    public function insert($table, $parameters)
    {
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)', $table,
            implode(', ', array_keys($parameters)),
            ':'.implode(', :', array_keys($parameters))
        );

        try {
            $statement = $this->pdo->prepare($sql);

            $statement->execute($parameters);

            return true;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Поиск единственной записи в таблице
     *
     * @param  string $table            Имя таблицы
     * @param  array  $parameters
     * @return array                   Массив единственного ряда таблицы
     * @throws Exception
     */
    public function find($table, $parameters)
    {

        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = %s", $table,
            implode(', ', array_keys($parameters)),
            ':'.implode(', :', array_keys($parameters))
        );

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);

            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}
