<?php
/**
 * Plugin Name: Lidia Zucaro Fidelity Card
 * Plugin URI:  https://example.com/lidia-zucaro-fidelity-card/
 * Description: Un plugin per gestire le carte fedeltà del salone Lidia Zucaro Parrucchieri.
 * Version:     1.0
 * Author:      Tuo Nome
 * Author URI:  https://example.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lidia-zucaro-fidelity-card
 * Domain Path: /languages
 */

// Evitiamo l'accesso diretto al file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definiamo la costante per il percorso base del plugin.
define( 'LZFC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Includiamo il file della classe per l'amministrazione.
require_once LZFC_PLUGIN_PATH . 'includes/admin/class-lz-admin.php';

// Creiamo la tabella del database all'attivazione del plugin.
function lzfc_crea_tabella_fidelity() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fidelity_cards';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        timbri int(11) NOT NULL DEFAULT 0,
        data_scadenza date NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'lzfc_crea_tabella_fidelity' );

// Inizializziamo la classe per l'amministrazione.
function lzfc_admin_init() {
    new LZ_Admin();
}
add_action( 'plugins_loaded', 'lzfc_admin_init' );

/**
 * Shortcode per visualizzare la carta fedeltà dell'utente.
 *
 * @return string HTML per la carta fedeltà.
 */
function lzfc_visualizza_carta_utente() {
    // Verifica se l'utente è loggato
    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'Devi effettuare il login per visualizzare la tua Fidelity Card.', 'lidia-zucaro-fidelity-card' ) . '</p>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'fidelity_cards';
    $user_id = get_current_user_id();
    $dati_carta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id ) );

    // Inizio HTML della carta fedeltà
    $output = '<div class="lzfc-fidelity-card">';

    // Nome utente
    $user = get_userdata( $user_id );
    $output .= '<div class="lzfc-user-name">' . esc_html( $user->display_name ) . '</div>';
    
    // Griglia dei timbri
    $output .= '<div class="lzfc-timbri-grid">';
    for ($i = 1; $i <= 10; $i++) {
        $output .= '<div class="lzfc-timbro' . ($i <= $dati_carta->timbri ? ' lzfc-timbro-accumulato' : '') . '">';
            if ($i <= $dati_carta->timbri && $i != 10) {
                // Visualizza il logo del timbro
                $output .= '<img src="' . plugin_dir_url(__FILE__) . 'assets/img/logo-timbro.png" alt="Timbro">';
            } elseif ($i == 10) {
                // Visualizza "Omaggio" nel decimo quadrato
                $output .= '<span class="lzfc-omaggio">Omaggio</span>';
            } else {
                // Visualizza il numero del quadrato
                $output .= '<span class="lzfc-numero-timbro">' . $i . '</span>'; 
            }
        $output .= '</div>';
    }
    $output .= '</div>'; // Fine lzfc-timbri-grid

    return $output;
}
add_shortcode( 'lzfc_fidelity_card', 'lzfc_visualizza_carta_utente' );

