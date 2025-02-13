<?php
/**
 * Classe per gestire le funzionalità di amministrazione del plugin.
 */
class LZ_Admin {

    /**
     * Costruttore della classe.
     */
    public function __construct() {
        // Aggancia la funzione lzfc_admin_menu al gancio admin_menu.
        add_action( 'admin_menu', array( $this, 'lzfc_admin_menu' ) );

        // Aggancia la funzione lzfc_crea_carta_utente al gancio user_register.
        add_action( 'user_register', array( $this, 'lzfc_crea_carta_utente' ) );
    }

    /**
     * Aggiunge la voce di menu del plugin al menu di amministrazione.
     */
    public function lzfc_admin_menu() {
        add_menu_page(
            'Lidia Zucaro Fidelity Card', // Titolo della pagina
            'Fidelity Card', // Titolo del menu
            'manage_options', // Capability necessaria per accedere alla pagina
            'lzfc-admin-page', // Slug della pagina
            array( $this, 'lzfc_admin_page_content' ), // Funzione per il contenuto della pagina
            'dashicons-awards', // Icona del menu
            25 // Posizione del menu
        );
    }

    /**
     * Definisci il contenuto della pagina di amministrazione del plugin.
     */
    public function lzfc_admin_page_content() {
        // Verifica se l'utente ha i permessi per visualizzare la pagina.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fidelity_cards';

        // Gestisci l'invio del form per aggiungere un timbro.
        if ( isset( $_POST['aggiungi_timbro'] ) && isset( $_POST['user_id'] ) ) {
            $user_id = intval( $_POST['user_id'] );
            $this->aggiungi_timbro( $user_id );
        }

        // Gestisci l'invio del form per aggiornare la scadenza.
        if ( isset( $_POST['aggiorna_scadenza'] ) && isset( $_POST['user_id'] ) ) {
            $user_id = intval( $_POST['user_id'] );
            $nuova_scadenza = $_POST['nuova_scadenza'];
            $this->aggiorna_scadenza( $user_id, $nuova_scadenza );
        }

        // Recupera il numero totale di carte attive.
        $numero_carte_attive = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

        // Visualizza il numero di carte attive.
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Lidia Zucaro Fidelity Card', 'lidia-zucaro-fidelity-card' ) . '</h1>';
        echo '<p>' . sprintf( esc_html__( 'Numero di carte attive: %d', 'lidia-zucaro-fidelity-card' ), $numero_carte_attive ) . '</p>';

        // Form per selezionare l'utente.
        echo '<form method="post" action="">';
        echo '<label for="user_id">' . esc_html__( 'Seleziona un utente:', 'lidia-zucaro-fidelity-card' ) . '</label>';
        wp_dropdown_users( array(
            'name' => 'user_id',
            'id' => 'user_id',
            'show_option_none' => esc_html__( 'Seleziona un utente', 'lidia-zucaro-fidelity-card' ),
        ) );
        echo '<input type="submit" name="visualizza_utente" value="' . esc_attr__( 'Visualizza', 'lidia-zucaro-fidelity-card' ) . '" class="button">';
        echo '</form>';

        // Visualizza i dettagli della carta fedeltà dell'utente selezionato.
        if ( isset( $_POST['visualizza_utente'] ) && isset( $_POST['user_id'] ) ) {
            $user_id = intval( $_POST['user_id'] );
            $this->visualizza_dettagli_carta( $user_id );
        } else {
            // Se non è stato selezionato alcun utente, mostra un messaggio
            echo '<p>' . esc_html__( 'Seleziona un utente per visualizzare i dettagli della sua Fidelity Card.', 'lidia-zucaro-fidelity-card' ) . '</p>';
        }

        // Gestisci la creazione manuale della carta
        if ( isset( $_POST['crea_carta'] ) && isset( $_POST['user_id'] ) ) {
            $user_id = intval( $_POST['user_id'] );
            $this->lzfc_crea_carta_utente( $user_id );

            // Messaggio di successo
            echo '<p>' . esc_html__( 'Fidelity Card creata con successo!', 'lidia-zucaro-fidelity-card' ) . '</p>';

            // Reindirizza per evitare l'invio multiplo del form (opzionale)
            wp_redirect( admin_url( 'admin.php?page=lzfc-admin-page' ) );
            exit;
        }

        echo '</div>';
    }

