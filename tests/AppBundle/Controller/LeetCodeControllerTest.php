<?php

namespace Tests\AppBundle\Controller;

use CommonBundle\Service\LeetCodeService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LeetCodeControllerTest extends WebTestCase
{
    protected $leetCodeService = null;
    public function __construct(LeetCodeService $leetCodeService)
    {
        $this->leetCodeService = $leetCodeService;
    }

    public function testIndex() {
        $arr = [2,2,1,1,3,4,3,5,5];
        $this->assertEquals(4, $this->leetCodeService->onlyOnceNum($arr));
    }
}