<?php

namespace CommonBundle\Service;

class LeetCodeService extends BaseService
{
    /**
     * 给定一个非空整数数组，除了某个元素只出现一次以外，其余每个元素均出现两次。找出那个只出现了一次的元素。
     * 输入: [4,1,2,1,2]
     * 输出: 4
     * @param array
     * @return int
     */
    public function onlyOnceNum($nums) {
        $nums = [4,1,2,1,2,4,3];
        /**
         * 数组方式处理: 一次循环, 如果在后续循环发现数组中有相同的值就删除这个元素, 否则添加这个元素. 最后这个数组只有一个值
        */
        $res = [];
        foreach ($nums as $k => $num) {
            if (empty($res)) {
                $res[] = $num;
                continue;
            }
            if (in_array($num, $res)) {
                $index = array_keys($res, $num);
                unset($res[$index[0]]);
            } else {
                $res[] = $num;
            }
        }
        $res = array_values($res)[0];


        /**
         * 异或处理: 一个值异或另一个值,相同返回真, 不同返回假
        */









        return $res;
    }
}