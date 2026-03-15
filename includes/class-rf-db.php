<?php
/**
 * RazorForms — Database Layer
 *
 * Custom wp_rf_submissions table install, CRUD, and stats.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_DB {

    public static function submissions_table() {
        global $wpdb;
        return $wpdb->prefix . 'rf_submissions';
    }

    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table   = self::submissions_table();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id           BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            razorpay_id       VARCHAR(120)        NOT NULL DEFAULT '',
            order_ref         VARCHAR(60)         NOT NULL DEFAULT '',
            status            VARCHAR(40)         NOT NULL DEFAULT 'pending',
            amount            DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
            currency          VARCHAR(10)         NOT NULL DEFAULT 'INR',
            field_data        LONGTEXT            NOT NULL,
            ip_address        VARCHAR(60)         NOT NULL DEFAULT '',
            user_agent        VARCHAR(500)        NOT NULL DEFAULT '',
            created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY       (id),
            KEY idx_form      (form_id),
            KEY idx_rzp       (razorpay_id),
            KEY idx_status    (status),
            KEY idx_created   (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        update_option( 'rf_db_version', RF_VERSION );
    }

    public static function insert_submission( $data ) {
        global $wpdb;

        $defaults = array(
            'form_id'    => 0,
            'razorpay_id'=> '',
            'order_ref'  => 'RF-' . strtoupper( wp_generate_password( 8, false ) ),
            'status'     => 'pending',
            'amount'     => 0,
            'currency'   => 'INR',
            'field_data' => '{}',
            'ip_address' => self::get_ip(),
            'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
        );
        $row = wp_parse_args( $data, $defaults );

        $wpdb->insert( self::submissions_table(), array(
            'form_id'    => absint( $row['form_id'] ),
            'razorpay_id'=> sanitize_text_field( $row['razorpay_id'] ),
            'order_ref'  => sanitize_text_field( $row['order_ref'] ),
            'status'     => sanitize_text_field( $row['status'] ),
            'amount'     => floatval( $row['amount'] ),
            'currency'   => sanitize_text_field( $row['currency'] ),
            'field_data' => wp_json_encode( $row['field_data'] ),
            'ip_address' => sanitize_text_field( $row['ip_address'] ),
            'user_agent' => sanitize_text_field( $row['user_agent'] ),
        ), array('%d','%s','%s','%s','%f','%s','%s','%s','%s') );

        return $wpdb->insert_id;
    }

    public static function update_submission_status( $razorpay_id, $status ) {
        global $wpdb;
        $wpdb->update(
            self::submissions_table(),
            array( 'status' => sanitize_text_field($status) ),
            array( 'razorpay_id' => sanitize_text_field($razorpay_id) ),
            array('%s'), array('%s')
        );
    }

    public static function get_submissions( $args = array() ) {
        global $wpdb;
        $t = self::submissions_table();
        $d = array( 'per_page'=>20, 'page'=>1, 'form_id'=>0, 'status'=>'', 'search'=>'', 'orderby'=>'created_at', 'order'=>'DESC' );
        $a = wp_parse_args( $args, $d );

        $where = array('1=1'); $vals = array();
        if ( $a['form_id'] ) { $where[] = 's.form_id = %d'; $vals[] = $a['form_id']; }
        if ( $a['status']  ) { $where[] = 's.status = %s';  $vals[] = $a['status'];  }
        if ( $a['search']  ) {
            $like = '%' . $wpdb->esc_like($a['search']) . '%';
            $where[] = '(s.razorpay_id LIKE %s OR s.field_data LIKE %s OR p.post_title LIKE %s)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like;
        }

        $ws  = implode(' AND ', $where);
        $ob  = in_array($a['orderby'], array('created_at','amount','status','id')) ? $a['orderby'] : 'created_at';
        $ord = strtoupper($a['order']) === 'ASC' ? 'ASC' : 'DESC';
        $off = (absint($a['page'])-1) * absint($a['per_page']);

        $sql = "SELECT s.*, p.post_title as form_name FROM {$t} s
                LEFT JOIN {$wpdb->posts} p ON p.ID = s.form_id
                WHERE {$ws} ORDER BY s.{$ob} {$ord} LIMIT %d OFFSET %d";
        $vals[] = absint($a['per_page']); $vals[] = $off;
        return $wpdb->get_results( $wpdb->prepare($sql, $vals) );
    }

    public static function count_submissions( $args = array() ) {
        global $wpdb;
        $t = self::submissions_table();
        $where = array('1=1'); $vals = array();
        if ( !empty($args['form_id']) ) { $where[] = 'form_id = %d'; $vals[] = $args['form_id']; }
        if ( !empty($args['status'])  ) { $where[] = 'status = %s';  $vals[] = $args['status'];  }
        $ws  = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$t} WHERE {$ws}";
        return (int) ( empty($vals) ? $wpdb->get_var($sql) : $wpdb->get_var($wpdb->prepare($sql,$vals)) );
    }

    public static function get_submission( $id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, p.post_title as form_name FROM " . self::submissions_table() . " s
             LEFT JOIN {$wpdb->posts} p ON p.ID = s.form_id
             WHERE s.id = %d", absint($id)
        ));
        if ( $row ) $row->field_data = json_decode( $row->field_data, true ) ?: array();
        return $row;
    }

    public static function get_stats( $form_id = 0 ) {
        global $wpdb;
        $t = self::submissions_table();
        $w = $form_id ? $wpdb->prepare("AND form_id=%d", $form_id) : '';
        return array(
            'total'    => (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='paid' {$w}"),
            'revenue'  => (float) $wpdb->get_var("SELECT SUM(amount) FROM {$t} WHERE status='paid' {$w}"),
            'month'    => (float) $wpdb->get_var("SELECT SUM(amount) FROM {$t} WHERE status='paid' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) {$w}"),
            'failed'   => (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='failed' {$w}"),
            'pending'  => (int)   $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='pending' {$w}"),
        );
    }

    private static function get_ip() {
        foreach ( array('HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR') as $k ) {
            if ( !empty($_SERVER[$k]) ) return sanitize_text_field( explode(',',$_SERVER[$k])[0] );
        }
        return '';
    }
}
