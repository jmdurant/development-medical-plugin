<?php
/** Synthetic optional SCF values and enqueue contract; no site or database. */
declare(strict_types=1);
define('GCM_URL','https://example.invalid/plugin');
define('GCM_VERSION','fixture');
$checks=0;
function check($ok,$label){$GLOBALS['checks']++;if(!$ok)throw new RuntimeException($label);}
function add_action(...$args){}
function current_user_can(...$args){return true;}
function get_field($name,$context){
    check($context==='options','Existing validation option storage is not silently moved');
    return $GLOBALS['fields'][$name]??false;
}
function wp_enqueue_script(...$args){$GLOBALS['enqueue']=$args;}
function wp_localize_script($handle,$name,$settings){$GLOBALS['settings']=$settings;}
require dirname(__DIR__).'/includes/validation.php';
$fields=[];gcm_enqueue_validation_js();
check($settings===['methods'=>[],'min_prefix'=>'','max_prefix'=>''],'Absent settings are optional and omit console debug output');
$fields=['validation_methods'=>false,'character_limit_class_prefix'=>'malformed'];gcm_enqueue_validation_js();
check($settings===['methods'=>[],'min_prefix'=>'','max_prefix'=>''],'Malformed option values do not cause offsets/type errors');
$fields=['validation_methods'=>[null,'bad',['class'=>'letters','methods'=>['letters']]],'character_limit_class_prefix'=>['min_length'=>'min-','max_length'=>false]];
gcm_enqueue_validation_js();
check($settings['methods']===[['class'=>'letters','methods'=>['letters']]] && $settings['min_prefix']==='min-' && $settings['max_prefix']==='','Configured method and prefix survive type normalization');
check($enqueue[2]===[],'Validation has no implicit jQuery or CF7 API dependency');
check($enqueue[4]===['in_footer'=>true,'strategy'=>'defer'],'Owned script requests native footer/defer');
check($enqueue[3]==='fixture.'.substr(hash_file('sha256',dirname(__DIR__).'/assets/validation.js'),0,12),'Validation cache key follows source bytes');
echo "PASS $checks optional validation settings/enqueue assertions\n";
