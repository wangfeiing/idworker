# idworker
全局唯一ID生成器，实现了snow flake 算法

# Snowflake
SnowFlake的结构如下(每部分用-分开):<br>
 *0 - 0000000000 0000000000 0000000000 0000000000 0 - 00000 - 00000 - 000000000000* <br>
 * 1位标识，由于long基本类型带符号的，最高位是符号位，正数是0，负数是1，所以id一般是正数，最高位是0<br>
 * 41位时间截(毫秒级)，注意，41位时间截不是存储当前时间的时间截，而是存储时间截的差值（当前时间截 - 开始时间截)
 得到的值），这里的的开始时间截，一般是我们的id生成器开始使用的时间，由我们程序来指定的（如下下面程序SnowflakeIdWorker类的startTime属性）。
 41位的时间截，可以使用69年，年T = (1L << 41) / (1000L * 60 * 60 * 24 * 365) = 69<br>
 * 10位的数据机器位，可以部署在1024个节点，包括5位datacenterId和5位workerId<br>
 * 12位序列，毫秒内的计数，12位的计数顺序号支持每个节点每毫秒(同一机器，同一时间截)产生4096个ID序号<br>
 加起来刚好64位，为一个Long型。<br>
 SnowFlake的优点是，整体上按照时间自增排序，并且整个分布式系统内不会产生ID碰撞(由数据中心ID和机器ID作区分)，并且效率较高，经测试，SnowFlake每秒能够产生26万ID左右。

# 使用方式
```
// 引入文件
include './IdWorker.php';
//实例化对象
$idWorker = new SnowflakeIdWorker(0, 0);
//获取ID
$id = $idWorker->nextId();

echo $id . PHP_EOL;

```
# 多机部署
```
// 引入文件
include './IdWorker.php';

//多机部署的情况下 需要指定工作机器的ID 和 数据中心的ID
//每个节点要保持  $workerId 和  $datacenterId 与其他节点不相同
//另 用一个二维的($workerId,$datacenterId)来唯一标示一个机器
$workerId = 0;
$datacenterId = 0

//实例化对象
$idWorker = new SnowflakeIdWorker($workerId, $datacenterId);
//获取ID
$id = $idWorker->nextId();

echo $id . PHP_EOL;

```
ID生成器部署完成以后，需要RPC的方式对外提供服务。
