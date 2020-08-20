<?php
class MEmbed_tnaflix
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
			if (preg_match_all('/<li data-vid=(.*?)<\/li>/', $html, $matches)) {
				foreach ($matches[0] as $match) {

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
						'site'        => 'tnaflix',
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
					if (preg_match('/data-vid=\'(.*?)\'/', $match, $matches_id)) {
						$video['id'] = trim($matches_id[1]);
					} else {
						if (!$this->debug) continue;
						else $debug_e[] = 'ID';
					}

					//Embed Code
					$video['embed'] = '<iframe src="https://player.tnaflix.com/video/' . $video['id'] . '" width="' . E_WIDTH . '" height="' . E_HEIGHT . '" frameborder="0"></iframe>';
					if (already_added($video['embed'])) {
						++$this->video_already;
						continue;
					}
					
					//URL
					if(preg_match('/href=\'(.*?)\'/', $match, $matches_url)) {
						$video['url']   = 'https://www.tnaflix.com'.$matches_url[1];
					} else {
						$this->errors[]	= 'Failed to get video URL for ID: '.$video['id'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'URL';
					}
					
					//Title
					if(preg_match('/data-name=\'(.*?)\'/', $match, $matches_title)) {
						$video['title']	= htmlspecialchars_decode(strip_tags(stripslashes($matches_title[1])), ENT_QUOTES);
					} else {
						$this->errors[]	= 'Failed to get video title for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'TITLE';
					}

					//Duration
					if(preg_match('/videoDuration\'>(.*?)</', $match, $matches_duration)) {
                        $video['duration'] = duration_to_seconds($matches_duration[1]);
                    } else {
						$this->errors[]	= 'Failed to get video duration for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'DURATION';
					}
					
					//Thumbnails
                    if(preg_match('/data-original=\'(.*?)\'/', $match, $matches_thumb)) {
                        $thumb_url  = trim($matches_thumb[1]);
						$thumb_url = explode ("_", $thumb_url);
						$thumb_url[0] = addhttp($thumb_url[0]);
						
						$parts = explode('/', $thumb_url[0]);
						$last = array_pop($parts);
						$parts = array(implode('/', $parts), $last);			
						for ($i = 1; $i <= 20; $i++) {
							$video['thumbs'][] = $parts[0]."/".$i."_".$thumb_url[1];
						}
					} else {
						$this->errors[]	= 'Failed to get video thumbnails for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'THUMBS';	
					}

					//Get Video Page
					$html_video	= clean_html($curl->saveToString($video['url']));
					
					//Categories
					if (preg_match('/Categories:<\/span>(.*?)<\/div>/', $html_video, $matches_cat_str)) {
						if (preg_match_all('/">(.*?)<\/a>/', $matches_cat_str[1], $matches_category)) {
							$matches_category[1] = array_map('trim', $matches_category[1]);
							$video['category'] = implode(',', $matches_category[1]);
						} else {
							$debug_w[] = 'CATEGORY';
						}
					} else {
						$debug_w[] = 'CATEGORY';						
					}
					
					//Tags
					$debug_w[] = 'TAGS';

					//Check Embeddable Content
					if (!strpos($html_video, 'Share')) {
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