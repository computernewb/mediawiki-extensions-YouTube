<?php
/**
 * Parser hook-based extension to show audio and video players
 * from YouTube and other similar sites.
 *
 * @file
 * @ingroup Extensions
 * @author Przemek Piotrowski <ppiotr@wikia-inc.com> for Wikia, Inc.
 * @copyright © 2006-2008, Wikia Inc.
 * @license GPL-2.0-or-later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301
 * USA
 *
 * @todo one class (family) to rule 'em all
 * @todo make width/height_max != width/height_default; aoaudio height may be large - long playlist
 * @todo smart <video> and <audio> tag
 */

class YouTube {

	/**
	 * Register all the new tags with the Parser.
	 *
	 * @param Parser &$parser
	 */
	public static function registerTags( &$parser ) {
		$parser->setHook( 'youtube', [ __CLASS__, 'embedYouTube' ] );
		$parser->setHook( 'aovideo', [ __CLASS__, 'embedArchiveOrgVideo' ] );
		$parser->setHook( 'aoaudio', [ __CLASS__, 'embedArchiveOrgAudio' ] );
		$parser->setHook( 'bitchute', [ __CLASS__, 'embedBitChute' ] );
		$parser->setHook( 'bitview', [ __CLASS__, 'embedBitView' ] );
		$parser->setHook( 'nicovideo', [ __CLASS__, 'embedNicovideo' ] );
	}

	/**
	 * Get the YouTube video ID from the supplied URL.
	 *
	 * @param string $url YouTube video URL
	 * @return string|bool Video ID on success, boolean false on failure
	 */
	public static function url2ytid( $url ) {
		// @see http://linuxpanda.wordpress.com/2013/07/24/ultimate-best-regex-pattern-to-get-grab-parse-youtube-video-id-from-any-youtube-link-url/
		$pattern = '~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[a-z0-9;:@?&%=+\/\$_.-]*~i';
		$id = false;

		if ( preg_match( $pattern, $url, $preg ) ) {
			$id = $preg[1];
		} elseif ( preg_match( '/([0-9A-Za-z_-]+)/', $url, $preg ) ) {
			$id = $preg[1];
		}

		return $id;
	}

