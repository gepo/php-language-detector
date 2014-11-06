<?php
namespace LangDetector;

/*
*******************************************************************************
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************
*
* This class detect the language of a given text.
* Requires pspell and an aspell dictionary for each language that you want to detect.
*
* @author Marco Martinelli (https://github.com/martinellimarco)
* @author Gennady Telegin <gtelegin@gmail.com>
*/
class LangDetector
{
	private $langs;
	private $badChars = ',.;:?!#()[]{}<>+-_&@*\'"^\\/%$€£0123456789|';
	private $thres = 0.75;

	/**
	 * $langs is an array of language codes, e.g. ['it','en','fr']
	 */
	public function __construct($langs)
	{
		$this->langs = $langs;
	}

	protected function getLanguages()
	{
	    return $this->langs;
	}
	
	protected function filterText($text)
	{
	    $text = preg_replace('/@\w+/', '', $text); // Filter out instagram-like user references (aka, @mybestfriend)
	    
	    $text = strtr($text, $this->badChars, ' ');
	    $text = preg_replace('/\s+/', ' ', $text);
	    
	    return $text;
	}
	
	/**
	 * Returns an associative array that map each language code to the probability that $text is of that language.
	 */
	public function getProbabilities($text)
	{
		$probs = array();

		$words = explode(' ', $this->filterText($text));

		$totalWords = count($words);

		if ($totalWords > 0) {
    		foreach($this->getLanguages() as $lang) {
    			$pspell = pspell_new($lang);
    			$goodWords = 0;
    			
    			foreach ($words as $word) {
    				if (pspell_check($pspell, $word)) {
    					++$goodWords;
    				}
    			}
    			
    			$probs[$lang] = $goodWords/$totalWords;
    		}
    
    		arsort($probs);
		}
		
		return $probs;
	}

	/**
	 * Returns the most probable language for the given $text if the probability is above a threshold (0.75 by default), false otherwhise.
	 */
	public function getLang($text)
	{
		$probs = $this->getProbabilities($text);
		$lang = key($probs);
		
		if ($probs[$lang] >= $this->thres) {
		    next($probs);
		    $nextLang = key($probs);
		    if ($probs[$nextLang] >= $this->thres) {
		        return false;
		    }
		    
			return $lang;
		} else {
			return false;
		}
	}

	/**
	 * Set the threshold used by the getLang function.
	 */
	public function setThres($thres)
	{
		$this->thres = $thres;
	}
}
