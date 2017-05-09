<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ink;

/**
 * Description of Import
 *
 * @author eislas
 */
class Catalog {

    static function import() {
        set_time_limit(0);
        $groups = get_option('ink_catalog_groups', Main::$DEFAULT_GROUPS);
        $importSummary = array();
        foreach ($groups as $groupName) {
            $categoryId = self::createCategory($groupName);

            self::importGroup($groupName, $categoryId, $importSummary);

//            exit();
        }
        ?>
        <h2>Resumen de articulos importados:</h2>
        <style>
            b.number{
                color: green;
            }
        </style>
        <?php
        echo '<pre>';
        echo preg_replace('/": (\d+)/', "\": \t<b class='number'>$1</b>", json_encode($importSummary, JSON_PRETTY_PRINT));
        echo '</pre>';
    }

    static function createCategory($groupName, $parentId = 0) {
        $termExists = term_exists($groupName, 'product_cat', $parentId);


//            var_dump($groupExists);
        /*
         * array (size=2)
         *  'term_id' => string '30' (length=2)
         *  'term_taxonomy_id' => string '30' (length=2)
         */

        if (!$termExists) {
            $termId = wp_insert_term(
                    $groupName, // the term 
                    'product_cat', // the taxonomy
                    array(
                'parent' => $parentId
            ));
            $categoryId = $termId['term_id'];


//                var_dump($categoryId);
            /*
             *  array (size=2)
             *      'term_id' => int 30
             *      'term_taxonomy_id' => int 30
             */
        } else {
            $categoryId = $termExists['term_id'];
        }
        return $categoryId;
    }

    static function importGroup($groupName, $categoryId, &$importSummary = array()) {
        //http://www.grupocva.com/catalogo_clientes_xml/lista_precios.xml?cliente=26813&marca=HP&grupo=IMPRESORA%20DE%20AMPLIO%20FORMATO%20(PLOTTER)&clave=%&codigo=%        

        $endpoint = get_option('ink_catalog_endpoint', Main::DEFAULT_ENDPOINT);
        $clientid = get_option('ink_client_id', Main::DEFAULT_CLIENT_ID);

//        echo "\n<h1>$groupName</h1>\n";
        $reader = new \XMLReader();
        $queryString = http_build_query(array('cliente' => $clientid
            , 'subgpo' => 1
            , 'grupo' => $groupName
        ));
//        echo "$endpoint/lista_precios.xml?$queryString";
//        exit();
        $reader->open("$endpoint/lista_precios.xml?$queryString");

        $nodeName = '';
//        $reader->read();
        while ($nodeName != 'item') {
//            echo "<h1>$nodeName</h1>";
            $reader->read();
            $item = trim($reader->readOuterXML());
            if (empty($item)) {
                continue;
            }
            $simpleXMLElement = simplexml_load_string($item);
//            echo number_format(memory_get_usage() / 1024 / 1024, 3) . 'MB <br/>';
//            var_dump($simpleXMLElement);
            $nodeName = $simpleXMLElement->getName();
//            exit();
        }


        $items = 0;
        while ($item = $reader->readOuterXML()) {
            $item = trim($item);
//            echo number_format(memory_get_usage() / 1024 / 1024, 3) . 'MB <br/>';
            $reader->next();
            if (empty($item)) {
                continue;
//                echo 'XD';
            }
//            echo "#####<hr/>";
            self::importItem($item, $importSummary);
            $items++;
//            var_dump($item);
//            var_dump(simplexml_load_string($item));
//            break;
        }

//        echo "<h3>$items items importados</h3><hr/>\n";
    }

    static function importItem($item, &$importSummary = array()) {

        $itemObject = simplexml_load_string($item);

        $groupName = (string) $itemObject->grupo;
        $subGrupo = (string) $itemObject->subgrupo;

        $importSummary[$groupName ?: '#_Sin grupo_'][$subGrupo ?: '#_Sin subgrupo_'] ++;



//        var_dump($item, $groupName, $subGrupo);
        if (empty($subGrupo)) {
            //no hay subgrupo se asigna al grupo
//            var_dump("no hay subgrupo se asigna al grupo $groupName");
        } else {//hay subgrupo
            $parentId = self::createCategory($groupName); //se crea el padre
//            var_dump(" se crea la categoria padre $groupName,  $parentId");
            if ($parentId) {//existe el padre
                $categoryId = self::createCategory($subGrupo, $parentId); //se crea subcategoria
//                var_dump(" se crea la Subcategoria $subGrupo,  $categoryId");
                $groupName = $subGrupo;
            }
        }

//        var_dump($item);
//        var_dump($itemObject);
//        var_dump((string)$itemObject->codigo_fabricante);


        $productId = wc_get_product_id_by_sku((string) $itemObject->codigo_fabricante);
//        $productId = wc_get_product_id_by_sku('ttttkzd');
//        var_dump($productId);

        if (!$productId) {
            $productId = wp_insert_post(array(
                'post_title' => (string) $itemObject->descripcion,
                'post_content' => (string) $itemObject->descripcion,
                'post_status' => 'publish',
                'post_type' => "product",
            ));
        }
        wp_set_object_terms($productId, $groupName, 'product_cat');

        wp_set_object_terms($productId, 'simple', 'product_type');


        update_post_meta($productId, '_visibility', 'visible');

        $stockStatus = 'outofstock';
        if (((int) $itemObject->disponible) > 0) {
            $stockStatus = 'instock';
        }
        update_post_meta($productId, '_stock_status', $stockStatus);
        update_post_meta($productId, 'total_sales', '0');
        update_post_meta($productId, '_downloadable', 'no');
        update_post_meta($productId, '_virtual', 'yes');
        update_post_meta($productId, '_regular_price', '');
        update_post_meta($productId, '_sale_price', '');
        update_post_meta($productId, '_purchase_note', '');
        update_post_meta($productId, '_featured', 'no');
        update_post_meta($productId, '_weight', '');
        update_post_meta($productId, '_length', '');
        update_post_meta($productId, '_width', '');
        update_post_meta($productId, '_height', '');
        update_post_meta($productId, '_sku', (string) $itemObject->codigo_fabricante);

        update_post_meta($productId, '_sale_price_dates_from', '');
        update_post_meta($productId, '_sale_price_dates_to', '');
        update_post_meta($productId, '_price', (string) $itemObject->precio);
        update_post_meta($productId, '_sold_individually', '');
        update_post_meta($productId, '_manage_stock', 'no');
        update_post_meta($productId, '_backorders', 'no');
        update_post_meta($productId, '_stock', (string) $itemObject->disponible);

        $term_taxonomy_ids = wp_set_object_terms($productId, (string) $itemObject->marca, 'pa_marca', true);
        $attributes = Array(
            'pa_marca' => Array(
                'name' => 'pa_marca',
                'value' => (string) $itemObject->marca,
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            )
        );
        update_post_meta($productId, '_product_attributes', $attributes);

        self::setProductPhotoFromUrl($productId, (string) $itemObject->imagen);
    }

