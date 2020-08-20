<?php
define('_VALID', true);
define('_ADMIN', true);
require 'include/config.php';
require 'include/function_global.php';
require $config['BASE_DIR'].'/classes/curl.class.php';
require $config['BASE_DIR'].'/include/config.aemembedder.php';
require $config['BASE_DIR']. '/classes/image.class.php';
require_once ($config['BASE_DIR']. '/include/function_thumbs.php');
require $config['BASE_DIR'].'/classes/filter.class.php';
require $config['BASE_DIR'].'/classes/validation.class.php';

// Defined embed width/height
define('E_WIDTH', 560);
define('E_HEIGHT', 340);

$width	 = (int) $config['img_max_width'];
$height	 = (int) $config['img_max_height'];

$image   = new VImageConv();

$categories	     = get_categories();
$defaut_category = reset($categories);

$run_key   = $_GET['run_key'];
$status	   = 1;
$debug     = 0;

$test=rand(10,100);

$sql     = "UPDATE  aembedder SET added = '" .$test. "' WHERE id = '1' LIMIT 1";
$conn->execute($sql);				

if ($run_key != '') {
	
	$sql   = "SELECT * FROM aembedder WHERE run_key = ".$conn->qStr($run_key)." LIMIT 1";
	$rs    = $conn->execute($sql);		
	$embed = $rs->getrows();
	$embed = $embed[0];

	$run_key = md5(microtime().rand());
	$sql     = "UPDATE aembedder SET run_key = '" .$run_key. "' WHERE id = " .intval($embed['id']). " LIMIT 1";
	$conn->execute($sql);
	
	$rs = $conn->execute("SELECT UID FROM signup WHERE UID = ".$conn->qStr($embed['uid'])." LIMIT 1");
	if (!$conn->Affected_Rows()) {
		$errors[] = 'Username is not a valid username on this system!';
	}
	
	$embed['status'] = (int) $status;
	if ($embed['cid'] != 0) {
		$category = $embed['cid'];
	}
	
	if (!$errors) {
		$membed_file	= $config['BASE_DIR'].'/classes/aembedder/'.$embed['website'].'.php';
		$membed_class	= 'MEmbed_'.$embed['website'];
		if (file_exists($membed_file) && is_file($membed_file)) {
			require $membed_file;
			$graber	= new $membed_class($embed['url'], $embed['uid'], $embed['cid'], $embed['status'], $debug);
			if ($graber->get_videos()) {
				$video_added	= $graber->video_added;
				$video_already	= $graber->video_already;
				$message		= 'Added '.$video_added.' videos!';
				$sql     = "UPDATE  aembedder SET added = '" .$video_added. "', total = total + " .$video_added. ", last_run = '" . date('Y-m-d H:i:s'). "', pid = '0' WHERE id = " .intval($embed['id']). " LIMIT 1";
				$conn->execute($sql);				
				if ($video_already !== 0) {
					$message .= ' '.$video_already.' videos are already added to your site!';
				}
				
				$messages[] = $message;
			} else {
				$errors	= array_merge($errors, $graber->errors);
			}
		} else {
			$errors[]	= 'Failed to load '.$embed['website'].' class file!';
		}
	}
	

}
function add_video($video) {
	global $categories, $category, $config, $conn, $width, $height, $image;
	$active_thumb = 1;
	if( count($video['thumbs']) > 4 ) {
		$active_thumb = 3;
	}
	if (!$category) {
		$category_video = match_category($video['category'], $video['title'], $video['description'], $video['tags']);
	} else {
		$category_video = $category;
	}
	if (!$video['tags']) {
		$video['tags'] = str_to_tags($video['title']);
	}
	if (!$video['description']) {
		$video['description'] = '';
	}	
	$sql = "INSERT INTO video
		   SET UID = ".$video['user_id'].", 
		   title = ".$conn->qStr($video['title']).",  
		   description = ".$conn->qStr($video['description']).",  
		   keyword = ".$conn->qStr($video['tags']).",  
		   channel = ".$category_video.",  
		   duration = ".$video['duration'].",  
		   thumb = ".$active_thumb.",  
		   thumbs = ".(count($video['thumbs'])-1).",  				   
		   embed_code = ".$conn->qStr($video['embed']).",
		   addtime = ".time().",
		   adddate = '".date('Y-m-d')."',
		   vkey = '" .mt_rand(). "',
		   type = 'public',
		   active = '0'";			   
	$conn->execute($sql);
	
	if ($conn->Affected_Rows()) {
		$VID 		= $conn->insert_Id();
		$thumb_dir  = get_thumb_dir($VID);
        $count      = 1;
        $valid      = 0;
		$curl = new VCurl();
		if (mkdir($thumb_dir)) {
      		foreach ($video['thumbs'] as $thumb) {
				$dst = $thumb_dir.'/'.$count.'.jpg';
          		if ($curl->saveToFile($thumb, $dst)) {
					if (filesize($dst) > 2000) {
						//-- Process Thumb - Aspect
						list($src_w, $src_h) = getimagesize($dst);
						$aspect     = $width / $height;
						$src_aspect = $src_w / $src_h;
						if ($aspect < $src_aspect) {
							$tmp_h = $height;
							$tmp_w = floor($tmp_h * $src_aspect);
							$image->process($dst, $dst, 'EXACT', $tmp_w, $tmp_h);
							$image->resize(true, true);
							$x = floor(($tmp_w - $width)/2);
							$y = 0;
						}
						else {
							$tmp_w = $width;
							$tmp_h = floor($tmp_w / $src_aspect);
							$image->process($dst, $dst, 'EXACT', $tmp_w, $tmp_h);
							$image->resize(true, true);
							$x = 0;
							$y = floor(($tmp_h - $height)/2);
						}
						$image->process($dst, $dst, 'EXACT', $width, $height);
						$image->crop($x, $y, $width, $height, true);				
						//-- Process Thumb - Aspect - END
						++$valid;
						++$count;
					}
					else {
						unlink($dst);
					}
				}
      		}
            if ($valid !== 0) {
				$vkey = substr(md5($VID),11,20);
				$conn->execute("UPDATE video SET active = '".$video['status']."', thumbs = ".$valid.", vkey = '".$vkey."' WHERE VID = ".$VID." LIMIT 1");
				add_tags($video['tags']);				
                return true;
			}	
		}
	} else {
		return false;
	}
}

