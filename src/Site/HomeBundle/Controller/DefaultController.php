<?php

namespace Site\HomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('SiteHomeBundle:Default:index.html.twig');
    }
	
	public function viewPageServicesAction()
	{
		return $this->render("SiteHomeBundle:Default:services.html.twig");
	}
	
	public function viewPageVenteNeufAction()
	{
		return $this->render("SiteHomeBundle:Default:venteneuf.html.twig");
	}
	
	public function viewPageVenteOccasionAction()
	{
		return $this->render("SiteHomeBundle:Default:venteoccasion.html.twig");
	}
	
	public function viewPageVenteOccasionDetail01Action()
	{
		return $this->render("SiteHomeBundle:Default:venteoccasiondetail01.html.twig");
	}
	
	public function viewPageVenteOccasionDetail02Action()
	{
		return $this->render("SiteHomeBundle:Default:venteoccasiondetail02.html.twig");
	}
	
	public function viewPageVenteOccasionDetail03Action()
	{
		return $this->render("SiteHomeBundle:Default:venteoccasiondetail03.html.twig");
	}
    public function viewPageVenteOccasionDetail04Action()
	{
		return $this->render("SiteHomeBundle:Default:venteoccasiondetail04.html.twig");
	}
	
	public function viewPageVenteNeufDetail01Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail01.html.twig");
	}
	
	public function viewPageVenteNeufDetail02Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail02.html.twig");
	}
	
	public function viewPageVenteNeufDetail03Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail03.html.twig");
	}
	
	public function viewPageVenteNeufDetail04Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail04.html.twig");
	}
	
    public function viewPageVenteNeufDetail05Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail05.html.twig");
	}
	
    public function viewPageVenteNeufDetail06Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail06.html.twig");
	}
	
    public function viewPageVenteNeufDetail07Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail07.html.twig");
	}
	
    public function viewPageVenteNeufDetail08Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail08.html.twig");
	}
	
    public function viewPageVenteNeufDetail09Action()
	{
		return $this->render("SiteHomeBundle:Default:venteneufdetail09.html.twig");
	}
	
	public function viewPageContactAction()
	{
		return $this->render("SiteHomeBundle:Default:contact.html.twig");
	}
}
