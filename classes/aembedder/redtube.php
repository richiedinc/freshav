<?php
class MEmbed_redtube
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

			preg_match_all('/js_thumbContainer videoblock_list(.*?)<\/ul>/', $html, $matches);
						
			if ($matches[0]) {
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
						'site'        => 'redtube',
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
					if (preg_match('/href="\/(.*?)"/', $match, $matches_id)) {
						$video['id'] = trim($matches_id[1]);
					} else {
						if (!$this->debug) continue;
						else $debug_e[] = 'ID';
					}
					
					//Embed Code
					$video['embed'] = '<iframe src="https://embed.redtube.com/?id=' . $video['id'] . '&bgcolor=000000" frameborder="0" width="' . E_WIDTH . '" height="' . E_HEIGHT . '" scrolling="no"></iframe>';					
					if (already_added($video['embed'])) {
						++$this->video_already;
						continue;
					}
					
					//URL
					if($video['id']) {
						$video['url']   = "http://www.redtube.com/".$video['id'];
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
					if(preg_match('/class="duration">(.*?)<\/a>/', $match, $matches_duration)) {				
						$matches_duration[1] = str_replace('<span class="hd-video-text">HD</span>','', $matches_duration[1]);
						$matches_duration[1] = trim(str_replace('<\/span>','', $matches_duration[1]));
                        $video['duration'] = duration_to_seconds(trim($matches_duration[1]));
                    } else {
						$this->errors[]	= 'Failed to get video duration for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'DURATION';				
					}
					
					//Thumbnails
                    if (preg_match('/data-path="(.*?)"/', $match, $matches_thumb)) {
							
						$thumb_url  = $matches_thumb[1];
						for ($i = 1; $i <= 16; $i++) {
							$video['thumbs'][] = str_replace('{index}', $i, $thumb_url);
						}							

                    } else {
						$this->errors[]	= 'Failed to get video thumbnails for '.$video['url'].'!';
						if (!$this->debug) continue;
						else $debug_e[] = 'THUMBS';	
					}

					//Get Video Page
					$html_video	= clean_html($curl->saveToString($video['url']));					
					
					//Categories
					if(preg_match('/video-infobox-label">Categories<\/div>(.*?)<\/div>/', $html_video, $matches_categories)) {
						if (preg_match_all('/href="\/redtube\/(.*?)">(.*?)</', $matches_categories[1], $matches_cat)) {
							$video['category'] = implode(',', $matches_cat[2]);
						} else {
							$debug_w[] = 'CATEGORY';
						}
					}
					
					//Tags
					if(preg_match('/video-infobox-label">Tags<\/div>(.*?)<\/div>/', $html_video, $matches_tags)) {
						if (preg_match_all('/href="(.*?)\/redtube\/(.*?)">(.*?)</', $matches_tags[1], $matches_tag)) {
						foreach ($matches_tag[3] as $k => $v) {
							$matches_tag[3][$k] = trim($v);
						}
						$video['tags'] = implode(', ', $matches_tag[3]);
						$video['tags'] = strtolower($video['tags']);
						} else {
							$debug_w[] = 'TAGS';
						}
					}					

					//Check Embeddable Content
					$video['embeddable'] = true;
					
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