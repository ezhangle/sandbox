<?php

/* 
  * Boost Software License - Version 1.0 - August 17th, 2003
  * 
  * Copyright (c) 2013 Developed by reg <entry.reg@gmail.com>
  * 
  * Permission is hereby granted, free of charge, to any person or organization
  * obtaining a copy of the software and accompanying documentation covered by
  * this license (the "Software") to use, reproduce, display, distribute,
  * execute, and transmit the Software, and to prepare derivative works of the
  * Software, and to permit third-parties to whom the Software is furnished to
  * do so, all subject to the following:
  * 
  * The copyright notices in the Software and this entire statement, including
  * the above license grant, this restriction and the following disclaimer,
  * must be included in all copies of the Software, in whole or in part, and
  * all derivative works of the Software, unless such copies or derivative
  * works are solely in the form of machine-executable object code generated by
  * a source language processor.
  * 
  * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  * FITNESS FOR A PARTICULAR PURPOSE, TITLE AND NON-INFRINGEMENT. IN NO EVENT
  * SHALL THE COPYRIGHT HOLDERS OR ANYONE DISTRIBUTING THE SOFTWARE BE LIABLE
  * FOR ANY DAMAGES OR OTHER LIABILITY, WHETHER IN CONTRACT, TORT OR OTHERWISE,
  * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
  * DEALINGS IN THE SOFTWARE. 
*/

namespace reg\graphics\_2d\text\imagestring;

/**
 * if only imagestring() -_- e.g.: 3QF232 (see my public repo)
 * \png support
 * simple html-style implementation
 */
class TextRender
{
    /**
     * multiline split symbol
     */
    const SYMBSPLIT     = "\n";
    /**
     * marker for different gdf
     * Bold style
     * /TODO: struct
     */
    const SYMBBOLD      = "\r";
    
    /**
     * Compression level: from 0 (no compression) to 9
     * @var integer 
     */
    public $quality     = 4;
    
    /**
     * loaded font
     * @var \_LoadFont
     */
    private $_font      = null;
    /**
     * image width
     * -1 for auto detect by text
     * @var int 
     */
    private $_width     = -1;
    /**
     * image height
     * -1 for auto detect by text
     * @var int 
     */
    private $_height    = -1;
    
    /**
     * for rendering files: template file path
     * @var string
     */
    private $_renderFname = null;
    
    /**
     * @param string $gdf   - name of font. Only GDF format!
     * @param int $width
     * @param int $height
     * @param string $size  - font size WxH (various ident)
     */
    public function __construct($gdf, $width = -1, $height = -1,  $size = '10x20')
    {
        $this->_font    = new _LoadFont($gdf, $size);
        $this->_width   = $width;
        $this->_height  = $height;
    }

    /**
     * @param string $text
     * @param array $color      - RGB, foreground
     * @param array $bgColor    - RGB, background
     * @param boolean $alpha    - transparent
     * @return int              - number of rendered parts
     */
    public function renderIntoFile($text, $color = array(0, 0, 0), $bgColor = array(255, 255, 255), $alpha = true)
    {
        $text = self::format($text);
        if($this->_width != -1){
            $text = $this->wordwrap($this->possibleSymbolsLength(), $text);
        }
        return $this->_renderAllParts($text, $color, $bgColor, $alpha); //auto
    }
    
    /**
     * only fixed width
     * @return int
     */
    public function possibleSymbolsLength()
    {
        if($this->_width < 1){
            return -1;
        }
        return floor($this->_width / $this->_font->width);
    }
    
    /**
     * only fixed height
     * @return int
     */    
    public function possibleLines()
    {
        if($this->_height < 1){
            return -1;
        }
        return floor($this->_height / $this->_font->height);
    }
    
    /**
     * @param string $tpl - template file path
     */
    public function setFnameForRender($tpl)
    {
        $this->_renderFname = $tpl;
    }
    
    public static function wordwrap($maxlen, $text)
    {
        return wordwrap($text, $maxlen, self::SYMBSPLIT);
    }
    
    public static function utf8To1251($text)
    {
        return iconv('UTF-8', 'windows-1251//IGNORE', $text);
    }    
    
    protected function _createImageByLines(&$lines, $color, $bgColor, $alpha)
    {
        if($this->_width != -1){
            $textWidth = $this->_width;
        }
        else{ //auto
            $textWidth = 0;
            foreach($lines as $line){
                if(($len = strlen($line)) > $textWidth){
                    $textWidth = $len;
                }
            }
            $textWidth *= $this->_font->width;
        }
        
        if($this->_height != -1){
            $textHeight = $this->_height;
        }
        else{ //auto
            $textHeight = count($lines) * $this->_font->height; /* + ($textHeight * intervalSpace) */
        }
        
        
        $img        = imagecreate($textWidth, $textHeight);
        $background = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
        
        //background transparent
        if($alpha){
            imagecolortransparent($img, $background);
        }
        
        $color      = imagecolorallocate($img, $color[0], $color[1], $color[2]);
        $fontBold   = $this->_font->copyWithStyle('bold');
        
        $y = 0;
        foreach($lines as $line){
            $bold = explode(self::SYMBBOLD, $line);
            
            $x = 0;
            for($i = 0, $n = count($bold); $i < $n; ++$i){
                if($i & 1 == 1){
                    imagestring($img, $fontBold->loaded, $x, $y, $bold[$i], $color);
                }
                else{
                    imagestring($img, $this->_font->loaded, $x, $y, $bold[$i], $color);
                }
                $x += strlen($bold[$i]) * $this->_font->width;
            }
            $y += $this->_font->height; /*+ intervalSpace*/
        }
        return $img;
    }
    
