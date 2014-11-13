<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Entity\CaptureJob;

/**
 * @Route("/capture", defaults={"_format": "json"})
 */
class CaptureController extends Controller
{
    /**
     * @Route("/status/{uuid}")
     * @Method({"GET"})
     */
    public function statusAction($uuid)
    {
        return new JsonResponse([
            "status" => "ready" // 'running'  or 'ready' or 'deleted' or 'failed' (maybe ?)
        ]);
    }
    
    /**
     * @Route("/download/{uuid}")
     * @Method({"GET"})
     */
    public function downloadAction($uuid)
    {
        return new BinaryFileResponse($this->get('app.camera_manager')->getStoragePathForCaptures() . $uuid . '.mp4');
    }
    
    
}
