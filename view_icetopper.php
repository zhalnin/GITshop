<?php
/**
 * Created by PhpStorm.
 * User: zhalnin
 * Date: 16/08/14
 * Time: 13:52
 */
define('myeshop', true);
require_once('include/Exceptions.php');
require_once('utility/pager.php');
include "include/DB.php";
include ('utility/handleData.php');
include ('utility/groupPrice.php');
session_start();
include "include/auth-cookie.php";

//unset($_SESSION['auth']);
//setcookie('rememberme','',0,'/');
try {
    // переход по ссылкам в меню навигации
    $go = '';
    if (isset($_GET['go'])) {
        $go = handleData($_GET['go']);
    }
    switch ($go) {
        case "news":
            $queryIceTopper = " WHERE visible = '1' AND new = '1'";
            $nameIceTopper = "Новинки товаров";
            break;
        case "leaders":
            $queryIceTopper = " WHERE visible = '1' AND leader = '1'";
            $nameIceTopper = "Лидеры продаж";
            break;
        case "sale":
            $queryIceTopper = " WHERE visible = '1' AND sale = '1'";
            $nameIceTopper = "Распродажа товаров";
            break;
        default:
            $queryIceTopper = "";
            break;

    }

    // получаем значение для сортировки товара
    if (isset ($_GET['sort'])) {
        $sorting = $_GET['sort'];
    } else {
        $sorting = '';
    }

    switch ($sorting) {
        case 'price-asc':
            $sorting = 'price ASC';
            $sort_name = 'От дешевых к дорогим';
            break;
        case 'price-desc':
            $sorting = 'price DESC';
            $sort_name = 'От дорогих к дешевым';
            break;
        case 'popular':
            $sorting = 'count DESC';
            $sort_name = 'Популярные';
            break;
        case 'news':
            $sorting = 'datetime DESC';
            $sort_name = 'Новинки';
            break;
        case 'brand':
            $sorting = 'brand ASC';
            $sort_name = 'По алфавиту';
            break;
        default:
            $sorting = 'products_id ASC';
            $sort_name = 'Нет сортировки';
            break;

    }


    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Shop</title>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <link href="css/reset.css" rel="stylesheet" type="text/css" />
        <link href="css/style.css" rel="stylesheet" type="text/css" />
        <link href="TrackBar/jQuery/trackbar.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="js/jquery-2.1.1.js"></script>
        <script type="text/javascript" src="js/jcarouserllite_1.0.1.js"></script>
        <script type="text/javascript" src="js/jquery.cookie.js"></script>
        <script type="text/javascript" src="TrackBar/jQuery/jquery.trackbar.js"></script>
        <script type="text/javascript" src="js/jquery.TextChange.js"></script>
        <script type="text/javascript" src="js/shop-script.js"></script>
    </head>
    <body>

    <div id="block-body">

        <!--    подключаем блок block-header-->
        <?php include('include/block-header.php'); ?>

        <div id="block-right">
            <?php include('include/block-category.php'); ?>
            <?php include('include/block-parameter.php'); ?>
            <?php include('include/block-news.php'); ?>
        </div>

        <div id="block-content">
            <?php
                if ($queryIceTopper != ""){

                if (isset( $_GET['sort'])) {
                    $sortPar = '&sort='.$_GET['sort'];
                } else {
                    $sortPar = '';
                }
                // параметры будут иметь вид сортировки при постраничной навигации
                $parameters = $sortPar;
                // добавляем к строке запроса способ сортировки
                $parameters .= '&go='.$go;

                /**
                 * Постраничная навигация
                 */
                $pnumber = 10; // количество выводимых предметов
                $pageLink = 5; // количество ссылок слева и справа
                if( isset($_GET['page'])) {
                    $page = intval($_GET['page']); // номер страницы
                } else {
                    $page = 0;
                }
                $page   = intval($page);
                $sth    = DB::getStatement("SELECT COUNT(*) as count FROM table_products $queryIceTopper");
                $sth->execute();
                $rows   = $sth->fetch();
//                echo "<tt><pre> - asjfksajdfklsdjf - ".print_r($rows, true). "</pre></tt>";
                if ($rows['count'] > 0) {

?>
                    <div id="block-sorting">
                        <p id="nav-breadcrumbs"><a href="index.php">Главная страница</a> \ <span><?php echo $nameIceTopper; ?></span></p>
                        <ul id="options-list">
                            <li>Вид: </li>
                            <li><img id="style-grid" src="images/icon-grid.png" /></li>
                            <li><img id="style-list" src="images/icon-list.png" /></li>

                            <li>Сортировать: </li>
                            <li><a href="#" id="select-sort"><?php echo $sort_name; ?> </a>
                                <ul id="sorting-list">
                                    <li><a href="view_icetopper.php?go=<?php echo $go; ?>&sort=price-asc" >От дешевых к дорогим</a> </li>
                                    <li><a href="view_icetopper.php?go=<?php echo $go; ?>&sort=price-desc" >От дорогих к дешевым</a> </li>
                                    <li><a href="view_icetopper.php?go=<?php echo $go; ?>&sort=popular" >Популярное</a> </li>
                                    <li><a href="view_icetopper.php?go=<?php echo $go; ?>&sort=news" >Новинки</a> </li>
                                    <li><a href="view_icetopper.php?go=<?php echo $go; ?>&sort=brand" >От А до Я</a> </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <ul id="block-tovar-grid">
                    <?php

                        $total  = $rows['count']; // всего позиций
                        $number = $total / $pnumber; // количество ссылок на странице
                        if ($total / $pnumber != 0 ) $number++; // если не равно 0, то добавляем 1
                        $number = intval($number); // и приводим к целому числу

                        if (empty($page) || $page < 0 ) $page = 1; // номер страницы
                        if ($page > $number) $page = $number; // если страница больше общего числа, то она и есть максимальная

                        $start = $page * $pnumber - $pnumber; // с какого id выводить товар

                        $queryStart = " LIMIT $start, $pnumber";

                        $sth = DB::getStatement("SELECT * FROM table_products $queryIceTopper ORDER BY $sorting $queryStart");
                        $sth->execute();
                        $rows = $sth->fetchAll();

                        //        echo "<tt><pre> - asjfksajdfklsdjf - ".print_r(gettype($pnumber), true). "</pre></tt>";

                        foreach ($rows as $row):

                            if (isset($row['image']) && file_exists('uploads_images/'.$row['image'])) {
                                $img_path   = 'uploads_images/'.$row['image'];
                                $max_width  = 200;
                                $max_height = 200;
                                list($width, $height) = getimagesize($img_path);
                                $ratioh = $max_height / $height;
                                $ratiow = $max_width / $width;
                                $ratio  = min($ratioh, $ratiow);
                                $width  = intval($ratio * $width);
                                $height = intval($ratio * $height);
                            } else {
                                $img_path = "images/no-image.png";
                                $width    = 110;
                                $height   = 200;
                            }
                            // количество отзывов
                            $sthReview = DB::getStatement('SELECT COUNT(*) as count FROM table_reviews WHERE products_id = ? AND moderat = ?');
                            $sthReview->execute(array($row['products_id'], 1));
                            $rowReview = $sthReview->fetch();

                            if ($rowReview['count'] >=0 ) {
                                $count_review = $rowReview['count'];
                            }

                            ?>
                            <li>
                                <div class="block-images-grid"><img src="<?php echo $img_path;  ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" /></div>
                                <p class="style-title-grid"><a href="view_content.php?id=<?php echo $row['products_id']; ?>"" ><?php echo $row['title'];  ?></a></p>
                                <ul class="reviews-and-counts-grid">
                                    <li><img src="images/eye-icon.png" /><p><?php echo $row['count']; ?></p></li>
                                    <li><img src="images/comment-icon.png" /><p><?php echo $count_review; ?></p></li>
                                </ul>
                                <a href="" class="add-cart-style-grid" tid="<?php echo $row['products_id']; ?>"></a>
                                <p class="style-price-grid"><strong><?php echo groupPrice($row['price']); ?></strong> руб.</p>
                                <div class="mini-features"><?php echo $row['mini_features'];  ?></div>
                            </li>

                        <?php endforeach; ?>

                    </ul>
                    <ul id="block-tovar-list">

                    <?php foreach ($rows as $row):

                            if (isset($row['image']) && file_exists('uploads_images/'.$row['image'])) {
                                $img_path   = 'uploads_images/'.$row['image'];
                                $max_width  = 150;
                                $max_height = 150;
                                list($width, $height) = getimagesize($img_path);
                                $ratioh = $max_height / $height;
                                $ratiow = $max_width / $width;
                                $ratio  = min($ratioh, $ratiow);
                                $width  = intval($ratio * $width);
                                $height = intval($ratio * $height);
                            } else {
                                $img_path = "images/noimages80x70.png";
                                $width    = 80;
                                $height   = 70;
                            }

                        // количество отзывов
                        $sthReview = DB::getStatement('SELECT COUNT(*) as count FROM table_reviews WHERE products_id = ? AND moderat = ?');
                        $sthReview->execute(array($row['products_id'], 1));
                        $rowReview = $sthReview->fetch();

                        if ($rowReview['count'] >=0 ) {
                            $count_review = $rowReview['count'];
                        }
                            //echo "<tt><pre>".print_r($row['image'], true). "</pre></tt>";
                            ?>
                            <li>
                                <div class="block-images-list"><img src="<?php echo $img_path;  ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" /></div>

                                <ul class="reviews-and-counts-list">
                                    <li><img src="images/eye-icon.png" /><p><?php echo $row['count']; ?></p></li>
                                    <li><img src="images/comment-icon.png" /><p><?php echo $count_review; ?></p></li>
                                </ul>
                                <p class="style-title-list"><a href="view_content.php?id=<?php echo $row['products_id']; ?>"" ><?php echo $row['title'];  ?></a></p>
                                <a href="" class="add-cart-style-list" tid="<?php echo $row['products_id']; ?>"></a>
                                <p class="style-price-list"><strong><?php echo groupPrice($row['price']); ?></strong> руб.</p>
                                <div class="style-text-list"><?php echo $row['mini_description'];  ?></div>
                            </li>

                        <?php endforeach;?>

                    </ul>

<?php

                                echo '<div class="pstrnav"><ul>';
                                echo pager( $page, $pageLink, $number, $total, $parameters);
                                echo '</ul></div>';
                        } else {
                            echo "<p>Данный товар отсутствует!</p>";
                        }

                    } else {
                        echo "<p>Данная категория не найдена!</p>";
                    }

?>


        </div><!-- end block-content -->

        <?php include('include/block-random.php'); ?>
        <?php include('include/block-footer.php'); ?>

    </div>

    </body>
    </html>
<?php
} catch(PDOException $ex) {
    throw new Exceptions($ex);
}
?>