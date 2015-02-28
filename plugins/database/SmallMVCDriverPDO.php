<?php
if(!defined('SMVC_SQL_NONE'))
	  define('SMVC_SQL_NONE', 0);
if(!defined('SMVC_SQL_INIT'))
	  define('SMVC_SQL_INIT', 1);
if(!defined('SMVC_SQL_ALL'))
	  define('SMVC_SQL_ALL', 2);

class SmallMVCDriverPDO{
	private $db = null;
	private $table = null;
  private $pdo = null;
  private $result = null;
  private $fetch_mode = PDO::FETCH_ASSOC;
  private $query_params = array('select' => '*');
	protected $dbname = null;

  function __destruct(){
    $this->pdo = null;
  }
	function __construct($table = null,$poolName = null){
		if(!isset($table)){
			$table = '';
		}
		$config = SMvc::instance(null, 'default')->config;
		if(!$poolName)
			$poolName = 'database';
		if($poolName && isset(SMvc::instance(null, 'default')->dbs[$poolName])){
			return SMvc::instance(null, 'default')->dbs[$poolName];
		}
		if($poolName && isset($config[$poolName]) && !empty($config[$poolName]['plugin'])){
			if(!class_exists('PDO',false)){
			 $e = new SmallMVCException("PHP PDO package is required.", DEBUG);     
			 throw $e;
			}
			if(empty($config[$poolName])){
				$e = new SmallMVCException("database definitions required.", DEBUG);
			 throw $e;
			}
			if(empty($config[$poolName]['charset']))
				$config[$poolName]['charset'] = $config['charset'];

			$this->dbname = $config[$poolName]['name'];
			$dsn = !empty($config[$poolName]['dsn']) ? $config[$poolName]['dsn'] : "{$config[$poolName]['type']}:host={$config[$poolName]['host']};port={$config[$poolName]['port']};dbname={$config[$poolName]['name']}";
			try{
				$this->pdo = new PDO(
					$dsn,
					$config[$poolName]['user'],
					$config[$poolName]['pass'],
					array(PDO::ATTR_PERSISTENT => !empty($config[$poolName]['persistent']) ? true : false)
					);
				$this->pdo->exec("set names '{$config[$poolName]['charset']}'");
			}catch (PDOException $e) {
					$e = new SmallMVCException(sprintf("Can't connect to PDO database '{$config[$poolName]['type']}'. Error: %s",$e->getMessage()), DEBUG);
					throw $e;
			}
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);    
			try {
				$result = $this->pdo->query("SELECT 1 FROM `$table` LIMIT 1");
				$this->table = "`$table`";//set table if it exists
			} catch (Exception $e) {
				//do nothing because the table doesn't exists
			}
		}
	}

	public function getQueryString(){
		$tmp = $this->query_params;
    $query = $this->_query_assemble($params,$fetch_mode);
		$query = explode('?', $query);
		for($i = 0; $i < count($query); $i++){
		  $query[$i] .= $params[$i];
		}
		$query = implode('', $query);
		$this->query_params = $tmp;
		return $query;
	}
  public function select($clause){
    $this->query_params['select'] = $clause;
		return $this;
  }  
	public function exists(){
		$retArray = $this->query('all');
		if(!empty($retArray))
			return true;
		return false;
	}
  public function from($table){
		$this->query_params['from'] = "`{$table}`";
		$this->table = "`{$table}`";
    return $this; 
  }  
  public function table($table){
		$this->table = "`{$table}`";
    return $this; 
  }
  public function where($clause = null,$args = null){
		if(empty($clause) || is_int($clause)){
      $e = new SmallMVCException(sprintf("where cannot be empty and must be a string"), DEBUG);
			throw $e;
		}
  
		if(!is_array($clause)) $clause = array($clause);
		if(!is_array($args)) $args = array($args);
		foreach($clause as &$each_clause){
			if(preg_match('/(\w*\bis\b(\s*(not)){0,1})|(\w*\blike\b)/i',$each_clause))
				$each_clause = ' '.$each_clause.' ';
			else if(!preg_match('/[=<>]/',$each_clause))
			 $each_clause .= ' = ';  
		
			if(strpos($each_clause,'?')===false)
				$each_clause .= ' ? ';
		}
    $this->_where($clause,$args,'AND');    
		return $this;
  }
  public function orwhere($clause,$args){
		if(empty($clause) || is_int($clasue)){
      $e = new SmallMVCException(sprintf("where cannot be empty and must be a string"), DEBUG);
			throw $e;
		}
  
		if(!is_array($clause)) $clause = array($clause);
		if(!is_array($args)) $args = array($args);
		for($i = 0; $i < count($clause); ++$i){
			if(!preg_match('![=<>]!',$clause[$i])) $clause[$i] .= '=';  
			if(strpos($clause[$i],'?')===false) $clause[$i] .= '?';
		}
		//if condition is like this: where id=x or id=y or id=c
		//	expand $clause numbers equal to $args
		if(count($clause) === 1 && count($clause) < count($args)){
			$repeat_nums = count($args) - count($clause);
			for($i = 0; $i < $repeat_nums; ++$i){
				$clause[] = $clause[0];
			}
		}

    $this->_where($clause,$args,'OR');
		return $this;
  }
  public function join($join_table,$join_on,$join_type=null){
    $clause = "JOIN {$join_table} ON {$join_on}";
    
    if(!empty($join_type))
      $clause = $join_type . ' ' . $clause;
    
    if(!isset($this->query_params['join']))
      $this->query_params['join'] = array();
      
    $this->query_params['join'][] = $clause;
		return $this;
  } 
  public function in($field,$elements,$list=false){
    $this->_in($field,$elements,$list,'AND');
		return $this;
  }
  public function orin($field,$elements,$list=false){
    $this->_in($field,$elements,$list,'OR');
		return $this;
  }
  public function orderby($clause){    
    $this->_set_clause('orderby',$clause);
		return $this;
  }  
  public function groupby($clause){    
    $this->_set_clause('groupby',$clause);
		return $this;
  }  
  public function limit($limit, $offset=0){    
    if(!empty($offset))
      $this->_set_clause('limit',sprintf('%d,%d',(int)$offset,(int)$limit));
    else
      $this->_set_clause('limit',sprintf('%d',(int)$limit));

		return $this;
  }  
  public function query($type=null, $query = null, $fetch_mode = null){
		if(!isset($query)){
      $query = $this->_query_assemble($params,$fetch_mode);
		}
  
		switch($type){
			case 'one':
				$this->limit(1);
				return $this->_query($query,$params,SMVC_SQL_INIT,$fetch_mode);
			case 'none':
				return $this->_query($query,$params,SMVC_SQL_NONE,$fetch_mode);
			case 'all':
			default:
				return $this->_query($query,$params,SMVC_SQL_ALL,$fetch_mode);
		}
  }  
  public function update($columns){
    if(empty($columns)||!is_array($columns)){
      $e = new SmallMVCException("Unable to update, at least one column required", DEBUG);
			throw $e;
      return false;
    }
		$query[] = "UPDATE ";
    $query[] = "{$this->table} SET";
    $fields = array();
    $params = array();
    foreach($columns as $cname => $cvalue){
      if(!empty($cname)){
        $fields[] = "{$cname}=?";
        $params[] = $cvalue;
      }
    }
    $query[] = implode(',',$fields);
    
    // assemble where clause
    if($this->_assemble_where($where_string,$where_params)){    
      $query[] = $where_string;
      $params = array_merge($params,$where_params);
    }
    $this->query_params = array('select' => '*');
    return $this->_query($query,$params);
  }

  public function insert($columns){
    if(empty($columns)||!is_array($columns)){
      $e = new SmallMVCException("Unable to insert, at least one column required", DEBUG);
			throw $e;
    }
    $column_names = array_keys($columns);
		$query[] = 'INSERT INTO ';
    $query[] = sprintf("{$this->table} (`%s`) VALUES",implode('`,`',$column_names));
    $fields = array();
    $params = array();

		$is_multi_data = false;
		for($i = 0; $i < count($column_names); ++$i){
			if(is_array($columns[$column_names[$i]]) && !empty($columns[$column_names[$i]][0])){
				$is_multi_data = true;
				foreach($columns[$column_names[$i]] as $value_index => $value){
					$params[$value_index * count($column_names) + $i] = $value;
					$fields[] = '?';
				}
			}else{
				$params[$i] = $columns[$column_names[$i]];
				$fields[] = '?';
			}
		}
		ksort($params, SORT_NUMERIC);
		for($m = 0; $m < count($fields)/count($column_names); ++$m){
			$temp_fields = array();
			for($n = 0; $n < count($column_names); ++$n){
				$temp_fields = array_slice($fields, $m * count($column_names), count($column_names), false);
			}
			if($m === 0){
				$query[] = '(' . implode(',',$temp_fields) . ')';
			}else{
				$query[] = ',(' . implode(',',$temp_fields) . ')';
			}
		}
    $this->_query($query,$params);
    return $this->lastId();
  }
  public function delete(){
		$query[] = "DELETE ";
    $query[] = "FROM {$this->table}";
    $params = array();
    
    // assemble where clause
    if($this->_assemble_where($where_string,$where_params)){    
      $query[] = $where_string;
      $params = array_merge($params,$where_params);
    }

		$this->query_params = !empty($this->query_params) ? array_merge(array('select' => '*'),$this->query_params) : array('select' => '*');
    $result = $this->_query($query,$params);
    $this->query_params = array('select' => '*');
		return $result;
  }
  public function lastId(){
    return $this->pdo->lastInsertId();
  }

  public function numRows(){
    return $this->result->rowCount();
  }
  public function affectedRows(){
    return $this->result->rowCount();
  }

  private function _where($clause = array(), $args=array(), $prefix='AND'){    
    // sanity check
    if(empty($clause) || empty($args)) return false;
		//data format check
		if(!is_array($clause) || !is_array($args)){
			$e = new SmallMVCException("params format error, either clause or args must be an array", DEBUG);
			throw $e;
		}
		// make sure number of ? match number of args
		if(count($args) != count($clause)){
			$e = new SmallMVCException("Number of where clause args don't match number args", DEBUG);
			throw $e;
		}
		foreach($clause as $each_clause){
			$this->query_params['where']['clause'][] = $each_clause;
		}
		foreach($args as $each_args){
			$this->query_params['where']['args'][] = $each_args;
			$this->query_params['where']['prefix'][] = $prefix;
		}
      
    return $this->query_params['where'];
  }  
  private function _in($field,$elements,$list=false,$prefix='AND')
  { 
    if(!$list)
    {
      if(!is_array($elements))
        $elements = explode(',',$elements);
        
      // quote elements for query
      foreach($elements as $idx => $element)
        $elements[$idx] = $this->pdo->quote($element);
      
      $clause = sprintf("{$field} IN (%s)", implode(',',$elements));
    }
    else
      $clause = sprintf("{$field} IN (%s)", $elements);
    
    $this->_where($clause,array(),$prefix);
  }  
	//set clasue and args to $this->query_params[$type] array where type is 'gourpby' 'join' 'where' ...
  private function _set_clause($type, $clause, $args=array()){    
    // sanity check
    if(empty($type)||empty($clause))
      return false;
      
    $this->query_params[$type] = array('clause'=>$clause);
    
    if(isset($args))
      $this->query_params[$type]['args'] = $args;
      
  }  
  
  private function _query_assemble(&$params,$fetch_mode=null){
    if(empty($this->query_params['from'])){
			if(empty($this->table)){
				$e = new SmallMVCException("Table is not exists in this database or table empty(use ->from(\$table) to set one)", DEBUG);
				throw $e;
				return false;
			}
			$this->query_params['from'] = $this->table;
    }
    $query = array();
    $query[] = "SELECT {$this->query_params['select']}";
    $query[] = "FROM {$this->query_params['from']}";

    // assemble JOIN clause
    if(!empty($this->query_params['join']))
      foreach($this->query_params['join'] as $cjoin)
        $query[] = $cjoin;
    // assemble WHERE clause
    if($where = $this->_assemble_where($where_string,$params))
      $query[] = $where_string;
    // assemble GROUPBY clause
    if(!empty($this->query_params['groupby']))
      $query[] = "GROUP BY {$this->query_params['groupby']['clause']}";
    // assemble ORDERBY clause
    if(!empty($this->query_params['orderby']))
      $query[] = "ORDER BY {$this->query_params['orderby']['clause']}";
    // assemble LIMIT clause
    if(!empty($this->query_params['limit']))
      $query[] = "LIMIT {$this->query_params['limit']['clause']}";
		//re-construct a new query_params
    $this->query_params = array('select' => '*');
    
    return $query;
  }
	//make where condition string like this and stored in variable:
	//	WHERE xx [=<>] ? AND|OR oo [=<>] ? => $where
	//	array(xx_condition, oo_condition) => $params
  private function _assemble_where(&$where,&$params){
    if(!empty($this->query_params['where'])){
			array_walk_recursive($this->query_params['where'], array($this,'filter_query_params'));
      $where_parts = array();
      empty($params) ? $params = array() : null;
			//$query_params['where'] = array(clause => array(), 'args' => array(), 'prefix' = array());
			$cwhere = $this->query_params['where'];
			$params = array_merge($params,(array)$cwhere['args']);
			for($i = 0; $i < count($this->query_params['where']['clause']); ++$i){
				if($i == 0) $where_parts[] = " {$this->query_params['where']['clause'][$i]}";
				else $where_parts[] = " {$this->query_params['where']['prefix'][$i]} {$this->query_params['where']['clause'][$i]}";
			}
      $where = 'WHERE '.implode(' ',$where_parts);
      return true;
    }
    return false;
  }  
  private function _query($query,$params=null,$return_type = SMVC_SQL_NONE,$fetch_mode=null){
		$checkArray = array();
		foreach($query as $each){
			preg_match('/^WHERE\s+/', $each) ? null : array_push($checkArray, $each);
		}
		array_walk_recursive($checkArray, array($this,'filter_query_params'));
    $query = is_array($query) ? implode(' ',$query) : $query;
    /* if no fetch mode, use default */
    if(!isset($fetch_mode))
      $fetch_mode = PDO::FETCH_ASSOC;  
  
    /* prepare the query
			 query string is something like this:
				select * from _table_ where xx = ? AND|OR oo > ?
				update _table_ set xx = ?
		 */
    try {
      $this->result = $this->pdo->prepare($query);
    } catch (PDOException $e) {
        $e = new SmallMVCException(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query), DEBUG);
				throw $e;
    }      
    
    /* execute with params */
    try {
      $this->result->execute($params);  
    } catch (PDOException $e) {
        $e = new SmallMVCException(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query), DEBUG);
				throw $e;
    }
  
		/* get result with fetch mode */
		$this->result->setFetchMode($fetch_mode);  
		switch($return_type){
			case SMVC_SQL_INIT:
				return $this->result->fetch();
				break;
			case SMVC_SQL_ALL:
				return $this->result->fetchAll();
				break;
			case SMVC_SQL_NONE:
			default:
				return true;
				break;
		}
  }
	private function filter_query_params($item, $key){
		$regexs = array(
			'(and|or)\\b\s+(>|<|=|in|like)',
			'\\/\\*.+?\\*\\/',
			'<\\s*script\\b',
			'\\bEXEC\\b',
			'UNION.+?Select',
			'Update.+?SET',
			'Insert\\s+INTO.+?VALUES',
			'(Select|Delete).+?FROM',
			'(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)',
			'char\(.+?\)'
			);
		foreach($regexs as $regex){
			if(preg_match("/{$regex}/is", $item)){
				$e = new SmallMVCException("SQL Injection Detected", DEBUG);
				throw $e;
			}
		}
	}
}
?>
