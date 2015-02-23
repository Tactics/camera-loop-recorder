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
        
        if (! $url || ! $loopDuration)
        {
            return new JsonResponse([
                "error" => [
                    "code" => 500,
                    "message" => 'Required parameter url or loop_duration not provided'
                ]
            ], 500);
        }
        
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
        $url = base64_decode($url);
        
        try {
            $status = $this->get('app.camera_manager')->getCameraStatus($url);
            $log = $this->get('app.camera_manager')->getCameraLog($url);
        }
        catch(AppBundle\Exception\CameraNotFoundException $e)
        {
            return new JsonResponse([
                "error" => [
                    "code" => 404,
                    "message" => 'Camera not found : ' . $e->getMessage()
                ]
            ], 404);
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
        $from = $request->request->get('from');
        $to = $request->request->get('to');
        
        $from = is_numeric($from) ? $from : strtotime($from);
        $to = is_numeric($to) ? $to : strtotime($to);
        
        try {
            $uuid = $this->get('app.camera_manager')->startCapture($url, $from, $to);
        }
        catch(AppBundle\Exception\CameraNotFoundException $e) {
            return new JsonResponse([
                "error" => [
                    "code" => 404,
                    "message" => 'Camera not found : ' . $e->getMessage()
                ]
            ], 404);
        }
        catch (AppBundle\Exception\CaptureException $e) {
            return new JsonResponse([
                "error" => [
                    "code" => 500,
                    "message" => 'Problem capturing: ' . $e->getMessage()
                ]
            ], 500);
        }
        
        return new JsonResponse([
            "uuid" => $uuid
        ]);
    }

}
