<?php

/* home.html.twig */
class __TwigTemplate_ca15498acc97cc35815950f333e7c0fb extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("base.html.twig");

        $this->blocks = array(
            'head' => array($this, 'block_head'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_head($context, array $blocks = array())
    {
        // line 4
        echo "\t";
        $this->displayParentBlock("head", $context, $blocks);
        echo "
";
    }

    // line 7
    public function block_content($context, array $blocks = array())
    {
        // line 8
        echo "\t<h1>";
        if (isset($context["page_name"])) { $_page_name_ = $context["page_name"]; } else { $_page_name_ = null; }
        echo twig_escape_filter($this->env, $_page_name_, "html", null, true);
        echo "</h1>
\t<p>My Dir: ";
        // line 9
        if (isset($context["my_dir"])) { $_my_dir_ = $context["my_dir"]; } else { $_my_dir_ = null; }
        echo twig_escape_filter($this->env, $_my_dir_, "html", null, true);
        echo "</p>
";
    }

    public function getTemplateName()
    {
        return "home.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  46 => 9,  40 => 8,  37 => 7,  30 => 4,  27 => 3,);
    }
}
