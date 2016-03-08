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
 * 框架加载器.
 *
 * @author Hacksign <evilsign@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @category 框架核心文件
 */
class SmallMVCLoader{
    /**
     * 构造函数
     */
    function __construct(){
    }
    /**
     * 加载Model类,并初始化一个实例.
     *
     * 已经加载的Model对象不会重新被实例化,而是直接返回之前加载的Model对象.
     *
     * @param string $modelName Model对象名称,不需要.php后缀或Model后缀
     * @param string|null $poolName 数据库池标识,如果为null,则使用默认名称'database'
     * @param ... 更多的参数会被作为modelName对象的构造参数.
     *
     * @return object
     */
    public function model($modelName, $poolName = null){
        $params_list = func_get_args();
        //remove modelName and poolName
        empty($params_list)? null : array_shift($params_list);
        empty($params_list)? null : array_shift($params_list);
        //end of remove
        $modelNameEmpty = false;
        if(empty($modelName)){
            $modelNameEmpty = true;
            $table = null;
        }else{
            $table = $modelName;
            $table = preg_replace("/(.*?)Model$/", "$1", $table);
        }
        $poolName = empty($poolName) ? 'database' : $poolName;
        (preg_match("/Model$/", $modelName) || $modelNameEmpty)? null : $modelName .= 'Model';
        if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]+$/', $modelName) && !$modelNameEmpty){
            $e = new SmallMVCException("Model name '{$modelName}' is an invalid syntax", DEBUG);
            throw $e;
        }
        if(method_exists($this, $modelName)){
            $e = new SmallMVCException("Model name '{$modelName}' exists in SmallMVCLoader change another name plz.", DEBUG);
            throw $e;
        }

        //get controller object
        $controller = SMvc::instance(null, 'controller');
        if(!empty($controller) && isset($controller->$modelName))
            return $controller->$modelName;

        if($modelNameEmpty || !$this->includeFile($modelName)){
            $modelName = SMvc::instance(null, 'default')->config['system']['model'];
            $this->includeFile($modelName);
        }
        try{
            $refClass = new ReflectionClass($modelName);
            $params_list = array_shift($params_list);
            array_unshift($params_list, $poolName);
            array_unshift($params_list, $table);
            $modelInstance = $refClass->newInstanceArgs($params_list);
        }catch(ReflectionException $refExp){
            $e = new SmallMVCException($refExp->__toString(), DEBUG);
            throw $e;
        }
        if(!$modelNameEmpty && !empty($controller))//store model if it is a exists model
            $controller->{$modelName} = $modelInstance;
        return $modelInstance;
    }
    /**
     * 初始化libName所代表的Library类,并返回此类的实例.
     *
     * 该函数可能会修改libName为实际加载的类名.每一次调用此函数,都会重新实例化一个libName对象.
     *
     * @param $libName 要加载的类名.改名成可能会根据实际加载的类名而被改变.
     * @param ... 更多的参数将会被当作libName对象的构造参数.
     *
     * @return object
     */
    public function library(&$libName){
        $params_list = func_get_args();
        //remove $libName
        empty($libName) ? null : array_shift($params_list);
        $alias = $libName;
        if(empty($alias)){
            $e = new SmallMVCException("Library name cannot be empty", DEBUG);
            throw $e;
        }
        if(!preg_match('!^[@a-zA-Z]\.{0,1}[a-zA-Z_.]+$!', $alias)){
            $e = new SmallMVCException("Library name '{$alias}' is an invalid syntax", DEBUG);
            throw $e;
        }
        if(method_exists($this, $alias)){
            $e = new SmallMVCException("Library name '{$alias}' is an invalid name", DEBUG);
            throw $e;
        }
        if($this->includeFile($libName)){
            try{
                $refClass = new ReflectionClass($libName);
                return $refClass->newInstanceArgs($params_list);
            }catch(ReflectionException $e){
                $e->type = DEBUG;
                throw $e;
            }
        }else{
            $e = new SmallMVCException("Library:'{$libName}' not found!", EXCEPTION_NOT_FOUND);
            throw $e;
        }
    }
    /**
     * 加载常规文件
     *
     * 加载任意的常规文件到框架中,并不会自动创建对象.
     *
     * 加载的文件名可能会根据实际加载的文件名称而被改变.
     *
     * @param $scriptName 要加载的文件名,可能会被改变.
     *
     * @return boolean
     */
    public function script(&$scriptName){
        if(!preg_match('/^[0-9a-zA-Z@][a-zA-Z_.0-9]+$/', $scriptName)){
            $e = new SmallMVCException("Invalid script name '{$scriptName}'", DEBUG);
            throw $e;
        }
        return $this->includeFile($scriptName);
    }
    /**
     * 判断文件是否存在
     *
     * @param $fileName 文件名,可以不包含php后缀
     *
     * @return boolean
     */
    private function fileExists($fileName = null){
        /*check errors and prepare data*/
        if(!isset($fileName) || empty($fileName))
            return false;
        if(!preg_match('/\.php$/', $fileName))
            $fileName .= '.php';
        $appPath = APPDIR;
        $ps = explode(PS, get_include_path());
        $ps = array_merge($ps, SMvc::instance(null, 'default')->config['project']['directory']);
        foreach($ps as $path){
            if(preg_match('/^@\./', $fileName) && preg_match("/^$appPath/", $path)){
                $testPath = $path. DS . preg_replace('/^@\.(.*)/', "$1", $fileName);
            }else{
                $testPath = $path. DS .$fileName;
            }

            if(file_exists($testPath)) return true;
            else unset($testPath);
        }
        return false;
    }
    /**
     * 包含fileName指定的文件.
     *
     * fileName可以不以.php结尾.fileName会被修正为真正包含的文件名(不包含'.php'结尾).
     *
     * 如果$fileName以'@.'开头.以'@.'开头时表示只从工程目录下寻找文件.
     *
     * 如果不以'@.'开头,则首先在项目目录下寻找文件,然后再工程目录下寻找文件.
     *
     * 每个目录之间以'.'分隔.
     *
     * @param string $fileName 要包含的文件名.
     *
     * @return void
     */
    private function includeFile(&$fileName = null){
        $fileName = trim($fileName);
        if(!isset($fileName) || empty($fileName)){
            $e = new SmallMVCException("fileName must be set", DEBUG);
            throw $e;
        }
        if(!preg_match('/\.php$/', $fileName))
            $fileName .= '.php';
        if(preg_match('/^@\./', $fileName)){
            $fileName = preg_replace('/^@\.(.*)/', "$1", $fileName);
            $includePath = implode(PS, SMvc::instance(null, 'default')->config['project']['directory']);
            $includePath = str_replace(DS.DS, DS, $includePath);
        }else{
            $open_basedir = ini_get('open_basedir');
            if(!empty($open_basedir)){
                //pick out path not allowed by php settings'open_basedir'
                // . is a exception
                $allowedPath = explode(PS, ini_get('open_basedir'));
                $includePath = explode(PS, get_include_path());
                foreach($includePath as $eachPath){
                    if($eachPath !== '.'){
                        if(in_array($eachPath, $allowedPath)){
                            $checkedPath[] = $eachPath;
                        }
                    }else{
                        $checkedPath[] = $eachPath;
                    }
                }
                $includePath = implode(PS, SMvc::instance(null, 'default')->config['project']['directory']) . PS . implode(PS, $checkedPath);
            }else{
                $includePath = implode(PS, SMvc::instance(null, 'default')->config['project']['directory']) . PS . get_include_path();
            }
        }
        $subPath = explode('.', $fileName);
        $fileName = implode('.', array_slice($subPath, -2, 2));
        $modifiedName = preg_replace('/(.*)\.php$/', '$1', $fileName);
        array_splice($subPath, -2, 2);
        !empty($subPath) ? $fileName = implode(DS, $subPath).DS.$fileName : null;
        $ps = explode(PS, $includePath);
        foreach($ps as $path){
            if(file_exists($path . DS . $fileName)){
                require_once($path . DS . $fileName);
                $fileName = $modifiedName;
                return true;
            }
        }
        //still not found
        //sacrifice capability to find
        foreach($ps as $path){
            $path = preg_replace('/(\/+)|(\\+)/', DS, $path);
            foreach(scandir($path) as $each_file){
                if(strcasecmp($each_file, $fileName) === 0 && ($each_file !== '.' || $each_file !== '..')){
                    require_once($path . DS . $each_file);
                    $fileName = preg_replace('/(.*)\.php$/', '$1', $each_file);
                    return true;
                }
            }
        }
        return false;
    }
}
?>
