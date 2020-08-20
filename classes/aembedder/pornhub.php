<?php
class MEmbed_pornhub
{
	public $url;
	public $user_id;
	public $category;
	public $status;
	public $video;
	public $errors	= array();
	public $debug_e = array();
	public $debug_w = array();
	public $message;
	private $overflow = 500;
	public $video_already	= 0;
	public $video_added	= 0;
	public $debug;
	
	public function __construct($url, $user_id, $category, $status, $debug) {
		$this->url		= $url;
		$this->user_id	= $user_id;
		$this->category	= $category;
		$this->status	= $status;
		$this->debug	= $debug;
	}
	
	public function get_videos() {
		$count  = 0;
		$curl	= new VCurl();
        $html   = clean_html($curl->saveToString($this->url));		
		if ($html) {
			if (preg_match_all('/js-pop videoblock (.*?)<\/li>/', $html, $matches)) {
				$counter = 0;
				foreach ($matches[0] as $match) {
					$counter++;
					if ($counter < 5) continue;
					unset($debug_e);
					unset($debug_w);				
                    ++$count;
                    if ($count > $this->overflow) {
                        $this->errors[] = 'Overflow reached (500)! Aborting!';
                        return false;
                    }
					
					$video  = array(
						'user_id'	  => $this->user_id,
						'status'	  => $this->status,						
						'site'        => 'pornhub',
						'id'		  => '',
						'embeddable'  => true,						
						'url'         => '',
						'title'       => '',
						'description' => '',
						'tags'        => '',
						'category'    => '',
						'thumbs'      => array(),
						'duration'    => 0,
						'embed'       => ''	
                    );
					
					//Video ID
					if (preg_match('/vkey="(.*?)"/', $match, $matches_id)) {
						$video['id'] = trim($matches_id[1]);
					} else {
						if (!$this->debug) continue;
						else $debug_e[] = 'ID';
					}

					//Embed Code					
					$video['embed'] = '<iframe src="https://www.pornhub.com/embed/' . $video['id'] . '" frameborder="0" width="' . E_WIDTH . '" height="' . E_HEIGHT . '" scrolling="no"></iframe>';
					if (already_added($video['embed'])) {
						++$this->video_already;
						continue;
					}
					
					//URL
					if(preg_match('/<a href="(.*?)"/', $match, $matches_url)) {
						$video['url']   = "https://www.pornhub.com".trim($matches_url[1]);
					} else {
						$this->errors[]	= 'Failed to get video URL for ID: '.$video['id'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'URL';
					}
					
					//Title
					if(preg_match('/title="(.*?)"/', $match, $matches_title)) {
						$video['title']	= htmlspecialchars_decode(strip_tags(stripslashes($matches_title[1])), ENT_QUOTES);
					} else {
						$this->errors[]	= 'Failed to get video title for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'TITLE';
					}

					//Duration
					if(preg_match('/duration">(.*?)</', $match, $matches_duration)) {
                        $video['duration'] = duration_to_seconds($matches_duration[1]);
                    } else {
						$this->errors[]	= 'Failed to get video duration for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'DURATION';
					}
					
					//Thumbnails
					if (preg_match('/data-thumbs="(.*?)"(.*?)data-path="(.*?)"/', $match, $matches_thumb)) {
                        $thumb_url = $matches_thumb['3'];
						$total_thumbs = intval($matches_thumb['1']);
						for ($i = 1; $i <= $total_thumbs; $i++) {
							$video['thumbs'][] = str_replace("{index}", $i, $thumb_url);
						}												
                    } else {
						$this->errors[]	= 'Failed to get video thumbnails for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'THUMBS';	
					}

					//Get Video Page
					$html_video	= clean_html($curl->saveToString($video['url']));
					
					//Categories
					if (preg_match_all('/\'Category\'\);">(.*?)<\/a>/', $html_video, $matches_category)) {
						$video['category'] = implode(',', $matches_category[1]);
					} else {
						$debug_w[] = 'CATEGORY';
					}
					
					//Tags
					if (preg_match_all('/<a href="\/video\/search\?search=(.*?)">(.*?)<\/a>/', $html_video, $matches_tags)) {
						$video['tags'] = implode(', ', $matches_tags[2]);
						$video['tags'] = strtolower($video['tags']);
					} else {
						$debug_w[] = 'TAGS';
					}

					//Check Embeddable Content
					if (!strpos($html_video, 'data-tab="embed"')) {
						$video['embeddable'] = false;
						$debug_w[] = 'EMBEDDABLE';
						if (end($matches[0]) !== $match) continue;
					}	
					
					//Debug Mode
					if ($this->debug) {
						echo "Match Content (". $count ."): <textarea style='width:100%' rows=10>".$match."</textarea><br>";
						if ($debug_e) echo "Errors: " . implode(', ',$debug_e) . "<br>";
						if ($debug_w) echo "Warnings: " . implode(', ',$debug_w) . "<br>";
						echo "<pre>";
						print_r($video);
						echo "</pre>";
						exit;
					}
					
					//Add Video
					if (add_video($video)) {
						++$this->video_added;
					} else {
						$this->errors[] = 'Failed to add '.$video['url'].'!';
					}					
					
				} //Foreach Loop - END
			} else {
				$this->errors[] = 'Failed to find embeddable videos on the specified page!';
			}
		} else {
			$this->errors[] = 'Failed to get html code for specified url!';
		}		
		if (!$this->errors) {
			return true;
		}
		return false;
	}
}
?>