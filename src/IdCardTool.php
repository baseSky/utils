<?php
/**
 * 身份证处理类
 * @author BastMain <527455294@qq.com>
 * */

namespace BastMain\utils;

class IdCardTool
{

    /*身份证号码*/
    protected $idcard = '';

    /**
     *  根据身份证号码获取性别
     * @param string $idcard 身份证号码
     * @return int $sex 性别 1男 2女 0未知
     */
    public function getSex($idcard)
    {
        if (empty($idcard)) return null;
        $sexint = (int)substr($idcard, 16, 1);
        return $sexint % 2 === 0 ? '女' : '男';
    }

    /**
     *  根据身份证号码获取生日
     * @param string $idcard 身份证号码
     * @return string $birthday
     */
    public function getBirthday($idcard)
    {
        if (empty($idcard)) return null;
        $bir = substr($idcard, 6, 8);
        $year = (int)substr($bir, 0, 4);
        $month = (int)substr($bir, 4, 2);
        $day = (int)substr($bir, 6, 2);
        return $year . "-" . $month . "-" . $day;
    }

    /**
     *  根据身份证号码计算年龄
     * @param string $idcard 身份证号码
     * @return int $age
     */
    public function getAge($idcard)
    {
        if (empty($idcard)) return null;
        #  获得出生年月日的时间戳
        $date = strtotime(substr($idcard, 6, 8));
        #  获得今日的时间戳
        $today = strtotime('today');
        #  得到两个日期相差的大体年数
        $diff = floor(($today - $date) / 86400 / 365);
        #  strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($idcard, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;
        return $age;
    }

    /**
     *  根据身份证号码获取出身地址
     * @param string $idcard 身份证号码
     * @return string $address
     */
    public function getAddress($idcard, $type = 1)
    {
        if (empty($idcard)) return null;
        $address = json_decode(file_get_contents('../area.json'),true);

        //var_dump($address);exit;
        switch ($type) {
            case 1:
                # 截取前六位数(获取基体到县区的地址)
                $key = substr($idcard, 0, 6);
                if (!empty($address[$key])) return $address[$key];
                # 截取前两位数(没有基体到县区的地址就获取省份)
                $key = substr($idcard, 0, 2);
                if (!empty($address[$key])) return $address[$key];
                # 都没有
                return '未知地址';
                break;
            case 2:
                # 截取前两位数(只获取省份)
                $key = substr($idcard, 0, 2);
                if (!empty($address[$key])) return $address[$key];
                break;
            default:
                return null;
                break;
        }
    }

    /**
     *  判断字符串是否是身份证号
     * @param string $idcard 身份证号码
     * @return bool true|false
     */
    public function IsIdCard($idcard)
    {
        #  转化为大写，如出现x
        $idcard = strtoupper($idcard);
        #  加权因子
        $wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        $ai = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        #  按顺序循环处理前17位
        $sigma = 0;
        #  提取前17位的其中一位，并将变量类型转为实数
        for ($i = 0; $i < 17; $i++) {
            $b = (int)$idcard{$i};
            #  提取相应的加权因子
            $w = $wi[$i];
            #  把从身份证号码中提取的一位数字和加权因子相乘，并累加
            $sigma += $b * $w;
        }
        #  计算序号
        $sidcard = $sigma % 11;
        #  按照序号从校验码串中提取相应的字符。
        $check_idcard = $ai[$sidcard];
        if ($idcard{17} == $check_idcard) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  根据身份证号，返回对应的生肖
     * @param string $idcard 身份证号码
     * @return string 返回对应的生肖
     */
    public function getZodiac($idcard)
    {
        if (empty($idcard)) return null;
        $start = 1901;
        $end = (int)substr($idcard, 6, 4);
        $x = ($start - $end) % 12;
        $val = '';
        if ($x == 1 || $x == -11) $val = '鼠';
        if ($x == 0) $val = '牛';
        if ($x == 11 || $x == -1) $val = '虎';
        if ($x == 10 || $x == -2) $val = '兔';
        if ($x == 9 || $x == -3) $val = '龙';
        if ($x == 8 || $x == -4) $val = '蛇';
        if ($x == 7 || $x == -5) $val = '马';
        if ($x == 6 || $x == -6) $val = '羊';
        if ($x == 5 || $x == -7) $val = '猴';
        if ($x == 4 || $x == -8) $val = '鸡';
        if ($x == 3 || $x == -9) $val = '狗';
        if ($x == 2 || $x == -10) $val = '猪';
        return $val;
    }

    /**
     *  根据身份证号，返回对应的星座
     * @param string $idcard 身份证号码
     * @return string 返回对应的生肖
     */
    public function getStarsign($idcard)
    {
        if (empty($idcard)) return null;
        $b = substr($idcard, 10, 4);
        $m = (int)substr($b, 0, 2);
        $d = (int)substr($b, 2);
        $val = '';
        if (($m == 1 && $d <= 21) || ($m == 2 && $d <= 19)) {
            $val = "水瓶座";
        } else if (($m == 2 && $d > 20) || ($m == 3 && $d <= 20)) {
            $val = "双鱼座";
        } else if (($m == 3 && $d > 20) || ($m == 4 && $d <= 20)) {
            $val = "白羊座";
        } else if (($m == 4 && $d > 20) || ($m == 5 && $d <= 21)) {
            $val = "金牛座";
        } else if (($m == 5 && $d > 21) || ($m == 6 && $d <= 21)) {
            $val = "双子座";
        } else if (($m == 6 && $d > 21) || ($m == 7 && $d <= 22)) {
            $val = "巨蟹座";
        } else if (($m == 7 && $d > 22) || ($m == 8 && $d <= 23)) {
            $val = "狮子座";
        } else if (($m == 8 && $d > 23) || ($m == 9 && $d <= 23)) {
            $val = "处女座";
        } else if (($m == 9 && $d > 23) || ($m == 10 && $d <= 23)) {
            $val = "天秤座";
        } else if (($m == 10 && $d > 23) || ($m == 11 && $d <= 22)) {
            $val = "天蝎座";
        } else if (($m == 11 && $d > 22) || ($m == 12 && $d <= 21)) {
            $val = "射手座";
        } else if (($m == 12 && $d > 21) || ($m == 1 && $d <= 20)) {
            $val = "魔羯座";
        }
        return $val;
    }

}