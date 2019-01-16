<?php





class NavigationController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function navigationAction()
    {
        return self::render('base/navigation.html.twig');
    }
}
