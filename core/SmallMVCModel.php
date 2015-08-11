<?php
/**
 * License:
 * (MIT License)
 * Copyright (c) 2013 Hacksign (http://www.hacksign.cn)
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**
 * 框架数据对象引擎.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCModel{
  /**
   * @var resource|null $driver 对象实例
   */
	private $driver = null;
  /**
   * 初始化对象池.
   *
   * 此函数根据工程/项目配置文件中有关数据库配置节(由$poolName指定)初始化与数据库通信的对象驱动.
   * 并根据配置文件中的first_param_position截取构造函数的参数,然后将这些参数传递给配置文件$config[$poolName]['plugin']指定的数据库驱动类,以初始化一个类对象与数据库通信.
   *
   *
   * @param string|null $tableName 表名称.
   * @param string|default $poolName 对象池名称.
   */
	function __construct($tableName, $poolName){
		$params_list = func_get_args();
		$userModelDriver = SMvc::instance(null, 'default')->config[$poolName]['plugin'];
		load($userModelDriver);
		try{
			$refClass = new ReflectionClass($userModelDriver);
			$firstParamPosition = SMvc::instance(null, 'default')->config[$poolName]['first_param_position'];
			$firstParamPosition = empty($firstParamPosition) ? 0 : $firstParamPosition;
			$params_list = array_slice($params_list, $firstParamPosition);
			$this->driver = $refClass->newInstanceArgs($params_list);
		}catch(ReflectionException $refExp){
			$e = new SmallMVCException($refExp->__toString(), DEBUG);
			throw $e;
		}

	}
  /**
   * magic方法,用于访问驱动类中的方法,和数据库通信.
   *
   * @param string $name 驱动类的方法名称.
   * @param mixed|null $args 传递给驱动对象的参数.
   *
   * @return mixed 驱动对象的处理结果.
   */
	function __call($name, $args = null){
		$retVal = call_user_func_array(array($this->driver, $name), $args);
		//check if $this is an drivered class and base class has none special return value
		if($retVal === $this->driver && $this->dirver !== $this) return $this;
		else return $retVal;
	}
}
?>