    static function updateProduct($product_id) {
        $product = wc_get_product($product_id);
        $sku = $product->get_sku();

        $endpoint = get_option('ink_catalog_endpoint', Main::DEFAULT_ENDPOINT);
        $clientid = get_option('ink_client_id', Main::DEFAULT_CLIENT_ID);

        $reader = new \XMLReader();
        $queryString = http_build_query(array('cliente' => $clientid
            , 'subgpo' => 1
            , 'codigo' => $sku
        ));
        $reader->open("$endpoint/lista_precios.xml?$queryString");

        $nodeName = '';
        while ($nodeName != 'item') {
            $reader->read();
            $item = trim($reader->readOuterXML());
            if (empty($item)) {
                continue;
            }
            $simpleXMLElement = simplexml_load_string($item);
            $nodeName = $simpleXMLElement->getName();
        }

        while ($item = $reader->readOuterXML()) {
            $item = trim($item);
            $reader->next();
            if (empty($item)) {
                continue;
            }
            self::importItem($item);
            break; //update just one
        }
    }

    static function setProductPhotoFromUrl($postid, $photo_url) {
        $photo_url = trim($photo_url);
        if (empty($photo_url)) {
            return false;
        }
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

// Download file to temp location
        $tmp = download_url($photo_url);


// If error storing temporarily, unlink
        if (is_wp_error($tmp)) {
            @unlink($tmp);
            return FALSE;
        }

// Set variables for storage
// fix file name for query strings
        preg_match('/[^\?]+\.(jpg|jpg|jpeg|gif|png)/i', $photo_url, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['type'] = 'image/' . $matches[1];
        $file_array['tmp_name'] = $tmp;

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );


//use media_handle_sideload to upload img:
        $thumbid = media_handle_sideload($file_array, $postid);
// If error storing permanently, unlink
        if (is_wp_error($thumbid)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        set_post_thumbnail($postid, $thumbid);
        @unlink($tmp);
        return TRUE;
    }

    /**
     * Test import
     */
    static function formImport() {
//// Script start
//        $rustart = getrusage();
        ?>
        <div class="wrap">
            <h1><?php _e('Importar Productos') ?></h1>
            <?php // echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';    ?>
            <p>
                Desde aquí puedes importar manualmente los productos. 
            </p>
            <p>
                Los productos ya existentes se actualizarán con la información del proveedor.
            </p>

            <form method="POST" id="form-import">
                <?php
                $submit = filter_input(INPUT_POST, 'submit');
                if (!empty($submit)) {
                    self::import();
                }
                ?>
                <?php submit_button('Importar ahora'); ?>
            </form>
            <span id="loading" style="display: none;">Iportando productos, por favor espere...</span>

            <script>
                jQuery(document).ready(function ($) {
                    $("#form-import").submit(function () {
                        $("#form-import").hide();
                        $("#loading").show();
                    });
                });
            </script>

        </div>
        <?php
//        // Script end
//        function rutime($ru, $rus, $index) {
//            return ($ru["ru_$index.tv_sec"] * 1000 + intval($ru["ru_$index.tv_usec"] / 1000)) - ($rus["ru_$index.tv_sec"] * 1000 + intval($rus["ru_$index.tv_usec"] / 1000));
//        }
//
//        $ru = getrusage();
//        echo "This process used " . rutime($ru, $rustart, "utime") .
//        " ms for its computations\n";
//        echo "It spent " . rutime($ru, $rustart, "stime") .
//        " ms in system calls\n";
    }

//    static function testCron() {
//        error_log("\n###" . date('Y-m-d H:i:s') . ' testCron', 3, INK_PATH . '/testcron.log');
//    }
}
