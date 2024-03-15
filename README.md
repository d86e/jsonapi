# JSONAPI

> 运行环境要求 PHP7.2+, 建议 PHP7.4.33, 兼容 PHP8.0+

JSONAPI 是一个根据数据库表全自动生成接口和文档的系统,  
欢迎大家在自己项目中使用它做二次开发.

不从零开始写数据库操作相关功能,  
尽可能防止 SQL 注入等漏洞出现,  
尽量使用经过多数人验证的各种方法,  
所以 JSONAPI 基于[ThinkPHP v6.1.0](https://github.com/top-think/framework)开发,  
严格来说更像是对[ThinkPHP](https://www.kancloud.cn/manual/thinkphp6_0/content)开发使用上的部份心得汇总,  
更多开发技巧可参考[ThinkPHP](https://www.kancloud.cn/manual/thinkphp6_0/content)官方文档.

## 主要特性

- 无需建路由对应的控制器 (Thinkphp 原本是需要的)
- 无需建数据表对应的模型 (Thinkphp 原本是需要的)
- 自动根据数据表生成标准的 JSON 接口,包含增删改查
- 自动生成美观的接口文档,包括自己写的其他所有接口
- 数据可验证,所有接口自动加载自定义的规则
- 所有接口请求都有事件产生,可自行订阅
- 可自由新建控制器,会自动继承增删改查方法也可重写
- 可自由写新的功能更强大的接口,函数注释会自动生成文档
- 动态生成数据模型,无需直接用数据库方法
- 可对数据进行严格且灵活的权限控制,增删改查独立控制
- 可自由添加中间件,服务,容器和注入等
- 其他 ThinkPHP 的优点统统继承,事件驱动,分布式数据库,Swoole 以及协程等等

## 安装说明

```
# git clone https://github.com/d86e/jsonapi
# cd jsonapi
# composer install
```

在 `/config/database.php` 或 `.env` 配置好数据库连接登录信息  
开发环境请执行如下命令启动 web 服务,默认端口为`8000`

```
# php think run
```

或者自定义端口

```
# php think run -p 8080
```

如果是生产环境,请将 web 根目录设为 `/public` 并将入口文件设为 `index.php`
同时给站点设置好隐藏 index.php 的伪静态规则

Apache 示例:

```
<IfModule mod_rewrite.c>
  Options +FollowSymlinks -Multiviews
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>

```

Nginx 示例:

```
location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}
```

如果需要更新 ThinkPHP 框架版本请使用以下命令

```
# composer update topthink/framework
```

请通过 web 链接访问安装权限控制数据库表  
`http://localhost:8000/install`

至此所有安装操作完成, 可以访问自动生成的接口文档了  
`http://localhost:8000/api`

## 使用说明

##### 一、文档生成规则

文档访问地址是`api`这个控制器的默认方法,所以应该是类似这样的地址  
`http://localhost:8000/api`  
每次访问程序会读一遍数据库和控制器目录动态显示这个文档,  
而不是生成静态的文档文件,以确保文档随时处于最新状态.
此文档仅在`app_debug`模式开启的情况下显示,  
在生产环境关闭 `app_debug` 模式后访问会是一个空白页面,以此确保线上系统的文档安全.

默认情况下,程序会读取表名做为控制器名(不包括表前缀),  
然后再在控制器名后加上增删改查其中之一种方法,
得到一个完整的接口地址(每个接口需要添加`api`路由标识).  
示例(读取用户列表):`http://localhost:8000/api/User/list`

程序还会读取 SQL 表的的注释做为接口的说明名称,  
同时读取每个 SQL 表的字段信息做为接口参数说明,  
包括 字段名, 字段类型, 默认值, 字段注释
<img src="https://gitee.com/d86e/images/raw/main/WX20240312-214529@2x.png" width="70%">

如果需要自己写控制器和一些高级方法,  
同样可以自动生成接口及文档
但是请写好函数的注释,  
程序会读取`@name `后面的函数说明名称,  
会读取`@param `后面的字段名和注释,  
字段名和注释中间用空格隔开,注释后面是必填,再后面是默认值,  
示例 `@param name 产品的名字 否 Null`
<img src="https://gitee.com/d86e/images/raw/main/WX20240312-214712@2x.png" width="70%">

##### 二、接口访问事件

基础控制器内已经写了增删改查方法及事件触发,  
自动生成的接口或自定义控制器继承它都会触发接口访问事件,  
事件标识是`RequestApi`  
可自行写监听类或订阅类,  
监听类的目录是`/app/listener/`  
订阅类的目录是`/app/subscribe/`  
事件`RequestApi`会把整个 Request Param 做为参数传递给监听或订阅类,  
监听和订阅类需要在事件定义文件`app/event.php`内注册绑定才会生效,
代码内有写了一个`RequestApi`的监听和订阅以及注册示例.

也可以在任意地方直接使用`Event::listen`对事件进行监听,  
或者使用`Event::subscribe`对事件进行订阅,  
更多高级用法和说明请看 [ThinkPHP 文档 - 事件](https://doc.thinkphp.cn/v6_1/shijianjizhi.html)

##### 三、数据提交及验证

所有接口支持自定议数据验证,会动自加载验证规则类.  
验证规则类在`/app/validate`内, 里面有一个示例`User.php`  
支持场景验证, 验证错信息支持多语言.
更多使用方法请看 [ThinkPHP - 验证器](https://doc.thinkphp.cn/v6_1/yanzhengqi.html)

提交的参数里,这些是可以用的:
`field`过滤返回想要的字段,  
`page` 字段是提交分页信息的,用法遵循 Thinkphp,  
`order` 数据排序.

接口支持所有类型的请求,get、post 等等

##### 四、用户权限控制

整个权限控制一共使用了四张数据表,如有和你业务系统表重名请自行修改,  
`auth`表用来记录其他表的名称和增删改查四种分别开启权限的模式,  
 一共有三种模式:  
 0、是只能操作用户自有数据.  
 1、规则表没有规则时可操作所有数据.  
 2、规则表没有规则时不可操作任何数据.

增删改查分别对应的规则表:  
`auth_write_rule`  
`auth_delete_rule`  
`auth_update_rule`  
`auth_read_rule`  
每个规则表都会记录 `name`表名 `data_id`数据 id `user_id`用户 id `type`是否可写.
程序会先读取`auth`表以确认访问的数据表是否开启了权限控制以及控制的模式.
然后会拿用户 id 去各个操作方法对应的表里找规则以决定用户是否有相关的操作权限.

一般约定用户 id 用`user_id`, 而数据 id 用`data_id`,  
在`baseModel.php`内使用了`Session::get('user_id')`的方式读取用户 id,
所以在其他地方处理用户登录时,应该把用户 id 以`user_id`为索引存入 Session 内.  
如果你不想使用 Session 记录用户的 id, 请自行改用别的方式存取, 仅在`baseModel.php`内有两个地方使用`Session::get('user_id')`

## 单元测试

测试工具用的是 PHPUnit 9.6.17  
[文档地址](https://docs.phpunit.de/en/9.6/index.html) `https://docs.phpunit.de/en/9.6/index.html`

封装了一个 Trait 类`TestExtendTrait`, 用于向控制器的方法请求数据,  
可以在测试类中直接使用, 像这样:  
`use tests\TestTesxt`

`tests/ApiTest.php` 是接口的单元测试类,  
里面已经写好了增删改查和文档页面的测试,  
共六个测试方法六个断言,  
测试`user`表, 请自行增加此表和测试数据,  
修改 `tests/ApiTest.php` 内的测试字段和数据.

然后执行单元测试命令:

```
./vendor/bin/phpunit tests/ApiTest.php
```

当测试通过后, 程序会输出:

```
......         6 / 6 (100%)

Time: 00:00.095, Memory: 10.00 MB

OK (6 tests, 6 assertions)
```

## 参与开发

想参与开发 jsonapi, 应该怎么做?  
首先 `Fork` 一份代码仓库, 然后`git clone`你的仓库到本地,  
按照上面的安装流程把工程跑起来,  
实现 jsonapi 功能的核心文件一共有三个:

##### 1、公共基础控制器

`/app/BaseController.php`,  
Api.php 是直接继承它获得增删改查方法的,
自己如果确实需要建其他控制器建议也继承它以便继承增删改查方法.

##### 2、公共基础模型

`/app/model/BaseModel.php`,  
所有的匿名模型都是继承它实现权限控制和数据操作的,  
自己如果确实需要建模型建议也继承它以便继承权限和数据操作.

##### 3、实现接口文档的控制器

`/app/controller/Api.php`  
此控制器的默认方法实现了接口文档的生成和输出,
直接输出 HTML,仅在`app_debu`g 模式下有输出.

JSONAPI 的实现整体代码量极其的少,  
每个函数都有非常详细的注释.

开发新的功能或修改之后`commit`并`git push`到你的仓库中,  
再通过`Pull Request`到本仓库, 审核通过后会第一时间合并到主分支.

同时也欢迎直接在 Github 加入到 JSONAPI 的开发团队中,请和[Tommy Tan](https://github.com/d86e) (<thf85@qq.com>)取得联系.

提前感谢每一位参与者.

请参阅 [ThinkPHP 完全开发手册](https://www.kancloud.cn/manual/thinkphp6_0/content) [ThinkPHP 核心框架包](https://github.com/top-think/framework).

## 打赏赞助

如果 JSONAPI 对你有帮助, 请给项目打赏支持.  
<img src="https://gitee.com/d86e/images/raw/main/wechat_pay.jpg" width="20%">

## 致敬感谢

[ThinkPHP](https://www.thinkphp.cn)  
[ThinkPHP 社区](https://q.thinkphp.cn)

## 版权信息

JSONAPI 遵循 Apache2 开源协议发布，并提供个人和商业免费使用。
所使用的底层框架 ThinkPHP 版权归 ThinkPHP 官方所有,
本项目可能包含的第三方源码和二进制文件之版权信息另行标注。

版权所有 Copyright 2024 by [Tommy Tan](https://github.com/d86e) All rights reserved。

更多细节参阅 [LICENSE.txt](LICENSE.txt)
