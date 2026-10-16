<?php
/**
 * Plugin Name: Propovoice Banrisul Boletos
 * Description: Integra invoices(Faturas) do PROPOVOICE com a API de Cobrança Banrisul para geração de boletos.
 * Version: 0.2.0
 * Requires PHP: 8.2
 * Author: Bruno Elias Ferreira
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PBB_FILE', __FILE__);
define('PBB_DIR', plugin_dir_path(__FILE__));
define('PBB_URL', plugin_dir_url(__FILE__));
define('PBB_VERSION', '0.1.0');

require_once PBB_DIR . 'includes/class-pbb-activator.php';
require_once PBB_DIR . 'includes/class-pbb-admin.php';
require_once PBB_DIR . 'includes/class-pbb-propovoice-api.php';
require_once PBB_DIR . 'includes/class-pbb-banrisul-api.php';
require_once PBB_DIR . 'includes/class-pbb-boletos-repository.php';

register_activation_hook(__FILE__, ['PBB_Activator', 'activate']);

add_action('plugins_loaded', function () {
    PBB_Admin::init();
});