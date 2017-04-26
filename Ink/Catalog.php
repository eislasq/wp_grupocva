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
        $groups = get_option('ink_catalog_groups', Main::$DEFAULT_GROUPS);

        foreach ($groups as $groupName) {
            $termExists = term_exists($groupName, 'product_cat', 0);


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
                    'parent' => 0
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




            self::importGroup($groupName, $categoryId, $groupName);

//            exit();
        }
    }

    static function importGroup($groupName, $categoryId, $groupName) {
        //http://www.grupocva.com/catalogo_clientes_xml/lista_precios.xml?cliente=26813&marca=HP&grupo=IMPRESORA%20DE%20AMPLIO%20FORMATO%20(PLOTTER)&clave=%&codigo=%        

        $endpoint = get_option('ink_catalog_endpoint', Main::DEFAULT_ENDPOINT);
        $clientid = get_option('ink_client_id', Main::DEFAULT_CLIENT_ID);

        echo "<h1>$groupName</h1>";
        $reader = new \XMLReader();
        $queryString = http_build_query(array('cliente' => $clientid
            , 'grupo' => $groupName
        ));
        $reader->open("$endpoint/lista_precios.xml?$queryString");

        $nodeName = '';
//        $reader->read();
        while ($nodeName != 'item') {
//            echo "<h1>$nodeName</h1>";
            $reader->read();
            $item = $reader->readOuterXML();
            if (empty(trim($item))) {
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
//            echo number_format(memory_get_usage() / 1024 / 1024, 3) . 'MB <br/>';
            $reader->next();
            if (empty(trim($item))) {
                continue;
//                echo 'XD';
            }
//            echo "#####<hr/>";
            self::importItem($item, $categoryId, $groupName);
            $items++;
//            var_dump($item);
//            var_dump(simplexml_load_string($item));
//            break;
        }

        echo "<h3>$items items importados</h3>";
    }

    static function importItem($item, $categoryId, $groupName) {

//        var_dump($item);

        $itemObject = simplexml_load_string($item);

//        var_dump($itemObject);
        
//        var_dump((string)$itemObject->clave);
        

        $productId = wc_get_product_id_by_sku((string)$itemObject->clave);
//        $productId = wc_get_product_id_by_sku('ttttkzd');
//        var_dump($productId);

        if (!$productId) {
            $productId = wp_insert_post(array(
                'post_title' => (string)$itemObject->descripcion,
                'post_content' => (string)$itemObject->descripcion,
                'post_status' => 'publish',
                'post_type' => "product",
            ));
        }
        wp_set_object_terms($productId, $groupName, 'product_cat');

        wp_set_object_terms($productId, 'simple', 'product_type');


        update_post_meta($productId, '_visibility', 'visible');
        update_post_meta($productId, '_stock_status', 'instock');
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
        update_post_meta($productId, '_sku', (string)$itemObject->clave);
        update_post_meta($productId, '_product_attributes', array());
        update_post_meta($productId, '_sale_price_dates_from', '');
        update_post_meta($productId, '_sale_price_dates_to', '');
        update_post_meta($productId, '_price', (string)$itemObject->precio);
        update_post_meta($productId, '_sold_individually', '');
        update_post_meta($productId, '_manage_stock', 'no');
        update_post_meta($productId, '_backorders', 'no');
        update_post_meta($productId, '_stock', (string)$itemObject->disponible);
    }

    static function test() {
//// Script start
//        $rustart = getrusage();
        self::import();

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

        exit();

        $reader = new \XMLReader();
        $reader->open('http://localhost/~eislas/www.inksumos.com.mx/lista_precios2.xml');

        $reader->next();
        $reader->read();
        $reader->read();
        while ($item = $reader->readOuterXML()) {
            $reader->next();
            if (empty(trim($item))) {
                continue;
//                echo 'XD';
            }
            echo "#####<hr/>";
            var_dump($item);
            var_dump(simplexml_load_string($item));
        }


//        $productos = file_get_contents('http://localhost/~eislas/www.inksumos.com.mx/lista_precios.xml');
////        var_dump($xmlGroups);
////        echo $xmlGroups;
////        echo '<pre>#########' . htmlentities($xmlGroups) . '</pre>';
//        $productos = mb_convert_encoding($productos, 'UTF-8');
//        $xml = simplexml_load_string($productos);
////        echo htmlentities($productos);
//        
//        var_dump($xml);
    }

}
