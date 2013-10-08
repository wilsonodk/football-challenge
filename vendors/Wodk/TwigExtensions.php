<?php
/*
 * This file is part of Wodk.
 *
 * (c) 2012 Wilson Wise
 *
 * Extensions to the Twig template engine.
 *
 */
class Wodk_TwigExtensions extends Twig_Extension 
{
    public function getName() {
        return 'one_space';
    }
    
    public function getFilters() {
    	return array(
    		'one_space' => new Twig_Filter_Method($this, 'oneSpaceFilter'),
    		'log_style' => new Twig_Filter_Method($this, 'styleLogLine'),
    		'get_day'	=> new Twig_Filter_Method($this, 'getDay'),
    		'no_wspace'	=> new Twig_Filter_Method($this, 'noWhiteSpace'),
    	);
    }
    
    public function oneSpaceFilter($str) {
    	$regex = '/(\s+)/';
    	$str   = trim($str);

    	return preg_replace($regex, ' ', $str);
    }
	
	public function styleLogLine($line) {
		$regex = '/\((\w+)\)/';
		$bits  = explode(']', $line, 2);

		preg_match($regex, $line, $matches);
    	
        return sprintf('<p><span class="stamp">%s]</span><span class="%s">%s</p>', $bits[0], $matches[1], $bits[1]);
    }
    
    public function getDay($date) {
		$days = array(
			'Sunday'	=> 'Sun.',
			'Monday'	=> 'Mon.',
			'Tuesday'	=> 'Tue.',
			'Wednesday'	=> 'Wed.',
			'Thursday'	=> 'Thr.',
			'Friday'	=> 'Fri.',
			'Saturday'	=> 'Sat.',
		);
		$bits = explode(', ', $date);
		
        return $days[$bits[0]];
    }
    
    public function noWhiteSpace($str) {
    	$regex = '/\w+/';
    	
        return preg_replace($regex, '', $str);
    }
}
?>