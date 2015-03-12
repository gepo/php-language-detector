<?php
namespace LangDetector;

use Psr\Log\LoggerInterface;

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
*/

/**
 * This class detect the language of a given text.
 * Requires pspell and an aspell dictionary for each language that you want to detect.
 *
 * @author Marco Martinelli (https://github.com/martinellimarco)
 * @author Gennady Telegin <gtelegin@gmail.com>
 */
class LangDetector
{
    private $logger;

    private $langs;
    private $badChars = ',.;:?!#()[]{}<>+-_&@*\'"^\\/%$€£0123456789|';
    private $splitRegex = '';
    private $pspell = null;

    private $thres = 0.75;

    /**
     * @param array $langs array of language codes to detect, e.g. ['it','en','fr']
     */
    public function __construct(array $langs, LoggerInterface $logger)
    {
        $this->langs = $langs;
        $this->logger = $logger;

        $this->splitRegex = '/[\s\r\t\n' . preg_quote($this->badChars, '/') . ']+/u';
        $this->createPspell();
    }

    /**
     * Returns an associative array that map each language code to the probability that $text is of that language.
     *
     * @param string $text Input text
     */
    public function getProbabilities($text)
    {
        $probs = array();
        $words = preg_split($this->splitRegex, $this->filterText($text), -1, PREG_SPLIT_NO_EMPTY);

        $totalWords = count($words);

        if ($totalWords > 0) {
            foreach ($this->pspell as $lang => $pspell) {

                $goodWords = 0;

                foreach ($words as $word) {
                    if (pspell_check($pspell, $word)) {
                        ++$goodWords;
                    }
                }

                $probs[$lang] = $goodWords / $totalWords;
            }

            arsort($probs);
        }

        return $probs;
    }

    /**
     * Returns the most probable language for the given $text if the probability is above a threshold (0.75 by default), false otherwhise.
     *
     * @param string $text Input text
     */
    public function getLang($text)
    {
        $probs = $this->getProbabilities($text);
        list ($lang, $value) = each($probs);

        if ($value >= $this->thres) {
            $nextValue = current($probs);

            if ($nextValue >= $this->thres) {
                return false;
            }

            return $lang;
        } else {
            return false;
        }
    }

    /**
     * Set the threshold used by the getLang function.
     *
     * @params float @thres
     */
    public function setThres($thres)
    {
        $this->thres = $thres;
    }

    protected function getLanguages()
    {
        return $this->langs;
    }

    protected function filterText($text)
    {
        return preg_replace('/@\w+/', '', $text); // Filter out instagram-like user references (aka, @mybestfriend)
    }

    private function createPspell()
    {
        if (!$this->pspell) {
            foreach($this->getLanguages() as $lang) {
                if (false !== $resource = @pspell_new($lang)) {
                    $this->pspell[$lang] = $resource;
                } else {
                    $this->logger->error("Could not open pspell dictionary for `" . $lang . "`");
                }
            }
        }
    }
}
