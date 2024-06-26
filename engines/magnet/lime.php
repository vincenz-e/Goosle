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
class LimeRequest extends EngineRequest {
	public function get_request_url() {
		$this->query = preg_replace("/[^a-z0-9- ]+/", "", $this->query);
		$this->query = str_replace(" ", "-", $this->query);
		$url = "https://www.limetorrents.lol/search/all/".$this->query."/";
        return $url;
	}
	
    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7',
		);
	}

	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
		
		// Failed to load page
		if(!$xpath) return $results;

		// Scrape the page
		foreach($xpath->query("//table[@class='table2']//tr[position()>1]") as $result) {
			$name = sanitize($xpath->evaluate(".//td[@class='tdleft']//a[2]", $result)[0]->textContent);
			$hash = sanitize($xpath->evaluate(".//td[@class='tdleft']//a[1]/@href", $result)[0]->textContent);
			$hash = explode("/", substr($hash, 0, strpos($hash, ".torrent?")));
			$hash = strtolower($hash[array_key_last($hash)]);
			$magnet = "magnet:?xt=urn:btih:".$hash."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->magnet_trackers);
			$seeders = sanitize($xpath->evaluate(".//td[@class='tdseed']", $result)[0]->textContent);
			$leechers = sanitize($xpath->evaluate(".//td[@class='tdleech']", $result)[0]->textContent);
			$size = sanitize($xpath->evaluate(".//td[@class='tdnormal'][2]", $result)[0]->textContent);

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = explode(" ", trim(sanitize($xpath->evaluate(".//td[@class='tdnormal'][1]", $result)[0]->textContent), ".,"));
			$category = $category[array_key_last($category)];
			$url = "https://www.limetorrents.lol".sanitize($xpath->evaluate(".//td[@class='tdleft']//a[2]/@href", $result)[0]->textContent);
			
			// Filter episodes
			if(!is_season_or_episode($this->query, $name)) continue;
			
			$results[] = array (
				// Required
				"id" => uniqid(rand(0, 9999)), "source" => "limetorrents.lol", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
				// Extra
				"category" => $category, "url" => $url
			);
		}
		unset($response, $xpath);
		
		return $results;
	}
}
?>