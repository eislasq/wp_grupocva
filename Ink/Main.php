<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ink;

/**
 * Description of Main
 *
 * @author eislas
 */
class Main {

    const DEFAULT_ENDPOINT = 'http://www.grupocva.com/catalogo_clientes_xml';

    private static $DEFAULT_GROUPS = array('CONSUMIBLES'
        , 'IMPRESORAS'
        , 'MULTIFUNCIONALES'
        , 'SCANNER'
        , 'IMPRESORA DE AMPLIO FORMATO (PLOTTER)'
    );

    static function config() {
        $endpoint = filter_input(INPUT_POST, 'endpoint');
        $clientid = filter_input(INPUT_POST, 'clientid');
        $submit = filter_input(INPUT_POST, 'submit');
        if (!empty($submit)) {
            update_option('ink_catalog_endpoint', $endpoint);
            update_option('ink_client_id', $clientid);
        }

        $endpoint = get_option('ink_catalog_endpoint', SELF::DEFAULT_ENDPOINT);
        $clientid = get_option('ink_client_id', '26813')
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de acceso a catalogos') ?></h1>
            <form method="POST">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="endpoint"><?php _e('Catalogo Endpoint') ?></label></th>
                        <td><input name="endpoint" type="text" id="blogname" value="<?php echo $endpoint; ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="clientid"><?php _e('Client Id') ?></label></th>
                        <td><input name="clientid" type="text" id="blogname" value="<?php echo $clientid; ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

        </div>
        <?php
    }

    static function groups() {

        /**
         * http://www.grupocva.com/catalogo_clientes_xml/grupos.xml
         * Grupos predeterminados:
         * 
         * Consumibles 
         * Impresoras
         * Multifuncionales
         * Scanner
         * Impresoras de amplio formato
         */
        $groups = filter_input(INPUT_POST, 'groups', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
        $submit = filter_input(INPUT_POST, 'submit');
        if (!empty($submit)) {
            update_option('ink_catalog_groups', $groups);
        }


        $endpoint = get_option('ink_catalog_endpoint', SELF::DEFAULT_ENDPOINT);
        $groups = get_option('ink_catalog_groups', self::$DEFAULT_GROUPS);

//        var_dump($groups);

        $xmlGroups = file_get_contents($endpoint . '/grupos.xml');
//        var_dump($xmlGroups);
//        echo $xmlGroups;
//        echo '<pre>#########' . htmlentities($xmlGroups) . '</pre>';
        $xmlGroups = mb_convert_encoding($xmlGroups, 'UTF-8');
        $xml = simplexml_load_string($xmlGroups);
//        var_dump($xml);
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de grupos a importar') ?></h1>
            <form method="POST">


                <ul>
                    <?php foreach ($xml->grupo as $grupo) : ?>
                        <li>
                            <label>
                                <input type="checkbox" name="groups[]" <?php echo in_array($grupo, $groups) ? 'checked' : ''; ?> value="<?php echo $grupo; ?>"/>
                                <?php echo $grupo; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php submit_button(); ?>
            </form>

        </div>
        <?php
    }

}
