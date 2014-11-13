<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\CameraManager;

/**
 * @Route("/camera", defaults={"_format": "json"})
 */
class CameraController extends Controller
{
    /**
     * @Route("/start")
     * @Method({"POST"})
     */
    public function startAction(Request $request)
    {
        $url = $request->request->get('url');
        $loopDuration = $request->request->get('loop_duration');
        
        $camera = $this->get('app.camera_manager')->startCamera($url, $loopDuration);
         
        return new JsonResponse([
            "status" => "running",
            "pid" => $camera->getPid()
        ]);        
    }
    
    /**
     * @Route("/stop")
     * @Method({"POST"})
     */
    public function stopAction(Request $request)
    {
        $url = $request->request->get('url');
        
        $this->get('app.camera_manager')->stopCamera($url);
        
        return new JsonResponse([
            "status" => "stopped"
        ]);
    }

    /**
     * @Route("/status/{url}", requirements={"url"=".+"})
     * @Method({"GET"})
     */
    public function statusAction($url)
    {
        $status = null;
        
        try {
            $status = $this->get('app.camera_manager')->getCameraStatus($url);
            $log = $this->get('app.camera_manager')->getCameraLog($url);
        }
        catch(Exception\CameraNotFoundException $e)
        {
            throw $this->createNotFoundException('Camera not found : ' . $e->getMessage());
        }
        
        return new JsonResponse([
            "status" => $status,
            'log' => $log
        ]);
    }

    /**
     * @Route("/capture")
     * @Method({"POST"})
     */
    public function captureAction(Request $request)
    {
        $url = $request->request->get('url');
        $from = strtotime($request->request->get('from'));
        $to = strtotime($request->request->get('to'));
        
        $uuid = $this->get('app.camera_manager')->startCapture($url, $from, $to);
        
        return new JsonResponse([
            "uuid" => $uuid
        ]);
    }

}
