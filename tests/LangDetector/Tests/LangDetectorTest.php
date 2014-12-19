<?php
namespace LangDetector\Tests;

use LangDetector\LangDetector;

class LangDetectorTest extends \PHPUnit_Framework_TestCase
{    
    public function testLanguageDetect()
    {
        $languageDetector = new LangDetector(['en', 'fr', 'de', 'ru']);
        $languageDetector->setThres(0.5);
        
        $this->assertEquals("en", $languageDetector->getLang("Just like that. Friendly squirrels."));
        $this->assertEquals("en", $languageDetector->getLang("Wtf? Bike in your room? Poor shøyt"));
        
        $this->assertEquals("ru", $languageDetector->getLang("Но ради чего.."));
        $this->assertEquals("ru", $languageDetector->getLang("@lalalala из этих \"не вредных\" кол самая нормальная non caffeine"));
        $this->assertEquals("ru", $languageDetector->getLang("ух ты, это где?)"));
        $this->assertEquals("ru", $languageDetector->getLang("Шикарна"));
        $this->assertEquals("ru", $languageDetector->getLang("Нефига не волк.. он одомашненный какой-то"));
        $this->assertEquals("ru", $languageDetector->getLang("Как так? Ни разу не был в зоопарке???во даёт!!!)))))"));
        
        $this->assertEquals(false, $languageDetector->getLang("https://www.youtube.com/watch?v=eeellaala"));
        
        $this->assertEquals(false, $languageDetector->getLang("😉"));
    }
}
