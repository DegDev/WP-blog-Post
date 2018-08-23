<?php
/**
 *  @author Dmitri Puscas <degwelloa@gmail.com>
 */

namespace App\Models;

use PDO;
use PDOException;
use App\Config\Config;

/**
 * Модель для работы с WordPress блог постами,
 * предназначена для загрузки данных поста из базы данных, последующей обработки и
 * вывода
 *
 * <code>
 * <?php
 * use App\Models\BlogPost;
 *
 * $models = BlogPost::getLatest($pdo);
 *
 * foreach($models as $menuItem){
 *
 *  echo $menuItem->title. "\n";
 *  echo $menuItem->teaser."\n";
 *  echo $menuItem->date.  "\n";
 *  echo $menuItem->imgSrc."\n";
 *  echo $menuItem->URL."\n";
 * }
 * </code>
 */
class BlogPost
{
    public $id;

    /**
     * Заглавие блог поста
     *
     * @var  string $title     Заглавие
     */
    public $title;

    /**
     * Путь к изображению блог поста
     *
     * @var  string $imgSrc    Путь к картинке
     */
    public $imgSrc;

    /**
     * Дата блог поста
     *
     * @var  string $date       Дата создания поста
     */
    public $date;

    /**
     * Текст - превью блог поста
     *
     * @var  string $teaser     Превью поста, первые 180 символов
     */
    public $teaser;

    /**
     *
     * Заглавие поста из ВордПресс базы данных.
     * Имя соотвествует имени в ВордПресс.
     *
     * @var  string $post_title      Заглавие поста
     */
    protected $post_title;

    /**
     * Дата Поста из ВордПресс базы данных
     *
     * @var  dateTime $post_date     Дата
     */
    protected $post_date;

    /**
     * Содержание поста из ВордПресс базы данных
     *
     * @var  string $post_content     Содержание поста
     */
    protected $post_content;

    /**
     * Путь к картинке поста из ВордПресс базы данных
     *
     * @var  string $meta_value     Путь к картинке
     */
    protected $meta_value;

    /**
     * ЧПУ из Ворд Пресс базы данных
     *
     * @var  string $post_name     ЧПУ поста
     */
    protected $post_name;

    /**
     * Описание поста, post meta description из WordPress
     *
     * @var  string $post_description    описание поста
     */
    protected $post_description = null;

    /**
     * Указываем конструтор явным образом
     *
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Инициализирует обьект класса, формирует данные для работы
     * во внешних представлениях
     *
     * @return bool
     * @throws Exception
     */
    public function init()
    {
        try {
            $this->id    = $this->ID;
            $this->title = $this->post_title;
            $this->date  = (new \DateTime($this->post_date))
                ->format('m/d/Y');

            $this->teaser = $this->contentToTeaser();

            $this->imgSrc = $this->meta_value;
            $this->URL    = $this->post_name;

            return true;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Удаляет лишние пробелы и html теги,
     * извлекает текст-превью из блог пост контента, 180 символов
     *
     * В случае если задан post_description возвращает его
     *
     * @return string   teaser
     */
    protected function contentToTeaser()
    {
        if ($this->post_description != null) {

            return $this->post_description;
        }
        // удаляем лишние пробелы и html теги
        $postContent = strip_tags(
            trim($this->post_content)
        );
        if (strlen($postContent) > 180) {
            $postContent = substr($postContent, 0, 180);
        }
        // возвращаем подстроку начиная от 0 длинной 180 символов
        return $postContent.'&nbsp;[…]';
    }

    /**
     * Извлекает из двух таблиц: aa_post и aa_postmeta данные, результат извлечения
     * создаёт обьекты BlogPost и заносит в соотвествующие одноимённые свойства
     * данные из полей таблиц
     *
     * @param    $pdo                    Экземпляр PDO объекта, соединенного с базой данных
     * @return   array BlogPost          Массив членами которого являются обьекты BlogPost
     */
    public static function getLatest($pdo)
    {
        $statement = $pdo->query(
            "SELECT p1.ID,p1.post_title,p1.post_date,left(p1.post_content,221) as post_content,p1.post_name,
                wm2.meta_value,wm3.meta_value as post_description
            FROM
                aa_posts p1
            LEFT JOIN
                aa_postmeta wm1
            ON (
                wm1.post_id = p1.id
                AND wm1.meta_value IS NOT NULL
                AND wm1.meta_key = '_thumbnail_id'
            )
            LEFT JOIN
                aa_postmeta wm2
            ON (
                wm1.meta_value = wm2.post_id
                AND wm2.meta_key = '_wp_attached_file'
                AND wm2.meta_value IS NOT NULL
            )
            LEFT JOIN
                aa_postmeta wm3
            ON (
                wm1.post_id = wm3.post_id
                AND wm3.meta_value IS NOT NULL
                AND wm3.meta_key = '_aioseop_description'
            )
            WHERE
                p1.post_status='publish'
                AND p1.post_type='post'
            ORDER BY
            p1.post_date DESC LIMIT 3");


        $statement->setFetchMode(PDO::FETCH_CLASS, "App\Models\BlogPost");
        return $statement->fetchAll();
    }

    /**
     *
     * Извлекает из двух таблиц: aa_post и aa_postmeta данные, результат извлечения
     * создаёт обьекты BlogPost и заносит в соотвествующие одноимённые свойства
     * данные из полей таблиц. Интересуют только посты начиная от 24 ноября 2017.
     *
     * @param    $pdo                    Экземпляр PDO объекта, соединенного с базой данных
     * @return   array BlogPost          Массив членами которого являются обьекты BlogPost
     */
    public static function getRandomPosts($pdo, $amount = 3, $order = 0)
    {

        if ($order == 0) {
            $orderStr = 'RAND()';
        }
        if ($order == 1) {
            $orderStr = 'p1.post_date DESC';
        }
        $statement = $pdo->query(
            "SELECT p1.ID,p1.post_title,p1.post_date,left(p1.post_content,221) as post_content,p1.post_name,
                wm2.meta_value
            FROM
                aa_posts p1
            LEFT JOIN
                aa_postmeta wm1
            ON (
                wm1.post_id = p1.id
                AND wm1.meta_value IS NOT NULL
                AND wm1.meta_key = '_thumbnail_id'
            )
            LEFT JOIN
                aa_postmeta wm2
            ON (
                wm1.meta_value = wm2.post_id
                AND wm2.meta_key = '_wp_attached_file'
                AND wm2.meta_value IS NOT NULL
            )
            WHERE
                p1.post_status='publish'
                AND p1.post_type='post'
                AND p1.post_date > '2017-11-24'
            ORDER BY
            {$orderStr} LIMIT {$amount}");

        $statement->setFetchMode(PDO::FETCH_CLASS, "App\Models\BlogPost");

        return $statement->fetchAll();
    }
}
