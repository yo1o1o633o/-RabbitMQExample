<?php

namespace AppBundle\Controller;

use CommonBundle\Service\LeetCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeetCodeController extends Controller
{
    protected $leetCodeService = null;
    public function __construct(LeetCodeService $leetCodeService)
    {
        $this->leetCodeService = $leetCodeService;
    }

    /**
     * @Route("/leetCode/index")
    */
    public function indexAction() {
        $res = $this->leetCodeService->onlyOnceNum([]);
        return new Response($res);
    }
}