<?php
/**
 * 
 * 	Plugin Name: ACF admin improve search post results
 * 	Version: 1.0
 * 	Wordpress Version: 4.2
 * 	Plugin URI: 
 * 	Description: Order posts search results by post_title relevance in Advanced Custom Fields in fields of types Post Object, Relationship or Page Link.
 * 	Author: Renato Sakamoto
 * 	Author URI: http://renatosakamoto.com.br
 *
 * Text Domain: acf-aisps
 * Domain Path: /languages
 */


class Acf_ImproveAdminPostSearch{

    const CAPAB_MANAGE = 'manage_options';

    static function getFieldTypes(){
        return array(
            'post_object',
            'relationship',
            'page_link'
        );
    }

    static function setupPlugin(){
        add_action ( 'plugins_loaded', array(__CLASS__, 'loadTextdomain' ));
        add_action ( 'pre_get_posts', array(__CLASS__, 'custom_acf_post_filter') );

        $fieldTypes=self::getFieldTypes();
        foreach($fieldTypes as $field_type){
            add_action ( 'acf/render_field_settings/type='.$field_type,   array(__CLASS__, 'render_field_settings'), 20, 1);    
        }
    }

    function loadTextdomain() {
        load_plugin_textdomain( 'Acf_ImproveAdminPostSearch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
    }

    static function render_field_settings($field){

        acf_render_field_setting( $field, array(
            'label'         => __('Order results by most relevant post_title?','acf-abspr'),
            'instructions'  => '',
            'type'          => 'radio',
            'name'          => 'Acf_ImproveAdminPostSearch-value',
            'choices'       => array(
                0               => __("No",'acf'),
                1               => __("Yes",'acf'),
            ),
            'layout'    =>  'horizontal',
        ));
    }



    static function custom_acf_post_filter($wp_query){
        $fieldTypes=self::getFieldTypes();

        if(strlen($_POST['s']) && preg_match('/^acf\/fields\/([a-z0-9\_\-]+)\/query$/', $_POST['action'], $m)){

            $fieldType=$m[1];

            if(in_array($fieldType, $fieldTypes) && preg_match('/^field\_[a-z0-9]+/', $_POST['field_key']) && $wp_query->query['s']==$_POST['s']){

                $field_object=get_field_object($_POST['field_key']);

                if($field_object && $field_object['Acf_ImproveAdminPostSearch-value']){
                    add_filter('posts_orderby', array(__CLASS__, 'custom_acf_post_filter_orderby') );
                }
            }
        }
    }

    static function custom_acf_post_filter_orderby($query){
        global $wpdb;
        $query='
    CASE 
        WHEN ('.$wpdb->posts.'.post_title = "'.esc_sql($_POST['s']).'") THEN 10
        WHEN ('.$wpdb->posts.'.post_title LIKE "'.$wpdb->esc_like($_POST['s']).'%") THEN 5
        WHEN ('.$wpdb->posts.'.post_title LIKE "%'.$wpdb->esc_like($_POST['s']).'%") THEN 2
        ELSE 1 
    END DESC,
    '.$wpdb->posts.'.post_title ASC' ;
        remove_filter('posts_orderby', array(__CLASS__, 'custom_acf_post_filter_orderby') );
        return $query;
    }
    
}

Acf_ImproveAdminPostSearch::setupPlugin();