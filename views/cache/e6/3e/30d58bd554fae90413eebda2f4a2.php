<?php

/* base.html.twig */
class __TwigTemplate_e63e30d58bd554fae90413eebda2f4a2 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'head' => array($this, 'block_head'),
            'content' => array($this, 'block_content'),
            'footer' => array($this, 'block_footer'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html>
<head>
";
        // line 4
        $this->displayBlock('head', $context, $blocks);
        // line 8
        echo "</head>
<body>
";
        // line 10
        $this->displayBlock('content', $context, $blocks);
        // line 12
        echo "<div id=\"footer\">
";
        // line 13
        $this->displayBlock('footer', $context, $blocks);
        // line 16
        echo "</div>
</body>
</html>";
    }

    // line 4
    public function block_head($context, array $blocks = array())
    {
        // line 5
        echo "<meta charset=\"utf-8\" />
<title>";
        // line 6
        if (isset($context["site_name"])) { $_site_name_ = $context["site_name"]; } else { $_site_name_ = null; }
        echo twig_escape_filter($this->env, $_site_name_, "html", null, true);
        echo " &ndash; ";
        if (isset($context["page_name"])) { $_page_name_ = $context["page_name"]; } else { $_page_name_ = null; }
        echo twig_escape_filter($this->env, $_page_name_, "html", null, true);
        echo "</title>
";
    }

    // line 10
    public function block_content($context, array $blocks = array())
    {
    }

    // line 13
    public function block_footer($context, array $blocks = array())
    {
        // line 14
        echo "\tfooter
";
    }

    public function getTemplateName()
    {
        return "base.html.twig";
    }

    public function getDebugInfo()
    {
        return array (  68 => 14,  65 => 13,  60 => 10,  50 => 6,  47 => 5,  44 => 4,  38 => 16,  36 => 13,  33 => 12,  31 => 10,  25 => 4,  20 => 1,  46 => 9,  40 => 8,  37 => 7,  30 => 4,  27 => 8,);
    }
}
