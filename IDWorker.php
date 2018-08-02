<?php
/**
 * Twitter_Snowflake<br>
 * SnowFlake的结构如下(每部分用-分开):<br>
 * 0 - 0000000000 0000000000 0000000000 0000000000 0 - 00000 - 00000 - 000000000000 <br>
 * 1位标识，由于long基本类型在Java中是带符号的，最高位是符号位，正数是0，负数是1，所以id一般是正数，最高位是0<br>
 * 41位时间截(毫秒级)，注意，41位时间截不是存储当前时间的时间截，而是存储时间截的差值（当前时间截 - 开始时间截)
 * 得到的值），这里的的开始时间截，一般是我们的id生成器开始使用的时间，由我们程序来指定的（如下下面程序IdWorker类的startTime属性）。41位的时间截，可以使用69年，年T = (1L << 41) / (1000L * 60 * 60 * 24 * 365) = 69<br>
 * 10位的数据机器位，可以部署在1024个节点，包括5位datacenterId和5位workerId<br>
 * 12位序列，毫秒内的计数，12位的计数顺序号支持每个节点每毫秒(同一机器，同一时间截)产生4096个ID序号<br>
 * 加起来刚好64位，为一个Long型。<br>
 * SnowFlake的优点是，整体上按照时间自增排序，并且整个分布式系统内不会产生ID碰撞(由数据中心ID和机器ID作区分)，并且效率较高，经测试，SnowFlake每秒能够产生26万ID左右。
 */
class SnowflakeIdWorker {
    // ==============================Fields===========================================
    /** 开始时间截 (2015-01-01) */
    private $twepoch = null;

    /** 机器id所占的位数 */
    private $workerIdBits = null;

    /** 数据标识id所占的位数 */
    private $datacenterIdBits = null;

    /** 支持的最大机器id，结果是31 (这个移位算法可以很快的计算出几位二进制数所能表示的最大十进制数) */
    private $maxWorkerId = null;

    /** 支持的最大数据标识id，结果是31 */
    private $maxDatacenterId = null;

    /** 序列在id中占的位数 */
    private $sequenceBits = null;

    /** 机器ID向左移12位 */
    private $workerIdShift = null;

    /** 数据标识id向左移17位(12+5) */
    private $datacenterIdShift =null;

    /** 时间截向左移22位(5+5+12) */
    private $timestampLeftShift = null;

    /** 生成序列的掩码，这里为4095 (0b111111111111=0xfff=4095) */
    private $sequenceMask = null;

    /** 工作机器ID(0~31) */
    private $workerId = null;

    /** 数据中心ID(0~31) */
    private $datacenterId = null;

    /** 毫秒内序列(0~4095) */
    private $sequence = null;

    /** 上次生成ID的时间截 */
    private $lastTimestamp = null;


    //==============================Constructors=====================================
    /**
     * 构造函数
     * @param workerId 工作ID (0~31)
     * @param datacenterId 数据中心ID (0~31)
     */
    public function  SnowflakeIdWorker( $workerId, $datacenterId) {

        $this->twepoch = 1420041600000;
        /** 机器id所占的位数 */
        $this->workerIdBits = 5;
        $this->datacenterIdBits = 5;
        $this->maxWorkerId = -1 ^ (-1 << $this->workerIdBits);
        $this->maxDatacenterId = -1 ^ (-1 << $this->datacenterIdBits);
        $this->sequenceBits = 12;

        /** 机器ID向左移12位 */
        $this->workerIdShift = $this->equenceBits;

        /** 数据标识id向左移17位(12+5) */
        $this->datacenterIdShift = $this->equenceBits + $this->workerIdBits;

        /** 时间截向左移22位(5+5+12) */
        $this->timestampLeftShift = 0;

        /** 生成序列的掩码，这里为4095 (0b111111111111=0xfff=4095) */
        $this->sequenceMask = -1 ^ (-1 << $this->sequenceBits);
        /** 毫秒内序列(0~4095) */
        $this->sequence = 0;
        /** 上次生成ID的时间截 */
        $this->lastTimestamp = -1;


        if ($workerId > $maxWorkerId || $workerId < 0) {
            throw new Exception(String.format("worker Id can't be greater than %d or less than 0", maxWorkerId));
        }
        if ($datacenterId > $maxDatacenterId || $datacenterId < 0) {
            throw new Exception(String.format("datacenter Id can't be greater than %d or less than 0", maxDatacenterId));
        }
        $this->workerId = $workerId;
        $this->datacenterId = $datacenterId;
    }

    // ==============================Methods==========================================
    /**
     * 获得下一个ID
     * @return SnowflakeId
     */
    public  function  nextId()  {
        $this->timestamp = $this->timeGen();

        //如果当前时间小于上一次ID生成的时间戳，说明系统时钟回退过这个时候应当抛出异常
        if ($this->timestamp < $this->lastTimestamp) {
            throw new Exception(
                    sprintf("Clock moved backwards.  Refusing to generate id for %d milliseconds", $lastTimestamp - $timestamp));
        }

        //如果是同一时间生成的，则进行毫秒内序列
        if ($this->lastTimestamp == $this->timestamp) {
            $this->equence = ($this->equence + 1) & $this->equenceMask;
            //毫秒内序列溢出
            if ($this->equence == 0) {
                //阻塞到下一个毫秒,获得新的时间戳
                $this->timestamp = $this->tilNextMillis($this->lastTimestamp);
            }
        }
        //时间戳改变，毫秒内序列重置
        else {
            $this->equence = 0;
        }

        //上次生成ID的时间截
        $this->lastTimestamp = $this->timestamp;

        //移位并通过或运算拼到一起组成64位的ID
        return (($this->timestamp - $this->twepoch) << $this->timestampLeftShift) //
                | ($this->datacenterId << $this->datacenterIdShift) //
                | ($this->workerId << $this->workerIdShift) //
                | $this->sequence;
    }

    /**
     * 阻塞到下一个毫秒，直到获得新的时间戳
     * @param lastTimestamp 上次生成ID的时间截
     * @return 当前时间戳
     */
    protected function  tilNextMillis($lastTimestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    /**
     * 返回以毫秒为单位的当前时间
     * @return 当前时间(毫秒)
     */
    protected function  timeGen() {
        return  intval(microtime(true) * 1000);
    }
}
//==============================Test=============================================
/** 测试 */
function test() {
    $idWorker = new SnowflakeIdWorker(0, 0);
    for ($i = 0; $i < 1000; $i++) {
        $id = $idWorker->nextId();
        // echo $id . PHP_EOL;
    }
}
test();
