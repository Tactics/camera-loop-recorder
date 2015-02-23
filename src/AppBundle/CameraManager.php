<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle;

use AppBundle\Entity\Camera;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Id\UuidGenerator;
use AppBundle\Exception\CaptureException;

/**
 * Description of CameraManager
 *
 * @author Gert
 */
class CameraManager
{
    const RUNNING = "running";
    const BROKEN = "broken";
    
    private $repo;
    private $om;
    private $storagePath;
    private $segmentTime = 30;
    
    private $psExec = "d:\\git\\camera-loop-recorder\data\bin\psexec.exe";
    
    public function __construct(ObjectManager $om, $storagePath)
    {
        $this->om = $om;
        $this->repo = $om->getRepository('AppBundle\Entity\Camera');
        $this->storagePath = $storagePath;
    }
    
    public function startCamera($url, $loopDuration)
    {
        if ($this->repo->findOneBy(['url' => $url]))
        {
            throw new Exception\CameraExistsException();
        }
        
        $camera = new Camera($url, $loopDuration, null);
        
        // clean if necessary
        Utils::delTree($this->getStoragePathForCamera($camera));
        
        $this->startCameraProcess($camera);
        return $camera;
    }
    
    public function stopCamera($url)
    {
        $camera = $this->getCameraByUrlOrThrowException($url);
        
        $command = "taskkill /PID {$camera->getPid()}";
       
        exec($command);
        
        $this->om->remove($camera);
        $this->om->flush();
        
        Utils::delTree($this->getStoragePathForCamera($camera));
    }
    
    public function getCameraStatus($url)
    {
        $camera = $this->getCameraByUrlOrThrowException($url);
        
        exec("tasklist /v /FI \"PID eq {$camera->getPid()}\" /FO:list", $output);
        
        if (isset($output[6]) && ((strpos($output[6], "Running") > 0) || (strpos($output[6], "Unknown") > 0)))
        {
            return self::RUNNING;
        }
        
        return self::BROKEN;
    }
    
    public function getCameraLog($url)
    {
        $camera = $this->getCameraByUrlOrThrowException($url);
        
        return Utils::fileTail($this->getStoragePathForCamera($camera) . 'log.txt', 20);
    }
    
    public function ensureCameraIsRunning($url)
    {
        if ($this->getCameraStatus($url) != self::RUNNING)
        {
            $this->startCameraProcess($camera);
        }
    }
    
    public function startCapture($url, $from, $to)
    {
        if ($from > $to)
        {
            throw new CaptureException('"from" should come before "to"');
        }

        if ($to > (time() + 300))
        {
            throw new CaptureException('Max time to record in the future is 360s');
        }
                
        $camera = $this->getCameraByUrlOrThrowException($url);
        $storagePath = $this->getStoragePathForCamera($camera);
        $folder = new \DirectoryIterator($storagePath);
        $captureUuid = uniqid();

        do {
            sleep(1);
            $list = $this->getRecordingListDescending($folder);
        }
        while(key($list) < $to);
        
        $list = $this->getRecordingListDescending($folder);
                
        $captureFiles = array();
        
        foreach($list as $mtime => $file)
        {
            if (
                ($mtime > $from)
                && ($mtime < $to)
            )
            {
                array_push($captureFiles, $file);
            }
        }
        
        if (empty($captureFiles))
        {
            throw new CaptureException('Request timeframe no longer available.');
        }

        $captureFilePrefix = $this->getStoragePathForCaptures() . $captureUuid;
        
        $output = '';
        
        chdir($storagePath);
        $command = 'ffmpeg -i "concat:' . implode('|', $captureFiles) . '" -f h264 -vcodec copy ' . $captureFilePrefix . '.h264 2>&1';
        exec($command, $output);
        
        $command = 'ffmpeg -f h264 -i ' . $captureFilePrefix . '.h264 -vcodec copy ' . $captureFilePrefix . '.mp4 2>&1';
        exec($command, $output);
        unlink($captureFilePrefix . '.h264');

        return $captureUuid;
    }
    
    private function getRecordingListDescending($folder)
    {
        $files = array();
        
        foreach($folder as $file)
        {
          if ($file->getExtension() != 'h264') continue;
          if ($file->getSize() == 0) continue; // current file has zero bytes
          
          $mtime = isset($files[$file->getMTime()]) ? ($file->getMTime() + 1): $file->getMTime();
          $files[$mtime] = $file->getFileName();
        }
        
        krsort($files);
        
        return $files;
    }
    
    private function getStoragePathForCamera($camera)
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . md5($camera->getUrl()) . DIRECTORY_SEPARATOR;
    }
    
    public function getStoragePathForCaptures()
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . 'capture' . DIRECTORY_SEPARATOR;
    }
    
    private function getCameraByUrlOrThrowException($url)
    {
        $camera = $this->repo->findOneBy(['url' => $url]);
                
        if (! $camera)
        {
            throw new Exception\CameraNotFoundException;
        }
        
        return $camera;
    }
    
    private function startCameraProcess(Camera $camera)
    {
        $url = $camera->getUrl();
        $totalSegments = $camera->getLoopDuration() * ceil(60 / $this->segmentTime) + 1;
        
        $path = $this->getStoragePathForCamera($camera);
        
        if (! is_dir($path))
        {
            mkdir($path);
        }        
        $command = "ffmpeg -i {$url}"
        . " -acodec copy -vcodec copy -map 0"
        . " -f segment -segment_wrap {$totalSegments} -segment_time {$this->segmentTime} "
        . " -loglevel error"
        . " {$path}loop%03d.h264"
        . " 2^> {$path}log.txt";
        
        // run the command with cmd AND psexec
        // using cmd /c makes the ffmpeg error output redirection to file work (escape character ^ in 2^>)
        exec("{$this->psExec} -d cmd.exe /c {$command} 2>&1", $output);
       
        // capture pid on the 6th line
        preg_match('/ID (\d+)/', $output[5], $matches);
        $pid = $matches[1];
        
        $camera->setPid($pid);        
        
        $this->om->persist($camera);
        $this->om->flush();
    }
    
    
}
