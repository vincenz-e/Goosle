<?php
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
class PirateBayRequest extends EngineRequest {
	public function get_request_url() {
		$args = array("q" => $this->query);
        $url = "https://apibay.org/q.php?".http_build_query($args);

        unset($args);

        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Connection' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		$categories = array(
			100 => "Audio",
			101 => "Music",
			102 => "Audio Book",
			103 => "Sound Clips",
			104 => "Audio FLAC",
			199 => "Audio Other",

			200 => "Video",
			201 => "Movie",
			202 => "Movie DVDr",
			203 => "Music Video",
			204 => "Movie Clip",
			205 => "TV Show",
			206 => "Handheld",
			207 => "HD Movie",
			208 => "HD TV Show",
			209 => "3D Movie",
			210 => "CAM/TS",
			211 => "UHD/4K Movie",
			212 => "UHD/4K TV Show",
			299 => "Video Other",
			
			300 => "Applications",
			301 => "Apps Windows",
			302 => "Apps Apple",
			303 => "Apps Unix",
			304 => "Apps Handheld",
			305 => "Apps iOS",
			306 => "Apps Android",
			399 => "Apps Other OS",

			400 => "Games",
			401 => "Games PC",
			402 => "Games Apple",
			403 => "Games PSx",
			404 => "Games XBOX360",
			405 => "Games Wii",
			406 => "Games Handheld",
			407 => "Games iOS",
			408 => "Games Android",
			499 => "Games Other OS",
			
			500 => "Porn",
			501 => "Porn Movie",
			502 => "Porn Movie DVDr",
			503 => "Porn Pictures",
			504 => "Porn Games",
			505 => "Porn HD Movie",
			506 => "Porn Movie Clip",
			507 => "Porn UHD/4K Movie",
			599 => "Porn Other",

			600 => "Other",
			601 => "Other E-Book",
			602 => "Other Comic",
			603 => "Other Pictures",
			604 => "Other Covers",
			605 => "Other Physibles",
			699 => "Other Other"
		);

		// Use API result
		foreach($json_response as $result) {
			// Nothing found
			if($result['name'] == "No results returned") break;
			
			$name = sanitize($result['name']);
			$hash = strtolower(sanitize($result['info_hash']));
			$magnet = "magnet:?xt=urn:btih:".$hash."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->magnet_trackers);
			$seeders = sanitize($result['seeders']);
			$leechers = sanitize($result['leechers']);
			$size = sanitize($result['size']);
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = sanitize($result['category']);
			$url = "https://thepiratebay.org/description.php?id=".sanitize($result['id']);
			$date_added = sanitize($result['added']);
			
			// Block these categories
			if(in_array($category, $this->opts->piratebay_categories_blocked)) continue;
			
			// Filter episodes
			if(!is_season_or_episode($this->query, $name)) continue;
			
			$results[] = array(
				// Required
				"id" => uniqid(rand(0, 9999)), "source" => "thepiratebay.org", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => human_filesize($size),
				// Extra
				"category" => $categories[$category], "url" => $url, "date_added" => $date_added,
 			);
		}
		unset($json_response);

		return $results;
	}
}
?>
