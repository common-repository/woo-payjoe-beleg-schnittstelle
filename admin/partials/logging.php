<?php

function payjoe_get_log_dir() {
	$upload_dir = wp_upload_dir();
	$payjoe_dir = $upload_dir['basedir'] . '/payjoe/';
	return $payjoe_dir;
}

function payjoe_check_log_dir() {
	$upload_dir = payjoe_get_log_dir();
	if ( ! is_dir( $upload_dir ) ) {
		mkdir( $upload_dir, 0700 );
	}

	payjoe_update_htaccess( $upload_dir );
}

function get_payjoe_log_path() {
	$upload_dir = wp_upload_dir();
	$payjoe_dir = $upload_dir['basedir'] . '/payjoe/';

	if ( ! is_dir( $payjoe_dir ) ) {
		mkdir( $payjoe_dir );
	}
	$date	 = gmdate( 'Y-m-d' );
	$filename = 'payjoe-' . $date . '.log';
	return $payjoe_dir . $filename;
}

function payjoe_update_htaccess(string $path) {
	WP_Filesystem();
	/**
	 * @var WP_Filesystem_Base $wp_filesystem
	 */
	global $wp_filesystem;

	$htaccess = path_join( $path, '.htaccess');
	if( ! $wp_filesystem->exists($htaccess) ) {
		$wp_filesystem->put_contents( $htaccess, "deny from all\n", 0600 );
	}
}

function _payjoe_cleanup_logs() {
	WP_Filesystem();
	/**
	 * @var WP_Filesystem_Base $wp_filesystem
	 */
	global $wp_filesystem;

	$upload_dir = wp_upload_dir();
	$payjoe_dir = path_join( $upload_dir['basedir'], 'payjoe' );

	$entries = $wp_filesystem->dirlist( $payjoe_dir );

	if ( ! $entries ) {
		return;
	}

	// Delete files older than a week
	$lastmoddelete = time() - 7 * 24 * 3600;

	foreach ( $entries as $entry ) {
		$name		= $entry['name'];
		$lastmodunix = $entry['lastmodunix'];
		if ( str_starts_with( $name, 'payjoe-' ) && str_ends_with( $name, '.log' ) ) {
			if ( $lastmodunix < $lastmoddelete ) {
				$wp_filesystem->delete( path_join( $payjoe_dir, $name ) );
			}
		}
	}
}


function payjoe_cleanup_logs() {
    try {
        _payjoe_cleanup_logs();
    } catch ( Throwable $e ) {
        echo 'Exception: ',  esc_html( $e->getMessage() ), "\n";
    }
}
