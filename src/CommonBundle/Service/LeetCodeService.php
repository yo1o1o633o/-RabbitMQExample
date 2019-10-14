<?php

namespace CommonBundle\Service;

class LeetCodeService extends BaseService
{
    /**
     * 给定一个非空整数数组，除了某个元素只出现一次以外，其余每个元素均出现两次。找出那个只出现了一次的元素。
     * 输入: [4,1,2,1,2]
     * 输出: 4
     */
    public function onlyOnceNum() {
        $nums = [4,1,2,1,2];
        // 尝试1  去除重复的元素,但是下标无法处理
        $tempNums = $nums;
        for ($i = 0; $i < count($nums) - 1; $i++) {
            for ($j = $i + 1; $j < count($nums); $j++) {
                if ($nums[$i] == $nums[$j]) {
                    unset($tempNums[$i]);
                    unset($tempNums[$j]);
                    break;
                }
            }
        }
        var_dump($tempNums);

        $num = "";
        for ($i = 0; $i < count($nums) - 1; $i++) {
            for ($j = $i + 1; $j < count($nums); $j++) {
                if ($nums[$i] == $nums[$j]) {
                    $num = "";
                } else {
                    $num = $i;
                }
            }
            if ($num != "") {
                break;
            }
        }
        echo $num;
    }
}