<?php
defined('_VALID') or die('Restricted Access!');

require $config['BASE_DIR']. '/classes/filter.class.php';
require $config['BASE_DIR']. '/include/compat/json.php';



$response = array('status' => 0, 'msg' => '', 'debug' => '');

$filter     = new VFilter();
$cron        = $filter->get('cron', 'INTEGER');

switch ($cron) {
    case 0:
		exec('crontab -r', $crontab);
        break;
    case 1:
		exec('crontab -r', $crontab);
		append_cronjob('0 * * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');
        break;
    case 3:
		exec('crontab -r', $crontab);
		append_cronjob('0 */3 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');
        break;		
    case 6:
		exec('crontab -r', $crontab);
		append_cronjob('0 */6 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');	
        break;
    case 12:
		exec('crontab -r', $crontab);
		append_cronjob('0 */12 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');	
        break;
    case 24:
		exec('crontab -r', $crontab);
		append_cronjob('0 0 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');
        break;
    case 168:
		exec('crontab -r', $crontab);
		append_cronjob('0 0 * * 0 IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php');
        break;			
}

$output = shell_exec('crontab -l');
if (strpos($output, '0 * * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 1;
}
if (strpos($output, '0 */3 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 3;
}
if (strpos($output, '0 */6 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 6;
}
if (strpos($output, '0 */12 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 12;
}
if (strpos($output, '0 0 * * * IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 24;
}
if (strpos($output, '0 0 * * 0 IS_CRON=1 php -q '.$config['BASE_DIR'].'/cron.php') !== false) {
    $ucron = 168;
}
if ($output == '') {
	$ucron = 0;
}

if ($cron == $ucron) {
	$response['status'] = 1;
}

function cronjob_exists($command){
    $cronjob_exists=false;
    exec('crontab -l', $crontab);
    if(isset($crontab)&&is_array($crontab)){
        $crontab = array_flip($crontab);
        if(isset($crontab[$command])){
            $cronjob_exists=true;
        }
    }
    return $cronjob_exists;
}
function append_cronjob($command){
    if(is_string($command)&&!empty($command)&&cronjob_exists($command)===FALSE){
        //add job to crontab
        exec('echo -e "`crontab -l`\n'.$command.'" | crontab -', $output);
    }
    return $output;
}

echo json_encode($response);
die();
?>
