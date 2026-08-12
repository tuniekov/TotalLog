<?php

class TotalLog
{
    /** @var modX $modx */
    public $modx;

    /** @var pdoFetch $pdoTools */
    public $pdo;

    /** @var array() $config */
    public $config = array();
    
    public $timings = [];
    protected $start = 0;
    protected $time = 0;
    public $gtsShop;
    public $getTables;
    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = MODX_CORE_PATH . 'components/totallog/';
        // $assetsUrl = MODX_ASSETS_URL . 'components/totallog/';

        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            // 'processorsPath' => $corePath . 'processors/',
            // 'customPath' => $corePath . 'custom/',

            // 'connectorUrl' => $assetsUrl . 'connector.php',
            // 'assetsUrl' => $assetsUrl,
            // 'cssUrl' => $assetsUrl . 'css/',
            // 'jsUrl' => $assetsUrl . 'js/',
        ], $config);

        $this->modx->addPackage('totallog', $this->config['modelPath']);
        $this->gtsShop = $modx->getService("gtsShop","gtsShop",
            MODX_CORE_PATH."components/gtsshop/model/",[]);
        //$this->modx->lexicon->load('totallog:default');
        $gettables_core_path = $this->modx->getOption('gettables_core_path',null, MODX_CORE_PATH . 'components/gettables/core/');
        $gettables_core_path = str_replace('[[+core_path]]', MODX_CORE_PATH, $gettables_core_path);
        if ($this->modx->loadClass('gettables', $gettables_core_path, false, true)) {
            $this->getTables = new getTables($this->modx, []);
        }

        if ($this->pdo = $this->modx->getService('pdoFetch')) {
            $this->pdo->setConfig($this->config);
        }
        $this->timings = [];
        $this->time = $this->start = microtime(true);
    }
    /**
     * Add new record to time log
     *
     * @param $message
     * @param null $delta
     */
    public function addTime($message, $delta = null)
    {
        $time = microtime(true);
        if (!$delta) {
            $delta = $time - $this->time;
        }

        $this->timings[] = array(
            'time' => number_format(round(($delta), 7), 7),
            'message' => $message,
        );
        $this->time = $time;
    }
    /**
     * Return timings log
     *
     * @param bool $string Return array or formatted string
     *
     * @return array|string
     */
    public function getTime($string = true)
    {
        $this->timings[] = array(
            'time' => number_format(round(microtime(true) - $this->start, 7), 7),
            'message' => '<b>Total time</b>',
        );
        $this->timings[] = array(
            'time' => number_format(round((memory_get_usage(true)), 2), 0, ',', ' '),
            'message' => '<b>Memory usage</b>',
        );

        if (!$string) {
            return $this->timings;
        } else {
            $res = '';
            foreach ($this->timings as $v) {
                $res .= $v['time'] . ': ' . $v['message'] . "\n";
            }

            return $res;
        }
    }
    
    public function success($message = "",$data = []){
        return array('success'=>1,'message'=>$message,'data'=>$data);
    }
    public function error($message = "",$data = []){
        return array('success'=>0,'message'=>$message,'data'=>$data);
    }
    public function checkPermissions($rule_action){
        // $this->modx->log(1,"checkPermissions ".print_r($rule_action,1));
        if(isset($rule_action['authenticated']) and $rule_action['authenticated'] == 1){
            if(!$this->modx->user->id > 0) return $this->error("Not api authenticated!",['user_id'=>$this->modx->user->id]);
        }

        if(isset($rule_action['groups']) and !empty($rule_action['groups'])){
            // $this->modx->log(1,"checkPermissions groups".print_r($rule_action['groups'],1));
            $groups = array_map('trim', explode(',', $rule_action['groups']));
            if(!$this->modx->user->isMember($groups)) return $this->error("Not api permission groups!");
        }
        if(isset($rule_action['permissions'])and !empty($rule_action['permissions'])){
            $permissions = array_map('trim', explode(',', $rule_action['permissions']));
            foreach($permissions as $pm){
                if(!$this->modx->hasPermission($pm)) return $this->error("Not api modx permission!");
            }
        }
        return $this->success();
    }
}