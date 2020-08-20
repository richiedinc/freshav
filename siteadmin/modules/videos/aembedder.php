<?php
defined('_VALID') or die('Restricted Access!');

define('DEFAULT_CATEGORY', 1);

require $config['BASE_DIR'].'/include/config.aemembedder.php';

Auth::checkAdmin();

$source  = array('website' => '', 'url' => '', 'username' => 'anonymous', 'uid' => 1, 'category' => '0');
$categories	= get_categories();
if (isset($_POST['add_source'])) {
	require $config['BASE_DIR'].'/classes/filter.class.php';
	require $config['BASE_DIR'].'/classes/validation.class.php';
	$filter		= new VFilter();
	$valid		= new VValidation();
	$url		= $filter->get('url');
	$username	= $filter->get('username');
	$category	= $filter->get('category');
	$source['url']	= $url;	
	$source['username']	= $username;
	if ($url == '') {
		$errors[] 		= 'URL field cannot be left blank!';
		$err['url'] = 1;
	} else {
	    $parts = explode('/', str_replace(array('http://www.', 'http://', 'https://www.', 'https://'), '', $url));
        if (isset($parts['0'])) {
      		$site = $parts['0'];
            if (!isset($sites[$site])) {
          		$errors[] = 'Invalid url! Supported sites: '.implode(', ', $sites).'!';
				$err['url'] = 1;
            } else {
				$source['website'] = $sites[$site];
			}
        } else {
			$errors[] = 'Failed to get site identifier from url!';
			$err['url'] = 1;
		}
	}
	
	if ($username == '') {
		$errors[]	= 'Username field cannot be left blank!';
		$err['username'] = 1;
	} else {
		$rs = $conn->execute("SELECT UID FROM signup WHERE username = ".$conn->qStr($username)." LIMIT 1");
		if (!$conn->Affected_Rows()) {
			$errors[] = 'Username is not a valid username on this system!';
			$err['username'] = 1;			
		} else {
			$source['uid'] 	= (int) $rs->fields['UID'];
		}
	}
	
	if ($category != '0') {
		$source['category'] 	= (int) $category;
	}

	if (!$errors) {
		$sql  = "INSERT INTO aembedder SET 
				website = '".$source['website']."', 
				url = '".$source['url']."', 
				uid = '".$source['uid']."', 
				cid = '".$source['category']."'; ";
        $conn->execute($sql);		
        $id      = $conn->insert_Id();
		$run_key = md5(microtime().rand());
		$sql     = "UPDATE aembedder SET run_key = '" .$run_key. "' WHERE id = " .intval($id). " LIMIT 1";
		$conn->execute($sql);
		unset($source);
		$source  = array('website' => '', 'url' => '', 'username' => 'anonymous', 'uid' => 1, 'category' => '0');
		$messages[] = "AE source sucessfully added!";
	}	
}

$sql     = "SELECT a.*, c.name, s.username FROM aembedder AS a, channel AS c, signup AS s WHERE a.cid = c.CHID AND a.uid = s.UID; ";
$rs      = $conn->execute($sql);
$sources1 = $rs->getrows();

$sql         = "SELECT a.*, s.username FROM aembedder AS a, signup AS s WHERE a.cid = '0' AND a.uid = s.UID; ";
$rs          = $conn->execute($sql);
$sources2 = $rs->getrows();

$sources = array_merge($sources1, $sources2);

//sort
$all   = (isset($_GET['all'])) ? intval($_GET['all']) : 0;
if ($all == 1) {
	unset ($_SESSION['search_sources_option']);
}

$option_orig        = array('sort' => 'id', 'order' => 'DESC');
$option             = ( isset($_SESSION['search_sources_option']) ) ? $_SESSION['search_sources_option'] : $option_orig;

if ( isset($_POST['search_sources']) ) {
	$option['sort']     = trim($_POST['sort']);
	$option['order']    = trim($_POST['order']);
	$_SESSION['search_sources_option'] = $option;
}

if ($option['sort'] == 'id') {
	usort($sources, "sortByOptionId");
} elseif ($option['sort'] == 'category') {
	usort($sources, "sortByOptionCategory");	
} else {
	usort($sources, "sortByOptionWebsite");
}

if ($option['order'] == 'DESC') {
	$sources = array_reverse($sources);	
}

function sortByOptionCategory($a, $b) {
	return strcmp($a['name'], $b['name']);
}
function sortByOptionWebsite($a, $b) {
	return strcmp($a['website'], $b['website']);
}
function sortByOptionId($a, $b) {
	return $a['id'] - $b['id'];
}

//---

$output = shell_exec('crontab -l');

if (strpos($output, '0 * * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 1;
}
if (strpos($output, '0 */3 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 3;
}
if (strpos($output, '0 */6 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 6;
}
if (strpos($output, '0 */12 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 12;
}
if (strpos($output, '0 0 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 24;
}
if (strpos($output, '0 0 * * 0 IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $option['run'] = 168;
}

if ($output == '') {
	$option['run'] = 0;
}

$plugin = 'jquery.aembedder.js';

$smarty->assign('option', $option);
$smarty->assign('sites', $sites);
$smarty->assign('plugin', $plugin);
$smarty->assign('source', $source);
$smarty->assign('sources', $sources);
$smarty->assign('categories', $categories);


?>