    /**
     * output into stream
     * @param resource $img
     */
    protected function _showImage(&$img)
    {
        header('Content-type: image/png');
        imagepng($img);
        exit(); //protect
    }
    
    /**
     * @param resource $img
     * @param string $fname
     */
    protected function _saveImage(&$img, $fname)
    {
//      System::getSettings()['render']['quality'];
        imagepng($img, $fname, $this->quality);
        imagedestroy($img);
    }
    
    protected function _toLines(&$text)
    {
        return explode(self::SYMBSPLIT, $text);
    }
    
    protected function positionsOfSymbol($symbol, &$text, &$found, $offset = 0)
    {
        if(($pos = strpos($text, $symbol, $offset)) !== false){
            $found[] = $pos + 1;
            return $this->positionsOfSymbol($symbol, $text, $found, $pos + 1);
        }
        return false;
    }    
    
    /**
     * Automatic calculation of limits page & render to file
     * @param string $text
     * @param array $color
     * @param array $bgColor
     * @param boolean $alpha
     * @return int
     */
    protected function _renderAllParts(&$text, $color, $bgColor, $alpha)
    {
        if($text{strlen($text) - 1} != self::SYMBSPLIT){
            $text .= self::SYMBSPLIT;
        }        
        $count  = substr_count($text, self::SYMBSPLIT);
        $max    = $this->possibleLines();
        $parts  = ceil($count / $max);
        
        $positions = array(0);
        $this->positionsOfSymbol(self::SYMBSPLIT, $text, $positions);
        
        for($i = 0; $i < $parts; ++$i){
            $start      = $positions[$i * $max];
            $indexMax   = ($i + 1) * $max;
            
            if($indexMax <  $count){
                $end = $positions[$indexMax];
            }
            else{
                $end = $positions[$count];
            }
            
            $linesOfPart = substr($text, $start, ($end - $start) - 1);
            $linesOfPart = $this->_toLines($linesOfPart);
            
            $img = $this->_createImageByLines($linesOfPart, $color, $bgColor, $alpha);
            
            if($this->_renderFname == null){
                throw new \Exception('is not set file path for rendering');
            }
            $this->_saveImage($img, $this->_renderFname . $i . '.png');
        }
        return $parts;
    }
    
    /**
     * Prepare data for imagestring()
     * @param string $text
     * @return string
     */
    public static function format(&$text)
    {
        $text = self::formatSpace($text);
        $text = self::formatTag('p', $text, '', self::SYMBSPLIT);
        $text = self::formatTag('li', $text, ' <b>*</b> ', self::SYMBSPLIT);
        $text = self::formatBold($text);
        $text = self::formatLineFeed($text);
        
        return $text;
    }
    
    protected static function formatLineFeed(&$text)
    {
        return preg_replace("#<br( /)?>#", self::SYMBSPLIT, $text);
    }
    
    protected static function formatSpace(&$text)
    {
        return str_replace('&nbsp;', ' ', $text);
    }
    
    protected static function formatTag($tag, &$text, $prefix, $postfix)
    {
        return preg_replace("#<". $tag ."[^>]*?>(.+?)</". $tag .">#is", $prefix . "$1" . $postfix, $text);
    }
    
    protected static function formatBold(&$text)
    {
        return preg_replace("#</?b>#", self::SYMBBOLD, $text);
    }
}

/**
 * helper object
 */
/*private*/ class _LoadFont
{
    /**
     * @var int
     */
    public /*readonly*/ $loaded  = null;
    /**
     * @var int
     */    
    public /*readonly*/ $width   = null;
    /**
     * @var int
     */    
    public /*readonly*/ $height  = null;
    /**
     * @var string
     */
    public /*readonly*/ $gdf     = null;
    /**
     * @var string
     */
    public /*readonly*/ $size    = null;
    
    /**
     * default path to gdf files
     * @var string
     */
    public static $path = 'fonts/GDF';
    
    /**
     * GDF loading
     * @param string $gdf
     * @param string $size
     * @param string $style
     */
    public function __construct($gdf, $size, $style = '')
    {
        $this->load($gdf, $size, $style);
    }
    
    /**
     * "Change" style for current font
     * @param string $style
     * @param string $size
     * @return \_LoadFont
     */
    public function copyWithStyle($style, $size = null)
    {
        return new _LoadFont($this->gdf, (($size != null)? $size: $this->size), $style);
    }

    private function load($gdf, $size, $style = '')
    {
//      $config = System::getSettings();
        $this->loaded   = imageloadfont(self::$path . '/' . $gdf . '/' . $size . 
                                                      (empty($style)? '': '_' . $style). '.gdf');
        
        $this->width    = imagefontwidth($this->loaded);
        $this->height   = imagefontheight($this->loaded);
        $this->gdf      = $gdf;
        $this->size     = $size;
    }

    /**
     * readonly
     */
    //public function __set($name, $value){}
}