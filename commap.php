<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              jopfr.e
 * @since             1.0.0
 * @package           Commap
 *
 * @wordpress-plugin
 * Plugin Name:       Commap
 * Plugin URI:        jopf.re
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            jopfre
 * Author URI:        jopfr.e
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       commap
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'COMMAP_VERSION', '1.0.0' );

// Register Comment Meta Fields
add_action( 'rest_api_init', function () {

	register_rest_field( 'comment', 'latitude', array(
		'get_callback' => function( $comment_arr ) {
			$comment_obj = get_comment( $comment_arr['id'] );
			$latitude = get_comment_meta( $comment_arr['id'], 'latitude' )[0];
			return (float) $latitude;
		},
		'update_callback' => function( $latitude, $comment_obj ) {
			$ret = update_comment_meta( $comment_obj->comment_ID, 'latitude', $latitude );
			if ( false === $ret ) {
				return new WP_Error(
					'rest_comment_latitude_failed',
					__( 'Failed to update comment latitude.' ),
					array( 'status' => 500 )
				);
			}
			return true;
		},
		'schema' => array(
			'description' => __( 'Comment latitude.' ),
			'type'        => 'float'
		),
	) );

	register_rest_field( 'comment', 'longitude', array(
		'get_callback' => function( $comment_arr ) {
			$comment_obj = get_comment( $comment_arr['id'] );
			$longitude = get_comment_meta( $comment_arr['id'], 'longitude' )[0];
			return (float) $longitude;
		},
		'update_callback' => function( $longitude, $comment_obj ) {
			$ret = update_comment_meta( $comment_obj->comment_ID, 'longitude', $longitude );
			if ( false === $ret ) {
				return new WP_Error(
					'rest_comment_longitude_failed',
					__( 'Failed to update comment longitude.' ),
					array( 'status' => 500 )
				);
			}
			return true;
		},
		'schema' => array(
			'description' => __( 'Comment latitude.' ),
			'type'        => 'float'
		),
	) );

} );

require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;







add_action('transition_comment_status', 'my_approve_comment_callback', 10, 3);
function my_approve_comment_callback($new_status, $old_status, $comment) {
    if($old_status != $new_status) {
        if($new_status == 'approved') {

        	$comment_meta = get_comment_meta( $comment->comment_ID );
					file_put_contents ( __DIR__.'/log', print_r($comment_meta['longitude'], true));

        	$serviceAccount = ServiceAccount::fromJsonFile(__DIR__.'/google-service-account.json');

					$firebase = (new Factory)
					    ->withServiceAccount($serviceAccount)
					    ->withDatabaseUri('https://refill-free.firebaseio.com')
					    ->create();

					$database = $firebase->getDatabase();

          $newMarker = $database
					  ->getReference('markers')
					  ->push([
					    'title' => $comment->comment_content,
					    'coordinates' => [
					    	'latitude' => (float) $comment_meta['latitude'][0],
					    	'longitude' => (float) $comment_meta['longitude'][0]
					    ]
					  ]);

        }
    }
}

// function filter_handler( $approved , $commentdata ){ 

// 	echo var_dump($commentdata);
//  }

// add_filter( 'pre_comment_approved' , 'filter_handler' , '99', 2 ); ?>