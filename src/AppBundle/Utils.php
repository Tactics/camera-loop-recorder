<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle;

/**
 * Description of Utils
 *
 * @author Gert
 */
class Utils
{

    /**
     * Return the last lines from a file
     * 
     * http://tekkie.flashbit.net/php/tail-functionality-in-php
     * 
     * @param string $fileName
     * @param integer $lines
     * 
     * @return string[]
     */
    public static function fileTail($fileName, $lines)
    {
        //global $fsize;
        $handle = fopen($fileName, "r");
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();
        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if(fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true; 
                    break; 
                }
                $t = fgetc($handle);
                $pos --;
            }
            $linecounter --;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines-$linecounter-1] = fgets($handle);
            if ($beginning) break;
        }
        fclose ($handle);
        return array_reverse($text);
    }
    
    /**
     * Recursively delete folder
     * 
     * @param type $dir
     * @return type
     */
    public static function delTree($dir)
    { 
        $files = array_diff(scandir($dir), array('.','..')); 
        
        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
        } 
        
        return rmdir($dir); 
    }
}
