<?php
class TwigFilters extends Twig_Extension 
{
    public function getName() {
        return 'one_space';
    }
    
    public function getFilters() {
    	return array(
    		'one_space' => new Twig_Filter_Method($this, 'oneSpaceFilter'),
    		'log_style' => new Twig_Filter_Method($this, 'styleLogLine'),
    	);
    }
    
    public function oneSpaceFilter($str) {
    	$regex = '/(\s+)/';
    	$str = trim($str);
    	return preg_replace($regex, ' ', $str);
    }
	
	public function styleLogLine($line) {
		$regex = '/\((\w+)\)/';
		$bits = explode(']', $line, 2);
		preg_match($regex, $line, $matches);
    	return sprintf('<p><span class="stamp">%s]</span><span class="%s">%s</p>', $bits[0], $matches[1], $bits[1]);
    }
}
?>