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

    static function config() {
        $endpoint = filter_input(INPUT_POST, 'endpoint');
        $clientid = filter_input(INPUT_POST, 'clientid');
        $submit = filter_input(INPUT_POST, 'submit');
        if(!empty($submit)){
            update_option('ink_catalog_endpoint', $endpoint);
            update_option('ink_client_id', $clientid);
        }

        $endpoint = get_option('ink_catalog_endpoint', 'http://www.grupocva.com/catalogo_clientes_xml');
        $clientid = get_option('ink_client_id', '26813')
        ?>
        <form method="POST">
            <div class="wrap">
                <h1><?php _e('Configuración de acceso a catalogos') ?></h1>

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

            </div>
        </form>
        <?php
    }

    static function groups() {
        
    }

}
