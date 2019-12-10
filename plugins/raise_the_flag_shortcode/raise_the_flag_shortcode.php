<?php
  /*
  Plugin Name: Raise the flag Shortcode
  Plugin URI: https://raisetheflagforautism.com
  Description: Allow raise the flag form to be used as shortcodes in Wordpress pages and posts
  Version: 1.0
  Author: JMA Consulting
  Author URI: http://jmaconsulting.biz
  License: AGPL3
  */
  add_filter('civicrm_shortcode_preprocess_atts', array('CiviCRM_For_WordPress_Shortcodes_RaiseTheFlag', 'civicrm_shortcode_preprocess_atts'), 10, 2);
// FIXME: Uncomment to allow support for multiple shortcodes on pages.
//add_filter('civicrm_shortcode_get_data', array('CiviCRM_For_WordPress_Shortcodes_Grant', 'civicrm_shortcode_get_data'), 10, 3);
  /**
   * Define CiviCRM_For_WordPress_Shortcodes Class
   */
  class CiviCRM_For_WordPress_Shortcodes_RaiseTheFlag {
    function civicrm_shortcode_preprocess_atts($args, $shortcode_atts) {
      if ($shortcode_atts['component'] == 'raise_the_flag') {
        $args['q'] = 'civicrm/add-event';
        return $args;
      }
    }
    // FIXME: Seems like multiple shortcodes don't work on a single page. Also,
    function civicrm_shortcode_get_data($data, $atts, $args) {
      if ($atts['component'] == 'raise_the_flag') {
        $data = [
          'title' => ts('Raise the Flag'),
          'text' => 'Raise the Flag form',
        ];
        return $data;
      }
    }
  }