<?php







class AboutController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/om", name="about")
     * @Method("GET")
     */
    public function showAction()
    {
        return self::render('about/show.html.twig');
    }
}
