<?php
/*
Plugin Name: WP Image Optimizer
Plugin URI:
Description: Optimize image files
Version: 0.0.1
Author: Yuta Haga <yutahaga7@gmail.com>
Author URI: https://github.com/yutahaga
License: MIT
*/
?>
<?php
/*  Copyright 2017 Yuta Haga (email : yutahaga7@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
namespace Inbit\Plugins;

class ImageOptimizer {
	const IMAGE_QUALITY = 80;
	const OPTIMIZERS = [
		'jpeg' => 'cjpeg',
		'png' => 'pngquant',
		'gif' => 'gifsicle',
	];

	private $enables;

	/**
	 * Singleton class instance.
	 *
	 * @return ImageOptimizer
	 */
	public static function get_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
			$instance->enables = [];
			$instance->check_optimizer();
			add_action( 'wp_handle_upload', [ $instance, 'handle_upload' ] );
		}

		return $instance;
	}

	/**
	 * Check the requirements is installed
	 *
	 * @return void
	 */
	private function check_optimizer() {
		foreach ( self::OPTIMIZERS as $type => $cmd ) {
			$is_exists = shell_exec( "which ${cmd}" );
			$this->enables[ $type ] = $is_exists ? true : false;
		}
	}

	/**
	 * Upload handler
	 *
	 * @return void
	 */
	public static function handle_upload( $file ) {
		if ( 0 !== strpos( $file['type'], 'image/' ) ) {
			return;
		}

		$instance = self::get_instance();

		$tmp_file = $file['file'] . '.optimized';

		switch ( $file['type'] ) {
			case 'image/jpeg':
				if ( $instance->enables['jpeg'] ) {
					shell_exec( 'cjpeg -quality ' . self::IMAGE_QUALITY . ' -progressive -outfile \'' . $tmp_file . '\' \'' . $file['file'] . '\'' );
					rename( $tmp_file, $file['file'] );
				}
				break;
			case 'image/png':
				if ( $instance->enables['png'] ) {
					$quality = self::IMAGE_QUALITY . '-' . (self::IMAGE_QUALITY + 10);
					shell_exec( 'pngquant --ext .png --force --speed 1 --quality=' . $quality . ' \'' . $file['file'] . '\'' );
				}
				break;
			case 'image/gif':
				if ( $instance->enables['gif'] ) {
					shell_exec( 'gifsicle -O2 \'' . $file['file'] . '\' > \'' . $tmp_file . '\'' );
					rename( $tmp_file, $file['file'] );
				}
				break;
		}

		return $file;
	}
}

$inbit_image_optimizer = ImageOptimizer::get_instance();
