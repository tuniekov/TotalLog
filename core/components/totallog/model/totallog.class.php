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
    /** @var array|null Кэш динамических полей на процесс */
    protected $dynamicFieldsCache = null;
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
        // gtsShop и getTables здесь когда-то поднимались за компанию, копипастой из
        // соседних компонентов, и ни разу не использовались. На сайте, где gtsShop нет,
        // это писало «Could not load class: gtsShop» в лог на каждый запрос к журналу.

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
    
    /**
     * Триггеры gtsAPI. Ключ — КЛАСС таблицы, поэтому одна запись покрывает и
     * TLItem (админский журнал), и TLItemUser (пользовательский) — класс у них общий.
     */
    public function regTriggers()
    {
        return [
            'TLItem' => [
                'gtsapi_rule' => 'ruleTLItem',
                'gtsapi_addfields' => 'addFieldsTLItem',
            ],
        ];
    }

    /**
     * gtsapi_addfields: закрываем динамические поля на запись.
     *
     * Почему не в gtsapi_rule: options() зовёт addFields() ВТОРОЙ раз, уже после
     * триггера правил, и пересобирает динамические поля заново — readonly, выставленный
     * раньше, терялся, и в админском журнале «Заказы» открывались на редактирование.
     * Этот триггер вызывается в конце каждого addFields(), поэтому переживает любой порядок.
     */
    public function addFieldsTLItem($params)
    {
        $fields = &$params['fields'];
        foreach (array_keys($this->dynamicFields()) as $name) {
            if (isset($fields[$name])) {
                $fields[$name]['readonly'] = 1;
            }
        }

        return $this->success();
    }

    /**
     * gtsapi_rule: дополняем конфигурацию таблицы динамическими полями gtsAPI.
     *
     * Зачем: excel_ids / raschet_ids / smens заданы в _build/configs/data.js и живут
     * в справочнике gtsAPI — их можно добавить прямо на сайте, руками. Прописывать их
     * ещё и в gtsapipackages.js значит держать список в двух местах и молча терять
     * новые поля. Берём список из справочника при каждом чтении конфига.
     *
     * gtsAPI сам подставляет такие поля только в таблицу, зарегистрированную в
     * gtsAPIFieldTable (у нас TLItem) — TLItemUser остаётся без них. И проставляет
     * их редактируемыми, а журнал только читают.
     */
    public function ruleTLItem($params)
    {
        $rule = &$params['rule'];
        if (empty($rule['properties']['fields']) || !is_array($rule['properties']['fields'])) {
            return $this->success();
        }

        foreach ($this->dynamicFields() as $name => $field) {
            if (isset($rule['properties']['fields'][$name])) {
                // Поле уже подставил gtsAPI (TLItem) — остаётся закрыть на запись
                $rule['properties']['fields'][$name]['readonly'] = 1;
                continue;
            }
            $rule['properties']['fields'] = $this->insertAfter(
                $rule['properties']['fields'],
                $name,
                $field,
                $field['after_field']
            );
        }

        return $this->success();
    }

    /**
     * Динамические поля журнала из справочника gtsAPI: имя → описание поля для конфига.
     * Порядок — по rank, как задано в data.js.
     */
    public function dynamicFields()
    {
        // Триггеры дёргаются по нескольку раз за запрос (addFields зовут и route_post,
        // и options) — справочник читаем один раз на процесс.
        if ($this->dynamicFieldsCache !== null) {
            return $this->dynamicFieldsCache;
        }
        $fields = [];
        try {
            $this->modx->addPackage('gtsapi', MODX_CORE_PATH . 'components/gtsapi/model/');
            $c = $this->modx->newQuery('gtsAPIField');
            $c->innerJoin('gtsAPIFieldGroupLink', 'Link', 'Link.field_id = gtsAPIField.id');
            $c->innerJoin('gtsAPIFieldGroupTableLink', 'TableLink', 'TableLink.group_field_id = Link.group_field_id');
            $c->innerJoin('gtsAPIFieldTable', 'FieldTable', 'FieldTable.id = TableLink.table_field_id');
            $c->where(['FieldTable.name_table' => 'TLItem']);
            $c->sortby('gtsAPIField.rank', 'ASC');
            $c->select($this->modx->getSelectColumns('gtsAPIField', 'gtsAPIField')
                . ', FieldTable.after_field as table_after_field');
            $c->prepare();
            $stmt = $this->modx->query($c->toSQL());
            if (!$stmt) return $fields;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = $row['name'];
                if ($name === '' || isset($fields[$name])) continue;
                $fields[$name] = [
                    'label' => $row['title'] !== '' ? $row['title'] : $name,
                    'type' => $row['field_type'] !== '' ? $row['field_type'] : 'text',
                    'readonly' => 1,
                    'after_field' => $row['after_field'] !== '' ? $row['after_field'] : $row['table_after_field'],
                ];
                if (!empty($row['modal_only'])) $fields[$name]['modal_only'] = 1;
                if (!empty($row['table_only'])) $fields[$name]['table_only'] = 1;
            }
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[TotalLog] dynamicFields: ' . $e->getMessage());
        }

        return $this->dynamicFieldsCache = $fields;
    }

    /**
     * Вставка поля сразу за указанным. Если якоря нет — в конец.
     */
    public function insertAfter($fields, $name, $field, $after)
    {
        unset($field['after_field']);
        if ($after === '' || !isset($fields[$after])) {
            $fields[$name] = $field;

            return $fields;
        }
        $out = [];
        foreach ($fields as $k => $v) {
            $out[$k] = $v;
            if ($k === $after) $out[$name] = $field;
        }

        return $out;
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