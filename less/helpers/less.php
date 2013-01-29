<?php  
/**
 * @package Helpers
 * @category Less
 * @author Lukas White <hello@lukaswhite.com>
 * @copyright (c) 2011 Lukas White
 */

 /**
  * Helper for the dynamic stylesheet language, Less.
  * 
  * Allows you to create a link to a less file (e.g. from a theme), and it will compile it to CSS and add
  * a link to that.
  * 
  * If the less file has changed more recently than the generated CSS file, it re-generates it.
  * 
  * Uses the Less PHP Compiler by Leaf Corcoran <leafot@gmail.com>
  */
defined('C5_EXECUTE') or die("Access Denied.");
class LessHelper {

	/** 
	 * Takes a Less file, compiles it into CSS, and returns a link to that CSS
	 * 
	 * If the Less file hasn't changed since it last compiled it, it uses the same CSS but once the 
	 * file changes it will compile it again. 
	 *
	 * This function looks for the Less file in several places, including the theme directory and optionally,
	 * a supplied package.
	 * 
	 * @param $file
	 * @param $pkgHandle
	 * @return $str
	 */
	public function link($file, $pkgHandle = null) {
		$fh = loader::helper('file');

		Loader::library('3rdparty/lessc.inc', 'less');
		
		$v 			= View::getInstance();
		$base		= $v->getThemeDirectory() . "/";
		$filename		= $file;
		$dest_filename	= str_replace(".less", ".rendered.css", $filename);
		$url			= BASE_URL . $v->getThemePath() . "/". $dest_filename . '?v=' . md5(APP_VERSION . PASSWORD_SALT);
		
		$cache_id		= 'lesscss_' . md5($dest_filename);
		$less			= new lessc();
		
		// Register preg_replace to be used within the less file
		$less->registerFunction("replace", function($args){
			// String to operate on
			$string = implode("", $args[2][0][2]);
			
			// Regex to match on
			$regex = implode("", $args[2][1][2]);
			
			// String to replace with
			$replace = implode("", $args[2][2][2]);
			
			return preg_replace($regex, $replace, $string);
		});
		
		
		if($cache_o = Cache::get('lesscss', $cache_id)){
			$cache			= $less->cachedCompile($cache_o);
			$last_updated	= $cache_o['updated']; 
		}else{
			$cache = $less->cachedCompile($base . $filename);
		
			$last_updated = 0;
		}

		if ($cache["updated"] > $last_updated) {
			Cache::set('lesscss', $cache_id, $cache);

			file_put_contents($base . $dest_filename, $cache['compiled']);
		}
		
		$lesso = new LessOutputObject();
		$lesso->file = $url . '&u=' . $cache['updated'];

		return $lesso;
	}

}

/** 
 * @access private
 */
class LessOutputObject extends HeaderOutputObject {

	public function __toString() {
		return '<link rel="stylesheet" type="text/css" href="' . $this->file . '" />';
	}
	
}