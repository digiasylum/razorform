<?php
/**
 * RazorForms — Webhook Handler
 *
 * REST endpoint for Razorpay server-side payment event callbacks.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Webhook {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        register_rest_route( 'razorforms/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public static function handle( WP_REST_Request $req ) {
        $secret  = get_option( 'rf_webhook_secret', '' );
        $payload = $req->get_body();

        if ( ! empty( $secret ) ) {
            $sig      = $req->get_header( 'x-razorpay-signature' );
            $expected = hash_hmac( 'sha256', $payload, $secret );
            if ( ! hash_equals( $expected, (string) $sig ) ) {
                return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 403 );
            }
        }

        $data  = json_decode( $payload, true );
        $event = $data['event'] ?? '';

        switch ( $event ) {
            case 'payment.captured':
                RF_DB::update_submission_status(
                    $data['payload']['payment']['entity']['id'] ?? '', 'paid'
                );
                break;

            case 'payment.failed':
                $p = $data['payload']['payment']['entity'];
                self::ensure_record( $p, 'failed' );
                break;

            case 'refund.created':
            case 'refund.processed':
                RF_DB::update_submission_status(
                    $data['payload']['refund']['entity']['payment_id'] ?? '', 'refunded'
                );
                break;
        }

        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    private static function ensure_record( $p, $status ) {
        global $wpdb;
        $rzp_id = sanitize_text_field( $p['id'] ?? '' );
        if ( empty( $rzp_id ) ) return;

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . RF_DB::submissions_table() . " WHERE razorpay_id = %s",
            $rzp_id
        ) );

        if ( $exists ) {
            RF_DB::update_submission_status( $rzp_id, $status );
        } else {
            $notes = $p['notes'] ?? array();
            RF_DB::insert_submission( array(
                'form_id'    => absint( $notes['form_id'] ?? 0 ),
                'razorpay_id'=> $rzp_id,
                'status'     => $status,
                'amount'     => floatval( ( $p['amount'] ?? 0 ) / 100 ),
                'currency'   => sanitize_text_field( $p['currency'] ?? 'INR' ),
                'field_data' => array(
                    '_name'  => sanitize_text_field( $p['email']   ?? '' ),
                    '_email' => sanitize_email(      $p['email']   ?? '' ),
                    '_phone' => sanitize_text_field( $p['contact'] ?? '' ),
                ),
            ) );
        }
    }
}
