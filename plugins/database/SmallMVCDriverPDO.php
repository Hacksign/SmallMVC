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
			$dsn = !empty($config[$poolName]['dsn']) ? $config[$poolName]['dsn'] : "{$config[$poolName]['type']}:host={$config[$poolName]['host']};dbname={$config[$poolName]['name']}";
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
		$tmp = $this->query_params;
		$retArray = $this->query('all');
		$this->query_params = $tmp;
		if(!empty($retArray))
			return true;
		return false;
	}
  public function from($clause){
		$this->query_params['from'] = "`$clause`";
    return $this; 
  }  
  public function table($table){
		$this->table = $table;
    return $this; 
  }  
  public function where($clause = null,$args = null){
		if(empty($clause) || is_int($clause)){
      $e = new SmallMVCException(sprintf("where cannot be empty and must be a string"), DEBUG);
			throw $e;
		}
  
		if(is_string($clause))
			$clause = array($clause);
		foreach($clause as &$each){
			if(preg_match('/(\w*\bis\b(\s*(not)){0,1})|(\w*\blike\b)/i',$each))
				$each = ' '.$each.' ';
			else if(!preg_match('/[=<>]/',$each))
			 $each .= ' = ';  
		
			if(strpos($each,'?')===false)
				$each .= ' ? ';
		}
    $this->_where($clause,(array)$args,'AND');    
		return $this;
  }
  public function orwhere($clause,$args){
		if(empty($clause) || is_int($clasue)){
      $e = new SmallMVCException(sprintf("where cannot be empty and must be a string"), DEBUG);
			throw $e;
		}
  
		if(is_string($clause))
			$clause = array($clause);
		for($i = 0; $i < count($clause); $i++){
			if(!preg_match('![=<>]!',$clause[$i]))
			 $clause[$i] .= '=';  
		
			if(strpos($clause[$i],'?')===false)
				$clause[$i] .= '?';
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
    if(empty($columns)||!is_array($columns))
    {
      $e = new SmallMVCException("Unable to update, at least one column required", DEBUG);
			throw $e;
      return false;
    }
		$query[] = "UPDATE ";
    $query[] = "{$this->table} SET";
    $fields = array();
    $params = array();
    foreach($columns as $cname => $cvalue)
    {
      if(!empty($cname))
      {
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

  public function insert($columns)
  {
    if(empty($columns)||!is_array($columns))
    {
      $e = new SmallMVCException("Unable to insert, at least one column required", DEBUG);
			throw $e;
      return false;
    }
    
    $column_names = array_keys($columns);
    
		$query[] = 'INSERT INTO ';
    $query[] = sprintf("{$this->table} (`%s`) VALUES",implode('`,`',$column_names));
    $fields = array();
    $params = array();
    foreach($columns as $cname => $cvalue)
    {
      if(!empty($cname))
      {
        $fields[] = "?";
        $params[] = $cvalue;
      }
    }
    $query[] = '(' . implode(',',$fields) . ')';
    $this->_query($query,$params);
    return $this->lastId();
  }
  public function delete(){
		$query[] = "DELETE ";
    $query[] = "FROM {$this->table}";
    $params = array();
    
    // assemble where clause
    if($this->_assemble_where($where_string,$where_params))
    {    
      $query[] = $where_string;
      $params = array_merge($params,$where_params);
    }

		$this->query_params = !empty($this->query_params) ? array_merge(array('select' => '*'),$this->query_params) : array('select' => '*');
    return $this->_query($query,$params);
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

  private function _where($clause, $args=array(), $prefix='AND'){    
    // sanity check
    if(empty($clause))
      return false;
    
    // make sure number of ? match number of args
		if(is_array($clause) && (count($args) != count($clause))){
      $e = new SmallMVCException("Number of where clause args don't match number args", DEBUG);
			throw $e;
		}
      
    if(!isset($this->query_params['where']))
      $this->query_params['where'] = array();
      
    return $this->query_params['where'] = array('clause'=>$clause,'args'=>$args,'prefix'=>$prefix);
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
			//$query_params['where'] = array( 0 => array('clause' => 'array('id = ', 'md5 = ')', 'args' => array(), 'prefix' = 'AND'), ...);
			$cwhere = $this->query_params['where'];
			$params = array_merge($params,(array) $cwhere['args']);
			$where_parts[] = join(" ".$cwhere['prefix']." ", $cwhere['clause']);
      $where = 'WHERE '.implode(' AND ',$where_parts);
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
        return false;
    }      
    
    /* execute with params */
    try {
      $this->result->execute($params);  
    } catch (PDOException $e) {
        $e = new SmallMVCException(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query), DEBUG);
				throw $e;
        return false;
    }
  
    /* get result with fetch mode */
    $this->result->setFetchMode($fetch_mode);  
  
    switch($return_type)
    {
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
