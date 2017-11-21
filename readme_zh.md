#1. 概述

本项目为CodeIgniter3扩展了其原生模型，提供了一个简单到ORM实现。
主要用于增删改事务比较多的场合，使得其代码可读性和可维护性得以提高。
不适合join和子查询比较多的场合。

#2. 安装

需要在application/config/config.php中修改composer加载路径
```php
$config['composer_autoload'] = dirname(APPPATH).DIRECTORY_SEPARATOR.'vendor/autoload.php';
```
composer.json中添加
```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/irelance/ci-model.git"
    }
]
```
composer.json的"require"中添加
```
"irelance/ci-model": "dev-master"
```
由于CodeIgniter3的入口文件与vendor目录在同一级中，因此需要配置服务器禁止其访问vendor目录

nginx配置如下：
```
location ^~ /vendor/ {
     deny all;
}
```

#3. 模型

##3.1. 定义模型

$_table：用于定义该模型使用到数据表

$_fields：用于定义该模型使用的数据列
```php
class User extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'user';
    protected $_fields = ['id', 'name', 'password', 'created_at', 'updated_at',];
}
```

##3.2. 新建对象

```php
$this->load->model('User');
$user = new User();
```

##3.3. 查询

###3.3.1. 返回一个User实例或者null：

```php
$this->load->model('User');
$user = User::findFirst([
    'conditions' => 'id=?',
    'bind'       => [3],
]);
$array=$user->toArray();//对象转数组
$json=json_encode($user);//对象转json
```

###3.3.2. 返回一个模型集（ModelSet）：

```php
$this->load->model('User');
$users = User::find([
    'conditions' => 'name like ?',
    'bind'       => ["a%"],
    'order'      => 'id desc',
    'limit'      => [2,3]
]);
$array=$users->toArray();//对象转数组
$json=json_encode($users);//对象转json
$userNumber=count($users);
```

###3.3.3. 返回一个分页（Paginator）：

重新封装了原生pagination，可以使用config/pagination.php配置相关设置
对部分选项进行了限定
```php
//controller
$this->load->model('User');
$pagination = User::pagination(3,[
    'conditions' => 'name like ?',
    'bind'       => ["a%"],
    'order'      => 'id desc',
]);
$array=$pagination->toArray();//对象转数组
$json=json_encode($pagination);//对象转json
$itemTotal=count($pagination);

//view
foreach($pagination as $item){
    echo $item->name;//输出用户名
}
echo $pagination->links();//输出链接
```

##3.4. 修改属性、对象保存和删除

对象集也适用以下方法进行批量修改：
```php
$user->setData('name','tom');
$user->setData([
    'name'=>'jerry',
    'password'=>'silly tom',
]);
/*更新或插入数据，返回bool值
 *如果启用了$_timeLogs
 * 数据插入时，'created_at'和'updated_at'自动更新
 * 数据更新时，'updated_at'自动更新
 */
$user->save();
$user->delete();//删除数据，返回bool值
```

#4. 模型关系（relation）

目前仅支持SimpleObjectRelationalMappingModel类

##4.1. 有一个(1-1)

例如：小猪Duck有一个父亲Jack
```php
class Pig extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'pig';
    protected $_fields = ['id', 'name', 'father_id', 'created_at', 'updated_at',];
    public function __construct()
    {
        parent::__construct();
        $this->hasOne('father_id', 'Pig', 'id', ['alias' => 'father']);//定义关系
    }
}
$this->load->model('Pig');
$duck = Pig::findFirst([
    'conditions' => 'name="Duck"',
]);
$jack=$duck->getFather();//找到Duck的父亲了
```

##4.2. 有很多(1-n)

例如：小明有很多任务
```php
class Mission extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'mission';
    protected $_fields = ['id', 'name', 'worker_id', 'created_at', 'updated_at',];
}
class Worker extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'worker';
    protected $_fields = ['id', 'name', 'created_at', 'updated_at',];
    public function __construct()
    {
        parent::__construct();
        $this->hasMany('id', 'Mission', 'worker_id');//定义关系
    }
}
$this->load->model('Worker');
$xiaoMing = Worker::findFirst([
    'conditions' => 'name="xiao ming"',
]);
$missions=$xiaoMing->getMission();//找到小明的所有任务了
```

##4.3. 从很多到很多(1-n-n)

例如：用户Hacker有很多角色，角色是根据关系表确定的
```php
class Role extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'role';
    protected $_fields = ['id', 'name', 'created_at', 'updated_at',];
}
class UserRole extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'user_role';
    protected $_fields = ['id', 'user_id', 'role_id',];
}
class User extends \Irelance\Ci3\Model\SimpleObjectRelationalMappingModel
{
    protected $_table = 'user';
    protected $_fields = ['id', 'name', 'created_at', 'updated_at',];
    public function __construct()
    {
        parent::__construct();
        $this->hasManyToMany('id', 'UserRole', 'user_id', 'role_id', 'Role', 'id');//定义关系
    }
}
$this->load->model('User');
$hacker = User::findFirst([
    'conditions' => 'name="Hacker"',
]);
$roles=$hacker->getRole();//找到Hacker的所有角色
```
