<?php

namespace AppBundle\Controller;

use CommonBundle\Service\RabbitService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    public $rabbitService = null;

    public function __construct(RabbitService $rabbitService)
    {
        $this->rabbitService = $rabbitService;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $a = $this->rabbitService->helloPub();
        return new JsonResponse($a);
    }
}
