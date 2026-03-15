<?php
/**
 * RazorForms — Form Templates
 *
 * 5 pre-built form templates: Agency, E-commerce, Donation, Event, Course.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Templates {

    public static function all() {
        return array(

            'agency' => array(
                'name'  => 'Services / Agency',
                'desc'  => 'Multi-service selector with custom pricing per service',
                'icon'  => '🏢',
                'color' => '#295cff',
                'meta'  => array(
                    'title'       => '',
                    'subtitle'    => '',
                    'description' => '',
                    'brand'       => '',
                    'color'       => '#295cff',
                    'btn_label'   => 'Pay Now',
                    'layout'      => 'split',
                    'price_mode'  => 'items',
                    'items'       => array(
                        array('name'=>'','desc'=>'','price'=>'','custom_price'=>false),
                    ),
                    'fields' => array(),
                    'show_trust_badges' => 1,
                ),
            ),

            'ecommerce' => array(
                'name'  => 'Product / E-commerce',
                'desc'  => 'Fixed-price product payment with variants',
                'icon'  => '🛒',
                'color' => '#f97316',
                'meta'  => array(
                    'title'       => 'Complete Your Order',
                    'subtitle'    => 'Fast, secure checkout',
                    'description' => 'You\'re one step away. Fill in your details and complete payment securely via Razorpay.',
                    'brand'       => 'Your Store',
                    'color'       => '#f97316',
                    'btn_label'   => 'Pay & Order Now',
                    'layout'      => 'centered',
                    'price_mode'  => 'fixed',
                    'fixed_price' => 1499,
                    'price_label' => 'one-time',
                    'fields' => array(
                        array('type'=>'text',   'label'=>'Full Name',      'placeholder'=>'Your name',  'required'=>true, 'options'=>'','width'=>'half'),
                        array('type'=>'text',   'label'=>'Shipping Address','placeholder'=>'Street, City, PIN','required'=>true,'options'=>'','width'=>'full'),
                        array('type'=>'select', 'label'=>'Size / Variant', 'placeholder'=>'',           'required'=>false,'options'=>"Small\nMedium\nLarge\nXL",'width'=>'half'),
                        array('type'=>'number', 'label'=>'Quantity',       'placeholder'=>'1',          'required'=>false,'options'=>'','width'=>'half'),
                        array('type'=>'textarea','label'=>'Order Notes',   'placeholder'=>'Any special instructions…','required'=>false,'options'=>'','width'=>'full'),
                    ),
                    'show_trust_badges' => 1,
                ),
            ),

            'donation' => array(
                'name'  => 'Donation / NGO',
                'desc'  => 'Client enters their own amount with quick-pick presets',
                'icon'  => '❤️',
                'color' => '#ef4444',
                'meta'  => array(
                    'title'       => 'Make a Difference Today',
                    'subtitle'    => 'Every rupee counts',
                    'description' => 'Your donation directly supports our mission. We are transparent about how every rupee is used.',
                    'brand'       => 'Your Foundation',
                    'color'       => '#ef4444',
                    'btn_label'   => 'Donate Now',
                    'layout'      => 'centered',
                    'price_mode'  => 'variable',
                    'min_price'   => 100,
                    'amount_label'=> 'Donation Amount (₹)',
                    'suggested'   => '500, 1000, 2500, 5000',
                    'fields' => array(
                        array('type'=>'text',  'label'=>'Full Name',   'placeholder'=>'Your name','required'=>true, 'options'=>'','width'=>'half'),
                        array('type'=>'select','label'=>'Donation For', 'placeholder'=>'',        'required'=>false,'options'=>"Education\nFood & Nutrition\nHealthcare\nWhere needed most",'width'=>'half'),
                        array('type'=>'textarea','label'=>'Message',   'placeholder'=>'Leave an encouraging message (optional)…','required'=>false,'options'=>'','width'=>'full'),
                    ),
                    'show_trust_badges' => 1,
                ),
            ),

            'event' => array(
                'name'  => 'Event / Registration',
                'desc'  => 'Event ticket payment with attendee details',
                'icon'  => '🎟️',
                'color' => '#8b5cf6',
                'meta'  => array(
                    'title'       => 'Secure Your Seat',
                    'subtitle'    => 'Limited spots available',
                    'description' => 'Register now and receive your confirmation email instantly. Bring the email to the event as your ticket.',
                    'brand'       => 'Your Event',
                    'color'       => '#8b5cf6',
                    'btn_label'   => 'Register & Pay',
                    'layout'      => 'split',
                    'price_mode'  => 'items',
                    'items' => array(
                        array('name'=>'General Ticket', 'desc'=>'Standard entry','price'=>999, 'custom_price'=>false),
                        array('name'=>'VIP Ticket',     'desc'=>'Front row + networking dinner','price'=>2999,'custom_price'=>false),
                        array('name'=>'Group (5 seats)','desc'=>'Best value for teams','price'=>3999,'custom_price'=>false),
                    ),
                    'fields' => array(
                        array('type'=>'text',  'label'=>'Organisation / Company','placeholder'=>'Where are you from?','required'=>false,'options'=>'','width'=>'half'),
                        array('type'=>'select','label'=>'Dietary Preference',   'placeholder'=>'',  'required'=>false,'options'=>"No preference\nVegetarian\nVegan\nJain",'width'=>'half'),
                        array('type'=>'number','label'=>'Number of Attendees',  'placeholder'=>'1', 'required'=>true, 'options'=>'','width'=>'half'),
                        array('type'=>'text',  'label'=>'Referral Code',        'placeholder'=>'Optional','required'=>false,'options'=>'','width'=>'half'),
                    ),
                    'show_trust_badges' => 0,
                ),
            ),

            'course' => array(
                'name'  => 'Course / Education',
                'desc'  => 'Course or workshop enrolment with batch selection',
                'icon'  => '🎓',
                'color' => '#10b981',
                'meta'  => array(
                    'title'       => 'Enrol Now',
                    'subtitle'    => 'Invest in yourself today',
                    'description' => 'Join hundreds of students who have already transformed their skills. Payment is 100% secure.',
                    'brand'       => 'Your Academy',
                    'color'       => '#10b981',
                    'btn_label'   => 'Enrol & Pay',
                    'layout'      => 'split',
                    'price_mode'  => 'items',
                    'items' => array(
                        array('name'=>'Starter Plan','desc'=>'Self-paced + recordings','price'=>4999, 'custom_price'=>false),
                        array('name'=>'Pro Plan',    'desc'=>'Live sessions + mentorship','price'=>9999,'custom_price'=>false),
                        array('name'=>'1-on-1 Coaching','desc'=>'Private sessions, custom plan','price'=>19999,'custom_price'=>false),
                    ),
                    'fields' => array(
                        array('type'=>'select',  'label'=>'Batch / Start Date',  'placeholder'=>'',    'required'=>true, 'options'=>"January 2025\nMarch 2025\nJune 2025",'width'=>'half'),
                        array('type'=>'text',    'label'=>'Current Qualification','placeholder'=>'e.g. B.Tech, MBA','required'=>false,'options'=>'','width'=>'half'),
                        array('type'=>'textarea','label'=>'Your Goal',           'placeholder'=>'What do you want to achieve from this course?','required'=>false,'options'=>'','width'=>'full'),
                    ),
                    'show_trust_badges' => 1,
                ),
            ),
        );
    }

    public static function get( $id ) {
        $all = self::all();
        return $all[$id] ?? null;
    }
}
