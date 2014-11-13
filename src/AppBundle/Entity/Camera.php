<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of CameraJob
 *
 * @author Gert
 * @ORM\Entity
 */
class Camera
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string")
     */
    protected $url;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $pid;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $loopDuration;
    
    public function __construct($url, $loopDuration, $pid)
    {
        $this->url = $url;
        $this->loopDuration = $loopDuration;
        $this->pid = $pid;
    }
    
    public function getPid()
    {
        return $this->pid;
    }
    
    public function getUrl()
    {
        return $this->url;
    }
    
    public function getLoopDuration()
    {
        return $this->loopDuration;
    }
    
    public function setPid($pid)
    {
        $this->pid = $pid;
    }
        
}