function clean_html($html) {
	$html   = str_replace(array("\n", "\r"), '', $html);
    $html   = preg_replace('/\s\s+/', ' ', $html);
	return $html;
}

function match_category($category, $title, $description, $tags) {

	global $categories, $defaut_category;

	$categories_arr = explode(',', $category);

	foreach ($categories_arr as $cat_video) {
		$cat_video = trim($cat_video);
		if ($cat_video != '') {
			foreach ($categories as $cat) {
				if (stripos($cat_video,$cat['name']) !== false or stripos($cat['name'],$cat_video) !== false) {
					return $cat['CHID'];
				}
			}
		}
	}

	foreach ($categories as $cat) {
		if (stripos($title,$cat['name']) !== false) {
			return $cat['CHID'];
		}
	}

	foreach ($categories as $cat) {
		if (stripos($description,$cat['name']) !== false) {
			return $cat['CHID'];
		}
	}

	foreach ($categories as $cat) {
		if (stripos($tags,$cat['name']) !== false) {
			return $cat['CHID'];
		}
	}

    return $defaut_category['CHID'];
}

function already_added($embed_code) {
	global $conn;
	$embed_code = str_replace (" ", "", $embed_code);
	$embed_code = str_replace ("'", "\"", $embed_code);		
	if (preg_match('/src="(.*?)"/', $embed_code, $matches)) {
		$sql = "SELECT * FROM video WHERE embed_code LIKE '%".trim($conn->qStr($matches[1]), "'")."%' LIMIT 1";
		$conn->execute($sql);
		if ($conn->Affected_Rows() > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function duration_to_seconds($duration) {
	$duration = trim($duration);
	$duration = str_replace('h',':',$duration);	
	$duration = str_replace('m',':',$duration);
	$duration = str_replace('s','',$duration);
    $duration   = explode(':', $duration);
	if (count($duration) == 1) {
		return intval($duration[0]);
	} elseif (count($duration) == 2) {
		$minutes = intval($duration[0]);
		$seconds = intval($duration[1]);
		return (($minutes * 60) + $seconds);
	} elseif (count($duration) == 3) {
		$hours   = intval($duration[0]);
		$minutes = intval($duration[1]);
		$seconds = intval($duration[2]);
		return (($hours * 3600) + ($minutes * 60) + $seconds);
	} else {
		return false;
	}
}

function addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
		$url = ltrim($url, '//');
        $url = "http://" . $url;
    }
    return $url;
}

function str_to_tags($tags_str) {
	$tags       = array();
	$tags_arr   = explode(' ', strtolower(prepare_string($tags_str, false)));
	foreach ($tags_arr as $tag) {
		if (strlen($tag) >= 5) {
			$tags[] = $tag;
		}
	}
	if (!empty($tags)) {
		return implode(', ', $tags);
	} else {
		foreach ($tags_arr as $tag) {
			if (strlen($tag) >= 4) {
				$tags[] = $tag;
			}
		}
		if (!empty($tags)) {
			return implode(', ', $tags);
		} else {
			foreach ($tags_arr as $tag) {
				if (strlen($tag) >= 3) {
					$tags[] = $tag;
				}
			}
			return implode(', ', $tags);
		}
	}
}

$smarty->assign('debug', $debug);
$smarty->assign('embed', $embed);
$smarty->assign('categories', $categories);
?>
