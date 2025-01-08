<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Dibsy_Apple_Pay_Domain_Association
 */
class WC_Dibsy_Apple_Pay_Domain_Association {
    
    /**
     * Method __construct
     *
     * @return void
     */
    public function __construct() {
        add_action( 'init',             array( $this, 'add_domain_association_rewrite_rule' ) );
        add_filter( 'query_vars',       array( $this, 'whitelist_domain_association_query_param' ), 10, 1 );
        add_action( 'parse_request',    array( $this, 'parse_domain_association_request' ), 10, 1 );
    }
    
    /**
     * Method add_domain_association_rewrite_rule
     *
     * @return void
     */
    public function add_domain_association_rewrite_rule() {
        $regex    = '^\.well-known\/apple-developer-merchantid-domain-association$';
        $redirect = 'index.php?apple-developer-merchantid-domain-association=wc-dibsy-apple-pay-express';
        
        add_rewrite_rule( $regex, $redirect, 'top' );
    }
    
    /**
     * Method whitelist_domain_association_query_param
     *
     * @param array $query_vars
     *
     * @return array
     */
    public function whitelist_domain_association_query_param( $query_vars ) {
        $query_vars[] = 'apple-developer-merchantid-domain-association';
        return $query_vars;
    }
    
    /**
     * Method parse_domain_association_request
     *
     * @param WP_Query $wp
     *
     * @return void
     */
    public function parse_domain_association_request( $wp ) {
        if (
            ! isset( $wp->query_vars['apple-developer-merchantid-domain-association'] ) ||
            'wc-dibsy-apple-pay-express' !== $wp->query_vars['apple-developer-merchantid-domain-association']
        ) {
            return;
        }


        $path = plugin_dir_path( WC_DIBSY_MAIN_FILE ) . 'assets/apple-developer-merchantid-domain-association';
        header( 'Content-Type: application/octet-stream' );
        header("Content-Disposition: attachment; filename=apple-developer-merchantid-domain-association");
        echo esc_html( file_get_contents( $path ) );
        exit;
    }
}

( new WC_Dibsy_Apple_Pay_Domain_Association() );