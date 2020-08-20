<?php
define('_VALID', true);
define('_ADMIN', true);
require 'include/config.php';

if (getenv('IS_CRON') == 1) {
	$sql     = "SELECT * FROM aembedder WHERE status = '1'";
	$rs      = $conn->execute($sql);	
	$sources = $rs->getrows();

	$content = "#!/bin/bash"."\n";

	foreach ($sources as $source) {
		$content = $content."wget -qO- ".$config['BASE_URL']."/aembed.php?run_key=".$source['run_key']." &> /dev/null;\n";
	}

	file_put_contents(dirname(__FILE__) . '/aembed.sh', $content);
	exec(dirname(__FILE__) . '/aembed.sh');
} else {
	echo "Access Denied!";
	exit;	
}

?>