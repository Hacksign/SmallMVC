<?php
// ------------------------------------------------------------------------

/* define SQL actions */
if(!defined('TMVC_SQL_NONE'))
  define('TMVC_SQL_NONE', 0);
if(!defined('TMVC_SQL_INIT'))
  define('TMVC_SQL_INIT', 1);
if(!defined('TMVC_SQL_ALL'))
  define('TMVC_SQL_ALL', 2);

class SmallMVCPDO
{
 
  var $pdo = null;
  var $result = null;
  var $fetch_mode = PDO::FETCH_ASSOC;
  var $query_params = array('select' => '*');
	private $table = null;

	protected $dbname = null;
  
	function __construct($config) {    
		if(!class_exists('PDO',false)){
		 $e = new Exception("PHP PDO package is required.");     
		 $e->type = DEBUG;
		 throw $e;
		}
		if(empty($config)){
			$e = new Exception("database definitions required.");
		 $e->type = DEBUG;
		 throw $e;
		}
		if(empty($config['charset']))
		 $config['charset'] = 'utf8';

		$this->dbname = $config['name'];
		$dsn = !empty($config['dsn']) ? $config['dsn'] : "{$config['type']}:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
		try{
      $this->pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        array(PDO::ATTR_PERSISTENT => !empty($config['persistent']) ? true : false)
        );
      $this->pdo->exec("SET CHARACTER SET {$config['charset']}"); 
    }catch (PDOException $e) {
        $e = new Exception(sprintf("Can't connect to PDO database '{$config['type']}'. Error: %s",$e->getMessage()));
				$e->type = DEBUG;
				throw $e;
    }
    
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);    
  }

  function select($clause){
    return $this->query_params['select'] = $clause;
  }  
	function table($table = null){
		$this->table = $table;
	}
  function from($clause){
    return $this->query_params['from'] = $clause;
  }  
  function where($clause,$args){
		if(empty($clause)){
      $e = new Exception(sprintf("where cannot be empty"));
			$e->type = DEBUG;
			throw $e;
		}
  
    if(!preg_match('![=<>]!',$clause))
     $clause .= '=';  
  
    if(strpos($clause,'?')===false)
      $clause .= '?';
      
    $this->_where($clause,(array)$args,'AND');    
  }  
  function orwhere($clause,$args)
  {
    $this->_where($clause,$args,'OR');    
  }  
  private function _where($clause, $args=array(), $prefix='AND'){    
    // sanity check
    if(empty($clause))
      return false;
    
    // make sure number of ? match number of args
		if(($count = substr_count($clause,'?')) && (count($args) != $count)){
      $e = new Exception(sprintf("Number of where clause args don't match number of ?: '%s'",$clause));
			$e->type = DEBUG;
			throw $e;
		}
      
    if(!isset($this->query_params['where']))
      $this->query_params['where'] = array();
      
    return $this->query_params['where'][] = array('clause'=>$clause,'args'=>$args,'prefix'=>$prefix);
  }  

  function join($join_table,$join_on,$join_type=null){
    $clause = "JOIN {$join_table} ON {$join_on}";
    
    if(!empty($join_type))
      $clause = $join_type . ' ' . $clause;
    
    if(!isset($this->query_params['join']))
      $this->query_params['join'] = array();
      
    $this->query_params['join'][] = $clause;
  } 

  function in($field,$elements,$list=false)
  {
    $this->_in($field,$elements,$list,'AND');
  }

  function orin($field,$elements,$list=false)
  {
    $this->_in($field,$elements,$list,'OR');
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
  function orderby($clause)
  {    
    $this->_set_clause('orderby',$clause);
  }  
  function groupby($clause){    
    $this->_set_clause('groupby',$clause);
  }  
  function limit($limit, $offset=0)
  {    
    if(!empty($offset))
      $this->_set_clause('limit',sprintf('%d,%d',(int)$offset,(int)$limit));
    else
      $this->_set_clause('limit',sprintf('%d',(int)$limit));
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
				$e = new Exception("Unable to get(), set from() first");
				$e->type = DEBUG;
				throw $e;
				return false;
			}
			$this->query_params['from'] = $this->table;
    }
		array_walk_recursive($this->query_params, array($this,'filter_query_params'));
    $query = array();
    $query[] = "SELECT {$this->query_params['select']}";
    $query[] = "FROM `{$this->query_params['from']}`";

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
    
    $query_string = implode(' ',$query);
    
		//re-construct a new query_params
    $this->query_params = array('select' => '*');
    
    return $query_string;
    
  }
	//make where condition string like this and stored in variable:
	//	WHERE xx [=<>] ? AND|OR oo [=<>] ? => $where
	//	array(xx_condition, oo_condition) => $params
  private function _assemble_where(&$where,&$params){
    if(!empty($this->query_params['where'])){
      $where_init = false;
      $where_parts = array();
      empty($params) ? $params = array() : null;
			//$query_params['where'] = array( 0 => array('clause' => 'id = ? ...', 'args' => array(), 'perfix' = 'AND'), ...);
      foreach($this->query_params['where'] as $cwhere){
        $prefix = !$where_init ? 'WHERE' : $cwhere['prefix'];
        $where_parts[] = "{$prefix} {$cwhere['clause']}";
        $params = array_merge($params,(array) $cwhere['args']);
        $where_init = true;
      }
      $where = implode(' ',$where_parts);      
      return true;
    }
    return false;
  }  
  
  function query($query=null,$params=null,$fetch_mode=null){
    if(!isset($query))
      $query = $this->_query_assemble($params,$fetch_mode);
  
    return $this->_query($query,$params,TMVC_SQL_NONE,$fetch_mode);
  }  

  function query_all($query=null,$params=null,$fetch_mode=null)
  {
    if(!isset($query))
      $query = $this->_query_assemble($params,$fetch_mode);
  
    return $this->_query($query,$params,TMVC_SQL_ALL,$fetch_mode);
  }  

  function query_one($query=null,$params=null,$fetch_mode=null)
  {
    if(!isset($query))
    {
      $this->limit(1);
      $query = $this->_query_assemble($params,$fetch_mode);
    }
  
    return $this->_query($query,$params,TMVC_SQL_INIT,$fetch_mode);
  }  
  
  function _query($query,$params=null,$return_type = TMVC_SQL_NONE,$fetch_mode=null){
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
        $e = new Exception(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query));
				$e->type = DEBUG;
				throw $e;
        return false;
    }      
    
    /* execute with params */
    try {
      $this->result->execute($params);  
    } catch (PDOException $e) {
        $e = new Exception(sprintf("PDO Error: %s Query: %s",$e->getMessage(),$query));
				$e->type = DEBUG;
				throw $e;
        return false;
    }
  
    /* get result with fetch mode */
    $this->result->setFetchMode($fetch_mode);  
  
    switch($return_type)
    {
      case TMVC_SQL_INIT:
        return $this->result->fetch();
        break;
      case TMVC_SQL_ALL:
        return $this->result->fetchAll();
        break;
      case TMVC_SQL_NONE:
      default:
        return true;
        break;
    }
  }

  function update($columns){
    if(empty($columns)||!is_array($columns))
    {
      $e = new Exception("Unable to update, at least one column required");
			$e->type = DEBUG;
			throw $e;
      return false;
    }
    $query = array("UPDATE {$this->table} SET");
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

    $query = implode(' ',$query);
    
    $this->query_params = array('select' => '*');
    
    return $this->_query($query,$params);
  }

  function insert($columns)
  {
    if(empty($columns)||!is_array($columns))
    {
      $e = new Exception("Unable to insert, at least one column required");
			$e->type = DEBUG;
			throw $e;
      return false;
    }
    
    $column_names = array_keys($columns);
    
    $query = array(sprintf("INSERT INTO `{$this->table}` (`%s`) VALUES",implode('`,`',$column_names)));
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
    
    $query = implode(' ',$query);
    
    $this->_query($query,$params);
    return $this->last_insert_id();
  }

  
  function delete(){
    $query = array("DELETE FROM `{$this->table}`");
    $params = array();
    
    // assemble where clause
    if($this->_assemble_where($where_string,$where_params))
    {    
      $query[] = $where_string;
      $params = array_merge($params,$where_params);
    }

    $query = implode(' ',$query);
    
		$this->query_params = !empty($this->query_params) ? array_merge(array('select' => '*'),$this->query_params) : array('select' => '*');
    
    return $this->_query($query,$params);
  }
  function next($fetch_mode=null)
  {
    if(isset($fetch_mode))
      $this->result->setFetchMode($fetch_mode);
    return $this->result->fetch();
  }
  function last_insert_id()
  {
    return $this->pdo->lastInsertId();
  }

  function num_rows()
  {
    return $this->result->rowCount();
  }
  function affected_rows()
  {
    return $this->result->rowCount();
  }
  function __destruct(){
    $this->pdo = null;
  }
  
	private function filter_query_params($item, $key){
		$regexs = array(
			'\'',
			'(and|or)\\b.+?(>|<|=|in|like)',
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
				$e = new Exception("SQL Injection Detected");
				$e->type = ERROR;
				throw $e;
				exit();
			}
		}
	}
}
?>