    /**
     * Visualizza i dettagli della carta fedeltà di un utente.
     *
     * @param int $user_id L'ID dell'utente.
     */
    private function visualizza_dettagli_carta( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fidelity_cards';
        $dati_carta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id ) );
        $user = get_userdata( $user_id ); // Ottieni i dati dell'utente

        if ( $dati_carta ) {
            echo '<h2>' . sprintf( esc_html__( 'Dettagli Fidelity Card per %s %s', 'lidia-zucaro-fidelity-card' ), $user->first_name, $user->last_name ) . '</h2>'; // Utilizza i dati dell'utente
            echo '<p>' . sprintf( esc_html__( 'Timbri collezionati: %d', 'lidia-zucaro-fidelity-card' ), $dati_carta->timbri ) . '</p>';
            echo '<p>' . sprintf( esc_html__( 'Data di scadenza: %s', 'lidia-zucaro-fidelity-card' ), date( 'd/m/Y', strtotime( $dati_carta->data_scadenza ) ) ) . '</p>';

            // Form per aggiungere un timbro
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '">';
            echo '<input type="submit" name="aggiungi_timbro" value="' . esc_attr__( 'Aggiungi Timbro', 'lidia-zucaro-fidelity-card' ) . '" class="button">';
            echo '</form>';

            // Form per aggiornare la scadenza
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '">';
            echo '<label for="nuova_scadenza">' . esc_html__( 'Nuova data di scadenza:', 'lidia-zucaro-fidelity-card' ) . '</label> ';
            echo '<input type="date" name="nuova_scadenza" id="nuova_scadenza" value="' . esc_attr( $dati_carta->data_scadenza ) . '">';
            echo '<input type="submit" name="aggiorna_scadenza" value="' . esc_attr__( 'Aggiorna Scadenza', 'lidia-zucaro-fidelity-card' ) . '" class="button">';
            echo '</form>';

        } else {
            // L'utente non ha una carta: mostra un form per crearne una
            echo '<p>' . esc_html__( 'Questo utente non ha ancora una Fidelity Card.', 'lidia-zucaro-fidelity-card' ) . '</p>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '">';
            echo '<input type="submit" name="crea_carta" value="' . esc_attr__( 'Crea Fidelity Card', 'lidia-zucaro-fidelity-card' ) . '" class="button">';
            echo '</form>';
        }
    }

    /**
     * Crea una nuova carta fedeltà per l'utente.
     *
     * @param int $user_id L'ID dell'utente.
     */
    public function lzfc_crea_carta_utente( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fidelity_cards';
        $data_scadenza = date( 'Y-m-d', strtotime( '+2 months' ) ); // Imposta la scadenza a 2 mesi da oggi
        $wpdb->insert( 
            $table_name, 
            array( 
                'user_id' => $user_id, 
                'timbri' => 0,
                'data_scadenza' => $data_scadenza 
            )
        );
    }
    

    /**
     * Aggiungi un timbro alla carta fedeltà di un utente.
     *
     * @param int $user_id L'ID dell'utente.
     */
    private function aggiungi_timbro( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fidelity_cards';

        // Ottieni i dati della carta fedeltà dell'utente
        $dati_carta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id ) );

        // Controlla se l'utente ha già 9 timbri
        if ( $dati_carta->timbri == 9 ) {
            // Logica per il decimo timbro (es. resetta i timbri a 0 e invia una notifica)
            $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET timbri = 0 WHERE user_id = %d", $user_id ) );
            // TODO: Implementa la logica per inviare una notifica all'utente (es. email, notifica sul sito)
            
        } else {
            // Aggiungi un timbro
            $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET timbri = timbri + 1 WHERE user_id = %d", $user_id ) );
        }

        // Reindirizza alla pagina per evitare l'invio multiplo del form
        wp_redirect( admin_url( 'admin.php?page=lzfc-admin-page' ) );
        exit;
    }
    
    /**
     * Aggiorna la data di scadenza della carta fedeltà di un utente.
     *
     * @param int $user_id L'ID dell'utente.
     * @param date $nuova_scadenza La nuova data di scadenza in formato YYYY-MM-DD.
     */
    private function aggiorna_scadenza( $user_id, $nuova_scadenza ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fidelity_cards';
        $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET data_scadenza = %s WHERE user_id = %d", $nuova_scadenza, $user_id ) );
    
        // Reindirizza alla pagina per evitare l'invio multiplo del form
        wp_redirect( admin_url( 'admin.php?page=lzfc-admin-page' ) );
        exit;
    }
}