	public static function embedYouTube( $input, $argv, $parser ) {
		$ytid   = '';
		$width  = $width_max  = 425;
		$height = $height_max = 355;

		if ( !empty( $argv['ytid'] ) ) {
			$ytid = self::url2ytid( $argv['ytid'] );
		} elseif ( !empty( $input ) ) {
			$ytid = self::url2ytid( $input );
		}

		// Did we not get an ID at all? That can happen if someone enters outright
		// gibberish and/or something that's not a YouTube URL.
		// Let's not even bother with generating useless HTML.
		if ( $ytid === false ) {
			return '';
		}

		// Support the pixel unit (px) in height/width parameters, because apparently
		// a lot of people use it there.
		// This way these parameters won't fail the filter_var() tests below if the
		// user-supplied values were like 450px or 200px or something instead of
		// 450 or 200
		if ( !empty( $argv['height'] ) ) {
			$argv['height'] = str_replace( 'px', '', $argv['height'] );
		}

		if ( !empty( $argv['width'] ) ) {
			$argv['width'] = str_replace( 'px', '', $argv['width'] );
		}

		$width = 560;
		$height = 315;
		$maxWidth = 960;
		$maxHeight = 720;

		if (
			!empty( $argv['width'] ) &&
			filter_var( $argv['width'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] ) &&
			$argv['width'] <= $maxWidth
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			filter_var( $argv['height'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] ) &&
			$argv['height'] <= $maxHeight
		) {
			$height = $argv['height'];
		}

		// Support YouTube's "enhanced privacy mode", in which "YouTube won’t
		// store information about visitors on your web page unless they play
		// the video" if the privacy argument was supplied
		// @see https://support.google.com/youtube/answer/171780?expand=PrivacyEnhancedMode#privacy
		$urlBase = '//www.youtube-nocookie.com/embed/';

		if ( !empty( $ytid ) ) {
			$url = $urlBase . $ytid;
			return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$url}\" frameborder=\"0\" allowfullscreen></iframe>";
		}
	}

	public static function url2aovid( $url ) {
		$id = $url;

		if ( preg_match( '/http:\/\/www\.archive\.org\/download\/(.+)\.flv$/', $url, $preg ) ) {
			$id = $preg[1];
		}

		preg_match( '/([0-9A-Za-z_\/.]+)/', $id, $preg );
		$id = $preg[1];

		return $id;
	}

	public static function embedArchiveOrgVideo( $input, $argv, $parser ) {
		$aovid   = '';
		$width  = $width_max  = 320;
		$height = $height_max = 263;

		if ( !empty( $argv['aovid'] ) ) {
			$aovid = self::url2aovid( $argv['aovid'] );
		} elseif ( !empty( $input ) ) {
			$aovid = self::url2aovid( $input );
		}
		if (
			!empty( $argv['width'] ) &&
			settype( $argv['width'], 'integer' ) &&
			( $width_max >= $argv['width'] )
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			settype( $argv['height'], 'integer' ) &&
			( $height_max >= $argv['height'] )
		) {
			$height = $argv['height'];
		}

		if ( !empty( $aovid ) ) {
			$url = "http://www.archive.org/download/{$aovid}.flv";
			return "<object type=\"application/x-shockwave-flash\" data=\"http://www.archive.org/flv/FlowPlayerWhite.swf\" width=\"{$width}\" height=\"{$height}\"><param name=\"movie\" value=\"http://www.archive.org/flv/FlowPlayerWhite.swf\"/><param name=\"flashvars\" value=\"config={loop: false, videoFile: '{$url}', autoPlay: false}\"/></object>";
		}
	}

	public static function url2aoaid( $url ) {
		$id = $url;

		if ( preg_match( '/http:\/\/www\.archive\.org\/details\/(.+)$/', $url, $preg ) ) {
			$id = $preg[1];
		}

		preg_match( '/([0-9A-Za-z_\/.]+)/', $id, $preg );
		$id = $preg[1];

		return $id;
	}

	public static function embedArchiveOrgAudio( $input, $argv, $parser ) {
		$aoaid   = '';
		$width  = $width_max  = 400;
		$height = $height_max = 170;

		if ( !empty( $argv['aoaid'] ) ) {
			$aoaid = self::url2aoaid( $argv['aoaid'] );
		} elseif ( !empty( $input ) ) {
			$aoaid = self::url2aoaid( $input );
		}
		if (
			!empty( $argv['width'] ) &&
			settype( $argv['width'], 'integer' ) &&
			( $width_max >= $argv['width'] )
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			settype( $argv['height'], 'integer' ) &&
			( $height_max >= $argv['height'] )
		) {
			$height = $argv['height'];
		}

		if ( !empty( $aoaid ) ) {
			$url = urlencode( "http://www.archive.org/audio/xspf-maker.php?identifier={$aoaid}" );
			return "<object type=\"application/x-shockwave-flash\" data=\"http://www.archive.org/audio/xspf_player.swf?playlist_url={$url}\" width=\"{$width}\" height=\"{$height}\"><param name=\"movie\" value=\"http://www.archive.org/audio/xspf_player.swf?playlist_url={$url}\"/></object>";
		}
	}
	
	public static function url2nvid( $url ) {
		$id = $url;

		preg_match( '/([0-9A-Za-z]+)/', $id, $preg );
		$id = $preg[1];

		return $id;
	}

	public static function embedNicovideo( $input, $argv, $parser ) {
		$nvid = '';
		$width  = 400;
		$height = 326;

		if ( !empty( $argv['nvid'] ) ) {
			$nvid = self::url2nvid( $argv['nvid'] );
		} elseif ( !empty( $input ) ) {
			$nvid = self::url2nvid( $input );
		}
		if (
			!empty( $argv['width'] ) &&
			settype( $argv['width'], 'integer' )
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			settype( $argv['height'], 'integer' )
		) {
			$height = $argv['height'];
		}

		if ( !empty( $nvid ) ) {
			$url = "https://embed.nicovideo.jp/watch/{$nvid}";
			return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$url}\"></iframe>";
		}
	}

	public static function url2bcid( $url ) {
		$id = $url;

		preg_match( '/([a-zA-Z0-9._-]{1,64})/', $id, $preg );
		$id = $preg[1];

		return $id;
	}

	public static function embedBitChute( $input, $argv, $parser ) {
		$bcid = '';
		$width  = 400;
		$height = 225;
		
		if ( $bcid === false ) {
			return '';
		}

		if ( !empty( $argv['bcid'] ) ) {
			$bcid = self::url2bcid( $argv['bcid'] );
		} elseif ( !empty( $input ) ) {
			$bcid = self::url2bcid( $input );
		}
		if (
			!empty( $argv['width'] ) &&
			settype( $argv['width'], 'integer' )
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			settype( $argv['height'], 'integer' )
		) {
			$height = $argv['height'];
		}

		if ( !empty( $bcid ) ) {
			$url = "https://bitchute.com/embed/{$bcid}";
			return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$url}\"></iframe>";
		}
	}

	public static function url2bvid( $url ) {
		$id = $url;

		preg_match( '/([0-9A-Za-z_-]+)/', $url, $preg );
		$id = $preg[1];

		return $id;
	}
	
	public static function embedBitView( $input, $argv, $parser ) {
		$bvid = '';
		$width = 425;
		$height = 355;
		
		if ( $bvid === false ) {
			return '';
		}

		if ( !empty( $argv['bvid'] ) ) {
			$bvid = self::url2bvid( $argv['bvid'] );
		} elseif ( !empty( $input ) ) {
			$bvid = self::url2bvid( $input );
		}
		if (
			!empty( $argv['width'] ) &&
			settype( $argv['width'], 'integer' )
		) {
			$width = $argv['width'];
		}
		if (
			!empty( $argv['height'] ) &&
			settype( $argv['height'], 'integer' )
		) {
			$height = $argv['height'];
		}
		
		// BitView can embed in both Flash and HTML5, so we will let the user decide what to embed
		
		if ( !empty( $argv['type'] ) && strtolower( $argv['type'] ) == 'flash' ) {
			$width = 450; // the flash player is restricted to this width & height no matter what.
			$height = 370;
			
			$urlBase = '//www.bitview.net/embed.php?v=';
			
			if ( !empty( $bvid ) ) {
				$url = $urlBase . $bvid;
				return "<object type=\"application/x-shockwave-flash\" data=\"{$url}\" width=\"{$width}\" height=\"{$height}\"><param name=\"movie\" value=\"{$url}\"/><param name=\"wmode\" value=\"transparent\"/></object>";
			}
		} else {
			// Embed in HTML5 
			$width = 450;
			$height = 370;
			$maxWidth = 960;
			$maxHeight = 720;
			
			if (
				!empty( $argv['width'] ) &&
				filter_var( $argv['width'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] ) &&
				$argv['width'] <= $maxWidth
			) {
				$width = $argv['width'];
			}
			if (
				!empty( $argv['height'] ) &&
				filter_var( $argv['height'], FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] ) &&
				$argv['height'] <= $maxHeight
			) {
				$height = $argv['height'];
			}
			
			$urlBase = '//www.bitview.net/embed.php?v=';
			
			if ( !empty( $bvid ) ) {
				$url = $urlBase . $bvid. '&wt=0';
				return "<iframe width=\"{$width}\" height=\"{$height}\" src=\"{$url}\" frameborder=\"0\" allowfullscreen></iframe>";
			}
		}
	}
}